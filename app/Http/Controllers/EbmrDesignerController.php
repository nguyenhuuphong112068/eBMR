<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class EbmrDesignerController extends Controller
{
    public function designer($id = null)
    {
        session(['title' => 'Thiết kế biểu mẫu BMR']);
        $template = null;
        $isReadOnly = false;
        $comments = [];

        if ($id) {
            $template = DB::table('ebmr_templates')->where('id', $id)->first();
            if ($template) {
                // Determine read-only state
                $currentUserId = session('user')['userId'] ?? null;
                if ($template->owner_id != $currentUserId || $template->status !== 'draft') {
                    $isReadOnly = true;
                }

                $blocks = DB::table('ebmr_template_blocks')->where('template_id', $id)->orderBy('order')->get();
                $fields = [];
                $fieldsConfig = new \stdClass();
                if ($blocks->isNotEmpty()) {
                    $fieldsConfig = json_decode($blocks->first()->fields_config);
                    foreach ($blocks as $block) {
                        $f = json_decode($block->properties);
                        $fields[] = $f;
                    }
                }
                $template->schema = (object)['fields' => $fields, 'fieldsConfig' => $fieldsConfig];

                // Load comments
                $comments = DB::table('ebmr_template_comments')
                    ->leftJoin('user_management', 'ebmr_template_comments.user_id', '=', 'user_management.id')
                    ->where('template_id', $id)
                    ->select('ebmr_template_comments.*', 'user_management.fullName as user_name')
                    ->orderBy('created_at', 'asc')
                    ->get();
            }
        }
        return view('pages.ebmr.designer', [
            'template' => $template,
            'isReadOnly' => $isReadOnly,
            'comments' => $comments
        ]);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'schema' => 'required|array',
            'log_history' => 'nullable|boolean'
        ]);

        $schemaData = $validated['schema'];
        $fields = $schemaData['fields'] ?? [];
        $fieldsConfig = $schemaData['fieldsConfig'] ?? null;

        $data = [
            'name' => $validated['name'],
            'updated_at' => now()
        ];

        if (isset($validated['log_history'])) {
            $data['log_history'] = $validated['log_history'];
        }

        if (!empty($validated['id'])) {
            $oldTemplateId = $validated['id'];
            $oldTemplate = clone(DB::table('ebmr_templates')->where('id', $oldTemplateId)->first());

            // Reconstruct old schema for logging
            $oldBlocks = DB::table('ebmr_template_blocks')->where('template_id', $oldTemplateId)->get();
            $oldFields = [];
            foreach ($oldBlocks as $ob) {
                $oldFields[] = json_decode($ob->properties, true);
            }
            $oldTemplate->schema = json_encode(['fields' => $oldFields]);

            if ($oldTemplate->log_history) {
                $this->logRevision($oldTemplate, $schemaData);
            }

            DB::table('ebmr_templates')->where('id', $oldTemplateId)->update($data);
            $id = $oldTemplateId;
            DB::table('ebmr_template_blocks')->where('template_id', $id)->delete();
        } else {
            $data['created_at'] = now();
            $id = DB::table('ebmr_templates')->insertGetId($data);
        }

        // Insert new blocks
        $blocksToInsert = [];
        $order = 0;
        foreach ($fields as $field) {
            $blocksToInsert[] = [
                'template_id' => $id,
                'type' => $field['type'] ?? 'unknown',
                'label' => $field['id'] ?? null,
                'order' => $order++,
                'content' => $field['content'] ?? null,
                'properties' => json_encode($field),
                'fields_config' => json_encode($fieldsConfig),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        if (!empty($blocksToInsert)) {
            DB::table('ebmr_template_blocks')->insert($blocksToInsert);
        }

        return response()->json(['success' => true, 'message' => 'Lưu biểu mẫu thành công', 'id' => $id]);
    }

    private function logRevision($oldTemplate, $newSchema)
    {
        $oldSchema = json_decode($oldTemplate->schema, true);
        $oldFields = $oldSchema['fields'] ?? [];
        $newFields = $newSchema['fields'] ?? [];

        $added = []; $deleted = []; $modified = [];
        $oldMap = []; foreach ($oldFields as $f) $oldMap[$f['id']] = $f;
        $newMap = []; foreach ($newFields as $f) $newMap[$f['id']] = $f;

        foreach ($newFields as $nf) {
            if (!isset($oldMap[$nf['id']])) {
                $added[] = $nf['label'] ?: $nf['type'];
            } else {
                $of = $oldMap[$nf['id']];
                $isDiff = (($of['label'] ?? null) !== ($nf['label'] ?? null)) ||
                    (($of['content'] ?? null) !== ($nf['content'] ?? null)) ||
                    (($of['type'] ?? null) !== ($nf['type'] ?? null)) ||
                    (($of['rows'] ?? 0) !== ($nf['rows'] ?? 0)) ||
                    (($of['cols'] ?? 0) !== ($nf['cols'] ?? 0));

                if ($isDiff) $modified[] = $nf['label'] ?: $nf['type'];
            }
        }
        foreach ($oldFields as $of) {
            if (!isset($newMap[$of['id']])) $deleted[] = $of['label'] ?: $of['type'];
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
            'details' => json_encode(['added' => $added, 'deleted' => $deleted, 'modified' => $modified]),
            'created_at' => now()
        ]);
    }

    public function storeComment(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|integer',
            'content' => 'required|string',
            'selection_id' => 'nullable|string',
            'selection_data' => 'nullable|array'
        ]);

        $id = DB::table('ebmr_template_comments')->insertGetId([
            'template_id' => $validated['template_id'],
            'user_id' => session('user')['userId'],
            'content' => $validated['content'],
            'selection_id' => $validated['selection_id'],
            'selection_data' => isset($validated['selection_data']) ? json_encode($validated['selection_data']) : null,
            'created_at' => now(), 'updated_at' => now()
        ]);

        $user = DB::table('user_management')->where('id', session('user')['userId'])->first();

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => $id, 'content' => $validated['content'], 'user_name' => $user->fullName,
                'created_at' => now()->format('Y-m-d H:i:s'), 'selection_id' => $validated['selection_id']
            ]
        ]);
    }

    public function deleteComment(Request $request)
    {
        DB::table('ebmr_template_comments')->where('id', $request->id)->delete();
        return response()->json(['success' => true]);
    }
}
