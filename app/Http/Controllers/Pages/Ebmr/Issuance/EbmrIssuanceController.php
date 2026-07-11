<?php

namespace App\Http\Controllers\Pages\Ebmr\Issuance;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class EbmrIssuanceController extends Controller
{
    /**
     * Show issuance center (list published templates)
     */
    public function index()
    {
        // Mỗi loại tài liệu (BMR/BPR/GF) có 1 trang ban hành riêng, lọc theo query
        // param ?type=..., cùng cơ chế với trang "Hồ Sơ Gốc" (EbmrTemplateController).
        $type = request('type', 'BMR');
        $titleMap = [
            'BMR' => 'Ban Hành Hồ Sơ Lô Sản Xuất',
            'BPR' => 'Ban Hành Hồ Sơ Đóng Gói',
            'GF' => 'Ban Hành Biểu Mẫu Chung',
        ];
        session(['title' => $titleMap[$type] ?? 'Ban Hành Hồ Sơ Lô']);

        $templates = DB::table('ebmr_templates')
            ->where('status', 'active')
            ->where('type', $type)
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach($templates as $t) {
            $t->edition = $t->version; // Map version to edition for view compatibility
            $t->name = $t->name ?? 'N/A';
            $t->document_code = $t->doc_code ?? 'N/A';
            $t->dosage_form = 'N/A';
            $t->batch_size = 'N/A';
            $t->department = 'N/A';
            $t->stages = [
                'pre' => null, 'pre_other' => null, 'pc' => null, 'tht' => null, 'dh' => null, 'bp' => null
            ];

            if ($t->type === 'GF') {
                $t->name = DB::table('gf_category')->where('id', $t->caterogy_id)->value('name') ?? 'N/A';
                $t->document_code = DB::table('gf_category')->where('id', $t->caterogy_id)->value('code') ?? 'N/A';
            } elseif ($t->type === 'MF') {
                $t->name = DB::table('mf_category')->where('id', $t->caterogy_id)->value('name') ?? 'N/A';
                $t->document_code = DB::table('mf_category')->where('id', $t->caterogy_id)->value('code') ?? 'N/A';
            } elseif ($t->type === 'BPR') {
                $cat = DB::table('finished_product_category')
                    ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                    ->where('finished_product_category.id', $t->caterogy_id)
                    ->select('finished_product_category.*', 'product_name.name')
                    ->first();
                if ($cat) {
                    $t->name = $cat->name ?? 'N/A';
                    $t->document_code = $cat->finished_product_code ?? 'N/A';
                    $t->batch_size = ($cat->batch_qty ?? 'N/A') . ' ' . ($cat->unit_batch_qty ?? '');
                    $t->department = $cat->deparment_code ?? 'N/A';
                }
            } else {
                $cat = DB::table('intermediate_category')
                    ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
                    ->leftJoin('dosage', 'intermediate_category.dosage_id', '=', 'dosage.id')
                    ->where('intermediate_category.id', $t->caterogy_id)
                    ->select('intermediate_category.*', 'product_name.name', 'dosage.name as dosage_name')
                    ->first();
                if ($cat) {
                    $t->name = $cat->name ?? 'N/A';
                    $t->document_code = $cat->intermediate_code ?? 'N/A';
                    $t->dosage_form = $cat->dosage_name ?? 'N/A';
                    $t->batch_size = ($cat->batch_size ?? 'N/A') . ' ' . ($cat->unit_batch_size ?? '');
                    $t->department = $cat->deparment_code ?? 'N/A';
                    $t->stages = [
                        'pre' => (isset($cat->prepering) && $cat->prepering) ? ($cat->quarantine_preparing ?? 0) : null,
                        'pre_other' => ((isset($cat->weight_1) && $cat->weight_1) || (isset($cat->weight_2) && $cat->weight_2)) ? ($cat->quarantine_weight ?? 0) : null,
                        'pc' => (isset($cat->blending) && $cat->blending) ? ($cat->quarantine_blending ?? 0) : null,
                        'tht' => (isset($cat->forming) && $cat->forming) ? ($cat->quarantine_forming ?? 0) : null,
                        'dh' => (isset($cat->coating) && $cat->coating) ? ($cat->quarantine_coating ?? 0) : null,
                        'bp' => (isset($cat->quarantine_total) && $cat->quarantine_total > 0) ? $cat->quarantine_total : null
                    ];
                }
            }
        }

        // Danh sách con dấu đang hoạt động — tuỳ chọn đóng dấu khi ban hành
        $seals = DB::table('seals')->where('active', true)->orderBy('name')->get();

        return view('pages.ebmr.issuance.index', [
            'templates' => $templates,
            'current_type' => $type,
            'seals' => $seals,
        ]);
    }

    /**
     * Issue a template as a Batch Record for operations
     */
    public function publish(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|integer',
            'batch_number' => 'required|string',
            'quantity' => 'nullable|integer|min:1|max:50',
            'format' => 'nullable|string',
            'seal_ids' => 'nullable|array',
            'seal_ids.*' => 'integer|exists:seals,id'
        ]);

        $templateId = $validated['template_id'];
        $qty = $request->input('quantity', 1);
        $format = $request->input('format', 'MANUAL');
        $startBatch = $validated['batch_number'];
        // Nhiều con dấu / lô — giữ nguyên thứ tự người dùng chọn
        $sealIds = array_values(array_map('intval', $request->input('seal_ids', [])));

        $template = DB::table('ebmr_templates')->where('id', $templateId)->first();
        if (!$template || $template->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Hồ sơ mẫu chưa có hiệu lực hoặc không tồn tại.']);
        }

        DB::beginTransaction();
        try {
            $createdBatches = [];
            $lastId = null;
            for ($i = 0; $i < $qty; $i++) {
                $currentBatch = ($i === 0) ? $startBatch : $this->incrementBatchNumber($startBatch, $i, $format);

                // Kiểm tra trùng số lô theo template_id
                $exists = DB::table('ebmr_records')
                    ->where('template_id', $templateId)
                    ->where('batch_number', $currentBatch)
                    ->exists();

                if ($exists) {
                    throw new \Exception('Số lô "' . $currentBatch . '" đã tồn tại cho mẫu hồ sơ này. Vui lòng kiểm tra lại.');
                }

                $lastId = DB::table('ebmr_records')->insertGetId([
                    'template_id' => $templateId,
                    'batch_number' => $currentBatch,
                    'sequence_in_year' => $this->extractSequence($currentBatch, $format, $templateId),
                    'created_by' => session('user')['userId'] ?? null,
                    'status' => 'active',
                    'seal_ids' => $sealIds ? json_encode($sealIds) : null,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $createdBatches[] = $currentBatch;
            }

            DB::commit();

            $msg = count($createdBatches) > 1 
                ? "Đã ban hành " . count($createdBatches) . " lô thành công: " . implode(', ', $createdBatches)
                : "Ban hành hồ sơ thành công. Số lô: " . $createdBatches[0];

            return response()->json([
                'success' => true,
                'message' => $msg,
                'record_id' => $lastId
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Gợi ý số lô tiếp theo
     */
    public function getSuggestion(Request $request, $id)
    {
        $format = $request->query('format', 'AAMMYY');
        $suggestion = $this->generateBatchNumber($id, $format);
        return response()->json(['suggestion' => $suggestion]);
    }

    private function generateBatchNumber($templateId, $format)
    {
        if ($format === 'MANUAL') return '';

        $now = now();
        $year = $now->format('Y');
        $yy = $now->format('y');
        $mm = $now->format('m');
        $yLast = substr($yy, -1);
        $ww = $now->weekOfYear;

        // Lấy STT lớn nhất trong năm cho template này
        $maxSeq = DB::table('ebmr_records')
            ->where('template_id', $templateId)
            ->whereYear('created_at', $year)
            ->max('sequence_in_year') ?? 0;

        $nextSeq = $maxSeq + 1;
        $aa = str_pad($nextSeq, 2, '0', STR_PAD_LEFT);

        if ($format === 'AAMMYY') {
            return $aa . $mm . $yy;
        } elseif ($format === 'YWWAA') {
            return $yLast . str_pad($ww, 2, '0', STR_PAD_LEFT) . $aa;
        }

        return '';
    }

    private function incrementBatchNumber($startBatch, $index, $format)
    {
        if ($format === 'AAMMYY') {
            $aaLen = strlen($startBatch) - 4;
            if ($aaLen <= 0) return $startBatch . $index;
            $aa = substr($startBatch, 0, $aaLen);
            $suffix = substr($startBatch, $aaLen);
            $nextAa = str_pad(intval($aa) + $index, $aaLen, '0', STR_PAD_LEFT);
            return $nextAa . $suffix;
        } elseif ($format === 'YWWAA') {
            $aa = substr($startBatch, -2);
            $prefix = substr($startBatch, 0, -2);
            $nextAa = str_pad(intval($aa) + $index, 2, '0', STR_PAD_LEFT);
            return $prefix . $nextAa;
        }

        return preg_replace_callback('/(\d+)$/', function($m) use ($index) {
            return str_pad(intval($m[1]) + $index, strlen($m[1]), '0', STR_PAD_LEFT);
        }, $startBatch);
    }

    private function extractSequence($batch, $format, $templateId)
    {
        if ($format === 'AAMMYY') {
            return intval(substr($batch, 0, strlen($batch) - 4));
        } elseif ($format === 'YWWAA') {
            return intval(substr($batch, -2));
        }
        return (DB::table('ebmr_records')
            ->where('template_id', $templateId)
            ->whereYear('created_at', date('Y'))
            ->max('sequence_in_year') ?? 0) + 1;
    }

    public function checkBatchNumber(Request $request)
    {
        $exists = DB::table('ebmr_records')
            ->where('template_id', $request->template_id)
            ->where('batch_number', $request->batch_number)
            ->exists();

        return response()->json(['exists' => $exists]);
    }
}
