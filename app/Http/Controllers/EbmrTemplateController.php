<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class EbmrTemplateController extends Controller
{
    /**
     * List templates in drafting or submitted status
     */
    public function index()
    {
        session(['title' => 'Soạn Thảo Hồ Sơ BMR']);
        $templates = DB::table('ebmr_templates')
            ->whereIn('status', ['draft', 'submitted'])
            ->leftJoin('user_management', 'ebmr_templates.owner_id', '=', 'user_management.id')
            ->select('ebmr_templates.*', 'user_management.fullName as owner_name')
            ->orderBy('ebmr_templates.updated_at', 'desc')
            ->get();

        $users = DB::table('user_management')->select('id', 'fullName as name')->orderBy('fullName')->get();

        return view('pages.ebmr.templates.list', [
            'templates' => $templates,
            'users' => $users
        ]);
    }

    /**
     * Store or Update Level 1 Metadata
     */
    public function storeMetadata(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'document_code' => 'required|string|max:50',
            'edition' => 'required|string|max:10',
            'name' => 'required|string|max:255',
            'effective_date' => 'required|date',
            'dosage_form' => 'nullable|string|max:100',
            'batch_size' => 'nullable|string|max:100'
        ]);

        $data = [
            'document_code' => $validated['document_code'],
            'edition' => $validated['edition'],
            'name' => $validated['name'],
            'effective_date' => $validated['effective_date'],
            'dosage_form' => $validated['dosage_form'],
            'batch_size' => $validated['batch_size'],
            'updated_at' => now()
        ];

        if (empty($validated['id'])) {
            $data['owner_id'] = session('user')['userId'] ?? null;
            $data['status'] = 'draft';
            $data['created_at'] = now();
            $data['schema'] = json_encode(['type' => 'document-flow', 'fields' => []]);
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
