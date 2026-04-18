<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class EbmrController extends Controller
{
    /**
     * Show the Form Designer
     */
    public function designer()
    {
        return view('pages.ebmr.designer');
    }

    /**
     * Show the drafting page
     */
    public function draft()
    {
        $templates = DB::table('ebmr_templates')->get();
        
        // Seed a sample template if none exist
        if ($templates->isEmpty()) {
            $sampleSchema = json_encode([
                "sections" => [
                    [
                        "section_title" => "Thông tin sản xuất",
                        "fields" => [
                            ["id" => "batch_no", "type" => "text", "label" => "Số Lô (Batch No)", "validation" => ["required" => true]],
                            ["id" => "man_date", "type" => "date", "label" => "Ngày sản xuất", "validation" => ["required" => true]],
                        ]
                    ],
                    [
                        "section_title" => "Kiểm tra kỹ thuật",
                        "fields" => [
                            ["id" => "temp", "type" => "number", "label" => "Nhiệt độ (°C)"],
                            ["id" => "status", "type" => "radio", "label" => "Trạng thái thiết bị", "options" => [
                                ["label" => "Đạt", "value" => "ok"],
                                ["label" => "Không đạt", "value" => "fail"]
                            ]],
                            ["id" => "note", "type" => "rich-text", "label" => "Ghi chú"]
                        ]
                    ]
                ]
            ]);
            
            DB::table('ebmr_templates')->insert([
                'name' => 'Biểu mẫu kiểm tra thông số (Mẫu)',
                'schema' => $sampleSchema,
                'category' => 'General',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $templates = DB::table('ebmr_templates')->get();
        }

        // Decode schema for view context if needed, or handle in JS
        return view('pages.ebmr.draft', ['templates' => $templates]);
    }

    /**
     * Store a new template from designer
     */
    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'schema' => 'required|array'
        ]);

        $id = DB::table('ebmr_templates')->insertGetId([
            'name' => $validated['name'],
            'schema' => json_encode($validated['schema']),
            'category' => 'Custom',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lưu biểu mẫu thành công',
            'id' => $id
        ]);
    }

    /**
     * Save the record
     */
    public function save(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|integer',
            'data' => 'required|array'
        ]);

        $id = DB::table('ebmr_records')->insertGetId([
            'template_id' => $validated['template_id'],
            'data' => json_encode($validated['data']),
            'created_by' => session('user')['userId'] ?? null,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lưu hồ sơ thành công',
            'id' => $id
        ]);
    }
}
