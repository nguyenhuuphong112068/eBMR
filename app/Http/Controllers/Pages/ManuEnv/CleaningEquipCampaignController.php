<?php

namespace App\Http\Controllers\Pages\ManuEnv;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CleaningEquipProcess;
use App\Models\CleaningEquipProcessList;
use App\Models\CleaningEquipCampaign;
use App\Models\CleaningEquipCampaignStep;
use App\Models\CleaningRoomCampaign;
use App\Traits\EditsCampaignStepResults;

class CleaningEquipCampaignController extends Controller
{
    use EditsCampaignStepResults;

    /**
     * GET /cleaning-process/equip/{equip_id}/campaign/open
     * Mở trang thực hiện vệ sinh thiết bị.
     */
    public function openCampaignPage(Request $request, $equip_id)
    {
        session(['title' => 'Vệ Sinh Thiết Bị']);

        $userId   = session('user')['userId']   ?? 1;
        $fullName = session('user')['fullName'] ?? 'N/A';

        $equip = DB::table('instrument')->where('id', $equip_id)->first();
        if (!$equip) abort(404, 'Không tìm thấy thiết bị.');

        $campaignId    = $request->query('campaign_id');
        $roomCampaignId = $request->query('room_campaign_id');
        $campaign = null;

        if ($campaignId) {
            // Xem lại campaign cụ thể
            $campaign = CleaningEquipCampaign::where('equipment_id', $equip_id)->findOrFail($campaignId);
            $activeProcess = CleaningEquipProcessList::findOrFail($campaign->process_list_id);
            $processSteps = CleaningEquipProcess::where('process_list_id', $activeProcess->id)->orderBy('step')->get();
        } else {
            // Tìm campaign in_progress cho thiết bị này
            $campaign = CleaningEquipCampaign::where('equipment_id', $equip_id)
                ->where('status', 'in_progress')
                ->when($roomCampaignId, fn($q) => $q->where('room_campaign_id', $roomCampaignId))
                ->first();

            if ($campaign) {
                $activeProcess = CleaningEquipProcessList::findOrFail($campaign->process_list_id);
                $processSteps  = CleaningEquipProcess::where('process_list_id', $activeProcess->id)->orderBy('step')->get();
            } else {
                // Tạo mới: tìm quy trình active
                $type = $request->query('type', 1);
                $activeProcess = CleaningEquipProcessList::where('equipment_id', $equip_id)
                    ->where('cleaning_type', $type)
                    ->whereIn('status', ['active', 'approved', 'submitted'])
                    ->orderByRaw("FIELD(status, 'active', 'approved', 'submitted')")
                    ->orderBy('version', 'desc')
                    ->first();

                if (!$activeProcess) {
                    return redirect()->back()->with('error', "Thiết bị {$equip->code} chưa có quy trình vệ sinh.");
                }

                $processSteps = CleaningEquipProcess::where('process_list_id', $activeProcess->id)->orderBy('step')->get();

                if ($processSteps->isEmpty()) {
                    return redirect()->back()->with('error', "Quy trình vệ sinh thiết bị {$equip->code} chưa có bước nào.");
                }

                // Tạo campaign mới
                DB::beginTransaction();
                try {
                    $campaign = CleaningEquipCampaign::create([
                        'equipment_id'    => $equip_id,
                        'process_list_id' => $activeProcess->id,
                        'room_campaign_id'=> $roomCampaignId,
                        'clean_location'  => $roomCampaignId ? 'in_room' : 'clearing_room',
                        'status'          => 'in_progress',
                        'cleaning_type'   => $activeProcess->cleaning_type ?? 1,
                        'employee_ids'    => [$userId],
                        'started_by'      => $userId,
                        'started_at'      => now(),
                    ]);

                    foreach ($processSteps as $s) {
                        CleaningEquipCampaignStep::create([
                            'campaign_id'     => $campaign->id,
                            'process_step_id' => $s->id,
                            'step'            => $s->step,
                            'is_done'         => false,
                        ]);
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'Lỗi khởi tạo: ' . $e->getMessage());
                }
            }
        }

        // Load steps với thông tin người xác nhận
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

        // Nếu có room_campaign, load thêm thông tin
        $roomCampaign = $campaign->room_campaign_id
            ? CleaningRoomCampaign::find($campaign->room_campaign_id)
            : null;

        $sourceRoom = $campaign->source_room_id
            ? DB::connection('pms')->table('room')->where('id', $campaign->source_room_id)->first()
            : null;

        return view('pages.manu_env.cleaning_process.equip_campaign_execute', [
            'equip'          => $equip,
            'campaign'       => $campaign,
            'campaignSteps'  => $campaignSteps,
            'processList'    => $activeProcess,
            'roomCampaign'   => $roomCampaign,
            'sourceRoom'     => $sourceRoom,
        ]);
    }

    /**
     * POST /cleaning-process/equip-campaign/{campaign_id}/step/{step_id}/complete
     */
    public function completeStep(Request $request, $campaign_id, $step_id)
    {
        $userId   = session('user')['userId']   ?? 1;
        $fullName = session('user')['fullName'] ?? 'N/A';

        $campaignStep = CleaningEquipCampaignStep::where('id', $step_id)
            ->where('campaign_id', $campaign_id)
            ->firstOrFail();

        $imagePaths = [];
        if ($request->hasFile('images')) {
            $files = is_array($request->file('images')) ? $request->file('images') : [$request->file('images')];
            foreach ($files as $file) {
                if (count($imagePaths) >= 5) break;
                $filename = 'cleaning_equip_step_' . $campaignStep->id . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
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

        $campaign   = CleaningEquipCampaign::with('steps')->findOrFail($campaign_id);
        $doneSteps  = $campaign->steps->where('is_done', true)->count();
        $totalSteps = $campaign->steps->count();

        return response()->json([
            'success'     => true,
            'message'     => 'Ghi nhận bước ' . $campaignStep->step . ' hoàn thành!',
            'done_steps'  => $doneSteps,
            'total_steps' => $totalSteps,
            'done_by'     => $fullName,
        ]);
    }

    /**
     * POST /cleaning-process/equip-campaign/{campaign_id}/step/{step_id}/edit
     */
    public function editStep(Request $request, $campaign_id, $step_id)
    {
        return $this->handleEditCampaignStep(
            $request,
            'cleaning_equip',
            CleaningEquipCampaignStep::class,
            CleaningEquipCampaign::class,
            $campaign_id,
            $step_id,
            ['done' => 'is_done', 'note' => 'result_note', 'by' => 'done_by', 'at' => 'done_at']
        );
    }

    /**
     * GET /cleaning-process/equip-campaign/step/{step_id}/history
     */
    public function getStepHistory($step_id)
    {
        return $this->handleGetCampaignStepHistory('cleaning_equip', $step_id);
    }

    /**
     * POST /cleaning-process/equip-campaign/{campaign_id}/complete
     */
    public function completeCampaign(Request $request, $campaign_id)
    {
        $userId   = session('user')['userId']   ?? 1;
        $fullName = session('user')['fullName'] ?? 'N/A';

        $campaign = CleaningEquipCampaign::with('steps')->findOrFail($campaign_id);

        $undoneSteps = $campaign->steps->where('is_done', false)->count();
        if ($undoneSteps > 0) {
            return response()->json([
                'success' => false,
                'message' => "Còn {$undoneSteps} bước chưa hoàn thành.",
            ]);
        }

        $processList  = CleaningEquipProcessList::find($campaign->process_list_id);
        $cleaningType = $campaign->cleaning_type ?? ($processList?->cleaning_type ?? 1);

        $cleanLevel      = match((int)$cleaningType) { 2 => 'Vệ Sinh Cấp II', 3 => 'Vệ Sinh Lại', default => 'Vệ Sinh Cấp I' };
        $cleanExpiryDate = match((int)$cleaningType) { 2 => now()->addDays(7), 3 => now()->addHours(24), default => now()->addDays(3) };

        DB::beginTransaction();
        try {
            $campaign->update([
                'status'       => 'completed',
                'completed_by' => $userId,
                'completed_at' => now(),
            ]);

            // Ghi nhận vào room_logbooks
            $roomIdToLog = $campaign->source_room_id ?? $campaign->clearing_room_id;
            
            if ($roomIdToLog) {
                DB::table('room_logbooks')->insert([
                    'room_id'            => $roomIdToLog,
                    'campaign_id'        => $campaign->room_campaign_id,
                    'campaign_equip_id'  => $campaign->id,
                    'equipment_id'       => $campaign->equipment_id,
                    'action_type'        => 'cleaning',
                    'start_time'         => $campaign->started_at ?? now(),
                    'end_time'           => now(),
                    'employee_ids'       => json_encode([$userId]),
                    'previous_status'    => 'cleaning',
                    'current_status'     => 'cleaned',
                    'clean_level'        => $cleanLevel,
                    'clean_expiry_date'  => $cleanExpiryDate,
                    'created_by'         => $userId,
                    'remarks'            => 'Hoàn thành vệ sinh thiết bị bởi ' . $fullName,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }

            // Ghi nhận vào instrument_logbooks
            DB::table('instrument_logbooks')->insert([
                'instrument_id'      => $campaign->equipment_id,
                'campaign_id'        => $campaign->id,
                'action_type'        => 'cleaning',
                'start_time'         => $campaign->started_at ?? now(),
                'end_time'           => now(),
                'employee_ids'       => json_encode([$userId]),
                'previous_status'    => 'cleaning',
                'current_status'     => 'cleaned',
                'clean_level'        => $cleanLevel,
                'clean_expiry_date'  => $cleanExpiryDate,
                'created_by'         => $userId,
                'remarks'            => 'Hoàn thành vệ sinh thiết bị bởi ' . $fullName,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Hoàn thành vệ sinh thiết bị!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    /**
     * GET /cleaning-process/equip-campaign/{campaign_id}
     */
    public function getCampaign($campaign_id)
    {
        $campaign = CleaningEquipCampaign::with('steps')->findOrFail($campaign_id);

        return response()->json([
            'success'    => true,
            'campaign'   => $campaign,
            'steps'      => $campaign->steps->values(),
        ]);
    }
    /**
     * GET /cleaning-process/equip/{equip_id}/campaign/print
     */
    public function printCampaign(Request $request, $equip_id)
    {
        $equip = DB::table('instrument')->where('id', $equip_id)->first();
        if (!$equip) abort(404, 'Không tìm thấy thiết bị.');

        $campaignId = $request->query('campaign_id');
        if (!$campaignId) abort(404, 'Không tìm thấy chiến dịch.');

        $campaign = CleaningEquipCampaign::where('equipment_id', $equip_id)->findOrFail($campaignId);
        $activeProcess = CleaningEquipProcessList::findOrFail($campaign->process_list_id);
        $processSteps = CleaningEquipProcess::where('process_list_id', $activeProcess->id)->orderBy('step')->get();

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

        return view('pages.manu_env.cleaning_process.equip_campaign_print', [
            'equip'          => $equip,
            'campaign'       => $campaign,
            'campaignSteps'  => $campaignSteps,
            'processList'    => $activeProcess,
        ]);
    }
}
