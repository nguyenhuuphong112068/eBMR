<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class EbmrController extends Controller
{
    public function indexTemplates()
    {
        session(['title' => 'Hồ Sơ Sản Xuất BMR']);
        $templates = DB::table('ebmr_templates')
            ->leftJoin('users', 'ebmr_templates.owner_id', '=', 'users.id')
            ->select('ebmr_templates.*', 'users.name as owner_name')
            ->orderBy('ebmr_templates.updated_at', 'desc')
            ->get();
            
        return view('pages.ebmr.templates.list', ['templates' => $templates]);
    }

    /**
     * Store or Update Level 1 Metadata
     */
    public function storeTemplateMetadata(Request $request)
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
            // Original schema structure for compatibility if needed
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

    /**
     * Get single template metadata for modal
     */
    public function getTemplateMetadata($id)
    {
        $template = DB::table('ebmr_templates')->where('id', $id)->first();
        return response()->json($template);
    }

    /**
     * Show the Form Designer
     */
    public function designer($id = null)
    {
        session(['title' => 'Thiết kế biểu mẫu BMR']);
        $template = null;
        if ($id) {
            $template = DB::table('ebmr_templates')->where('id', $id)->first();
            if ($template && isset($template->schema)) {
                $template->schema = json_decode($template->schema);
            }
        }
        return view('pages.ebmr.designer', ['template' => $template]);
    }

    /**
     * Get list of templates for the Open modal
     */
    public function getTemplates()
    {
        $templates = DB::table('ebmr_templates')
            ->select('id', 'name', 'updated_at', 'log_history')
            ->orderBy('updated_at', 'desc')
            ->get();
            
        return response()->json($templates);
    }

    /**
     * Get revision history for a specific template
     */
    public function getHistory($id)
    {
        $history = DB::table('ebmr_revision_history')
            ->where('template_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($history);
    }

    /**
     * Store or Update a template from designer
     */
    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'schema' => 'required|array',
            'log_history' => 'nullable|boolean'
        ]);

        $data = [
            'name' => $validated['name'],
            'schema' => json_encode($validated['schema']),
            'category' => 'Custom',
            'updated_at' => now()
        ];
        
        if (isset($validated['log_history'])) {
            $data['log_history'] = $validated['log_history'];
        }

        if (!empty($validated['id'])) {
            $oldTemplate = DB::table('ebmr_templates')->where('id', $validated['id'])->first();
            
            // Log history if enabled
            if ($oldTemplate && $oldTemplate->log_history) {
                $this->logRevision($oldTemplate, $validated['schema']);
            }
            
            DB::table('ebmr_templates')->where('id', $validated['id'])->update($data);
            $id = $validated['id'];
        } else {
            $data['created_at'] = now();
            $id = DB::table('ebmr_templates')->insertGetId($data);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lưu biểu mẫu thành công',
            'id' => $id
        ]);
    }

    /**
     * Compare schemas and log to revision history
     */
    private function logRevision($oldTemplate, $newSchema)
    {
        $oldSchema = json_decode($oldTemplate->schema, true);
        $oldFields = $oldSchema['fields'] ?? [];
        $newFields = $newSchema['fields'] ?? [];

        $added = [];
        $deleted = [];
        $modified = [];

        $oldMap = [];
        foreach ($oldFields as $f) $oldMap[$f['id']] = $f;

        $newMap = [];
        foreach ($newFields as $f) $newMap[$f['id']] = $f;

        // Check for added/modified
        foreach ($newFields as $nf) {
            if (!isset($oldMap[$nf['id']])) {
                $added[] = $nf['label'] ?: $nf['type'];
            } else {
                $of = $oldMap[$nf['id']];
                $isDiff = ($of['label'] !== $nf['label']) || 
                           ($of['content'] !== $nf['content']) ||
                           ($of['type'] !== $nf['type']) ||
                           (($of['rows'] ?? 0) !== ($nf['rows'] ?? 0)) ||
                           (($of['cols'] ?? 0) !== ($nf['cols'] ?? 0));
                
                if ($isDiff) {
                    $modified[] = $nf['label'] ?: $nf['type'];
                }
            }
        }

        // Check for deleted
        foreach ($oldFields as $of) {
            if (!isset($newMap[$of['id']])) {
                $deleted[] = $of['label'] ?: $of['type'];
            }
        }

        if (empty($added) && empty($deleted) && empty($modified)) return;

        $summaryParts = [];
        if (!empty($added)) $summaryParts[] = "Thêm: " . implode(', ', $added);
        if (!empty($deleted)) $summaryParts[] = "Xóa: " . implode(', ', $deleted);
        if (!empty($modified)) $summaryParts[] = "Điều chỉnh: " . implode(', ', $modified);

        DB::table('ebmr_revision_history')->insert([
            'template_id' => $oldTemplate->id,
            'user_id' => session('user')['userId'] ?? null,
            'change_summary' => implode(' | ', $summaryParts),
            'details' => json_encode([
                'added' => $added,
                'deleted' => $deleted,
                'modified' => $modified
            ]),
            'created_at' => now()
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
