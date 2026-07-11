<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CampaignStepEditHistory;

/**
 * Dùng chung cho 4 controller quản lý bước xác nhận Đạt/Không đạt (Vệ Sinh Phòng, Vệ
 * Sinh Thiết Bị, Dọn Quang Phòng, Dọn Quang Thiết Bị) — cả 4 có cùng nghiệp vụ "sửa lại
 * kết quả 1 bước đã xác nhận, bắt buộc ghi lý do + lịch sử" nhưng khác tên cột
 * (is_done/is_checked, result_note/notes, done_by/checked_by, done_at/checked_at) nên
 * nhận field map thay vì hard-code, tránh lặp lại logic 4 lần.
 */
trait EditsCampaignStepResults
{
    /**
     * $fields: ['done' => 'is_done'|'is_checked', 'note' => 'result_note'|'notes',
     *           'by' => 'done_by'|'checked_by', 'at' => 'done_at'|'checked_at']
     */
    protected function handleEditCampaignStep(
        Request $request,
        string $stepType,
        string $stepModelClass,
        string $campaignModelClass,
        $campaign_id,
        $step_id,
        array $fields
    ) {
        $validated = $request->validate([
            'is_passed' => 'required',
            'result_note' => 'nullable|string',
            'reason' => 'required|string|max:500',
        ]);

        $campaign = $campaignModelClass::findOrFail($campaign_id);
        if ($campaign->status === 'completed') {
            return response()->json(['success' => false, 'message' => 'Chiến dịch đã hoàn thành, không thể sửa kết quả bước.']);
        }

        $step = $stepModelClass::where('id', $step_id)->where('campaign_id', $campaign_id)->firstOrFail();
        $doneField = $fields['done'];
        if (!$step->{$doneField}) {
            return response()->json(['success' => false, 'message' => 'Bước này chưa được xác nhận lần đầu, vui lòng dùng nút Ghi nhận thông thường.']);
        }

        $userId = session('user')['userId'] ?? 1;
        $noteField = $fields['note'];
        $newIsPassed = filter_var($validated['is_passed'], FILTER_VALIDATE_BOOLEAN);
        $newNote = $validated['result_note'] ?? '';

        $newImages = null;
        if ($request->hasFile('images')) {
            $files = is_array($request->file('images')) ? $request->file('images') : [$request->file('images')];
            $newImages = [];
            foreach ($files as $file) {
                if (count($newImages) >= 5) break;
                $filename = $stepType . '_edit_' . $step->id . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('upLoadData/img/cleaning_result'), $filename);
                $newImages[] = '/upLoadData/img/cleaning_result/' . $filename;
            }
        }

        CampaignStepEditHistory::create([
            'step_type' => $stepType,
            'campaign_step_id' => $step->id,
            'old_is_passed' => $step->is_passed,
            'new_is_passed' => $newIsPassed,
            'old_note' => $step->{$noteField},
            'new_note' => $newNote,
            'old_images' => $step->attached_images,
            'new_images' => $newImages ?? $step->attached_images,
            'reason' => $validated['reason'],
            'changed_by' => $userId,
            'changed_at' => now(),
        ]);

        $update = [
            'is_passed' => $newIsPassed,
            $noteField => $newNote,
            $fields['by'] => $userId,
            $fields['at'] => now(),
        ];
        if ($newImages !== null) {
            $update['attached_images'] = $newImages;
        }
        $step->update($update);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật kết quả bước.',
            'is_passed' => $newIsPassed,
        ]);
    }

    protected function handleGetCampaignStepHistory(string $stepType, $step_id)
    {
        $history = CampaignStepEditHistory::where('step_type', $stepType)
            ->where('campaign_step_id', $step_id)
            ->orderByDesc('changed_at')
            ->get();

        $userIds = $history->pluck('changed_by')->filter()->unique();
        $names = DB::table('user_management')->whereIn('id', $userIds)->pluck('fullName', 'id');

        $history = $history->map(function ($h) use ($names) {
            $h->changed_by_name = $names[$h->changed_by] ?? 'N/A';
            return $h;
        });

        return response()->json(['success' => true, 'history' => $history->values()]);
    }
}
