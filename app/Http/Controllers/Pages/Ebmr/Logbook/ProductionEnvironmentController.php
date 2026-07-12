<?php

namespace App\Http\Controllers\Pages\Ebmr\Logbook;

use App\Http\Controllers\Controller;
use App\Services\EbmrProductResolver;
use App\Services\ProductionEnvironmentReportService;
use App\Services\ProductionEnvironmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Lịch sử môi trường sản xuất: liệt kê từng PHIÊN sản xuất (1 công đoạn của 1 lô tại 1
 * phòng, tương ứng 1 dòng ebmr_record_distributions đã "Bắt đầu sản xuất") kèm toàn bộ
 * lần đọc Nhiệt độ/Độ ẩm/Chênh áp ghi nhận được trong khoảng started_at → production_ended_at
 * (xem RecordProductionEnvironment command + ProductionEnvironmentService).
 */
class ProductionEnvironmentController extends Controller
{
    public function index(Request $request)
    {
        session(['title' => 'Lịch Sử Môi Trường Sản Xuất']);

        $workshopsList = DB::table('stage_production')->pluck('workshop_code')->unique()->values();
        $workshop = $request->input('workshop', $workshopsList->first());

        $sessions = DB::table('ebmr_record_distributions')
            ->join('ebmr_records', 'ebmr_record_distributions.record_id', '=', 'ebmr_records.id')
            ->join('ebmr_templates', 'ebmr_records.template_id', '=', 'ebmr_templates.id')
            ->whereNotNull('ebmr_record_distributions.started_at')
            ->select(
                'ebmr_record_distributions.id',
                'ebmr_record_distributions.room_id',
                'ebmr_record_distributions.room_code',
                'ebmr_record_distributions.room_name',
                'ebmr_record_distributions.section_label',
                'ebmr_record_distributions.started_at',
                'ebmr_record_distributions.production_ended_at',
                'ebmr_record_distributions.started_by',
                'ebmr_records.batch_number',
                'ebmr_records.id as record_id',
                'ebmr_templates.type as template_type',
                'ebmr_templates.caterogy_id as template_category_id'
            )
            ->orderByDesc('ebmr_record_distributions.started_at')
            ->paginate(20)
            ->withQueryString();

        // Gộp thống kê (số lần đọc, số lần vượt ngưỡng) cho các phiên trong trang hiện tại —
        // tránh N+1 query khi render danh sách.
        $distIds = collect($sessions->items())->pluck('id');
        $stats = DB::table('ebmr_records_bms')
            ->whereIn('distribution_id', $distIds)
            ->select(
                'distribution_id',
                DB::raw('count(*) as reading_count'),
                DB::raw('sum(case when is_out_of_bounds = 1 then 1 else 0 end) as out_of_bounds_count')
            )
            ->groupBy('distribution_id')
            ->get()
            ->keyBy('distribution_id');

        // Người bấm "Bắt đầu sản xuất" — coi là nhân sự thực hiện phiên sản xuất này.
        $executorNames = DB::table('user_management')
            ->whereIn('id', collect($sessions->items())->pluck('started_by')->filter()->unique()->values())
            ->pluck('fullName', 'id');

        foreach ($sessions as $s) {
            $s->reading_count = $stats[$s->id]->reading_count ?? 0;
            $s->out_of_bounds_count = $stats[$s->id]->out_of_bounds_count ?? 0;
            $s->product_name = EbmrProductResolver::resolveName($s->template_type, $s->template_category_id);
            $s->executor_name = $executorNames[$s->started_by] ?? '-';
        }

        return view('pages.logbooks.production_environment_list', [
            'sessions' => $sessions,
            'workshopsList' => $workshopsList,
            'workshop' => $workshop,
        ]);
    }

    public function show($distributionId)
    {
        $data = $this->buildSessionData($distributionId);

        return view('pages.logbooks.production_environment_show', $data);
    }

    /**
     * Báo cáo in cho 1 phiên sản xuất — mở ở tab mới, tự động in (window.onload), theo
     * cùng mẫu với các báo cáo vệ sinh phòng/thiết bị (xem room_campaign_print.blade.php).
     */
    public function printReport($distributionId)
    {
        $data = $this->buildSessionData($distributionId);

        return view('pages.logbooks.production_environment_print', $data);
    }

    /**
     * Dữ liệu phiên dùng chung với bản PDF tự đính kèm khi Kết thúc sản xuất — logic
     * gom dữ liệu nằm ở ProductionEnvironmentReportService::buildSessionData.
     */
    private function buildSessionData($distributionId): array
    {
        $data = ProductionEnvironmentReportService::buildSessionData((int) $distributionId);
        if (!$data) abort(404, 'Không tìm thấy phiên sản xuất.');

        return $data;
    }

    /**
     * Toàn bộ lần đọc (từ lúc Bắt đầu sản xuất đến hiện tại) của 1 phiên sản xuất, dạng
     * JSON — dùng cho nút Môi trường trên thanh công cụ trang thực thi (xem
     * resources/js/designer-v2/env-monitor.js) để vẽ biểu đồ 10 lần đọc gần nhất.
     */
    public function readingsJson($distributionId)
    {
        $dist = DB::table('ebmr_record_distributions')->where('id', $distributionId)->first();
        if (!$dist) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy phiên sản xuất.'], 404);
        }

        $condition = DB::table('room_manufactured_condition')->where('room_id', $dist->room_id)->first();

        $readings = DB::table('ebmr_records_bms')
            ->where('distribution_id', $distributionId)
            ->orderBy('captured_at')
            ->get(['captured_at', 'temperature', 'humidity', 'pressure', 'is_out_of_bounds']);

        return response()->json([
            'success' => true,
            'started_at' => $dist->started_at,
            'thresholds' => ProductionEnvironmentService::thresholds($condition),
            'readings' => $readings,
        ]);
    }
}
