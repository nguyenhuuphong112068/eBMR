<?php

namespace App\Http\Controllers\Pages\Ebmr\Logbook;

use App\Http\Controllers\Controller;
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
            ->whereNotNull('ebmr_record_distributions.started_at')
            ->select(
                'ebmr_record_distributions.id',
                'ebmr_record_distributions.room_id',
                'ebmr_record_distributions.room_code',
                'ebmr_record_distributions.room_name',
                'ebmr_record_distributions.section_label',
                'ebmr_record_distributions.started_at',
                'ebmr_record_distributions.production_ended_at',
                'ebmr_records.batch_number',
                'ebmr_records.id as record_id'
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

        foreach ($sessions as $s) {
            $s->reading_count = $stats[$s->id]->reading_count ?? 0;
            $s->out_of_bounds_count = $stats[$s->id]->out_of_bounds_count ?? 0;
        }

        return view('pages.logbooks.production_environment_list', [
            'sessions' => $sessions,
            'workshopsList' => $workshopsList,
            'workshop' => $workshop,
        ]);
    }

    public function show($distributionId)
    {
        $dist = DB::table('ebmr_record_distributions')
            ->join('ebmr_records', 'ebmr_record_distributions.record_id', '=', 'ebmr_records.id')
            ->where('ebmr_record_distributions.id', $distributionId)
            ->select('ebmr_record_distributions.*', 'ebmr_records.batch_number')
            ->first();
        if (!$dist) abort(404, 'Không tìm thấy phiên sản xuất.');

        $room = DB::connection('pms')->table('room')->where('id', $dist->room_id)->first();
        $condition = DB::table('room_manufactured_condition')->where('room_id', $dist->room_id)->first();

        $readings = DB::table('ebmr_records_bms')
            ->where('distribution_id', $distributionId)
            ->orderBy('captured_at')
            ->get();

        $stats = [
            'count' => $readings->count(),
            'out_of_bounds' => $readings->where('is_out_of_bounds', true)->count(),
            'temp_min' => $readings->min('temperature'),
            'temp_max' => $readings->max('temperature'),
            'humid_min' => $readings->min('humidity'),
            'humid_max' => $readings->max('humidity'),
            'press_min' => $readings->min('pressure'),
            'press_max' => $readings->max('pressure'),
        ];

        return view('pages.logbooks.production_environment_show', [
            'dist' => $dist,
            'room' => $room,
            'condition' => $condition,
            'readings' => $readings,
            'stats' => $stats,
        ]);
    }
}
