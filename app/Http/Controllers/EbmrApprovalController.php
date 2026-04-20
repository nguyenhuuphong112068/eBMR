<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class EbmrApprovalController extends Controller
{
    /**
     * Show documents pending approval for current user
     */
    public function index()
    {
        session(['title' => 'Phê Duyệt Hồ Sơ']);
        $userId = session('user')['userId'];

        $myPendingWorkflows = DB::table('ebmr_template_workflows')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->get();

        $templateIds = $myPendingWorkflows->pluck('template_id')->unique();

        $templates = DB::table('ebmr_templates')
            ->leftJoin('user_management', 'ebmr_templates.owner_id', '=', 'user_management.id')
            ->select('ebmr_templates.*', 'user_management.fullName as owner_name')
            ->whereIn('ebmr_templates.id', $templateIds)
            ->where('ebmr_templates.status', 'submitted')
            ->orderBy('ebmr_templates.updated_at', 'desc')
            ->get();

        $actionableTemplates = collect();
        foreach ($templates as $t) {
            $wfForMe = $myPendingWorkflows->where('template_id', $t->id)->first();
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

    public function getTemplateWorkflow($id)
    {
        $workflows = DB::table('ebmr_template_workflows')->where('template_id', $id)->get();
        return response()->json($workflows);
    }

    public function storeTemplateWorkflow(Request $request, $id)
    {
        $validated = $request->validate([
            'reviewers' => 'nullable|array',
            'reviewers.*' => 'integer',
            'approver' => 'nullable|integer',
            'authorizer' => 'nullable|integer'
        ]);

        DB::transaction(function () use ($id, $validated) {
            DB::table('ebmr_template_workflows')->where('template_id', $id)->delete();
            $insertData = [];
            if (!empty($validated['reviewers'])) {
                foreach ($validated['reviewers'] as $userId) {
                    $insertData[] = ['template_id' => $id, 'role' => 'reviewer', 'user_id' => $userId, 'step_order' => 1, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()];
                }
            }
            if (!empty($validated['approver'])) $insertData[] = ['template_id' => $id, 'role' => 'approver', 'user_id' => $validated['approver'], 'step_order' => 2, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()];
            if (!empty($validated['authorizer'])) $insertData[] = ['template_id' => $id, 'role' => 'authorizer', 'user_id' => $validated['authorizer'], 'step_order' => 3, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()];
            
            if (count($insertData) > 0) DB::table('ebmr_template_workflows')->insert($insertData);
            
            // Auto update status to submitted
            DB::table('ebmr_templates')->where('id', $id)->where('status', 'draft')->update(['status' => 'submitted']);
        });

        return response()->json(['success' => true, 'message' => 'Lưu luồng trình ký thành công']);
    }

    public function process(Request $request)
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
            DB::table('ebmr_template_workflows')->where('id', $workflow->id)->update(['status' => $newWfStatus, 'comment' => $validated['comment'], 'updated_at' => now()]);

            if ($newWfStatus === 'rejected') {
                DB::table('ebmr_template_workflows')->where('template_id', $workflow->template_id)->where('status', 'pending')->update(['status' => 'cancelled', 'updated_at' => now()]);
                DB::table('ebmr_templates')->where('id', $workflow->template_id)->update(['status' => 'draft']);
            } else {
                $pendingCount = DB::table('ebmr_template_workflows')->where('template_id', $workflow->template_id)->where('status', 'pending')->count();
                if ($pendingCount === 0) {
                    DB::table('ebmr_templates')->where('id', $workflow->template_id)->update(['status' => 'published', 'updated_at' => now()]);
                }
            }
        });

        $msg = $validated['action'] === 'approve' ? 'Đã phê duyệt thành công' : 'Đã từ chối hồ sơ';
        return response()->json(['success' => true, 'message' => $msg]);
    }
}
