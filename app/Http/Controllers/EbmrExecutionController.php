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
        if ($mode == 'completed') {
            $title = 'Hồ Sơ Hoàn Thành';
        } elseif ($mode == 'history') {
            $title = 'Lịch Sử Ban Hành BMR';
        } else {
            $title = 'Hồ Sơ Đã Nhận Ban Hành';
        }

        session(['title' => $title]);

        $query = DB::table('ebmr_records')
            ->join('ebmr_templates', 'ebmr_records.template_id', '=', 'ebmr_templates.id')
            ->leftJoin('user_management', 'ebmr_records.created_by', '=', 'user_management.id')
            ->select('ebmr_records.*', 'user_management.fullName as issuer_name', 'ebmr_templates.type', 'ebmr_templates.caterogy_id');

        if ($mode == 'completed') {
            $query->whereIn('ebmr_records.status', ['completed', 'reviewed']);
        } elseif ($mode != 'history') {
            // Working mode
            $query->whereNotIn('ebmr_records.status', ['completed', 'reviewed']);
        }

        $records = $query->orderBy('ebmr_records.created_at', 'desc')->get();

        foreach($records as $r) {
            if ($r->type === 'GF') {
                $r->template_name = DB::table('gf_category')->where('id', $r->caterogy_id)->value('name') ?? 'N/A';
                $r->document_code = DB::table('gf_category')->where('id', $r->caterogy_id)->value('code') ?? 'N/A';
            } elseif ($r->type === 'MF') {
                $r->template_name = DB::table('mf_category')->where('id', $r->caterogy_id)->value('name') ?? 'N/A';
                $r->document_code = DB::table('mf_category')->where('id', $r->caterogy_id)->value('code') ?? 'N/A';
            } elseif ($r->type === 'BPR') {
                $cat = DB::table('finished_product_category')
                    ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                    ->where('finished_product_category.id', $r->caterogy_id)
                    ->select('finished_product_category.finished_product_code', 'product_name.name')
                    ->first();
                $r->template_name = $cat->name ?? 'N/A';
                $r->document_code = $cat->finished_product_code ?? 'N/A';
            } else {
                $cat = DB::table('intermediate_category')
                    ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
                    ->where('intermediate_category.id', $r->caterogy_id)
                    ->select('intermediate_category.intermediate_code', 'product_name.name')
                    ->first();
                $r->template_name = $cat->name ?? 'N/A';
                $r->document_code = $cat->intermediate_code ?? 'N/A';
            }

            // --- Fetch Stages (Sections) ---
            $r->sections = DB::table('ebmr_template_blocks')
                ->where('template_id', $r->template_id)
                ->where('type', 'section')
                ->orderBy('order')
                ->get()
                ->map(function($b) {
                    $prop = json_decode($b->properties);
                    return [
                        'id' => $b->section_id,
                        'label' => $prop->label ?? 'N/A'
                    ];
                });
        }

        return view('pages.ebmr.records.list', [
            'records' => $records,
            'mode' => $mode
        ]);
    }

    /**
     * Execution interface for a specific record
     */
    public function execute(Request $request, $id)
    {
        session(['title' => 'Ghi Chép Hồ Sơ BMR']);
        $sectionId = $request->query('section');

        $record = DB::table('ebmr_records')->where('id', $id)->first();
        if (!$record) return redirect()->back()->with('error', 'Hồ sơ không tồn tại.');

        $template = DB::table('ebmr_templates')->where('id', $record->template_id)->first();
        if (!$template) return redirect()->back()->with('error', 'Mẫu hồ sơ không tồn tại.');

        if ($template->type === 'GF') {
            $cat = DB::table('gf_category')->where('id', $template->caterogy_id)->first();
            $template->category_code = $cat->code ?? '';
            $template->category_name = $cat->name ?? '';
            $template->relatived_sop_no = $cat->relatived_sop_no ?? '';
            $template->name = $template->category_name;
            $template->document_code = $template->category_code;
        } elseif ($template->type === 'MF') {
            $cat = DB::table('mf_category')->where('id', $template->caterogy_id)->first();
            $template->category_code = $cat->code ?? '';
            $template->category_name = $cat->name ?? '';
            $template->name = $template->category_name;
            $template->document_code = $template->category_code;
        } elseif ($template->type === 'BPR') {
            $cat = DB::table('finished_product_category')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                ->where('finished_product_category.id', $template->caterogy_id)
                ->select('finished_product_category.*', 'product_name.name as product_name')
                ->first();
            $template->category_code = $cat->finished_product_code ?? '';
            $template->category_name = $cat->product_name ?? '';
            $template->name = $template->category_name;
            $template->document_code = $template->category_code;
        } else {
            $cat = DB::table('intermediate_category')
                ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
                ->leftJoin('dosage', 'intermediate_category.dosage_id', '=', 'dosage.id')
                ->where('intermediate_category.id', $template->caterogy_id)
                ->select('intermediate_category.*', 'product_name.name as product_name', 'dosage.name as dosage_name')
                ->first();
            $template->category_code = $cat->intermediate_code ?? '';
            $template->category_name = $cat->product_name ?? '';
            $template->dosage_name = $cat->dosage_name ?? '';
            $template->type_name = $cat->type ?? 'Thuốc Kê Đơn';
            $template->batch_size = ($cat->batch_size ?? '').' '.($cat->unit_batch_size ?? '');
            $template->name = $template->category_name;
            $template->document_code = $template->category_code;
        }

        $fields = [];
        $fieldsConfig = new \stdClass();

        $blocks = DB::table('ebmr_template_blocks')->where('template_id', $template->id)->orderBy('order')->get();
        
        // Fetch content blocks
        $blockIds = $blocks->pluck('id')->toArray();
        $contentBlocks = DB::table('ebmr_content_blocks')->whereIn('ebmr_template_blocks_id', $blockIds)->get()->groupBy('ebmr_template_blocks_id');

        // Load fieldsConfig from the new dedicated table (One row per variable)
        $variants = DB::table('ebmr_variants')->where('template_id', $template->id)->get();
        if ($variants->isNotEmpty()) {
            $fieldsConfig = [];
            foreach ($variants as $v) {
                $config = json_decode($v->config, true) ?? [];
                $fieldsConfig[$v->field_key] = array_merge([
                    'id' => $v->field_key,
                    'name' => $v->name,
                    'label' => $v->label,
                    'type' => $v->type,
                    'section_id' => $v->section_id,
                    'block_id' => $v->block_id,
                ], $config);
            }
        } else if ($blocks->isNotEmpty()) {
            // Fallback for legacy data
            $fieldsConfig = json_decode($blocks->first()->fields_config, true) ?? [];
        } else {
            $fieldsConfig = [];
        }
    
        $allFields = [];
        foreach ($blocks as $block) {
            $f = json_decode($block->properties, true);
            $this->injectContent($f, $block, $contentBlocks->get($block->id));
            $f['db_id'] = $block->id; // Track DB ID for section matching
            if (isset($f['type']) && $f['type'] === 'linked-template') {
                $linkedTemplateId = $f['template_id'] ?? null;
                if ($linkedTemplateId) {
                    $linkedBlocks = DB::table('ebmr_template_blocks')->where('template_id', $linkedTemplateId)->orderBy('order')->get();
                    
                    // Fetch linked content blocks
                    $lbIds = $linkedBlocks->pluck('id')->toArray();
                    $lContentBlocks = DB::table('ebmr_content_blocks')->whereIn('ebmr_template_blocks_id', $lbIds)->get()->groupBy('ebmr_template_blocks_id');

                    $variantsLink = DB::table('ebmr_variants')->where('template_id', $linkedTemplateId)->get();
                    if ($variantsLink->isNotEmpty()) {
                        $linkedConfig = [];
                        foreach ($variantsLink as $v) {
                            $config = json_decode($v->config, true) ?? [];
                            $linkedConfig[$v->field_key] = array_merge([
                                'id' => $v->field_key,
                                'name' => $v->name,
                                'label' => $v->label,
                                'type' => $v->type,
                                'section_id' => $v->section_id,
                                'block_id' => $v->block_id,
                            ], $config);
                        }
                    } else if ($linkedBlocks->isNotEmpty()) {
                        $linkedConfig = json_decode($linkedBlocks->first()->fields_config, true) ?? [];
                    } else {
                        $linkedConfig = [];
                    }
                    $fieldsConfig = array_merge((array)$fieldsConfig, (array)$linkedConfig);
                    foreach ($linkedBlocks as $lb) {
                        $linkedF = json_decode($lb->properties, true);
                        $this->injectContent($linkedF, $lb, $lContentBlocks->get($lb->id));
                        $linkedF['is_linked'] = true; // Mark as linked if needed by frontend
                        $allFields[] = $linkedF;
                    }
                }
            } else {
                $allFields[] = $f;
            }
        }

        // --- Section Filtering Logic ---
        $activeSectionLabel = null;
        if ($sectionId) {
            $blocksQuery = DB::table('ebmr_template_blocks')
                ->where('template_id', $template->id)
                ->where(function($q) use ($sectionId, $template) {
                    $q->where('section_id', $sectionId)
                      ->orWhere('section_id', $template->caterogy_id);
                })
                ->orderBy('order')
                ->get();
            
            $bqIds = $blocksQuery->pluck('id')->toArray();
            $bqContentBlocks = DB::table('ebmr_content_blocks')->whereIn('ebmr_template_blocks_id', $bqIds)->get()->groupBy('ebmr_template_blocks_id');

            $activeSectionLabel = null;
            foreach ($blocksQuery as $block) {
                $f = json_decode($block->properties, true);
                $this->injectContent($f, $block, $bqContentBlocks->get($block->id));
                if (isset($f['type']) && $f['type'] === 'section' && $block->section_id == $sectionId) {
                    $activeSectionLabel = $f['label'] ?? 'Phân đoạn';
                }
                
                if (isset($f['type']) && $f['type'] === 'linked-template') {
                    $linkedTemplateId = $f['template_id'] ?? null;
                    if ($linkedTemplateId) {
                        $linkedBlocks = DB::table('ebmr_template_blocks')->where('template_id', $linkedTemplateId)->orderBy('order')->get();
                        
                        $lbIds = $linkedBlocks->pluck('id')->toArray();
                        $lContentBlocks = DB::table('ebmr_content_blocks')->whereIn('ebmr_template_blocks_id', $lbIds)->get()->groupBy('ebmr_template_blocks_id');

                        if ($linkedBlocks->isNotEmpty()) {
                            $variantsLink2 = DB::table('ebmr_variants')->where('template_id', $linkedTemplateId)->get();
                            if ($variantsLink2->isNotEmpty()) {
                                $linkedConfig = [];
                                foreach ($variantsLink2 as $v) {
                                    $config = json_decode($v->config, true) ?? [];
                                    $linkedConfig[$v->field_key] = array_merge([
                                        'id' => $v->field_key,
                                        'name' => $v->name,
                                        'label' => $v->label,
                                        'type' => $v->type,
                                        'section_id' => $v->section_id,
                                        'block_id' => $v->block_id,
                                    ], $config);
                                }
                            } else {
                                $linkedConfig = json_decode($linkedBlocks->first()->fields_config, true) ?? [];
                            }
                            $fieldsConfig = array_merge((array)$fieldsConfig, (array)$linkedConfig);
                        }
                        foreach ($linkedBlocks as $lb) {
                            $linkedF = json_decode($lb->properties, true);
                            $this->injectContent($linkedF, $lb, $lContentBlocks->get($lb->id));
                            $linkedF['is_linked'] = true;
                            $fields[] = $linkedF;
                        }
                    }
                } else {
                    $fields[] = $f;
                }
            }
        } else {
            // Use the already fetched allFields if no section filtering
            $fields = $allFields;
        }

        $fieldsConfig = (object)$fieldsConfig;

        // Lấy dữ liệu và gộp lại theo block_uuid (Khởi tạo là object để tránh lỗi JSON array trong JS)
        $runDataRaw = DB::table('ebmr_run_data')->where('record_id', $id)->get();
        $executionValues = (object)[];
        foreach ($runDataRaw as $rd) {
            $blockUuid = $rd->block_uuid;
            if (!isset($executionValues->$blockUuid)) {
                $executionValues->$blockUuid = (object)[];
            }
            
            // Khởi tạo đối tượng meta nếu chưa có
            if (!isset($executionValues->$blockUuid->_meta)) {
                $executionValues->$blockUuid->_meta = (object)[];
            }

            $cellId = ($rd->cell_id && $rd->cell_id !== 'default') ? $rd->cell_id : 'default';
            $executionValues->$blockUuid->$cellId = $rd->raw_value;
            
            // Lưu metadata
            $executionValues->$blockUuid->_meta->$cellId = (object)[
                'by' => $rd->updated_by,
                'at' => $rd->updated_at ? \Carbon\Carbon::parse($rd->updated_at)->format('d/m/Y H:i') : null
            ];
        }

        $template->schema = (object)['fields' => $fields, 'fieldsConfig' => $fieldsConfig];

        return view('pages.ebmr.execute', [
            'record' => $record,
            'template' => $template,
            'executionValues' => $executionValues,
            'isExecutionMode' => true,
            'isReadOnly' => in_array($record->status, ['completed', 'reviewed']),
            'activeSectionId' => $sectionId,
            'activeSectionLabel' => $activeSectionLabel
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
                        if ($cellId === '_meta') continue;
                        
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
            return response()->json([
                'success' => true, 
                'message' => 'Lưu dữ liệu phân rã thành công',
                'updated_by' => $userName,
                'updated_at' => $now->format('d/m/Y H:i')
            ]);
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

    /**
     * Verify another user's credentials (e.g. for checker role) and return their full name
     */
    public function verifyChecker(Request $request)
    {
        $username = $request->username;
        $password = $request->password;

        if (!$username || !$password) {
            return response()->json(['success' => false, 'message' => 'Vui lòng nhập tài khoản và mật khẩu.']);
        }

        $user = DB::table('user_management')->where('userName', $username)->first();
        if ($user && Hash::check($password, $user->passWord)) {
            return response()->json([
                'success' => true,
                'fullName' => $user->fullName ?? $user->userName
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Tài khoản hoặc mật khẩu không chính xác.']);
    }
    private function injectContent(&$field, $block, $contentBlocks)
    {
        if (!$contentBlocks || empty($block->content)) return;

        // 1. Rebuild the full HTML by replacing placeholders with text
        $fullHtml = $block->content;
        foreach ($contentBlocks as $cb) {
            $placeholder = "[[CONTENT_$cb->id]]";
            $text = $cb->vi_contents ?? '';
            $fullHtml = str_replace($placeholder, $text, $fullHtml);
        }

        if ($block->type === 'static-text') {
            // Flexible regex to match any wrapper tag and extract inner content
            if (preg_match('/^<([a-z0-9]+)[^>]*>(.*)<\/\1>$/is', trim($fullHtml), $matches)) {
                $content = $matches[2];
            } else {
                $content = $fullHtml;
            }

            // --- VARIABLE INJECTION ---
            $content = preg_replace_callback('/\{\{(field_[0-9]+)\}\}/', function ($m) {
                return '<span contenteditable="false" class="ebmr-field-badge" data-field-id="'.$m[1].'" onclick="selectField(event, \''.$m[1].'\')"></span>';
            }, $content);

            $field['content'] = $content;
        } elseif ($block->type === 'table') {
            $rows = $field['rows'] ?? 0;
            $cols = $field['cols'] ?? 0;
            $cbMap = $contentBlocks ? $contentBlocks->keyBy('id') : collect();

            if (!isset($field['data'])) $field['data'] = [];
            for ($r = 0; $r < $rows; $r++) {
                if (!isset($field['data'][$r])) $field['data'][$r] = [];
                for ($c = 0; $c < $cols; $c++) {
                    if (isset($field['data'][$r][$c]) && is_array($field['data'][$r][$c])) {
                        $cell = &$field['data'][$r][$c];
                        $dbId = $cell['db_id'] ?? null;
                        if ($dbId && $cbMap->has($dbId)) {
                            $cb = $cbMap->get($dbId);
                            $content = $cb->vi_contents ?? '';
                            
                            // --- VARIABLE INJECTION ---
                            $content = preg_replace_callback('/\{\{(field_[0-9]+)\}\}/', function ($m) {
                                return '<span contenteditable="false" class="ebmr-field-badge" data-field-id="'.$m[1].'" onclick="selectField(event, \''.$m[1].'\')"></span>';
                            }, $content);
                            
                            $cell['content'] = $content;
                        }
                    } else {
                        $field['data'][$r][$c] = ['content' => '', 'rs' => 1, 'cs' => 1, 'hidden' => false];
                    }
                }
            }
        }
    }
}
