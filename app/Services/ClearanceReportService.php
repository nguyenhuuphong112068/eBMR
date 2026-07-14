<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Xuất "Báo Cáo Dọn Quang" (phòng + từng thiết bị trong phòng, nếu có) của 1 phiên sản
 * xuất thành PDF và đính kèm vào phân đoạn (ebmr_record_section_attachments) — được gọi
 * tự động khi người dùng bấm "Kết thúc sản xuất" (EbmrExecutionController::endProduction),
 * song song với Báo Cáo Môi Trường Sản Xuất (ProductionEnvironmentReportService).
 *
 * Campaign dọn quang đã được tạo sẵn khi "Bắt đầu sản xuất" (startProduction): campaign
 * PHÒNG neo qua clearance_room_campaigns.distribution_id, mỗi campaign THIẾT BỊ neo qua
 * clearance_equip_campaigns.room_campaign_id. Service này chỉ đọc lại chúng, dựng PDF và
 * lưu đính kèm — không thay đổi dữ liệu nghiệp vụ dọn quang.
 *
 * Bảng bước phòng/thiết bị khác tên cột (is_done/done_by/done_at/result_note so với
 * is_checked/checked_by/checked_at/notes) nên được chuẩn hoá tại normalizeSteps() trước
 * khi truyền cho blade dùng chung (clearance_report_pdf.blade.php).
 */
class ClearanceReportService
{
    /**
     * Xuất & đính kèm PDF dọn quang cho 1 phiên sản xuất. Trả về mảng các dòng đính kèm
     * đã tạo (rỗng nếu phiên chưa có campaign dọn quang). Ném exception nếu render/lưu
     * thất bại — nơi gọi tự quyết định có cho lỗi này chặn nghiệp vụ chính hay không.
     */
    public static function attachReportsPdf(int $distributionId, ?int $userId = null, ?string $userName = null): array
    {
        $dist = DB::table('ebmr_record_distributions')
            ->join('ebmr_records', 'ebmr_record_distributions.record_id', '=', 'ebmr_records.id')
            ->join('ebmr_templates', 'ebmr_records.template_id', '=', 'ebmr_templates.id')
            ->where('ebmr_record_distributions.id', $distributionId)
            ->select(
                'ebmr_record_distributions.*',
                'ebmr_records.batch_number',
                'ebmr_templates.type as template_type',
                'ebmr_templates.caterogy_id as template_category_id'
            )
            ->first();
        if (!$dist) {
            throw new \RuntimeException("Không tìm thấy phiên sản xuất #{$distributionId}.");
        }

        // Campaign dọn quang PHÒNG của đúng phiên này (ưu tiên bản đã hoàn thành mới nhất).
        $roomCampaign = DB::table('clearance_room_campaigns')
            ->where('distribution_id', $distributionId)
            ->orderByRaw("CASE WHEN status = 'completed' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->first();
        if (!$roomCampaign) {
            return [];
        }

        $productName = EbmrProductResolver::resolveName($dist->template_type, $dist->template_category_id);
        $generatedAt = now();
        $attachments = [];

        // ── 1. Báo cáo dọn quang PHÒNG ──────────────────────────────────────────
        $room = DB::connection('pms')->table('room')->where('id', $roomCampaign->room_id)->first();
        $roomProcess = DB::table('clearance_room_processes_list')->where('id', $roomCampaign->process_list_id)->first();
        $roomSteps = DB::table('clearance_room_campaign_steps as cs')
            ->leftJoin('clearance_room_processes as p', 'cs.process_step_id', '=', 'p.id')
            ->where('cs.campaign_id', $roomCampaign->id)
            ->orderBy('cs.step')
            ->select('cs.*', 'p.content', 'p.standard')
            ->get();

        $attachments[] = self::renderAndAttach($dist, $userId, $userName, $generatedAt, [
            'title' => 'BÁO CÁO DỌN QUANG PHÒNG',
            'entityTypeLabel' => 'Phòng',
            'entityCode' => $room->code ?? ('#' . $roomCampaign->room_id),
            'entityName' => $room->name ?? '',
            'processCode' => $roomProcess->process_code ?? '',
            'processName' => $roomProcess->process_name ?? '',
            'version' => $roomProcess->version ?? 1,
            'productName' => $productName,
            'batchNumber' => $dist->batch_number,
            'sectionLabel' => $dist->section_label ?: $dist->section_id,
            'startedAt' => $roomCampaign->started_at ? \Carbon\Carbon::parse($roomCampaign->started_at) : null,
            'completedAt' => $roomCampaign->completed_at ? \Carbon\Carbon::parse($roomCampaign->completed_at) : null,
            'startedByName' => self::userName($roomCampaign->started_by),
            'completedByName' => self::userName($roomCampaign->completed_by),
            'steps' => self::normalizeSteps($roomSteps, ['done' => 'is_done', 'by' => 'done_by', 'at' => 'done_at', 'note' => 'result_note']),
            'generatedAt' => $generatedAt,
        ], 'clearance_room', 'bao-cao-don-quang-phong', $room->code ?? 'phong');

        // ── 2. Báo cáo dọn quang từng THIẾT BỊ trong phòng (nếu có) ──────────────
        $equipCampaigns = DB::table('clearance_equip_campaigns')
            ->where('room_campaign_id', $roomCampaign->id)
            ->orderBy('id')
            ->get();

        foreach ($equipCampaigns as $ec) {
            $equip = DB::table('instrument')->where('id', $ec->equipment_id)->first();
            $equipProcess = DB::table('clearance_equip_processes_list')->where('id', $ec->process_list_id)->first();
            $equipSteps = DB::table('clearance_equip_campaign_steps as cs')
                ->leftJoin('clearance_equip_processes as p', 'cs.process_step_id', '=', 'p.id')
                ->where('cs.campaign_id', $ec->id)
                ->orderBy('cs.step')
                ->select('cs.*', 'p.content', 'p.standard')
                ->get();

            $attachments[] = self::renderAndAttach($dist, $userId, $userName, $generatedAt, [
                'title' => 'BÁO CÁO DỌN QUANG THIẾT BỊ',
                'entityTypeLabel' => 'Thiết bị',
                'entityCode' => $equip->code ?? ('#' . $ec->equipment_id),
                'entityName' => $equip->name ?? '',
                'processCode' => $equipProcess->process_code ?? '',
                'processName' => $equipProcess->process_name ?? '',
                'version' => $equipProcess->version ?? 1,
                'productName' => $productName,
                'batchNumber' => $dist->batch_number,
                'sectionLabel' => $dist->section_label ?: $dist->section_id,
                'startedAt' => $ec->started_at ? \Carbon\Carbon::parse($ec->started_at) : null,
                'completedAt' => $ec->completed_at ? \Carbon\Carbon::parse($ec->completed_at) : null,
                'startedByName' => self::userName($ec->started_by),
                'completedByName' => self::userName($ec->completed_by),
                'steps' => self::normalizeSteps($equipSteps, ['done' => 'is_checked', 'by' => 'checked_by', 'at' => 'checked_at', 'note' => 'notes']),
                'generatedAt' => $generatedAt,
            ], 'clearance_equip', 'bao-cao-don-quang-thiet-bi', $equip->code ?? 'thiet-bi');
        }

        return $attachments;
    }

    /**
     * Render 1 báo cáo dọn quang ra PDF, lưu file và ghi 1 dòng đính kèm cho phân đoạn.
     */
    private static function renderAndAttach($dist, ?int $userId, ?string $userName, $generatedAt, array $viewData, string $filePrefix, string $fileSlug, ?string $entityCode): object
    {
        $pdfContent = Pdf::loadView('pages.manu_env.clearance_report_pdf', $viewData)
            ->setPaper('a4')
            ->output();

        $dir = public_path('upLoadData/doc/ebmr_records/' . $dist->record_id);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = $filePrefix . '_' . $dist->record_id . '_' . $dist->id . '_' . time() . '_' . Str::random(8) . '.pdf';
        file_put_contents($dir . '/' . $filename, $pdfContent);

        $now = now();
        $id = DB::table('ebmr_record_section_attachments')->insertGetId([
            'record_id' => $dist->record_id,
            'section_id' => $dist->section_id,
            'section_label' => $dist->section_label,
            'title' => $viewData['title'] . ' — ' . ($entityCode ?: '') . ' — Lô ' . $dist->batch_number,
            'file_name' => $fileSlug . '_' . Str::slug($entityCode ?: 'na') . '_' . $dist->batch_number . '.pdf',
            'file_path' => '/upLoadData/doc/ebmr_records/' . $dist->record_id . '/' . $filename,
            'file_size' => strlen($pdfContent),
            'uploaded_by' => $userId,
            'uploaded_by_name' => $userName ?: 'Hệ thống',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return DB::table('ebmr_record_section_attachments')->where('id', $id)->first();
    }

    /**
     * Chuẩn hoá danh sách bước từ 2 lược đồ cột khác nhau (phòng/thiết bị) về 1 cấu trúc
     * thống nhất cho blade: nội dung/tiêu chuẩn strip_tags, ảnh kết quả + chữ ký người
     * thực hiện nhúng dạng data URI.
     */
    private static function normalizeSteps($steps, array $cols): array
    {
        $allowedTags = '<br><b><i><strong><em><u><ul><li><ol><p>';
        $out = [];
        foreach ($steps as $s) {
            $isDone = (bool) ($s->{$cols['done']} ?? false);
            $byId = $s->{$cols['by']} ?? null;
            $doneUser = $byId ? DB::table('user_management')->where('id', $byId)->first() : null;

            $out[] = [
                'step' => $s->step,
                'content' => trim(strip_tags($s->content ?? '', $allowedTags)),
                'standard' => trim(strip_tags($s->standard ?? '', $allowedTags)),
                'is_done' => $isDone,
                'is_passed' => is_null($s->is_passed ?? null) ? null : (bool) $s->is_passed,
                'note' => $s->{$cols['note']} ?? '',
                'doneByName' => $doneUser->fullName ?? '',
                'doneAt' => ($s->{$cols['at']} ?? null) ? \Carbon\Carbon::parse($s->{$cols['at']}) : null,
                'signatureUri' => self::imageToDataUri($doneUser->signature_image ?? null),
                'images' => self::imagesToDataUris($s->attached_images ?? null),
            ];
        }
        return $out;
    }

    private static function userName($userId): ?string
    {
        if (!$userId) return null;
        return DB::table('user_management')->where('id', $userId)->value('fullName');
    }

    /**
     * Chuyển 1 đường dẫn ảnh (relative public path hoặc sẵn data URI) thành data URI để
     * dompdf nhúng offline. Trả về null nếu không có/không đọc được.
     */
    private static function imageToDataUri(?string $path): ?string
    {
        if (!$path) return null;
        if (Str::startsWith($path, 'data:image')) return $path;

        $rel = ltrim(preg_replace('#^https?://[^/]+#', '', $path), '/');
        $full = public_path($rel);
        if (!is_file($full)) return null;

        $data = @file_get_contents($full);
        if ($data === false) return null;

        $mime = match (strtolower(pathinfo($full, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /**
     * $attachedImages có thể là mảng (đã cast) hoặc chuỗi JSON — chuẩn hoá rồi map sang
     * data URI (bỏ ảnh không đọc được).
     */
    private static function imagesToDataUris($attachedImages): array
    {
        if (empty($attachedImages)) return [];
        $list = is_array($attachedImages) ? $attachedImages : (json_decode($attachedImages, true) ?: []);
        $out = [];
        foreach ($list as $img) {
            $uri = self::imageToDataUri(is_string($img) ? $img : null);
            if ($uri) $out[] = $uri;
        }
        return $out;
    }
}
