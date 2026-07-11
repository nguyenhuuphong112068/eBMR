<?php

namespace App\Console\Commands;

use App\Models\SystemSetting;
use App\Services\ProductionEnvironmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RecordProductionEnvironment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production-env:record';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ghi nhận định kỳ Nhiệt độ/Độ ẩm/Chênh áp cho mọi phòng đang trong phiên sản xuất (Bắt đầu → Kết thúc sản xuất), theo tần suất cấu hình ở Chính Sách Chung';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $frequencyMinutes = (int) SystemSetting::get('env_recording_frequency_minutes', 15);

        // Phiên sản xuất đang "mở": đã bắt đầu, chưa kết thúc — đúng phạm vi "từ Bắt
        // đầu sản xuất đến khi Kết thúc sản xuất" theo yêu cầu nghiệp vụ.
        $activeSessions = DB::table('ebmr_record_distributions')
            ->whereNotNull('started_at')
            ->whereNull('production_ended_at')
            ->get();

        if ($activeSessions->isEmpty()) {
            $this->info('Không có phiên sản xuất nào đang hoạt động.');
            return;
        }

        $conditions = DB::table('room_manufactured_condition')->get()->keyBy('room_id');
        $recorded = 0;

        foreach ($activeSessions as $dist) {
            $lastCapturedAt = DB::table('ebmr_records_bms')
                ->where('distribution_id', $dist->id)
                ->orderByDesc('captured_at')
                ->value('captured_at');

            $dueForRecording = !$lastCapturedAt
                || Carbon::parse($lastCapturedAt)->diffInMinutes(now()) >= $frequencyMinutes;
            if (!$dueForRecording) continue;

            $room = DB::connection('pms')->table('room')->where('id', $dist->room_id)->first();
            $reading = ProductionEnvironmentService::simulateReading((int) $dist->room_id);
            $condition = $conditions->get($dist->room_id);
            $isOutOfBounds = ProductionEnvironmentService::isOutOfBounds($reading, $condition);

            DB::table('ebmr_records_bms')->insert([
                'ebmr_record_id' => $dist->record_id,
                'stage_code' => (int) ($room->stage_code ?? 0),
                'room_id' => $dist->room_id,
                'distribution_id' => $dist->id,
                'temperature' => $reading['temperature'],
                'humidity' => $reading['humidity'],
                'pressure' => $reading['pressure'],
                'is_out_of_bounds' => $isOutOfBounds,
                'captured_at' => now(),
                'recorded_type' => 'auto',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $recorded++;
        }

        $this->info("Đã kiểm tra {$activeSessions->count()} phiên sản xuất, ghi nhận {$recorded} lần đọc mới (tần suất {$frequencyMinutes} phút).");
    }
}
