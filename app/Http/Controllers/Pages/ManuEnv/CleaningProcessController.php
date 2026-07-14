<?php

namespace App\Http\Controllers\Pages\ManuEnv;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CleaningRoomProcess;
use App\Models\CleaningEquipProcess;
use App\Models\CleaningRoomProcessList;
use App\Models\CleaningEquipProcessList;
use App\Models\CleaningRoomCampaign;
use App\Models\CleaningRoomCampaignStep;
use App\Models\CleaningEquipCampaign;
use App\Models\CleaningEquipCampaignStep;
use App\Services\ApprovalWorkflowNotifier;
use App\Traits\EditsCampaignStepResults;
use Carbon\Carbon;

class CleaningProcessController extends Controller
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
            $processesList = CleaningRoomProcessList::where('room_id', $id)->orderBy('version', 'desc')->get();
        } elseif ($type === 'equipment') {
            $entity = DB::table('instrument')->where('id', $id)->first();
            if (!$entity) abort(404);
            $entityName = $entity->name;
            $entityCode = $entity->code;
            $processesList = CleaningEquipProcessList::where('equipment_id', $id)->orderBy('version', 'desc')->get();
        } else {
            abort(400, 'Invalid type');
        }

        foreach($processesList as $p) {
            $p->current_workflow_step = null;
            if ($p->status === 'submitted') {
                $pendingWf = DB::table('cleaning_process_workflows')
                    ->where('process_list_id', $p->id)
                    ->where('type', $type)
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

        return view('pages.manu_env.cleaning_process.list', compact('type', 'id', 'entityName', 'entityCode', 'processesList', 'users'));
    }

    public function createList(Request $request, $type, $id)
    {
        $request->validate([
            'process_code' => 'required|string',
            'process_name' => 'required|string',
            'cleaning_type' => 'nullable|integer|in:1,2,3',
        ]);

        $userId = session('user')['userId'] ?? 1;

        if ($type === 'room') {
            $list = CleaningRoomProcessList::create([
                'room_id' => $id,
                'process_code' => $request->process_code,
                'process_name' => $request->process_name,
                'version' => 1,
                'status' => 'draft',
                'created_by' => $userId,
                'cleaning_type' => $request->cleaning_type ?? 1
            ]);
        } else {
            $list = CleaningEquipProcessList::create([
                'equipment_id' => $id,
                'process_code' => $request->process_code,
                'process_name' => $request->process_name,
                'version' => 1,
                'status' => 'draft',
                'created_by' => $userId,
                'cleaning_type' => $request->cleaning_type ?? 1
            ]);
        }

        return response()->json(['success' => true, 'list_id' => $list->id]);
    }

    public function upVersion(Request $request, $type, $list_id)
    {
        $userId = session('user')['userId'] ?? 1;

        if ($type === 'room') {
            $oldList = CleaningRoomProcessList::findOrFail($list_id);
            if ($oldList->status !== 'active' && $oldList->status !== 'approved') {
                return response()->json(['success' => false, 'message' => 'Chỉ có thể lên ấn bản từ bản đã duyệt/hiện hành']);
            }
            $newList = CleaningRoomProcessList::create([
                'room_id' => $oldList->room_id,
                'process_code' => $oldList->process_code,
                'process_name' => $oldList->process_name,
                'version' => $oldList->version + 1,
                'status' => 'draft',
                'created_by' => $userId,
                'cleaning_type' => $oldList->cleaning_type
            ]);
            $steps = CleaningRoomProcess::where('process_list_id', $oldList->id)->get();
            foreach($steps as $s) {
                CleaningRoomProcess::create([
                    'process_list_id' => $newList->id,
                    'step' => $s->step,
                    'content' => $s->content,
                    'standard' => $s->standard
                ]);
            }
        } else {
            $oldList = CleaningEquipProcessList::findOrFail($list_id);
            if ($oldList->status !== 'active' && $oldList->status !== 'approved') {
                return response()->json(['success' => false, 'message' => 'Chỉ có thể lên ấn bản từ bản đã duyệt/hiện hành']);
            }
            $newList = CleaningEquipProcessList::create([
                'equipment_id' => $oldList->equipment_id,
                'process_code' => $oldList->process_code,
                'process_name' => $oldList->process_name,
                'version' => $oldList->version + 1,
                'status' => 'draft',
                'created_by' => $userId,
                'cleaning_type' => $oldList->cleaning_type
            ]);
            $steps = CleaningEquipProcess::where('process_list_id', $oldList->id)->get();
            foreach($steps as $s) {
                CleaningEquipProcess::create([
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
            $list = CleaningRoomProcessList::findOrFail($list_id);
            $id = $list->room_id;
            $entity = DB::connection('pms')->table('room')->where('id', $id)->first();
            if (!$entity) abort(404);
            $entityName = $entity->name;
            $entityCode = $entity->code;
            $processes = CleaningRoomProcess::where('process_list_id', $list_id)->orderBy('step', 'asc')->get();
        } elseif ($type === 'equipment') {
            $list = CleaningEquipProcessList::findOrFail($list_id);
            $id = $list->equipment_id;
            $entity = DB::table('instrument')->where('id', $id)->first();
            if (!$entity) abort(404);
            $entityName = $entity->name;
            $entityCode = $entity->code;
            $processes = CleaningEquipProcess::where('process_list_id', $list_id)->orderBy('step', 'asc')->get();
        } else {
            abort(400, 'Invalid type');
        }

        foreach ($processes as $p) {
            $p->content = preg_replace('/(src|href)=["\']?(https?:\/\/[^\/]+)(\/upLoadData\/img\/cleaning_process\/)/i', '$1="$3', $p->content);
            $p->content = str_replace(['http://127.0.0.1:8001', 'http://localhost:8001'], '', $p->content);

            $p->standard = preg_replace('/(src|href)=["\']?(https?:\/\/[^\/]+)(\/upLoadData\/img\/cleaning_process\/)/i', '$1="$3', $p->standard);
            $p->standard = str_replace(['http://127.0.0.1:8001', 'http://localhost:8001'], '', $p->standard);
        }

        return view('pages.manu_env.cleaning_process.index', compact('type', 'id', 'list_id', 'entityName', 'entityCode', 'processes', 'list'));
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
                CleaningRoomProcess::where('process_list_id', $list_id)->delete();
                foreach ($processes as $p) {
                    CleaningRoomProcess::create([
                        'process_list_id' => $list_id,
                        'step' => $p['step'],
                        'content' => $p['content'],
                        'standard' => $p['standard'],
                    ]);
                }
            } elseif ($type === 'equipment') {
                CleaningEquipProcess::where('process_list_id', $list_id)->delete();
                foreach ($processes as $p) {
                    CleaningEquipProcess::create([
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
                $filename = 'cleaning_process_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                // Lưu vào thư mục public/upLoadData/img/cleaning_process
                $file->move(public_path('upLoadData/img/cleaning_process'), $filename);

                // Sinh URL trả về cho editor (chỉ lấy path relative)
                $url = '/upLoadData/img/cleaning_process/' . $filename;
                return response()->json(['url' => $url]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Lỗi tải ảnh: ' . $e->getMessage()], 500);
            }
        }
        
        return response()->json(['error' => 'Không tìm thấy file tải lên'], 400);
    }

    public function getWorkflow($type, $list_id)
    {
        $latest = DB::table('cleaning_process_workflows')->where('type', $type)->where('process_list_id', $list_id)->orderBy('id', 'desc')->first();
        if ($latest) {
            $workflows = DB::table('cleaning_process_workflows')->where('type', $type)->where('process_list_id', $list_id)->where('created_at', $latest->created_at)->get();
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
            DB::table('cleaning_process_workflows')->where('type', $type)->where('process_list_id', $list_id)->where('status', 'pending')->update(['status' => 'cancelled']);
            $insertData = [];
            if (!empty($validated['reviewers'])) {
                foreach ($validated['reviewers'] as $userId) {
                    $insertData[] = [
                        'type' => $type, 'process_list_id' => $list_id, 'role' => 'reviewer', 'user_id' => $userId, 'step_order' => 1, 'status' => 'pending',
                        'due_date' => $validated['reviewer_due_dates'][$userId] ?? null,
                        'reason' => $validated['reviewer_reasons'][$userId] ?? null,
                        'created_at' => now(), 'updated_at' => now(),
                    ];
                }
            }
            if (!empty($validated['approver'])) $insertData[] = [
                'type' => $type, 'process_list_id' => $list_id, 'role' => 'approver', 'user_id' => $validated['approver'], 'step_order' => 2, 'status' => 'pending',
                'due_date' => $validated['approver_due_date'] ?? null,
                'reason' => $validated['approver_reason'] ?? null,
                'created_at' => now(), 'updated_at' => now(),
            ];
            if (!empty($validated['authorizer'])) $insertData[] = [
                'type' => $type, 'process_list_id' => $list_id, 'role' => 'authorizer', 'user_id' => $validated['authorizer'], 'step_order' => 3, 'status' => 'pending',
                'due_date' => $validated['authorizer_due_date'] ?? null,
                'reason' => $validated['authorizer_reason'] ?? null,
                'created_at' => now(), 'updated_at' => now(),
            ];

            if (count($insertData) > 0) DB::table('cleaning_process_workflows')->insert($insertData);

            if ($type === 'room') {
                DB::table('cleaning_room_processes_list')->where('id', $list_id)->where('status', 'draft')->update(['status' => 'submitted']);
            } else {
                DB::table('cleaning_equip_processes_list')->where('id', $list_id)->where('status', 'draft')->update(['status' => 'submitted']);
            }
        });

        ApprovalWorkflowNotifier::notifyActionableStep('cleaning', (int) $list_id, $type);

        return response()->json(['success' => true, 'message' => 'Lưu luồng trình ký thành công']);
    }

    public function setEffectiveDate(Request $request, $type, $list_id)
    {
        $validated = $request->validate([
            'effective_date' => 'required|date'
        ]);
        
        $table = $type === 'room' ? 'cleaning_room_processes_list' : 'cleaning_equip_processes_list';
        
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
                    ->where('cleaning_type', $list->cleaning_type)
                    ->where('id', '!=', $list_id)
                    ->where('status', 'active')
                    ->update(['status' => 'expired', 'updated_at' => now()]);
            } else {
                DB::table($table)
                    ->where('equipment_id', $list->equipment_id)
                    ->where('cleaning_type', $list->cleaning_type)
                    ->where('id', '!=', $list_id)
                    ->where('status', 'active')
                    ->update(['status' => 'expired', 'updated_at' => now()]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Cập nhật ngày hiệu lực thành công']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CAMPAIGN METHODS – Thực hiện quy trình vệ sinh phòng
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /cleaning-process/room/{room_id}/campaign/open
     * Mở trang thực hiện vệ sinh: đổi trạng thái phòng → cleaning, tạo campaign, render trang.
     */
    public function openCampaignPage(Request $request, $room_id)
    {
        session(['title' => 'Vệ Sinh Phòng']);

        $userId   = session('user')['userId']   ?? 1;
        $fullName = session('user')['fullName'] ?? 'N/A';

        // Lấy thông tin phòng từ PMS DB
        $room = DB::connection('pms')->table('room')->where('id', $room_id)->first();
        if (!$room) abort(404, 'Không tìm thấy phòng.');

        $campaignId = $request->query('campaign_id');
        $campaign = null;
        
        if ($campaignId) {
            // View specific campaign
            $campaign = CleaningRoomCampaign::where('room_id', $room_id)->findOrFail($campaignId);
            $activeProcess = CleaningRoomProcessList::findOrFail($campaign->process_list_id);
            $processSteps = CleaningRoomProcess::where('process_list_id', $activeProcess->id)
                ->orderBy('step', 'asc')
                ->get();
        } else {
            // Logic cho việc bắt đầu campaign mới hoặc tiếp tục campaign in_progress
            $type = $request->query('type', 1);

            // Tìm quy trình theo loại: ưu tiên active → approved → submitted → draft
            $activeProcess = CleaningRoomProcessList::where('room_id', $room_id)
                ->where('cleaning_type', $type)
                ->whereIn('status', ['active', 'approved', 'submitted', 'draft'])
                ->orderByRaw("FIELD(status, 'active', 'approved', 'submitted', 'draft')")
                ->orderBy('version', 'desc')
                ->first();

            if (!$activeProcess) {
                return redirect()->route('pages.ebmr.production')
                    ->with('error', "Phòng {$room->code} chưa có quy trình vệ sinh. Vui lòng thiết kế quy trình tại Môi Trường > Vệ Sinh Phòng.");
            }

            $processSteps = CleaningRoomProcess::where('process_list_id', $activeProcess->id)
                ->orderBy('step', 'asc')
                ->get();

            if ($processSteps->isEmpty()) {
                return redirect()->route('pages.ebmr.production')
                    ->with('error', "Quy trình vệ sinh của phòng {$room->code} chưa có bước nào. Vui lòng thiết kế các bước thực hiện.");
            }

            // Kiểm tra xem có campaign nào đang thực hiện dở dang không
            $campaign = CleaningRoomCampaign::where('room_id', $room_id)
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

                // 1. Tạo campaign mới
                $campaign = CleaningRoomCampaign::create([
                    'room_id'         => $room_id,
                    'process_list_id' => $activeProcess->id,
                    'status'          => 'in_progress',
                    'started_by'      => $userId,
                    'started_at'      => now(),
                    'employee_ids'    => $employeeIds,
                ]);

                // 2. Đổi trạng thái phòng → 'cleaning'
                DB::table('room_logbooks')->insert([
                    'room_id'         => $room_id,
                    'campaign_id'     => $campaign->id,
                    'equipment_id'    => null,
                    'action_type'     => 'cleaning',
                    'start_time'      => now(),
                    'employee_ids'    => json_encode($employeeIds),
                    'previous_status' => 'dirty',
                    'current_status'  => 'cleaning',
                    'created_by'      => $userId,
                    'remarks'         => 'Bắt đầu vệ sinh bởi ' . $fullName,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);



                // 3. Tạo các bước phòng tương ứng
                foreach ($processSteps as $s) {
                    CleaningRoomCampaignStep::create([
                        'campaign_id'     => $campaign->id,
                        'process_step_id' => $s->id,
                        'step'            => $s->step,
                        'is_done'         => false,
                    ]);
                }

                // 4. BắT BUỘC: Tạo equip campaigns cho tất cả thiết bị CỐ ĐỊNH trong phòng
                $fixedEquipments = DB::table('equipment_in_room')
                    ->join('instrument', 'equipment_in_room.equipment_id', '=', 'instrument.id')
                    ->where('equipment_in_room.room_id', $room_id)
                    ->where('instrument.is_Portable_equipment', 0)
                    ->select('instrument.*')
                    ->get();

                foreach ($fixedEquipments as $equip) {
                    // Tìm quy trình vệ sinh thiết bị có active/approved
                    $equipProcess = CleaningEquipProcessList::where('equipment_id', $equip->id)
                        ->whereIn('status', ['active', 'approved', 'submitted'])
                        ->orderByRaw("FIELD(status, 'active', 'approved', 'submitted')")
                        ->orderBy('version', 'desc')
                        ->first();

                    if (!$equipProcess) continue; // Bỏ qua nếu thiết bị chưa có quy trình

                    // Kiểm tra thiết bị này có campaign đang chạy không
                    $existingEquipCampaign = CleaningEquipCampaign::where('equipment_id', $equip->id)
                        ->where('status', 'in_progress')
                        ->first();

                    if ($existingEquipCampaign) continue; // Bỏ qua nếu đã có

                    $equipSteps = CleaningEquipProcess::where('process_list_id', $equipProcess->id)
                        ->orderBy('step', 'asc')
                        ->get();

                    if ($equipSteps->isEmpty()) continue;

                    $equipCampaign = CleaningEquipCampaign::create([
                        'equipment_id'    => $equip->id,
                        'process_list_id' => $equipProcess->id,
                        'room_campaign_id'=> $campaign->id,
                        'clean_location'  => 'in_room',
                        'source_room_id'  => $room_id,
                        'status'          => 'in_progress',
                        'cleaning_type'   => $campaign->cleaning_type ?? 1,
                        'employee_ids'    => $employeeIds,
                        'started_by'      => $userId,
                        'started_at'      => now(),
                    ]);

                    foreach ($equipSteps as $es) {
                        CleaningEquipCampaignStep::create([
                            'campaign_id'     => $equipCampaign->id,
                            'process_step_id' => $es->id,
                            'step'            => $es->step,
                            'is_done'         => false,
                        ]);
                    }

                    // Ghi nhận vào room_logbooks
                    DB::table('room_logbooks')->insert([
                        'room_id'            => $room_id,
                        'campaign_id'        => $campaign->id,
                        'campaign_equip_id'  => $equipCampaign->id,
                        'equipment_id'       => $equip->id,
                        'action_type'        => 'cleaning',
                        'start_time'         => now(),
                        'employee_ids'       => json_encode($employeeIds),
                        'previous_status'    => 'dirty',
                        'current_status'     => 'cleaning',
                        'created_by'         => $userId,
                        'remarks'            => 'Bắt đầu vệ sinh thiết bị ' . $equip->code . ' bởi ' . $fullName,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);

                    // Ghi nhận vào instrument_logbooks
                    DB::table('instrument_logbooks')->insert([
                        'instrument_id'      => $equip->id,
                        'action_type'        => 'cleaning',
                        'start_time'         => now(),
                        'employee_ids'       => json_encode($employeeIds),
                        'previous_status'    => 'dirty',
                        'current_status'     => 'cleaning',
                        'created_by'         => $userId,
                        'remarks'            => 'Bắt đầu vệ sinh thiết bị ' . $equip->code . ' bởi ' . $fullName,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->route('pages.ebmr.production')
                    ->with('error', 'Lỗi khởi tạo quy trình vệ sinh: ' . $e->getMessage());
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

        // 5. Load equip campaigns liên kết với campaign phòng này
        $equipCampaigns = CleaningEquipCampaign::where('room_campaign_id', $campaign->id)
            ->with(['steps'])
            ->get()
            ->map(function ($ec) {
                $equip = DB::table('instrument')->where('id', $ec->equipment_id)->first();
                $ec->equipment_code = $equip->code ?? '';
                $ec->equipment_name = $equip->name ?? '';
                return $ec;
            });

        return view('pages.manu_env.cleaning_process.campaign_execute', [
            'room'           => $room,
            'campaign'       => $campaign,
            'campaignSteps'  => $campaignSteps,
            'processList'    => $activeProcess,
            'equipCampaigns' => $equipCampaigns,
        ]);
    }

    /**
     * GET /cleaning-process/room/{room_id}/campaign/print
     */
    public function printCampaign(Request $request, $room_id)
    {
        $room = DB::connection('pms')->table('room')->where('id', $room_id)->first();
        if (!$room) abort(404, 'Không tìm thấy phòng.');

        $campaignId = $request->query('campaign_id');
        if (!$campaignId) abort(404, 'Không tìm thấy chiến dịch.');

        $campaign = \App\Models\CleaningRoomCampaign::where('room_id', $room_id)->findOrFail($campaignId);
        $activeProcess = \App\Models\CleaningRoomProcessList::findOrFail($campaign->process_list_id);
        $processSteps = \App\Models\CleaningRoomProcess::where('process_list_id', $activeProcess->id)->orderBy('step', 'asc')->get();

        $campaign->load('steps.doneByUser');
        $campaignSteps = $campaign->steps->map(function ($step) use ($processSteps) {
            $source = $processSteps->firstWhere('id', $step->process_step_id);
            $content = $source ? $source->content : '';
            $content = str_replace('http://127.0.0.1:8001', '', $content);
            $content = str_replace('http://localhost:8001', '', $content);
            $step->content  = $content;
            $step->standard = $source ? $source->standard : '';
            return $step;
        });

        return view('pages.manu_env.cleaning_process.room_campaign_print', [
            'room'           => $room,
            'campaign'       => $campaign,
            'campaignSteps'  => $campaignSteps,
            'processList'    => $activeProcess,
        ]);
    }

    /**
     * POST /cleaning-process/room/{room_id}/campaign/start
     * Tạo một lần thực hiện vệ sinh mới cho phòng dựa trên quy trình active hiện tại.
     */
    public function startCampaign(Request $request, $room_id)
    {
        $userId = session('user')['userId'] ?? 1;

        $type = $request->input('type', 1);

        // Tìm quy trình active cho phòng và theo loại
        $activeProcess = CleaningRoomProcessList::where('room_id', $room_id)
            ->where('cleaning_type', $type)
            ->where('status', 'active')
            ->orderBy('version', 'desc')
            ->first();

        if (!$activeProcess) {
            return response()->json([
                'success' => false,
                'message' => 'Phòng này chưa có quy trình vệ sinh đang hiện hành (active). Vui lòng thiết lập quy trình trước.'
            ]);
        }

        $steps = CleaningRoomProcess::where('process_list_id', $activeProcess->id)
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
            $campaign = CleaningRoomCampaign::create([
                'room_id'        => $room_id,
                'process_list_id'=> $activeProcess->id,
                'status'         => 'in_progress',
                'started_by'     => $userId,
                'started_at'     => now(),
            ]);

            // Tạo các bước tương ứng
            foreach ($steps as $s) {
                CleaningRoomCampaignStep::create([
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
     * GET /cleaning-process/campaign/{campaign_id}
     * Trả về thông tin campaign + danh sách bước kèm nội dung quy trình.
     */
    public function getCampaign($campaign_id)
    {
        $campaign = CleaningRoomCampaign::with('steps')->findOrFail($campaign_id);

        $processList = CleaningRoomProcessList::find($campaign->process_list_id);
        $room = DB::connection('pms')->table('room')->where('id', $campaign->room_id)->first();

        // Gắn nội dung HTML cho từng bước từ bảng cleaning_room_processes
        $stepsWithContent = $campaign->steps->map(function ($step) {
            $source = CleaningRoomProcess::find($step->process_step_id);
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
     * Lấy trạng thái vệ sinh thiết bị thuộc chiến dịch phòng
     */
    public function getEquipStatuses($campaign_id)
    {
        $equips = CleaningEquipCampaign::where('room_campaign_id', $campaign_id)->get(['id', 'equipment_id', 'status'])
            ->map(function($ec) {
                $equip = DB::table('instrument')->where('id', $ec->equipment_id)->first();
                $ec->equipment_code = $equip->code ?? '';
                return $ec;
            });
        return response()->json([
            'success' => true,
            'data'    => $equips
        ]);
    }

    /**
     * POST /cleaning-process/campaign/{campaign_id}/step/{step_id}/complete
     * Ghi nhận hoàn thành một bước vệ sinh.
     */
    public function completeStep(Request $request, $campaign_id, $step_id)
    {
        $userId   = session('user')['userId']   ?? 1;
        $fullName = session('user')['fullName'] ?? 'N/A';

        $campaignStep = CleaningRoomCampaignStep::where('id', $step_id)
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
        $campaign   = CleaningRoomCampaign::with('steps')->findOrFail($campaign_id);
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
     * POST /cleaning-process/campaign/{campaign_id}/step/{step_id}/edit
     * Sửa lại kết quả 1 bước đã xác nhận (vd lỡ bấm "Không đạt" khi thực tế "Đạt") —
     * bắt buộc nhập lý do, luôn ghi lịch sử trước/sau vào campaign_step_edit_history.
     */
    public function editStep(Request $request, $campaign_id, $step_id)
    {
        return $this->handleEditCampaignStep(
            $request,
            'cleaning_room',
            CleaningRoomCampaignStep::class,
            CleaningRoomCampaign::class,
            $campaign_id,
            $step_id,
            ['done' => 'is_done', 'note' => 'result_note', 'by' => 'done_by', 'at' => 'done_at']
        );
    }

    /**
     * GET /cleaning-process/campaign/step/{step_id}/history
     */
    public function getStepHistory($step_id)
    {
        return $this->handleGetCampaignStepHistory('cleaning_room', $step_id);
    }

    /**
     * POST /cleaning-process/campaign/{campaign_id}/complete
     * Hoàn thành toàn bộ campaign → cập nhật trạng thái phòng thành 'cleaned'.
     */
    public function completeCampaign(Request $request, $campaign_id)
    {
        $userId   = session('user')['userId']   ?? 1;
        $fullName = session('user')['fullName'] ?? 'N/A';

        $campaign = CleaningRoomCampaign::with('steps')->findOrFail($campaign_id);

        // Lấy loại quy trình để tính hạn vệ sinh
        $processList = CleaningRoomProcessList::find($campaign->process_list_id);
        $cleaningType = $processList ? $processList->cleaning_type : 1;

        // Nếu có bất kỳ bước nào KHÔNG ĐẠT thì quy trình kết thúc nhưng phòng
        // vẫn ở trạng thái "cần vệ sinh" (dirty) chứ không được coi là đã sạch.
        $hasFailedStep = $campaign->steps->contains(fn($s) => $s->is_passed === false);

        $cleanExpiryDate = null;
        $cleanLevel = null;
        if (!$hasFailedStep) {
            if ($cleaningType == 1) {
                $cleanExpiryDate = now()->addDays(3);
                $cleanLevel = 'Vệ Sinh Cấp I';
            } elseif ($cleaningType == 2) {
                $cleanExpiryDate = now()->addDays(7);
                $cleanLevel = 'Vệ Sinh Cấp II';
            } elseif ($cleaningType == 3) {
                $cleanExpiryDate = now()->addHours(24);
                $cleanLevel = 'Vệ Sinh Lại';
            }
        }

        $resultStatus = $hasFailedStep ? 'dirty' : 'cleaned';
        $remarks      = $hasFailedStep
            ? 'Kết thúc vệ sinh phòng (có bước KHÔNG ĐẠT) bởi ' . $fullName
            : 'Hoàn thành vệ sinh bởi ' . $fullName;

        // Kiểm tra tất cả bước đã hoàn thành chưa
        $undoneSteps = $campaign->steps->where('is_done', false)->count();
        if ($undoneSteps > 0) {
            return response()->json([
                'success' => false,
                'message' => "Còn {$undoneSteps} bước chưa hoàn thành. Vui lòng thực hiện hết tất cả các bước trước khi hoàn thành."
            ]);
        }

        DB::beginTransaction();
        try {
            // Đánh dấu campaign hoàn thành
            $campaign->update([
                'status'       => 'completed',
                'completed_by' => $userId,
                'completed_at' => now(),
            ]);

            // Cập nhật room_logbooks: ghi nhận trạng thái 'cleaned' hoặc 'dirty' nếu có bước KHÔNG ĐẠT
            DB::table('room_logbooks')->insert([
                'room_id'           => $campaign->room_id,
                'campaign_id'       => $campaign->id,
                'equipment_id'      => null,
                'action_type'       => 'cleaning',
                'start_time'        => $campaign->started_at ?? now(),
                'end_time'          => now(),
                'employee_ids'      => json_encode([$userId]),
                'previous_status'   => 'cleaning',
                'current_status'    => $resultStatus,
                'clean_level'       => $cleanLevel,
                'clean_expiry_date' => $cleanExpiryDate,
                'created_by'        => $userId,
                'remarks'           => $remarks,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            DB::commit();

            return response()->json([
                'success'    => true,
                'has_failed' => $hasFailedStep,
                'message'    => $hasFailedStep
                    ? 'Quy trình vệ sinh đã kết thúc nhưng có bước KHÔNG ĐẠT — phòng vẫn ở trạng thái "Cần vệ sinh". Vui lòng bắt đầu lại quy trình vệ sinh.'
                    : 'Hoàn thành quy trình vệ sinh! Trạng thái phòng đã được cập nhật thành "Đã vệ sinh".'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }
}

