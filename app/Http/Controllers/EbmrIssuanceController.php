<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class EbmrIssuanceController extends Controller
{
    /**
     * Show issuance center (list published templates)
     */
    public function index()
    {
        session(['title' => 'Ban Hành Hồ Sơ Lô']);
        $templates = DB::table('ebmr_templates')
            ->where('status', 'active')
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach($templates as $t) {
            $t->edition = $t->version; // Map version to edition for view compatibility
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

        return view('pages.ebmr.issuance.index', [
            'templates' => $templates
        ]);
    }

    /**
     * Issue a template as a Batch Record for operations
     */
    public function publish(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|integer',
            'batch_number' => 'required|string|unique:ebmr_records,batch_number'
        ]);

        $template = DB::table('ebmr_templates')->where('id', $validated['template_id'])->first();

        if (!$template || $template->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Hồ sơ mẫu chưa có hiệu lực hoặc không tồn tại.']);
        }

        $id = DB::table('ebmr_records')->insertGetId([
            'template_id' => $validated['template_id'],
            'batch_number' => $validated['batch_number'],
            'created_by' => session('user')['userId'] ?? null,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ban hành hồ sơ thành công. Số lô: ' . $validated['batch_number'],
            'record_id' => $id
        ]);
    }
}
