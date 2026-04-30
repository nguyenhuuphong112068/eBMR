<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EbmrController extends Controller
{
    public function indexTemplates()
    {
        session(['title' => 'Soạn Thảo Hồ Sơ BMR']);
        $templates = DB::table('ebmr_templates')
            ->whereIn('status', ['draft', 'submitted'])
            ->leftJoin('user_management', 'ebmr_templates.owner_id', '=', 'user_management.id')
            ->select('ebmr_templates.*', 'user_management.fullName as owner_name')
            ->orderBy('ebmr_templates.updated_at', 'desc')
            ->get();

        foreach($templates as $t) {
            if ($t->type === 'GF') {
                $t->name = DB::table('gf_category')->where('id', $t->caterogy_id)->value('name') ?? 'N/A';
            } elseif ($t->type === 'MF') {
                $t->name = DB::table('mf_category')->where('id', $t->caterogy_id)->value('name') ?? 'N/A';
            } elseif ($t->type === 'BPR') {
                $t->name = DB::table('finished_product_category')
                    ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                    ->where('finished_product_category.id', $t->caterogy_id)
                    ->value('product_name.name') ?? 'N/A';
            } else {
                $t->name = DB::table('intermediate_category')
                    ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
                    ->where('intermediate_category.id', $t->caterogy_id)
                    ->value('product_name.name') ?? 'N/A';
            }

            // --- Fetch Stages (Sections) ---
            $t->sections = DB::table('ebmr_template_blocks')
                ->where('template_id', $t->id)
                ->where('type', 'section')
                ->orderBy('order')
                ->get()
                ->map(function($b) {
                    $prop = json_decode($b->properties);
                    return [
                        'id' => $b->id,
                        'label' => $prop->label ?? 'N/A'
                    ];
                });
        }

        $users = DB::table('user_management')->select('id', 'fullName as name')->orderBy('fullName')->get();

        return view('pages.ebmr.templates.list', [
            'templates' => $templates,
            'users' => $users
        ]);
    }

    public function issueCenter()
    {
        session(['title' => 'Ban Hành Hồ Sơ Lô']);
        $templates = DB::table('ebmr_templates')
            ->where('status', 'active')
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach($templates as $t) {
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
                    ->select('finished_product_category.finished_product_code', 'product_name.name')
                    ->first();
                $t->name = $cat->name ?? 'N/A';
                $t->document_code = $cat->finished_product_code ?? 'N/A';
            } else {
                $cat = DB::table('intermediate_category')
                    ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
                    ->where('intermediate_category.id', $t->caterogy_id)
                    ->select('intermediate_category.intermediate_code', 'product_name.name')
                    ->first();
                $t->name = $cat->name ?? 'N/A';
                $t->document_code = $cat->intermediate_code ?? 'N/A';
            }
        }

        return view('pages.ebmr.issuance.index', [
            'templates' => $templates
        ]);
    }

    public function indexRecords(Request $request)
    {
        $mode = $request->query('mode', 'working');
        $title = ($mode == 'history') ? 'Lịch Sử Ban Hành BMR' : 'Hồ Sơ Đã Nhận Ban Hành';

        session(['title' => $title]);

        $records = DB::table('ebmr_records')
            ->join('ebmr_templates', 'ebmr_records.template_id', '=', 'ebmr_templates.id')
            ->leftJoin('user_management', 'ebmr_records.created_by', '=', 'user_management.id')
            ->select('ebmr_records.*', 'user_management.fullName as issuer_name', 'ebmr_templates.type', 'ebmr_templates.caterogy_id')
            ->orderBy('ebmr_records.created_at', 'desc')
            ->get();

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
                        'id' => $b->id,
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
     * Store or Update Level 1 Metadata
     */
    public function storeTemplateMetadata(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'edition' => 'required|string|max:10',
            'effective_date' => 'required|date',
            'dosage_form' => 'nullable|string|max:100',
            'batch_size' => 'nullable|string|max:100'
        ]);

        $data = [
            'edition' => $validated['edition'],
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
     * Get workflow assignees for a template
     */
    public function getTemplateWorkflow($id)
    {
        $workflows = DB::table('ebmr_template_workflows')->where('template_id', $id)->get();
        return response()->json($workflows);
    }

    /**
     * Store workflow assignees for a template
     */
    public function storeTemplateWorkflow(Request $request, $id)
    {
        $validated = $request->validate([
            'reviewers' => 'nullable|array',
            'reviewers.*' => 'integer',
            'approver' => 'nullable|integer',
            'authorizer' => 'nullable|integer'
        ]);

        DB::transaction(function () use ($id, $validated) {
            // Delete existing workflows for safe recreation
            DB::table('ebmr_template_workflows')->where('template_id', $id)->delete();

            $insertData = [];

            // 1. Reviewers (can be multiple)
            if (!empty($validated['reviewers'])) {
                foreach ($validated['reviewers'] as $userId) {
                    $insertData[] = [
                        'template_id' => $id,
                        'role' => 'reviewer',
                        'user_id' => $userId,
                        'step_order' => 1,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }

            // 2. Approver (1 user)
            if (!empty($validated['approver'])) {
                $insertData[] = [
                    'template_id' => $id,
                    'role' => 'approver',
                    'user_id' => $validated['approver'],
                    'step_order' => 2,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            // 3. Authorizer (1 user)
            if (!empty($validated['authorizer'])) {
                $insertData[] = [
                    'template_id' => $id,
                    'role' => 'authorizer',
                    'user_id' => $validated['authorizer'],
                    'step_order' => 3,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            if (count($insertData) > 0) {
                DB::table('ebmr_template_workflows')->insert($insertData);
            }

            // Update the template status to submitted if it was draft
            $template = DB::table('ebmr_templates')->where('id', $id)->first();
            if ($template && $template->status === 'draft') {
                DB::table('ebmr_templates')->where('id', $id)->update(['status' => 'submitted']);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Lưu luồng trình ký thành công'
        ]);
    }

    /**
     * Show documents pending approval for current user
     */
    public function approvals()
    {
        session(['title' => 'Phê Duyệt Hồ Sơ']);
        $userId = session('user')['userId'];

        // Get all workflows for this user that are pending
        $myPendingWorkflows = DB::table('ebmr_template_workflows')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->get();

        $templateIds = $myPendingWorkflows->pluck('template_id')->unique();

        // Get the templates
        $templates = DB::table('ebmr_templates')
            ->leftJoin('user_management', 'ebmr_templates.owner_id', '=', 'user_management.id')
            ->select('ebmr_templates.*', 'user_management.fullName as owner_name')
            ->whereIn('ebmr_templates.id', $templateIds)
            ->where('ebmr_templates.status', 'submitted')
            ->orderBy('ebmr_templates.updated_at', 'desc')
            ->get();

        // For each template, assess if it's currently actionable for this user
        $actionableTemplates = collect();
        foreach ($templates as $t) {
            $wfForMe = $myPendingWorkflows->where('template_id', $t->id)->first();

            // Allow action if there are no pending workflows with a smaller step_order
            $hasEarlierPending = DB::table('ebmr_template_workflows')
                ->where('template_id', $t->id)
                ->where('status', 'pending')
                ->where('step_order', '<', $wfForMe->step_order)
                ->exists();

            if (!$hasEarlierPending) {
                $t->my_role = $wfForMe->role;
                $t->workflow_id = $wfForMe->id;
                $actionableTemplates->push($t);
            }
        }

        return view('pages.ebmr.approvals.list', ['templates' => $actionableTemplates]);
    }

    /**
     * Process approval (Approve/Reject)
     */
    public function processApproval(Request $request)
    {
        $validated = $request->validate([
            'workflow_id' => 'required|integer',
            'action' => 'required|in:approve,reject',
            'comment' => 'nullable|string'
        ]);

        $workflow = DB::table('ebmr_template_workflows')->where('id', $validated['workflow_id'])->first();
        if (!$workflow || $workflow->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Luồng duyệt không hợp lệ hoặc đã xử lý']);
        }

        $newWfStatus = $validated['action'] === 'approve' ? 'approved' : 'rejected';

        DB::transaction(function () use ($workflow, $newWfStatus, $validated) {
            // Update the workflow item
            DB::table('ebmr_template_workflows')
                ->where('id', $workflow->id)
                ->update([
                    'status' => $newWfStatus,
                    'comment' => $validated['comment'],
                    'updated_at' => now()
                ]);

            if ($newWfStatus === 'rejected') {
                // If rejected, reject all pending workflows and return template to draft
                DB::table('ebmr_template_workflows')
                    ->where('template_id', $workflow->template_id)
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled', 'updated_at' => now()]);

                DB::table('ebmr_templates')
                    ->where('id', $workflow->template_id)
                    ->update(['status' => 'draft']);
            } else {
                // If approved, check if there are any remaining workflows for this template
                $pendingCount = DB::table('ebmr_template_workflows')
                    ->where('template_id', $workflow->template_id)
                    ->where('status', 'pending')
                    ->count();

                if ($pendingCount === 0) {
                    // All approved! Issue the template
                    DB::table('ebmr_templates')
                        ->where('id', $workflow->template_id)
                        ->update(['status' => 'issued', 'updated_at' => now()]);
                }
            }
        });

        $msg = $validated['action'] === 'approve' ? 'Đã phê duyệt thành công' : 'Đã từ chối hồ sơ';
        return response()->json(['success' => true, 'message' => $msg]);
    }

    /**
     * Show the Form Designer
     */
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
                // Only owner can edit, and only if status is 'draft'
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

    /**
     * Store a comment on a template
     */
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
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $user = DB::table('user_management')->where('id', session('user')['userId'])->first();

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => $id,
                'content' => $validated['content'],
                'user_name' => $user->fullName,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'selection_id' => $validated['selection_id']
            ]
        ]);
    }

    public function deleteComment(Request $request)
    {
        $id = $request->id;
        // Optional: verify author/owner
        DB::table('ebmr_template_comments')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Issue a template as a Batch Record for operations
     */
    public function issueTemplate(Request $request)
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

    /**
     * Get list of templates for the Open modal
     */
    public function getTemplates()
    {
        $templates = DB::table('ebmr_templates')
            ->select('id', 'updated_at', 'log_history', 'type', 'caterogy_id')
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach($templates as $t) {
            if ($t->type === 'GF') {
                $t->name = DB::table('gf_category')->where('id', $t->caterogy_id)->value('name') ?? 'N/A';
            } elseif ($t->type === 'MF') {
                $t->name = DB::table('mf_category')->where('id', $t->caterogy_id)->value('name') ?? 'N/A';
            } elseif ($t->type === 'BPR') {
                $t->name = DB::table('finished_product_category')
                    ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                    ->where('finished_product_category.id', $t->caterogy_id)
                    ->value('product_name.name') ?? 'N/A';
            } else {
                $t->name = DB::table('intermediate_category')
                    ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
                    ->where('intermediate_category.id', $t->caterogy_id)
                    ->value('product_name.name') ?? 'N/A';
            }
        }

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
            'schema' => 'required|array',
            'log_history' => 'nullable|boolean'
        ]);

        $schemaData = $validated['schema'];
        $fields = $schemaData['fields'] ?? [];
        $fieldsConfig = $schemaData['fieldsConfig'] ?? null;

        $data = [
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

            // Log history if enabled
            if ($oldTemplate->log_history) {
                $this->logRevision($oldTemplate, $schemaData);
            }

            DB::table('ebmr_templates')->where('id', $oldTemplateId)->update($data);
            $id = $oldTemplateId;

            // Clear old blocks to rewrite (simplest strategy for templates)
            DB::table('ebmr_template_blocks')->where('template_id', $id)->delete();
        } else {
            $data['created_at'] = now();
            // Since our migrations don't default category, avoid errors for now
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
                $isDiff = (($of['label'] ?? null) !== ($nf['label'] ?? null)) ||
                    (($of['content'] ?? null) !== ($nf['content'] ?? null)) ||
                    (($of['type'] ?? null) !== ($nf['type'] ?? null)) ||
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
            $fieldsConfig = json_decode($blocks->first()->fields_config, true) ?? [];
        } else {
            $fieldsConfig = [];
        }

        foreach ($blocks as $block) {
            $f = json_decode($block->properties, true);
            if (isset($f['type']) && $f['type'] === 'linked-template') {
                $linkedTemplateId = $f['template_id'] ?? null;
                if ($linkedTemplateId) {
                    $linkedBlocks = DB::table('ebmr_template_blocks')->where('template_id', $linkedTemplateId)->orderBy('order')->get();
                    if ($linkedBlocks->isNotEmpty()) {
                        $linkedConfig = json_decode($linkedBlocks->first()->fields_config, true) ?? [];
                        $fieldsConfig = array_merge((array)$fieldsConfig, (array)$linkedConfig);
                    }
                    foreach ($linkedBlocks as $lb) {
                        $linkedF = json_decode($lb->properties, true);
                        $linkedF['is_linked'] = true;
                        $fields[] = $linkedF;
                    }
                }
            } else {
                $fields[] = $f;
            }
        }
        
        $fieldsConfig = (object)$fieldsConfig;

        $runDataRaw = DB::table('ebmr_run_data')->where('record_id', $id)->get();
        $executionValues = [];
        foreach ($runDataRaw as $rd) {
            $executionValues[$rd->block_uuid] = json_decode($rd->value, true);
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
        dd($request->all());
        $validated = $request->validate([
            'record_id' => 'required|integer',
            'data' => 'required|array',
            'status' => 'nullable|string'
        ]);

        $userId = session('user')['userId'] ?? null;
        $now = now();

        DB::beginTransaction();
        try {
            // Cập nhật trạng thái lô
            if (!empty($validated['status'])) {
                DB::table('ebmr_records')
                    ->where('id', $validated['record_id'])
                    ->update(['status' => $validated['status'], 'updated_at' => $now]);
            }

            // Lưu dữ liệu biểu mẫu vào bảng ebmr_run_data
            foreach ($validated['data'] as $blockUuid => $value) {
                // $value có thể là map row_col cho table, hoặc string cho signature
                DB::table('ebmr_run_data')->updateOrInsert(
                    ['record_id' => $validated['record_id'], 'block_uuid' => $blockUuid],
                    [
                        'filled_by' => $userId,
                        'filled_at' => $now,
                        'value' => json_encode($value),
                        'updated_at' => $now
                    ]
                );
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Lưu dữ liệu thành công'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi lưu trữ: ' . $e->getMessage()
            ]);
        }
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

    /**
     * Verify user password for electronic signature
     */
    public function verifyPassword(Request $request)
    {
        $password = $request->password;
        $userSession = session('user');

        // Cố gắng lấy username từ nhiều khóa khác nhau để đảm bảo tương thích
        $username = $userSession['username'] ?? $userSession['user_name'] ?? $userSession['userName'] ?? null;

        if (!$userSession || !$username) {
            return response()->json(['success' => false, 'message' => 'Phiên đăng nhập hết hạn hoặc không tìm thấy thông tin người dùng.']);
        }

        // Truy vấn database để lấy thông tin user
        $user = DB::table('user_management')->where('userName', $username)->first();

        if ($user && Hash::check($password, $user->passWord)) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Mật khẩu xác nhận không chính xác.']);
    }
}
