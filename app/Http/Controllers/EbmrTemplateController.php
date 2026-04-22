<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class EbmrTemplateController extends Controller
{
    /**
     * List templates in drafting or submitted status
     */
    public function index(Request $request)
    {
        $type = $request->query('type', 'BMR');
        $title = 'Soạn Thảo Hồ Sơ BMR';
        if ($type === 'GF') $title = 'Biểu Mẫu Dùng Chung';
        if ($type === 'BPR') $title = 'Hồ Sơ Đóng Gói';
        if ($type === 'MF') $title = 'Biểu Mẫu Gốc';
        
        session(['title' => $title]);

        $templatesQuery = DB::table('ebmr_templates')
            ->whereIn('ebmr_templates.status', ['draft', 'submitted'])
            ->where('ebmr_templates.type', $type)
            ->leftJoin('user_management', 'ebmr_templates.owner_id', '=', 'user_management.id')
            ->select('ebmr_templates.*', 'user_management.fullName as owner_name');

        if ($type === 'GF') {
            $templatesQuery->leftJoin('gf_category', 'ebmr_templates.caterogy_id', '=', 'gf_category.id')
                ->addSelect('gf_category.code as category_code', 'gf_category.name as category_name');
        } elseif ($type === 'MF') {
            $templatesQuery->leftJoin('mf_category', 'ebmr_templates.caterogy_id', '=', 'mf_category.id')
                ->addSelect('mf_category.code as category_code', 'mf_category.name as category_name');
        } elseif ($type === 'BPR') {
            $templatesQuery->leftJoin('finished_product_category', 'ebmr_templates.caterogy_id', '=', 'finished_product_category.id')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                ->addSelect('finished_product_category.finished_product_code as category_code', 'product_name.name as category_name');
        } else { // BMR
            $templatesQuery->leftJoin('intermediate_category', 'ebmr_templates.caterogy_id', '=', 'intermediate_category.id')
                ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
                ->addSelect('intermediate_category.intermediate_code as category_code', 'product_name.name as category_name');
        }

        $templates = $templatesQuery->orderBy('ebmr_templates.updated_at', 'desc')->get();

        $users = DB::table('user_management')->select('id', 'fullName as name')->orderBy('fullName')->get();
        
        // Fetch items for the selection modal based on current type
        $category_items = [];
        if ($type === 'GF') {
            $category_items = DB::table('gf_category')->where('active', 1)->get();
        } elseif ($type === 'MF') {
            $category_items = DB::table('mf_category')->where('active', 1)->get();
        } elseif ($type === 'BPR') {
            $category_items = DB::table('finished_product_category')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                ->select('finished_product_category.*', 'product_name.name as product_name')
                ->where('finished_product_category.active', 1)->get();
        } else { // BMR
            $category_items = DB::table('intermediate_category')
                ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
                ->leftJoin('dosage', 'intermediate_category.dosage_id', '=', 'dosage.id')
                ->select('intermediate_category.*', 'product_name.name as product_name', 'dosage.name as dosage_name')
                ->where('intermediate_category.active', 1)
                ->where('intermediate_category.cancel', 0)
                ->get();
        }

        return view('pages.ebmr.templates.list', [
            'templates' => $templates,
            'users' => $users,
            'category_items' => $category_items,
            'current_type' => $type
        ]);
    }

    /**
     * Store or Update Level 1 Metadata
     */
    public function storeMetadata(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'caterogy_id' => 'required|integer',
            'version' => 'required|integer',
            'issued_date' => 'nullable|date',
            'effective_date' => 'nullable|date',
            'type' => 'nullable|string|max:10'
        ]);

        $data = [
            'caterogy_id' => $validated['caterogy_id'],
            'version' => $validated['version'],
            'issued_date' => $request->input('issued_date'),
            'effective_date' => $request->input('effective_date'),
            'type' => $validated['type'] ?? 'BMR',
            'updated_at' => now()
        ];

        if (empty($validated['id'])) {
            $data['owner_id'] = session('user')['userId'] ?? null;
            $data['status'] = 'draft';
            $data['created_at'] = now();
            
            $id = DB::table('ebmr_templates')->insertGetId($data);
            $message = 'Khởi tạo hồ sơ mới thành công';
        } else {
            DB::table('ebmr_templates')->where('id', $validated['id'])->update($data);
            $id = $validated['id'];
            $message = 'Cập nhật thông tin hồ sơ thành công';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'id' => $id
        ]);
    }

    public function getNextVersion(Request $request) {
        $categoryId = $request->category_id;
        $type = $request->type ?? 'BMR';

        $maxVersion = DB::table('ebmr_templates')
            ->where('caterogy_id', $categoryId)
            ->where('type', $type)
            ->max('version');
        
        return response()->json([
            'next_version' => ($maxVersion ?? 0) + 1
        ]);
    }

    public function getMetadata($id)
    {
        $template = DB::table('ebmr_templates')->where('id', $id)->first();
        return response()->json($template);
    }

    public function getTemplates()
    {
        $templates = DB::table('ebmr_templates')
            ->select('id', 'name', 'updated_at', 'log_history')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($templates);
    }

    public function getHistory($id)
    {
        $history = DB::table('ebmr_revision_history')
            ->where('template_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($history);
    }
}
