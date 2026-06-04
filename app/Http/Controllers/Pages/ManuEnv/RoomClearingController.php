<?php

namespace App\Http\Controllers\Pages\ManuEnv;

use App\Http\Controllers\Controller;
use App\Models\RoomClearing;
use App\Models\CleaningEquipCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomClearingController extends Controller
{
    public function index()
    {
        session(['title' => 'Phòng Vệ Sinh Chung']);

        $rooms = RoomClearing::withCount([
            'equipCampaigns as in_progress_count' => fn($q) => $q->where('status', 'in_progress'),
        ])->orderBy('status')->orderBy('code')->get();

        return view('pages.manu_env.room_clearing.index', compact('rooms'));
    }

    public function dashboard($id)
    {
        session(['title' => 'Dashboard Phòng VS Chung']);

        $room = RoomClearing::findOrFail($id);

        // Các campaign đang in_progress tại phòng này
        $inProgress = CleaningEquipCampaign::where('clearing_room_id', $id)
            ->where('status', 'in_progress')
            ->with('steps')
            ->get()
            ->map(fn($c) => $this->enrichCampaign($c));

        // Lịch sử các campaign đã completed
        $completed = CleaningEquipCampaign::where('clearing_room_id', $id)
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->limit(20)
            ->get()
            ->map(fn($c) => $this->enrichCampaign($c));

        return view('pages.manu_env.room_clearing.dashboard', compact('room', 'inProgress', 'completed'));
    }

    private function enrichCampaign(CleaningEquipCampaign $c): CleaningEquipCampaign
    {
        $equip = DB::table('instrument')->where('id', $c->equipment_id)->first();
        $c->equipment_code = $equip->code ?? '';
        $c->equipment_name = $equip->name ?? '';
        $doneCount = $c->steps->where('is_done', true)->count();
        $totalCount = $c->steps->count();
        $c->progress_done  = $doneCount;
        $c->progress_total = $totalCount;
        return $c;
    }

    public function store(Request $request)
    {
        $userId = session('user')['userId'] ?? 1;

        $validated = $request->validate([
            'code'        => 'required|string|max:50|unique:room_clearings,code',
            'name'        => 'required|string|max:200',
            'area'        => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        RoomClearing::create(array_merge($validated, ['created_by' => $userId]));

        return response()->json(['success' => true, 'message' => 'Tạo phòng vệ sinh chung thành công!']);
    }

    public function update(Request $request, $id)
    {
        $room = RoomClearing::findOrFail($id);

        $validated = $request->validate([
            'code'        => 'required|string|max:50|unique:room_clearings,code,' . $id,
            'name'        => 'required|string|max:200',
            'area'        => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status'      => 'nullable|in:active,inactive',
        ]);

        $room->update($validated);

        return response()->json(['success' => true, 'message' => 'Cập nhật phòng vệ sinh chung thành công!']);
    }

    /**
     * Nhận thiết bị di động vào phòng VS chung và tạo campaign mới
     */
    public function receiveEquip(Request $request, $id)
    {
        $userId = session('user')['userId'] ?? 1;
        $fullName = session('user')['fullName'] ?? 'N/A';

        $room = RoomClearing::findOrFail($id);

        $validated = $request->validate([
            'equipment_id'    => 'required|integer',
            'process_list_id' => 'required|integer',
            'cleaning_type'   => 'nullable|integer|in:1,2,3',
            'employee_ids'    => 'nullable|array',
        ]);

        $equip = DB::table('instrument')->where('id', $validated['equipment_id'])->first();
        if (!$equip) return response()->json(['success' => false, 'message' => 'Thiết bị không tồn tại.']);

        // Kiểm tra thiết bị đã có campaign đang chạy chưa
        $existing = CleaningEquipCampaign::where('equipment_id', $validated['equipment_id'])
            ->where('status', 'in_progress')
            ->first();
        if ($existing) {
            return response()->json(['success' => false, 'message' => "Thiết bị {$equip->code} đang có quy trình vệ sinh dang dở."]);
        }

        $steps = DB::table('cleaning_equip_processes')
            ->where('process_list_id', $validated['process_list_id'])
            ->orderBy('step')
            ->get();

        if ($steps->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Quy trình vệ sinh chưa có bước nào.']);
        }

        $employeeIds = $validated['employee_ids'] ?? [$userId];
        if (!in_array($userId, $employeeIds)) $employeeIds[] = $userId;

        DB::beginTransaction();
        try {
            $campaign = CleaningEquipCampaign::create([
                'equipment_id'    => $validated['equipment_id'],
                'process_list_id' => $validated['process_list_id'],
                'clean_location'  => 'clearing_room',
                'clearing_room_id'=> $id,
                'source_room_id'  => null,
                'status'          => 'in_progress',
                'cleaning_type'   => $validated['cleaning_type'] ?? 1,
                'employee_ids'    => $employeeIds,
                'started_by'      => $userId,
                'started_at'      => now(),
            ]);

            foreach ($steps as $s) {
                DB::table('cleaning_equip_campaign_steps')->insert([
                    'campaign_id'     => $campaign->id,
                    'process_step_id' => $s->id,
                    'step'            => $s->step,
                    'is_done'         => false,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            // Ghi nhận vào instrument_logbooks
            DB::table('instrument_logbooks')->insert([
                'instrument_id'      => $validated['equipment_id'],
                'campaign_id'        => $campaign->id,
                'action_type'        => 'cleaning',
                'start_time'         => now(),
                'employee_ids'       => json_encode($employeeIds),
                'previous_status'    => 'dirty',
                'current_status'     => 'cleaning',
                'created_by'         => $userId,
                'remarks'            => 'Bắt đầu vệ sinh thiết bị tại ' . $room->name . ' bởi ' . $fullName,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            DB::commit();

            return response()->json([
                'success'     => true,
                'message'     => "Đã tiếp nhận thiết bị {$equip->code} vào phòng {$room->code}!",
                'campaign_id' => $campaign->id,
                'open_url'    => route('pages.manu_env.cleaning_process.equip.campaign.open', ['equip_id' => $validated['equipment_id']]) . '?campaign_id=' . $campaign->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }
}
