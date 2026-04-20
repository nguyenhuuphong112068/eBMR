<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class EbmrExecutionController extends Controller
{
    /**
     * List of issued records (Batch Records)
     */
    public function index(Request $request)
    {
        $mode = $request->query('mode', 'working');
        $title = ($mode == 'history') ? 'Lịch Sử Ban Hành BMR' : 'Hồ Sơ Đã Nhận Ban Hành';

        session(['title' => $title]);

        $records = DB::table('ebmr_records')
            ->join('ebmr_templates', 'ebmr_records.template_id', '=', 'ebmr_templates.id')
            ->leftJoin('user_management', 'ebmr_records.created_by', '=', 'user_management.id')
            ->select('ebmr_records.*', 'ebmr_templates.name as template_name', 'ebmr_templates.document_code', 'user_management.fullName as issuer_name')
            ->orderBy('ebmr_records.created_at', 'desc')
            ->get();

        return view('pages.ebmr.records.list', [
            'records' => $records,
            'mode' => $mode
        ]);
    }

    /**
     * Execution interface for a specific record
     */
    public function execute($id)
    {
        session(['title' => 'Ghi Chép Hồ Sơ BMR']);

        $record = DB::table('ebmr_records')->where('id', $id)->first();
        if (!$record) return redirect()->back()->with('error', 'Hồ sơ không tồn tại.');

        $template = DB::table('ebmr_templates')->where('id', $record->template_id)->first();
        if (!$template) return redirect()->back()->with('error', 'Mẫu hồ sơ không tồn tại.');

        $fields = [];
        $fieldsConfig = new \stdClass();

        $blocks = DB::table('ebmr_template_blocks')->where('template_id', $template->id)->orderBy('order')->get();
        if ($blocks->isNotEmpty()) {
            $fieldsConfig = json_decode($blocks->first()->fields_config);
        }

        foreach ($blocks as $block) {
            $f = json_decode($block->properties, true);
            $fields[] = $f;
        }

        // Lấy dữ liệu và gộp lại theo block_uuid
        $runDataRaw = DB::table('ebmr_run_data')->where('record_id', $id)->get();
        $executionValues = [];
        foreach ($runDataRaw as $rd) {
            if ($rd->cell_id && $rd->cell_id !== 'default') {
                if (!isset($executionValues[$rd->block_uuid])) {
                    $executionValues[$rd->block_uuid] = [];
                }
                $executionValues[$rd->block_uuid][$rd->cell_id] = $rd->raw_value;
            } else {
                $executionValues[$rd->block_uuid] = $rd->raw_value;
            }
        }

        $template->schema = (object)['fields' => $fields, 'fieldsConfig' => $fieldsConfig];

        return view('pages.ebmr.execute', [
            'record' => $record,
            'template' => $template,
            'executionValues' => $executionValues,
            'isExecutionMode' => true,
            'isReadOnly' => false
        ]);
    }

    /**
     * Update the record data during execution
     */
    public function updateRecordData(Request $request)
    {
        Log::info('--- SAVE ATTEMPT ---');
        Log::info($request->all());
        
        $validated = $request->validate([
            'record_id' => 'required',
            'data' => 'nullable',
            'status' => 'nullable|string'
        ]);

        $userId = session('user')['userId'] ?? 1;
        $now = now();
        $dataEntries = $request->input('data') ?? [];
        Log::info("Data Entries to process: " . count($dataEntries));
        DB::beginTransaction();
        try {
            if (!empty($validated['status'])) {
                DB::table('ebmr_records')
                    ->where('id', $validated['record_id'])
                    ->update(['status' => $validated['status'], 'updated_at' => $now]);
            }

            $userName = session('user')['fullName'] ?? 'System';
            foreach ($dataEntries as $blockUuid => $value) {
                Log::info("Processing block: " . $blockUuid . " with value: " . json_encode($value));
                if (empty($blockUuid)) continue;

                // Nếu value là mảng hoặc đối tượng (dành cho bảng/ô có tọa độ)
                if (is_array($value) || is_object($value)) {
                    foreach ($value as $cellId => $rawValue) {
                        Log::info("Saving cell: " . $cellId . " = " . $rawValue);
                        DB::table('ebmr_run_data')->updateOrInsert(
                            [
                                'record_id' => $validated['record_id'], 
                                'block_uuid' => $blockUuid,
                                'cell_id' => $cellId
                            ],
                            [
                                'filled_by' => $userId,
                                'filled_at' => $now,
                                'value' => json_encode([$cellId => $rawValue]),
                                'raw_value' => $rawValue,
                                'updated_at' => $now,
                                'updated_by' => $userName,
                            ]
                        );
                    }
                } else {
                    Log::info("Saving direct value for block: " . $blockUuid);
                    // Nếu là giá trị đơn
                    DB::table('ebmr_run_data')->updateOrInsert(
                        [
                            'record_id' => $validated['record_id'], 
                            'block_uuid' => $blockUuid,
                            'cell_id' => 'default'
                        ],
                        [
                            'filled_by' => $userId,
                            'filled_at' => $now,
                            'value' => json_encode(['text' => $value]),
                            'raw_value' => $value,
                            'updated_at' => $now,
                            'updated_by' => $userName,
                        ]
                    );
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Lưu dữ liệu phân rã thành công']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Lỗi Database: ' . $e->getMessage()]);
        }
    }

    /**
     * Verify user password for electronic signature
     */
    public function verifyPassword(Request $request)
    {
        $password = $request->password;
        $userSession = session('user');
        $username = $userSession['username'] ?? $userSession['user_name'] ?? $userSession['userName'] ?? null;

        if (!$userSession || !$username) {
            return response()->json(['success' => false, 'message' => 'Phiên đăng nhập hết hạn.']);
        }

        $user = DB::table('user_management')->where('userName', $username)->first();
        if ($user && Hash::check($password, $user->passWord)) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Mật khẩu xác nhận không chính xác.']);
    }
}
