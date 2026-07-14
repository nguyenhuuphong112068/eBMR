<?php

namespace App\Http\Controllers\Pages\ManuEnv;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ClearanceRoomProcess;
use App\Models\ClearanceEquipProcess;
use App\Models\ClearanceRoomProcessList;
use App\Models\ClearanceEquipProcessList;
use App\Models\ClearanceRoomCampaign;
use App\Models\ClearanceRoomCampaignStep;
use App\Models\ClearanceEquipCampaign;
use App\Models\ClearanceEquipCampaignStep;
use App\Services\ApprovalWorkflowNotifier;
use App\Traits\EditsCampaignStepResults;
use Carbon\Carbon;

class ClearanceProcessController extends Controller
{
    use EditsCampaignStepResults;

    public function list($type, $id)
    {
        \App\Services\DocumentActivationService::activateAllIssuedDocuments();
        $entityName = '';
        $entityCode = '';
        $processesList = [];

        if ($type === 'room') {
            $entity = DB::connection('pms')->table('room')->where('id', $id)->first();
            if (!$entity) abort(404);
            $entityName = $entity->name;
            $entityCode = $entity->code;
            $processesList = ClearanceRoomProcessList::where('room_id', $id)->orderBy('version', 'desc')->get();
        } elseif ($type === 'equipment') {
            $entity = DB::table('instrument')->where('id', $id)->first();
            if (!$entity) abort(404);
            $entityName = $entity->name;
            $entityCode = $entity->code;
            $processesList = ClearanceEquipProcessList::where('equipment_id', $id)->orderBy('version', 'desc')->get();
        } else {
            abort(400, 'Invalid type');
        }

        foreach($processesList as $p) {
            $p->current_workflow_step = null;
            if ($p->status === 'submitted') {
                $wfTable = $type === 'room' ? 'clearance_room_process_workflows' : 'clearance_equip_process_workflows';
                $pendingWf = DB::table($wfTable)
                    ->where('process_list_id', $p->id)
                    ->where('status', 'pending')
                    ->orderBy('step_order', 'asc')
                    ->first();
                if ($pendingWf) {
                    $user = DB::table('user_management')->where('id', $pendingWf->user_id)->first();
                    $roleStr = $pendingWf->role === 'reviewer' ? 'Kiểm tra' : ($pendingWf->role === 'approver' ? 'Phê duyệt' : 'Ban hành');
                    $p->current_workflow_step = 'Đang chờ ' . $roleStr . ' (' . ($user->fullName ?? 'Unknown') . ')';
                }
            }
        }

        $users = DB::table('user_management')->select('id', 'fullName as name')->get();

        return view('pages.manu_env.clearance_process.list', compact('type', 'id', 'entityName', 'entityCode', 'processesList', 'users'));
    }

    public function createList(Request $request, $type, $id)
    {
        $request->validate([
            'process_code' => 'required|string',
            'process_name' => 'required|string',
            'clearance_type' => 'nullable|integer|in:1,2,3',
        ]);

        $userId = session('user')['userId'] ?? 1;

        if ($type === 'room') {
            $list = ClearanceRoomProcessList::create([
                'room_id' => $id,
                'process_code' => $request->process_code,
                'process_name' => $request->process_name,
                'version' => 1,
                'status' => 'draft',
                'created_by' => $userId,
                'clearance_type' => $request->clearance_type ?? 1
            ]);
        } else {
            $list = ClearanceEquipProcessList::create([
                'equipment_id' => $id,
                'process_code' => $request->process_code,
                'process_name' => $request->process_name,
                'version' => 1,
                'status' => 'draft',
                'created_by' => $userId,
                'clearance_type' => $request->clearance_type ?? 1
            ]);
        }

        return response()->json(['success' => true, 'list_id' => $list->id]);
    }

    public function upVersion(Request $request, $type, $list_id)
    {
        $userId = session('user')['userId'] ?? 1;

        if ($type === 'room') {
            $oldList = ClearanceRoomProcessList::findOrFail($list_id);
            if ($oldList->status !== 'active' && $oldList->status !== 'approved') {
                return response()->json(['success' => false, 'message' => 'Chỉ có thể lên ấn bản từ bản đã duyệt/hiện hành']);
            }
            $newList = ClearanceRoomProcessList::create([
                'room_id' => $oldList->room_id,
                'process_code' => $oldList->process_code,
                'process_name' => $oldList->process_name,
                'version' => $oldList->version + 1,
                'status' => 'draft',
                'created_by' => $userId,
                'clearance_type' => $oldList->clearance_type
            ]);
            $steps = ClearanceRoomProcess::where('process_list_id', $oldList->id)->get();
            foreach($steps as $s) {
                ClearanceRoomProcess::create([
                    'process_list_id' => $newList->id,
                    'step' => $s->step,
                    'content' => $s->content,
                    'standard' => $s->standard
                ]);
            }
        } else {
            $oldList = ClearanceEquipProcessList::findOrFail($list_id);
            if ($oldList->status !== 'active' && $oldList->status !== 'approved') {
                return response()->json(['success' => false, 'message' => 'Chỉ có thể lên ấn bản từ bản đã duyệt/hiện hành']);
            }
            $newList = ClearanceEquipProcessList::create([
                'equipment_id' => $oldList->equipment_id,
                'process_code' => $oldList->process_code,
                'process_name' => $oldList->process_name,
                'version' => $oldList->version + 1,
                'status' => 'draft',
                'created_by' => $userId,
                'clearance_type' => $oldList->clearance_type
            ]);
            $steps = ClearanceEquipProcess::where('process_list_id', $oldList->id)->get();
            foreach($steps as $s) {
                ClearanceEquipProcess::create([
                    'process_list_id' => $newList->id,
                    'step' => $s->step,
                    'content' => $s->content,
                    'standard' => $s->standard
                ]);
            }
        }
        return response()->json(['success' => true, 'list_id' => $newList->id]);
    }

    public function index($type, $list_id)
    {
        $entityName = '';
        $entityCode = '';
        $processes = [];
        $id = 0;

        if ($type === 'room') {
            $list = ClearanceRoomProcessList::findOrFail($list_id);
            $id = $list->room_id;
            $entity = DB::connection('pms')->table('room')->where('id', $id)->first();
            if (!$entity) abort(404);
            $entityName = $entity->name;
            $entityCode = $entity->code;
            $processes = ClearanceRoomProcess::where('process_list_id', $list_id)->orderBy('step', 'asc')->get();
        } elseif ($type === 'equipment') {
            $list = ClearanceEquipProcessList::findOrFail($list_id);
            $id = $list->equipment_id;
            $entity = DB::table('instrument')->where('id', $id)->first();
            if (!$entity) abort(404);
            $entityName = $entity->name;
            $entityCode = $entity->code;
            $processes = ClearanceEquipProcess::where('process_list_id', $list_id)->orderBy('step', 'asc')->get();
        } else {
            abort(400, 'Invalid type');
        }

        foreach ($processes as $p) {
            $p->content = preg_replace('/(src|href)=["\']?(https?:\/\/[^\/]+)(\/upLoadData\/img\/clearance_process\/)/i', '$1="$3', $p->content);
            $p->content = str_replace(['http://127.0.0.1:8001', 'http://localhost:8001'], '', $p->content);

            $p->standard = preg_replace('/(src|href)=["\']?(https?:\/\/[^\/]+)(\/upLoadData\/img\/clearance_process\/)/i', '$1="$3', $p->standard);
            $p->standard = str_replace(['http://127.0.0.1:8001', 'http://localhost:8001'], '', $p->standard);
        }

        return view('pages.manu_env.clearance_process.index', compact('type', 'id', 'list_id', 'entityName', 'entityCode', 'processes', 'list'));
    }

    public function store(Request $request, $type, $list_id)
    {
        $request->validate([
            'processes' => 'array',
            'processes.*.step' => 'required|numeric',
            'processes.*.content' => 'nullable|string',
            'processes.*.standard' => 'nullable|string',
        ]);

        $processes = $request->input('processes', []);
        
        DB::beginTransaction();
        try {
            if ($type === 'room') {
                ClearanceRoomProcess::where('process_list_id', $list_id)->delete();
                foreach ($processes as $p) {
                    ClearanceRoomProcess::create([
                        'process_list_id' => $list_id,
                        'step' => $p['step'],
                        'content' => $p['content'],
                        'standard' => $p['standard'],
                    ]);
                }
            } elseif ($type === 'equipment') {
                ClearanceEquipProcess::where('process_list_id', $list_id)->delete();
                foreach ($processes as $p) {
                    ClearanceEquipProcess::create([
                        'process_list_id' => $list_id,
                        'step' => $p['step'],
                        'content' => $p['content'],
                        'standard' => $p['standard'],
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Đã lưu quy trình vệ sinh thành công!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    public function uploadImage(Request $request)
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            try {
                $filename = 'clearance_process_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                // Lưu vào thư mục public/upLoadData/img/clearance_process
                $file->move(public_path('upLoadData/img/clearance_process'), $filename);

                // Sinh URL trả về cho editor (chỉ lấy path relative)
                $url = '/upLoadData/img/clearance_process/' . $filename;
                return response()->json(['url' => $url]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Lỗi tải ảnh: ' . $e->getMessage()], 500);
            }
        }
        
        return response()->json(['error' => 'Không tìm thấy file tải lên'], 400);
    }

    public function getWorkflow($type, $list_id)
    {
        $wfTable = $type === 'room' ? 'clearance_room_process_workflows' : 'clearance_equip_process_workflows';
        $latest = DB::table($wfTable)->where('process_list_id', $list_id)->orderBy('id', 'desc')->first();
        if ($latest) {
            $workflows = DB::table($wfTable)->where('process_list_id', $list_id)->where('created_at', $latest->created_at)->get();
            return response()->json($workflows);
        }
        return response()->json([]);
    }

    public function storeWorkflow(Request $request, $type, $list_id)
    {
        $validated = $request->validate([
            'reviewers' => 'nullable|array',
            'reviewers.*' => 'integer',
            'approver' => 'nullable|integer',
            'authorizer' => 'nullable|integer',
            'reviewer_due_dates' => 'nullable|array',
            'reviewer_due_dates.*' => 'nullable|date',
            'reviewer_reasons' => 'nullable|array',
            'reviewer_reasons.*' => 'nullable|string|max:500',
            'approver_due_date' => 'nullable|date',
            'approver_reason' => 'nullable|string|max:500',
            'authorizer_due_date' => 'nullable|date',
            'authorizer_reason' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($type, $list_id, $validated) {
            $wfTable = $type === 'room' ? 'clearance_room_process_workflows' : 'clearance_equip_process_workflows';
            DB::table($wfTable)->where('process_list_id', $list_id)->where('status', 'pending')->update(['status' => 'cancelled']);
            $insertData = [];
            if (!empty($validated['reviewers'])) {
                foreach ($validated['reviewers'] as $userId) {
                    $insertData[] = [
                        'process_list_id' => $list_id, 'role' => 'reviewer', 'user_id' => $userId, 'step_order' => 1, 'status' => 'pending',
                        'due_date' => $validated['reviewer_due_dates'][$userId] ?? null,
                        'reason' => $validated['reviewer_reasons'][$userId] ?? null,
                        'created_at' => now(), 'updated_at' => now(),
                    ];
                }
            }
            if (!empty($validated['approver'])) $insertData[] = [
                'process_list_id' => $list_id, 'role' => 'approver', 'user_id' => $validated['approver'], 'step_order' => 2, 'status' => 'pending',
                'due_date' => $validated['approver_due_date'] ?? null,
                'reason' => $validated['approver_reason'] ?? null,
                'created_at' => now(), 'updated_at' => now(),
            ];
            if (!empty($validated['authorizer'])) $insertData[] = [
                'process_list_id' => $list_id, 'role' => 'authorizer', 'user_id' => $validated['authorizer'], 'step_order' => 3, 'status' => 'pending',
                'due_date' => $validated['authorizer_due_date'] ?? null,
                'reason' => $validated['authorizer_reason'] ?? null,
                'created_at' => now(), 'updated_at' => now(),
            ];

            if (count($insertData) > 0) DB::table($wfTable)->insert($insertData);

            if ($type === 'room') {
                DB::table('clearance_room_processes_list')->where('id', $list_id)->where('status', 'draft')->update(['status' => 'submitted']);
            } else {
                DB::table('clearance_equip_processes_list')->where('id', $list_id)->where('status', 'draft')->update(['status' => 'submitted']);
            }
        });

        ApprovalWorkflowNotifier::notifyActionableStep($type === 'room' ? 'clearance_room' : 'clearance_equip', (int) $list_id);

        return response()->json(['success' => true, 'message' => 'Lưu luồng trình ký thành công']);
    }

    public function setEffectiveDate(Request $request, $type, $list_id)
    {
        $validated = $request->validate([
            'effective_date' => 'required|date'
        ]);
        
        $table = $type === 'room' ? 'clearance_room_processes_list' : 'clearance_equip_processes_list';
        
        $list = DB::table($table)->where('id', $list_id)->first();
        if (!$list) {
            return response()->json(['success' => false, 'message' => 'Quy trình không tồn tại']);
        }
        
        if ((int)$list->created_by != (int)(session('user')['userId'] ?? 0)) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này (created_by: '.$list->created_by.', user: '.(session('user')['userId'] ?? 'null').')']);
        }
        
        $effectiveDate = Carbon::parse($validated['effective_date']);
        $newStatus = $effectiveDate->isToday() || !$effectiveDate->isFuture() ? 'active' : 'issued';
        
        DB::table($table)->where('id', $list_id)->update([
            'status' => $newStatus,
            'effective_date' => $validated['effective_date'],
            'updated_at' => now(),
        ]);
        
        if ($newStatus === 'active') {
            if ($type === 'room') {
                DB::table($table)
                    ->where('room_id', $list->room_id)
                    ->where('clearance_type', $list->clearance_type)
                    ->where('id', '!=', $list_id)
                    ->where('status', 'active')
                    ->update(['status' => 'expired', 'updated_at' => now()]);
            } else {
                DB::table($table)
                    ->where('equipment_id', $list->equipment_id)
                    ->where('clearance_type', $list->clearance_type)
                    ->where('id', '!=', $list_id)
                    ->where('status', 'active')
                    ->update(['status' => 'expired', 'updated_at' => now()]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Cập nhật ngày hiệu lực thành công']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CAMPAIGN METHODS – Thực hiện quy trình dọn quang phòng (+ thiết bị trong phòng)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /clearance-process/room/{room_id}/campaign/open
     * Mở trang thực hiện dọn quang phòng; luôn kèm campaign dọn quang của từng thiết bị
     * trong phòng (nếu có) qua room_campaign_id — UI chỉ cho hoàn thành phòng khi toàn
     * bộ thiết bị cũng đã hoàn thành (xem checkFinishButton() trong campaign_execute.blade.php).
     */
    public function openCampaignPage(Request $request, $room_id)
    {
        session(['title' => 'Dọn Quang Phòng']);

        $userId   = session('user')['userId']   ?? 1;
        $fullName = session('user')['fullName'] ?? 'N/A';

        // Lấy thông tin phòng từ PMS DB
        $room = DB::connection('pms')->table('room')->where('id', $room_id)->first();
        if (!$room) abort(404, 'Không tìm thấy phòng.');

        $campaignId = $request->query('campaign_id');
        $campaign = null;
        
        if ($campaignId) {
            // View specific campaign
            $campaign = ClearanceRoomCampaign::where('room_id', $room_id)->findOrFail($campaignId);
            $activeProcess = ClearanceRoomProcessList::findOrFail($campaign->process_list_id);
            $processSteps = ClearanceRoomProcess::where('process_list_id', $activeProcess->id)
                ->orderBy('step', 'asc')
                ->get();
        } else {
            // Logic cho việc bắt đầu campaign mới hoặc tiếp tục campaign in_progress
            $type = $request->query('type', 1);

            // Tìm quy trình theo loại: ưu tiên active → approved → submitted → draft
            $activeProcess = ClearanceRoomProcessList::where('room_id', $room_id)
                ->where('clearance_type', $type)
                ->whereIn('status', ['active', 'approved', 'submitted', 'draft'])
                ->orderByRaw("FIELD(status, 'active', 'approved', 'submitted', 'draft')")
                ->orderBy('version', 'desc')
                ->first();

            if (!$activeProcess) {
                return redirect()->route('pages.ebmr.production')
                    ->with('error', "Phòng {$room->code} chưa có quy trình dọn quang. Vui lòng thiết kế quy trình tại Môi Trường > Dọn Quang Phòng.");
            }

            $processSteps = ClearanceRoomProcess::where('process_list_id', $activeProcess->id)
                ->orderBy('step', 'asc')
                ->get();

            if ($processSteps->isEmpty()) {
                return redirect()->route('pages.ebmr.production')
                    ->with('error', "Quy trình dọn quang của phòng {$room->code} chưa có bước nào. Vui lòng thiết kế các bước thực hiện.");
            }

            // Kiểm tra xem có campaign nào đang thực hiện dở dang không
            $campaign = ClearanceRoomCampaign::where('room_id', $room_id)
                ->where('status', 'in_progress')
                ->first();
        }

        if (!$campaign) {
            DB::beginTransaction();
            try {
                $employeeIds = $request->query('employee_ids', [$userId]);
                if (!is_array($employeeIds)) $employeeIds = [$employeeIds];
                // Convert to integer
                $employeeIds = array_map('intval', $employeeIds);
                if (!in_array($userId, $employeeIds)) {
                    $employeeIds[] = $userId;
                }

                // 1. Tạo campaign mới. Lưu ý: KHÔNG ghi room_logbooks ở đây — dọn quang
                // (clearance) là quy trình độc lập với vệ sinh (cleaning); trạng thái
                // 'cleaned'/'cleaning' của phòng chỉ do CleaningProcessController quản lý.
                $campaign = ClearanceRoomCampaign::create([
                    'room_id'         => $room_id,
                    'distribution_id' => $request->query('distribution_id'),
                    'process_list_id' => $activeProcess->id,
                    'status'          => 'in_progress',
                    'started_by'      => $userId,
                    'started_at'      => now(),
                    'employee_ids'    => $employeeIds,
                ]);

                // 2. Tạo các bước phòng tương ứng
                foreach ($processSteps as $s) {
                    ClearanceRoomCampaignStep::create([
                        'campaign_id'     => $campaign->id,
                        'process_step_id' => $s->id,
                        'step'            => $s->step,
                        'is_done'         => false,
                    ]);
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->route('pages.ebmr.production')
                    ->with('error', 'Lỗi khởi tạo quy trình dọn quang: ' . $e->getMessage());
            }
        }

        // 4. Load lại campaign với steps + nội dung HTML
        $campaign->load('steps.doneByUser');
        $campaignSteps = $campaign->steps->map(function ($step) use ($processSteps) {
            $source = $processSteps->firstWhere('id', $step->process_step_id);
            
            $content = $source ? $source->content : '';
            // Xóa domain cứng của ảnh (vd: http://127.0.0.1:8001/cleaning_images/...) thành path tương đối
            $content = preg_replace('/(src|href)=["\']?(https?:\/\/[^\/]+)(\/cleaning_images\/)/i', '$1="$3', $content);
            // Backup replace in case URL is anywhere else
            $content = str_replace('http://127.0.0.1:8001', '', $content);
            $content = str_replace('http://localhost:8001', '', $content);
            
            $step->content  = $content;
            $step->standard = $source ? $source->standard : '';
            return $step;
        });

        // Thiết bị cần dọn quang cùng phòng trong lần này (nếu có) — campaign phòng chỉ
        // được phép hoàn thành khi tất cả các campaign này cũng đã 'completed'.
        $equipCampaigns = ClearanceEquipCampaign::where('room_campaign_id', $campaign->id)
            ->get()
            ->map(function ($ec) {
                $instrument = DB::table('instrument')->where('id', $ec->equipment_id)->first();
                $ec->equipment_code = $instrument->code ?? '?';
                $ec->equipment_name = $instrument->name ?? '';
                return $ec;
            });

        return view('pages.manu_env.clearance_process.campaign_execute', [
            'room'           => $room,
            'campaign'       => $campaign,
            'campaignSteps'  => $campaignSteps,
            'processList'    => $activeProcess,
            'equipCampaigns' => $equipCampaigns,
        ]);
    }

    /**
     * POST /clearance-process/room/{room_id}/campaign/start
     * Tạo một lần thực hiện vệ sinh mới cho phòng dựa trên quy trình active hiện tại.
     */
    public function startCampaign(Request $request, $room_id)
    {
        $userId = session('user')['userId'] ?? 1;

        $type = $request->input('type', 1);

        // Tìm quy trình active cho phòng và theo loại
        $activeProcess = ClearanceRoomProcessList::where('room_id', $room_id)
            ->where('clearance_type', $type)
            ->where('status', 'active')
            ->orderBy('version', 'desc')
            ->first();

        if (!$activeProcess) {
            return response()->json([
                'success' => false,
                'message' => 'Phòng này chưa có quy trình vệ sinh đang hiện hành (active). Vui lòng thiết lập quy trình trước.'
            ]);
        }

        $steps = ClearanceRoomProcess::where('process_list_id', $activeProcess->id)
            ->orderBy('step', 'asc')
            ->get();

        if ($steps->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Quy trình vệ sinh chưa có bước nào. Vui lòng thiết kế quy trình trước.'
            ]);
        }

        DB::beginTransaction();
        try {
            // Tạo campaign
            $campaign = ClearanceRoomCampaign::create([
                'room_id'        => $room_id,
                'process_list_id'=> $activeProcess->id,
                'status'         => 'in_progress',
                'started_by'     => $userId,
                'started_at'     => now(),
            ]);

            // Tạo các bước tương ứng
            foreach ($steps as $s) {
                ClearanceRoomCampaignStep::create([
                    'campaign_id'     => $campaign->id,
                    'process_step_id' => $s->id,
                    'step'            => $s->step,
                    'is_done'         => false,
                ]);
            }

            DB::commit();

            return response()->json([
                'success'     => true,
                'campaign_id' => $campaign->id,
                'message'     => 'Bắt đầu quy trình vệ sinh thành công!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    /**
     * GET /clearance-process/campaign/{campaign_id}
     * Trả về thông tin campaign + danh sách bước kèm nội dung quy trình.
     */
    public function getCampaign($campaign_id)
    {
        $campaign = ClearanceRoomCampaign::with('steps')->findOrFail($campaign_id);

        $processList = ClearanceRoomProcessList::find($campaign->process_list_id);
        $room = DB::connection('pms')->table('room')->where('id', $campaign->room_id)->first();

        // Gắn nội dung HTML cho từng bước từ bảng cleaning_room_processes
        $stepsWithContent = $campaign->steps->map(function ($step) {
            $source = ClearanceRoomProcess::find($step->process_step_id);
            $step->content  = $source ? $source->content  : '';
            $step->standard = $source ? $source->standard : '';
            return $step;
        });

        $totalSteps = $stepsWithContent->count();
        $doneSteps  = $stepsWithContent->where('is_done', true)->count();

        return response()->json([
            'success'      => true,
            'campaign'     => [
                'id'            => $campaign->id,
                'status'        => $campaign->status,
                'process_code'  => $processList->process_code ?? '',
                'process_name'  => $processList->process_name ?? '',
                'version'       => $processList->version ?? 1,
                'room_code'     => $room->code ?? '',
                'room_name'     => $room->name ?? '',
                'total_steps'   => $totalSteps,
                'done_steps'    => $doneSteps,
            ],
            'steps' => $stepsWithContent->values(),
        ]);
    }



    /**
     * POST /clearance-process/campaign/{campaign_id}/step/{step_id}/complete
     * Ghi nhận hoàn thành một bước dọn quang.
     */
    public function completeStep(Request $request, $campaign_id, $step_id)
    {
        $userId   = session('user')['userId']   ?? 1;
        $fullName = session('user')['fullName'] ?? 'N/A';

        $campaignStep = ClearanceRoomCampaignStep::where('id', $step_id)
            ->where('campaign_id', $campaign_id)
            ->firstOrFail();

        $imagePaths = [];
        if ($request->hasFile('images')) {
            $files = is_array($request->file('images')) ? $request->file('images') : [$request->file('images')];
            foreach ($files as $file) {
                if (count($imagePaths) >= 5) break; // Limit to 5 images
                // Cấu trúc: [tên_bảng]_[id]_[timestamp]_[uniqid].[ext]
                $filename = 'cleaning_room_campaign_steps_' . $campaignStep->id . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('upLoadData/img/cleaning_result'), $filename);
                $imagePaths[] = '/upLoadData/img/cleaning_result/' . $filename;
            }
        }

        $campaignStep->update([
            'is_done'         => true,
            'is_passed'       => $request->has('is_passed') ? filter_var($request->input('is_passed'), FILTER_VALIDATE_BOOLEAN) : null,
            'result_note'     => $request->input('result_note', ''),
            'attached_images' => empty($imagePaths) ? null : $imagePaths,
            'done_by'         => $userId,
            'done_at'         => now(),
        ]);

        // Tính lại tiến độ
        $campaign   = ClearanceRoomCampaign::with('steps')->findOrFail($campaign_id);
        $totalSteps = $campaign->steps->count();
        $doneSteps  = $campaign->steps->where('is_done', true)->count();

        return response()->json([
            'success'     => true,
            'message'     => 'Ghi nhận bước ' . $campaignStep->step . ' hoàn thành!',
            'done_steps'  => $doneSteps,
            'total_steps' => $totalSteps,
            'done_by'     => $fullName,
        ]);
    }

    /**
     * POST /clearance-process/campaign/{campaign_id}/step/{step_id}/edit
     */
    public function editStep(Request $request, $campaign_id, $step_id)
    {
        return $this->handleEditCampaignStep(
            $request,
            'clearance_room',
            ClearanceRoomCampaignStep::class,
            ClearanceRoomCampaign::class,
            $campaign_id,
            $step_id,
            ['done' => 'is_done', 'note' => 'result_note', 'by' => 'done_by', 'at' => 'done_at']
        );
    }

    /**
     * GET /clearance-process/campaign/step/{step_id}/history
     */
    public function getStepHistory($step_id)
    {
        return $this->handleGetCampaignStepHistory('clearance_room', $step_id);
    }

    /**
     * POST /clearance-process/campaign/{campaign_id}/complete
     * Hoàn thành toàn bộ campaign dọn quang phòng. KHÔNG đụng tới room_logbooks/trạng
     * thái vệ sinh — dọn quang là quy trình độc lập với vệ sinh (khác campaign, khác
     * mục đích). Nếu campaign gắn với 1 phiên sản xuất (distribution_id), yêu cầu toàn
     * bộ campaign dọn quang thiết bị cùng phòng cũng đã 'completed' trước khi cho phép
     * hoàn thành, rồi chốt clearance_completed_at trên ebmr_record_distributions —
     * đây là mốc thật sự mở khóa trang ghi chép hồ sơ.
     */
    public function completeCampaign(Request $request, $campaign_id)
    {
        $userId = session('user')['userId'] ?? 1;

        $campaign = ClearanceRoomCampaign::with('steps')->findOrFail($campaign_id);

        // Kiểm tra tất cả bước đã hoàn thành chưa
        $undoneSteps = $campaign->steps->where('is_done', false)->count();
        if ($undoneSteps > 0) {
            return response()->json([
                'success' => false,
                'message' => "Còn {$undoneSteps} bước chưa hoàn thành. Vui lòng thực hiện hết tất cả các bước trước khi hoàn thành."
            ]);
        }

        // Toàn bộ thiết bị cùng phòng trong lần dọn quang này cũng phải xong
        $pendingEquip = ClearanceEquipCampaign::where('room_campaign_id', $campaign->id)
            ->where('status', '!=', 'completed')
            ->count();
        if ($pendingEquip > 0) {
            return response()->json([
                'success' => false,
                'message' => "Còn {$pendingEquip} thiết bị chưa hoàn thành dọn quang. Vui lòng thực hiện hết trước khi hoàn thành."
            ]);
        }

        DB::beginTransaction();
        try {
            $campaign->update([
                'status'       => 'completed',
                'completed_by' => $userId,
                'completed_at' => now(),
            ]);

            $redirectUrl = null;
            if ($campaign->distribution_id) {
                $dist = DB::table('ebmr_record_distributions')->where('id', $campaign->distribution_id)->first();
                if ($dist) {
                    DB::table('ebmr_record_distributions')->where('id', $dist->id)->update([
                        'clearance_completed_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Các công đoạn NỐI TRANG cùng nhóm (gộp chung 1 card ở productionIndex,
                    // cùng chốt started_at khi bấm "Bắt đầu sản xuất" — xem
                    // EbmrExecutionController::startProduction) cũng phải mở khoá ghi chép
                    // đồng thời, nếu không công đoạn còn lại sẽ kẹt mãi ở "Đang dọn quang".
                    $groupSectionIds = [$dist->section_id];
                    if (!empty($dist->group_key)) {
                        DB::table('ebmr_record_distributions')
                            ->where('record_id', $dist->record_id)
                            ->where('group_key', $dist->group_key)
                            ->where('id', '!=', $dist->id)
                            ->update([
                                'clearance_completed_at' => now(),
                                'updated_at' => now(),
                            ]);
                        $groupSectionIds = DB::table('ebmr_record_distributions')
                            ->where('record_id', $dist->record_id)
                            ->where('group_key', $dist->group_key)
                            ->pluck('section_id')
                            ->all();
                    }

                    $redirectUrl = route('pages.ebmr.execute', $dist->record_id)
                        . '?section=' . urlencode(implode(',', $groupSectionIds)) . '&dist=' . $dist->id;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Hoàn thành dọn quang phòng & thiết bị!',
                'redirect_url' => $redirectUrl,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    /**
     * GET /clearance-process/room-campaign/{campaign_id}/equip-status
     * Poll trạng thái các campaign dọn quang thiết bị cùng phòng — dùng để cập nhật
     * badge realtime bên sidebar campaign_execute.blade.php mà không cần tải lại trang.
     */
    public function getEquipStatus($campaign_id)
    {
        $data = ClearanceEquipCampaign::where('room_campaign_id', $campaign_id)
            ->get(['id', 'status'])
            ->values();

        return response()->json(['success' => true, 'data' => $data]);
    }
}

