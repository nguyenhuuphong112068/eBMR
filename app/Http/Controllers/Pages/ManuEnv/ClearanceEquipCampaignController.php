<?php

namespace App\Http\Controllers\Pages\ManuEnv;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ClearanceEquipProcess;
use App\Models\ClearanceEquipProcessList;
use App\Models\ClearanceEquipCampaign;
use App\Models\ClearanceEquipCampaignStep;
use App\Models\ClearanceRoomCampaign;
use App\Traits\EditsCampaignStepResults;

/**
 * Thực hiện dọn quang từng thiết bị. Campaign luôn được ClearanceProcessController
 * (phòng) hoặc EbmrExecutionController::startProduction tạo trước (kèm room_campaign_id) —
 * controller này chỉ mở/ghi nhận tiến độ 1 campaign đã tồn tại, không tự khởi tạo mới
 * (khác CleaningEquipCampaignController vốn có nhánh tự tạo cho luồng vệ sinh độc lập).
 */
class ClearanceEquipCampaignController extends Controller
{
    use EditsCampaignStepResults;

    /**
     * GET /clearance-process/equip/{equip_id}/campaign/open?campaign_id=
     */
    public function openCampaignPage(Request $request, $equip_id)
    {
        session(['title' => 'Dọn Quang Thiết Bị']);

        $equip = DB::table('instrument')->where('id', $equip_id)->first();
        if (!$equip) abort(404, 'Không tìm thấy thiết bị.');

        $campaignId = $request->query('campaign_id');
        if (!$campaignId) {
            return redirect()->route('pages.ebmr.production')->with('error', 'Thiếu thông tin chiến dịch dọn quang.');
        }

        $campaign = ClearanceEquipCampaign::where('equipment_id', $equip_id)->findOrFail($campaignId);
        $activeProcess = ClearanceEquipProcessList::findOrFail($campaign->process_list_id);
        $processSteps = ClearanceEquipProcess::where('process_list_id', $activeProcess->id)->orderBy('step')->get();

        $campaign->load('steps.doneByUser');
        $campaignSteps = $campaign->steps->map(function ($step) use ($processSteps) {
            $source = $processSteps->firstWhere('id', $step->process_step_id);
            $content = $source ? $source->content : '';
            $content = str_replace(['http://127.0.0.1:8001', 'http://localhost:8001'], '', $content);
            $step->content = $content;
            $step->standard = $source ? $source->standard : '';
            return $step;
        });

        $roomCampaign = $campaign->room_campaign_id ? ClearanceRoomCampaign::find($campaign->room_campaign_id) : null;
        $room = $roomCampaign ? DB::connection('pms')->table('room')->where('id', $roomCampaign->room_id)->first() : null;

        return view('pages.manu_env.clearance_process.equip_campaign_execute', [
            'equip' => $equip,
            'campaign' => $campaign,
            'campaignSteps' => $campaignSteps,
            'processList' => $activeProcess,
            'roomCampaign' => $roomCampaign,
            'room' => $room,
        ]);
    }

    /**
     * POST /clearance-process/equip-campaign/{campaign_id}/step/{step_id}/complete
     */
    public function completeStep(Request $request, $campaign_id, $step_id)
    {
        $userId = session('user')['userId'] ?? 1;
        $fullName = session('user')['fullName'] ?? 'N/A';

        $campaignStep = ClearanceEquipCampaignStep::where('id', $step_id)
            ->where('campaign_id', $campaign_id)
            ->firstOrFail();

        $imagePaths = [];
        if ($request->hasFile('images')) {
            $files = is_array($request->file('images')) ? $request->file('images') : [$request->file('images')];
            foreach ($files as $file) {
                if (count($imagePaths) >= 5) break;
                $filename = 'clearance_equip_step_' . $campaignStep->id . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('upLoadData/img/cleaning_result'), $filename);
                $imagePaths[] = '/upLoadData/img/cleaning_result/' . $filename;
            }
        }

        $campaignStep->update([
            'is_checked' => true,
            'is_passed' => $request->has('is_passed') ? filter_var($request->input('is_passed'), FILTER_VALIDATE_BOOLEAN) : null,
            'notes' => $request->input('result_note', ''),
            'attached_images' => empty($imagePaths) ? null : $imagePaths,
            'checked_by' => $userId,
            'checked_at' => now(),
        ]);

        $campaign = ClearanceEquipCampaign::with('steps')->findOrFail($campaign_id);
        $doneSteps = $campaign->steps->where('is_checked', true)->count();
        $totalSteps = $campaign->steps->count();

        return response()->json([
            'success' => true,
            'message' => 'Ghi nhận bước ' . $campaignStep->step . ' hoàn thành!',
            'done_steps' => $doneSteps,
            'total_steps' => $totalSteps,
            'done_by' => $fullName,
        ]);
    }

    /**
     * POST /clearance-process/equip-campaign/{campaign_id}/step/{step_id}/edit
     */
    public function editStep(Request $request, $campaign_id, $step_id)
    {
        return $this->handleEditCampaignStep(
            $request,
            'clearance_equip',
            ClearanceEquipCampaignStep::class,
            ClearanceEquipCampaign::class,
            $campaign_id,
            $step_id,
            ['done' => 'is_checked', 'note' => 'notes', 'by' => 'checked_by', 'at' => 'checked_at']
        );
    }

    /**
     * GET /clearance-process/equip-campaign/step/{step_id}/history
     */
    public function getStepHistory($step_id)
    {
        return $this->handleGetCampaignStepHistory('clearance_equip', $step_id);
    }

    /**
     * POST /clearance-process/equip-campaign/{campaign_id}/complete
     */
    public function completeCampaign(Request $request, $campaign_id)
    {
        $userId = session('user')['userId'] ?? 1;

        $campaign = ClearanceEquipCampaign::with('steps')->findOrFail($campaign_id);

        $undoneSteps = $campaign->steps->where('is_checked', false)->count();
        if ($undoneSteps > 0) {
            return response()->json([
                'success' => false,
                'message' => "Còn {$undoneSteps} bước chưa hoàn thành.",
            ]);
        }

        $campaign->update([
            'status' => 'completed',
            'completed_by' => $userId,
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Hoàn thành dọn quang thiết bị!',
        ]);
    }

    /**
     * GET /clearance-process/equip-campaign/{campaign_id}
     */
    public function getCampaign($campaign_id)
    {
        $campaign = ClearanceEquipCampaign::with('steps')->findOrFail($campaign_id);

        return response()->json([
            'success' => true,
            'campaign' => $campaign,
            'steps' => $campaign->steps->values(),
        ]);
    }
}
