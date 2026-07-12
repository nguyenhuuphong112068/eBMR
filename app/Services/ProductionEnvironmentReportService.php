<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Xuất "Báo Cáo Môi Trường Sản Xuất" của 1 phiên sản xuất (1 dòng
 * ebmr_record_distributions) thành file PDF và đính kèm vào phân đoạn tương ứng
 * (ebmr_record_section_attachments) — được gọi tự động khi người dùng bấm
 * "Kết thúc sản xuất" (EbmrExecutionController::endProduction).
 *
 * Dữ liệu phiên (buildSessionData) dùng chung với trang xem/in HTML
 * (ProductionEnvironmentController::show/printReport). PDF render bằng dompdf nên
 * biểu đồ Chart.js (canvas) không dùng được — thay bằng SVG vẽ phía server
 * (mỗi thông số 1 biểu đồ riêng, nhúng dạng data URI).
 */
class ProductionEnvironmentReportService
{
    /**
     * Gom toàn bộ dữ liệu của 1 phiên sản xuất (thông tin phiên, ngưỡng, các lần đọc,
     * thống kê, dữ liệu biểu đồ). Trả về null nếu không tìm thấy phiên.
     */
    public static function buildSessionData(int $distributionId): ?array
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
        if (!$dist) return null;

        $room = DB::connection('pms')->table('room')->where('id', $dist->room_id)->first();
        $condition = DB::table('room_manufactured_condition')->where('room_id', $dist->room_id)->first();

        $productName = EbmrProductResolver::resolveName($dist->template_type, $dist->template_category_id);
        $executorName = $dist->started_by
            ? DB::table('user_management')->where('id', $dist->started_by)->value('fullName')
            : null;

        $readings = DB::table('ebmr_records_bms')
            ->where('distribution_id', $distributionId)
            ->orderBy('captured_at')
            ->get();

        $oobReadings = $readings->where('is_out_of_bounds', true)->values();

        $stats = [
            'count' => $readings->count(),
            'out_of_bounds' => $oobReadings->count(),
            'temp_min' => $readings->min('temperature'),
            'temp_max' => $readings->max('temperature'),
            'humid_min' => $readings->min('humidity'),
            'humid_max' => $readings->max('humidity'),
            'press_min' => $readings->min('pressure'),
            'press_max' => $readings->max('pressure'),
        ];

        // Dữ liệu cho biểu đồ diễn biến 3 thông số theo thời gian (Chart.js, xem view).
        $chartData = [
            'labels' => $readings->map(fn ($r) => Carbon::parse($r->captured_at)->format('d/m H:i:s'))->values(),
            'temperature' => $readings->pluck('temperature')->values(),
            'humidity' => $readings->pluck('humidity')->values(),
            'pressure' => $readings->pluck('pressure')->values(),
        ];

        return [
            'dist' => $dist,
            'room' => $room,
            'condition' => $condition,
            'productName' => $productName,
            'executorName' => $executorName ?: '-',
            'readings' => $readings,
            'stats' => $stats,
            'thresholds' => ProductionEnvironmentService::thresholds($condition),
            'oobReadings' => $oobReadings,
            'chartData' => $chartData,
        ];
    }

    /**
     * Xuất báo cáo môi trường của phiên thành PDF, lưu vào thư mục tài liệu của hồ sơ
     * và ghi 1 dòng đính kèm vào phân đoạn (cùng bảng/đường dẫn với upload thủ công —
     * xem EbmrExecutionController::uploadSectionAttachment). Trả về dòng đính kèm.
     *
     * Ném exception nếu phiên không tồn tại hoặc render/lưu PDF thất bại — nơi gọi
     * tự quyết định có cho lỗi này chặn nghiệp vụ chính hay không.
     */
    public static function attachReportPdf(int $distributionId, ?int $userId = null, ?string $userName = null): object
    {
        $data = self::buildSessionData($distributionId);
        if (!$data) {
            throw new \RuntimeException("Không tìm thấy phiên sản xuất #{$distributionId}.");
        }

        $dist = $data['dist'];
        $data['chartSvgs'] = self::buildChartSvgs($data['readings'], $data['thresholds']);
        $data['generatedAt'] = now();

        $pdfContent = Pdf::loadView('pages.logbooks.production_environment_report_pdf', $data)
            ->setPaper('a4')
            ->output();

        $dir = public_path('upLoadData/doc/ebmr_records/' . $dist->record_id);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = 'env_report_' . $dist->record_id . '_' . $dist->id . '_' . time() . '_' . Str::random(8) . '.pdf';
        file_put_contents($dir . '/' . $filename, $pdfContent);

        $roomCode = $data['room']->code ?? $dist->room_code;
        $now = now();
        $id = DB::table('ebmr_record_section_attachments')->insertGetId([
            'record_id' => $dist->record_id,
            'section_id' => $dist->section_id,
            'section_label' => $dist->section_label,
            'title' => 'Báo cáo môi trường sản xuất — ' . $roomCode . ' — Lô ' . $dist->batch_number,
            'file_name' => 'bao-cao-moi-truong-san-xuat_' . Str::slug($roomCode ?: 'phong') . '_' . $dist->batch_number . '.pdf',
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
     * Vẽ 3 biểu đồ SVG (Nhiệt độ / Độ ẩm / Chênh áp) — mỗi cái 1 data URI để nhúng
     * <img> trong PDF. Trả về [] nếu phiên chưa có lần đọc nào.
     */
    private static function buildChartSvgs($readings, array $thresholds): array
    {
        if ($readings->isEmpty()) return [];

        $times = $readings->map(fn ($r) => Carbon::parse($r->captured_at)->timestamp)->values()->all();

        return [
            [
                'label' => 'Nhiệt độ (°C)',
                'color' => '#e74c3c',
                'uri' => self::chartSvg($times, $readings->pluck('temperature')->map(fn ($v) => (float) $v)->all(), $thresholds['temp_min'], $thresholds['temp_max'], '#e74c3c'),
            ],
            [
                'label' => 'Độ ẩm (%)',
                'color' => '#3498db',
                'uri' => self::chartSvg($times, $readings->pluck('humidity')->map(fn ($v) => (float) $v)->all(), $thresholds['humid_min'], $thresholds['humid_max'], '#3498db'),
            ],
            [
                'label' => 'Chênh áp (Pa)',
                'color' => '#2ecc71',
                'uri' => self::chartSvg($times, $readings->pluck('pressure')->map(fn ($v) => (float) $v)->all(), $thresholds['press_min'], $thresholds['press_max'], '#2ecc71'),
            ],
        ];
    }

    /**
     * 1 biểu đồ đường đơn giản cho 1 thông số: đường giá trị + 2 đường ngưỡng min/max
     * (đứt nét đỏ) + nhãn thời gian đầu/giữa/cuối. Chỉ dùng hình cơ bản (line/path/
     * circle/text) để php-svg-lib của dompdf render được.
     */
    private static function chartSvg(array $times, array $values, float $thMin, float $thMax, string $color): string
    {
        $w = 680; $h = 170;
        $padL = 46; $padR = 10; $padT = 10; $padB = 24;
        $plotW = $w - $padL - $padR;
        $plotH = $h - $padT - $padB;

        $yMin = min(min($values), $thMin);
        $yMax = max(max($values), $thMax);
        if ($yMax - $yMin < 0.0001) { $yMin -= 1; $yMax += 1; }
        $pad = ($yMax - $yMin) * 0.1;
        $yMin -= $pad; $yMax += $pad;

        $tMin = min($times); $tMax = max($times);
        $tSpan = max($tMax - $tMin, 1);

        $x = fn ($t) => $padL + ($t - $tMin) / $tSpan * $plotW;
        $y = fn ($v) => $padT + (1 - ($v - $yMin) / ($yMax - $yMin)) * $plotH;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '">';
        // Khung + lưới ngang (4 mức)
        $svg .= '<rect x="' . $padL . '" y="' . $padT . '" width="' . $plotW . '" height="' . $plotH . '" fill="#fdfdfd" stroke="#cccccc" stroke-width="1"/>';
        for ($i = 1; $i <= 3; $i++) {
            $gy = round($padT + $plotH * $i / 4, 1);
            $val = round($yMax - ($yMax - $yMin) * $i / 4, 1);
            $svg .= '<line x1="' . $padL . '" y1="' . $gy . '" x2="' . ($padL + $plotW) . '" y2="' . $gy . '" stroke="#eeeeee" stroke-width="1"/>';
            $svg .= '<text x="' . ($padL - 4) . '" y="' . ($gy + 3) . '" font-size="9" text-anchor="end" fill="#666666">' . $val . '</text>';
        }
        $svg .= '<text x="' . ($padL - 4) . '" y="' . ($padT + 3) . '" font-size="9" text-anchor="end" fill="#666666">' . round($yMax, 1) . '</text>';
        $svg .= '<text x="' . ($padL - 4) . '" y="' . ($padT + $plotH + 3) . '" font-size="9" text-anchor="end" fill="#666666">' . round($yMin, 1) . '</text>';

        // Đường ngưỡng min/max (đứt nét)
        foreach ([$thMin, $thMax] as $th) {
            $ty = round($y($th), 1);
            $svg .= '<line x1="' . $padL . '" y1="' . $ty . '" x2="' . ($padL + $plotW) . '" y2="' . $ty . '" stroke="#e74c3c" stroke-width="1" stroke-dasharray="4,3"/>';
        }

        // Đường giá trị
        if (count($values) === 1) {
            $svg .= '<circle cx="' . round($x($times[0]), 1) . '" cy="' . round($y($values[0]), 1) . '" r="3" fill="' . $color . '"/>';
        } else {
            $d = '';
            foreach ($values as $i => $v) {
                $d .= ($i === 0 ? 'M' : ' L') . round($x($times[$i]), 1) . ' ' . round($y($v), 1);
            }
            $svg .= '<path d="' . $d . '" fill="none" stroke="' . $color . '" stroke-width="1.5"/>';
        }

        // Nhãn thời gian đầu / giữa / cuối
        $labelPoints = count($times) >= 3
            ? [0, intdiv(count($times), 2), count($times) - 1]
            : array_keys($times);
        $anchors = ['start', 'middle', 'end'];
        foreach (array_values(array_unique($labelPoints)) as $j => $idx) {
            $anchor = count($labelPoints) === 1 ? 'middle' : $anchors[min($j, 2)];
            $svg .= '<text x="' . round($x($times[$idx]), 1) . '" y="' . ($padT + $plotH + 14) . '" font-size="9" text-anchor="' . $anchor . '" fill="#666666">'
                . date('d/m H:i', $times[$idx]) . '</text>';
        }

        $svg .= '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
