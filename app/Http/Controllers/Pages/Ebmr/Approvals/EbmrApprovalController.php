<?php

namespace App\Http\Controllers\Pages\Ebmr\Approvals;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;

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

        $actionableTemplates = collect();

        // --- 1. EBMR TEMPLATES ---
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

        foreach ($templates as $t) {
            $wfForMe = $myPendingWorkflows->where('template_id', $t->id)->first();
            $hasEarlierPending = DB::table('ebmr_template_workflows')
                ->where('template_id', $t->id)
                ->where('status', 'pending')
                ->where('step_order', '<', $wfForMe->step_order)
                ->exists();

            if (!$hasEarlierPending) {
                $type = $t->type ?? 'BMR';
                if ($type === 'GF') {
                    $cat = DB::table('gf_category')->where('id', $t->caterogy_id)->first();
                    $t->document_code = $cat->code ?? '';
                    $t->name = $cat->name ?? '';
                } elseif ($type === 'MF') {
                    $cat = DB::table('mf_category')->where('id', $t->caterogy_id)->first();
                    $t->document_code = $cat->code ?? '';
                    $t->name = $cat->name ?? '';
                } elseif ($type === 'BPR') {
                    $cat = DB::table('finished_product_category')
                        ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                        ->where('finished_product_category.id', $t->caterogy_id)
                        ->select('finished_product_category.*', 'product_name.name as product_name')
                        ->first();
                    $t->document_code = $cat->finished_product_code ?? '';
                    $t->name = $cat->product_name ?? '';
                } else {
                    $cat = DB::table('intermediate_category')
                        ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
                        ->where('intermediate_category.id', $t->caterogy_id)
                        ->select('intermediate_category.*', 'product_name.name as product_name')
                        ->first();
                    $t->document_code = $cat->intermediate_code ?? '';
                    $t->name = $cat->product_name ?? '';
                }

                $t->my_role = $wfForMe->role;
                $t->workflow_id = $wfForMe->id;
                $t->workflow_type = 'ebmr';
                $t->view_url = route('pages.ebmr.designer', $t->id);
                $actionableTemplates->push($t);
            }
        }

        // --- 2. CLEANING PROCESSES ---
        $myCleaningWorkflows = DB::table('cleaning_process_workflows')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->get();

        foreach ($myCleaningWorkflows as $wf) {
            $hasEarlierPending = DB::table('cleaning_process_workflows')
                ->where('process_list_id', $wf->process_list_id)
                ->where('type', $wf->type)
                ->where('status', 'pending')
                ->where('step_order', '<', $wf->step_order)
                ->exists();

            if (!$hasEarlierPending) {
                if ($wf->type === 'room') {
                    $list = DB::table('cleaning_room_processes_list')
                        ->leftJoin('user_management', 'cleaning_room_processes_list.created_by', '=', 'user_management.id')
                        ->where('cleaning_room_processes_list.id', $wf->process_list_id)
                        ->where('cleaning_room_processes_list.status', 'submitted')
                        ->select('cleaning_room_processes_list.*', 'user_management.fullName as owner_name')
                        ->first();
                } else {
                    $list = DB::table('cleaning_equip_processes_list')
                        ->leftJoin('user_management', 'cleaning_equip_processes_list.created_by', '=', 'user_management.id')
                        ->where('cleaning_equip_processes_list.id', $wf->process_list_id)
                        ->where('cleaning_equip_processes_list.status', 'submitted')
                        ->select('cleaning_equip_processes_list.*', 'user_management.fullName as owner_name')
                        ->first();
                }

                if ($list) {
                    $t = new \stdClass();
                    $t->id = $list->id;
                    $t->document_code = $list->process_code . ' (V.' . $list->version . ')';
                    $t->name = $list->process_name;
                    $t->owner_name = $list->owner_name;
                    $t->updated_at = $list->updated_at;
                    $t->my_role = $wf->role;
                    $t->workflow_id = $wf->id;
                    $t->workflow_type = 'cleaning';
                    $t->view_url = route('pages.manu_env.cleaning_process.index', ['type' => $wf->type, 'list_id' => $list->id]);
                    
                    $actionableTemplates->push($t);
                }
            }
        }

        // --- 3. CLEARANCE ROOM PROCESSES ---
        $myClearanceRoomWorkflows = DB::table('clearance_room_process_workflows')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->get();

        foreach ($myClearanceRoomWorkflows as $wf) {
            $hasEarlierPending = DB::table('clearance_room_process_workflows')
                ->where('process_list_id', $wf->process_list_id)
                ->where('status', 'pending')
                ->where('step_order', '<', $wf->step_order)
                ->exists();

            if (!$hasEarlierPending) {
                $list = DB::table('clearance_room_processes_list')
                    ->leftJoin('user_management', 'clearance_room_processes_list.created_by', '=', 'user_management.id')
                    ->where('clearance_room_processes_list.id', $wf->process_list_id)
                    ->where('clearance_room_processes_list.status', 'submitted')
                    ->select('clearance_room_processes_list.*', 'user_management.fullName as owner_name')
                    ->first();

                if ($list) {
                    $t = new \stdClass();
                    $t->id = $list->id;
                    $t->document_code = $list->process_code . ' (V.' . $list->version . ')';
                    $t->name = $list->process_name;
                    $t->owner_name = $list->owner_name;
                    $t->updated_at = $list->updated_at;
                    $t->my_role = $wf->role;
                    $t->workflow_id = $wf->id;
                    $t->workflow_type = 'clearance_room';
                    $t->view_url = route('pages.manu_env.clearance_process.index', ['type' => 'room', 'list_id' => $list->id]);
                    
                    $actionableTemplates->push($t);
                }
            }
        }

        // --- 4. CLEARANCE EQUIP PROCESSES ---
        $myClearanceEquipWorkflows = DB::table('clearance_equip_process_workflows')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->get();

        foreach ($myClearanceEquipWorkflows as $wf) {
            $hasEarlierPending = DB::table('clearance_equip_process_workflows')
                ->where('process_list_id', $wf->process_list_id)
                ->where('status', 'pending')
                ->where('step_order', '<', $wf->step_order)
                ->exists();

            if (!$hasEarlierPending) {
                $list = DB::table('clearance_equip_processes_list')
                    ->leftJoin('user_management', 'clearance_equip_processes_list.created_by', '=', 'user_management.id')
                    ->where('clearance_equip_processes_list.id', $wf->process_list_id)
                    ->where('clearance_equip_processes_list.status', 'submitted')
                    ->select('clearance_equip_processes_list.*', 'user_management.fullName as owner_name')
                    ->first();

                if ($list) {
                    $t = new \stdClass();
                    $t->id = $list->id;
                    $t->document_code = $list->process_code . ' (V.' . $list->version . ')';
                    $t->name = $list->process_name;
                    $t->owner_name = $list->owner_name;
                    $t->updated_at = $list->updated_at;
                    $t->my_role = $wf->role;
                    $t->workflow_id = $wf->id;
                    $t->workflow_type = 'clearance_equip';
                    $t->view_url = route('pages.manu_env.clearance_process.index', ['type' => 'equipment', 'list_id' => $list->id]);
                    
                    $actionableTemplates->push($t);
                }
            }
        }

        // Sort by updated_at descending
        $actionableTemplates = $actionableTemplates->sortByDesc('updated_at')->values();

        return view('pages.ebmr.approvals.list', ['templates' => $actionableTemplates]);
    }

    public function getTemplateWorkflow($id)
    {
        $latest = DB::table('ebmr_template_workflows')->where('template_id', $id)->orderBy('id', 'desc')->first();
        if ($latest) {
            $workflows = DB::table('ebmr_template_workflows')->where('template_id', $id)->where('created_at', $latest->created_at)->get();
            return response()->json($workflows);
        }
        return response()->json([]);
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
            DB::table('ebmr_template_workflows')->where('template_id', $id)->where('status', 'pending')->update(['status' => 'cancelled']);
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
            'workflow_type' => 'required|in:ebmr,cleaning,clearance_room,clearance_equip',
            'action' => 'required|in:approve,reject',
            'comment' => 'nullable|string'
        ]);

        $newWfStatus = $validated['action'] === 'approve' ? 'approved' : 'rejected';

        if ($validated['workflow_type'] === 'ebmr') {
            $workflow = DB::table('ebmr_template_workflows')->where('id', $validated['workflow_id'])->first();
            if (!$workflow || $workflow->status !== 'pending') {
                return response()->json(['success' => false, 'message' => 'Luồng duyệt không hợp lệ hoặc đã xử lý']);
            }

            DB::transaction(function () use ($workflow, $newWfStatus, $validated) {
                DB::table('ebmr_template_workflows')->where('id', $workflow->id)->update(['status' => $newWfStatus, 'comment' => $validated['comment'], 'updated_at' => now()]);

                if ($newWfStatus === 'approved' && $workflow->role === 'authorizer') {
                    DB::table('ebmr_templates')->where('id', $workflow->template_id)->update(['issued_date' => now()]);
                }

                if ($newWfStatus === 'rejected') {
                    DB::table('ebmr_template_workflows')->where('template_id', $workflow->template_id)->where('status', 'pending')->update(['status' => 'cancelled', 'updated_at' => now()]);
                    DB::table('ebmr_templates')->where('id', $workflow->template_id)->update(['status' => 'draft']);
                } else {
                    $pendingCount = DB::table('ebmr_template_workflows')->where('template_id', $workflow->template_id)->where('status', 'pending')->count();
                    if ($pendingCount === 0) {
                        DB::table('ebmr_templates')->where('id', $workflow->template_id)->update(['status' => 'issued', 'updated_at' => now()]);
                    }
                }
            });
        } elseif ($validated['workflow_type'] === 'cleaning') {
            $workflow = DB::table('cleaning_process_workflows')->where('id', $validated['workflow_id'])->first();
            if (!$workflow || $workflow->status !== 'pending') {
                return response()->json(['success' => false, 'message' => 'Luồng duyệt không hợp lệ hoặc đã xử lý']);
            }

            DB::transaction(function () use ($workflow, $newWfStatus, $validated) {
                DB::table('cleaning_process_workflows')->where('id', $workflow->id)->update(['status' => $newWfStatus, 'comment' => $validated['comment'], 'updated_at' => now()]);

                $tableList = $workflow->type === 'room' ? 'cleaning_room_processes_list' : 'cleaning_equip_processes_list';

                if ($newWfStatus === 'rejected') {
                    DB::table('cleaning_process_workflows')->where('process_list_id', $workflow->process_list_id)->where('type', $workflow->type)->where('status', 'pending')->update(['status' => 'cancelled', 'updated_at' => now()]);
                    DB::table($tableList)->where('id', $workflow->process_list_id)->update(['status' => 'draft']);
                } else {
                    $pendingCount = DB::table('cleaning_process_workflows')->where('process_list_id', $workflow->process_list_id)->where('type', $workflow->type)->where('status', 'pending')->count();
                    if ($pendingCount === 0) {
                        DB::table($tableList)->where('id', $workflow->process_list_id)->update(['status' => 'approved', 'updated_at' => now()]);
                    }
                }
            });
        } elseif (in_array($validated['workflow_type'], ['clearance_room', 'clearance_equip'])) {
            $tableWf = $validated['workflow_type'] === 'clearance_room' ? 'clearance_room_process_workflows' : 'clearance_equip_process_workflows';
            $tableList = $validated['workflow_type'] === 'clearance_room' ? 'clearance_room_processes_list' : 'clearance_equip_processes_list';

            $workflow = DB::table($tableWf)->where('id', $validated['workflow_id'])->first();
            if (!$workflow || $workflow->status !== 'pending') {
                return response()->json(['success' => false, 'message' => 'Luồng duyệt không hợp lệ hoặc đã xử lý']);
            }

            DB::transaction(function () use ($workflow, $newWfStatus, $validated, $tableWf, $tableList) {
                DB::table($tableWf)->where('id', $workflow->id)->update(['status' => $newWfStatus, 'comment' => $validated['comment'], 'updated_at' => now()]);

                if ($newWfStatus === 'rejected') {
                    DB::table($tableWf)->where('process_list_id', $workflow->process_list_id)->where('status', 'pending')->update(['status' => 'cancelled', 'updated_at' => now()]);
                    DB::table($tableList)->where('id', $workflow->process_list_id)->update(['status' => 'draft']);
                } else {
                    $pendingCount = DB::table($tableWf)->where('process_list_id', $workflow->process_list_id)->where('status', 'pending')->count();
                    if ($pendingCount === 0) {
                        DB::table($tableList)->where('id', $workflow->process_list_id)->update(['status' => 'approved', 'updated_at' => now()]);
                    }
                }
            });
        }

        $msg = $validated['action'] === 'approve' ? 'Đã phê duyệt thành công' : 'Đã từ chối hồ sơ';
        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function getWorkflowHistory($type, $id)
    {
        if ($type === 'ebmr') {
            // Kèm theo owner_id của hồ sơ trên từng dòng để frontend biết có nên
            // hiện nút "Đổi người trình ký" hay không (chỉ chủ sở hữu mới được đổi).
            $workflows = DB::table('ebmr_template_workflows')
                ->join('user_management', 'ebmr_template_workflows.user_id', '=', 'user_management.id')
                ->leftJoin('ebmr_templates', 'ebmr_template_workflows.template_id', '=', 'ebmr_templates.id')
                ->where('ebmr_template_workflows.template_id', $id)
                ->select('ebmr_template_workflows.*', 'user_management.fullName as user_name', 'user_management.signature_image', 'ebmr_templates.owner_id')
                ->orderBy('ebmr_template_workflows.id', 'asc')
                ->get();
        } elseif ($type === 'clearance_room') {
            $workflows = DB::table('clearance_room_process_workflows')
                ->join('user_management', 'clearance_room_process_workflows.user_id', '=', 'user_management.id')
                ->where('process_list_id', $id)
                ->select('clearance_room_process_workflows.*', 'user_management.fullName as user_name', 'user_management.signature_image')
                ->orderBy('clearance_room_process_workflows.id', 'asc')
                ->get();
        } elseif ($type === 'clearance_equip') {
            $workflows = DB::table('clearance_equip_process_workflows')
                ->join('user_management', 'clearance_equip_process_workflows.user_id', '=', 'user_management.id')
                ->where('process_list_id', $id)
                ->select('clearance_equip_process_workflows.*', 'user_management.fullName as user_name', 'user_management.signature_image')
                ->orderBy('clearance_equip_process_workflows.id', 'asc')
                ->get();
        } else {
            // cleaning_room / cleaning_equipment: process_list_id là 2 dãy ID độc lập
            // (từ cleaning_room_processes_list và cleaning_equip_processes_list), nên
            // BẮT BUỘC lọc thêm theo cột "type" — nếu không, 1 process_list_id trùng số
            // giữa phòng và thiết bị sẽ bị gộp nhầm lịch sử trình ký của nhau.
            $subType = str_starts_with($type, 'cleaning_') ? substr($type, strlen('cleaning_')) : null;

            $query = DB::table('cleaning_process_workflows')
                ->join('user_management', 'cleaning_process_workflows.user_id', '=', 'user_management.id')
                ->where('process_list_id', $id);

            if ($subType) {
                $query->where('cleaning_process_workflows.type', $subType);
            }

            $workflows = $query
                ->select('cleaning_process_workflows.*', 'user_management.fullName as user_name', 'user_management.signature_image')
                ->orderBy('cleaning_process_workflows.id', 'asc')
                ->get();
        }
        return response()->json($workflows);
    }

    /**
     * Danh sách người dùng để chọn khi đổi người trình ký (dùng cho dropdown reassign).
     */
    public function getEligibleUsers()
    {
        $users = DB::table('user_management')->select('id', 'fullName as name')->orderBy('fullName')->get();
        return response()->json($users);
    }

    /**
     * Đổi người phụ trách 1 bước trình ký (Kiểm tra/Phê duyệt/Ban hành) đang CHỜ
     * DUYỆT — chỉ chủ sở hữu hồ sơ (owner_id) mới được thực hiện. Bước đã xử lý
     * (đã duyệt/từ chối/hủy) không thể đổi. Mọi thay đổi được ghi vào Audit Trail.
     */
    public function reassignWorkflowUser(Request $request)
    {
        $validated = $request->validate([
            'workflow_id' => 'required|integer',
            'new_user_id' => 'required|integer',
        ]);

        $workflow = DB::table('ebmr_template_workflows')->where('id', $validated['workflow_id'])->first();
        if (!$workflow) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy bước trình ký']);
        }
        if ($workflow->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Chỉ có thể đổi người khi bước này đang chờ duyệt']);
        }

        $template = DB::table('ebmr_templates')->where('id', $workflow->template_id)->first();
        if (!$template) {
            return response()->json(['success' => false, 'message' => 'Hồ sơ không tồn tại']);
        }
        if ((int) $template->owner_id !== (int) (session('user')['userId'] ?? 0)) {
            return response()->json(['success' => false, 'message' => 'Chỉ chủ sở hữu hồ sơ mới được đổi người trình ký']);
        }

        if ((int) $validated['new_user_id'] === (int) $workflow->user_id) {
            return response()->json(['success' => false, 'message' => 'Người được chọn trùng với người hiện tại']);
        }

        $newUser = DB::table('user_management')->where('id', $validated['new_user_id'])->first();
        if (!$newUser) {
            return response()->json(['success' => false, 'message' => 'Người dùng không tồn tại']);
        }
        $oldUser = DB::table('user_management')->where('id', $workflow->user_id)->first();

        DB::table('ebmr_template_workflows')->where('id', $workflow->id)->update([
            'user_id' => $validated['new_user_id'],
            'updated_at' => now(),
        ]);

        $roleLabel = $workflow->role === 'reviewer' ? 'Kiểm tra' : ($workflow->role === 'approver' ? 'Phê duyệt' : 'Ban hành');
        AuditTrialController::log(
            'ReassignApprover',
            'ebmr_template_workflows',
            $workflow->id,
            $roleLabel . ': ' . ($oldUser->fullName ?? ('#' . $workflow->user_id)),
            $roleLabel . ': ' . ($newUser->fullName ?? ('#' . $validated['new_user_id']))
        );

        return response()->json(['success' => true, 'message' => 'Đã đổi người trình ký thành công']);
    }
}
