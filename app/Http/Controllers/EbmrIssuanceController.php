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
            ->where('status', 'published')
            ->orderBy('updated_at', 'desc')
            ->get();

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

        if (!$template || $template->status !== 'published') {
            return response()->json(['success' => false, 'message' => 'Hồ sơ mẫu chưa được ban hành hoặc không tồn tại.']);
        }

        $id = DB::table('ebmr_records')->insertGetId([
            'template_id' => $validated['template_id'],
            'batch_number' => $validated['batch_number'],
            'data' => json_encode([]), 
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
