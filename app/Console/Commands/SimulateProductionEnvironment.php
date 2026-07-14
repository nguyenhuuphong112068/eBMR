<?php

namespace App\Console\Commands;

use App\Services\ProductionEnvironmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SimulateProductionEnvironment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production-env:simulate {--interval=10 : Tần suất giả lập ghi nhận (tính bằng giây)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chạy ngầm (Daemon) giả lập ghi nhận liên tục Nhiệt độ/Độ ẩm/Chênh áp cho các công đoạn đang sản xuất (Bắt đầu -> Chưa kết thúc)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $interval = (int) $this->option('interval');
        if ($interval <= 0) {
            $interval = 10;
        }

        $this->info("==================================================================");
        $this->info("BẮT ĐẦU CHẠY DAEMON GIẢ LẬP MÔI TRƯỜNG SẢN XUẤT");
        $this->info("Tần suất ghi nhận: {$interval} giây/lần.");
        $this->info("Dữ liệu tự động phát sinh khi bấm 'Bắt đầu sản xuất' và dừng khi 'Kết thúc sản xuất'.");
        $this->info("Bấm Ctrl+C để dừng tiến trình hoặc cấu hình chạy ngầm qua PM2.");
        $this->info("==================================================================");

        while (true) {
            // Lấy danh sách các phiên sản xuất đang "mở" (Đã bắt đầu, Chưa kết thúc)
            $activeSessions = DB::table('ebmr_record_distributions')
                ->whereNotNull('started_at')
                ->whereNull('production_ended_at')
                ->get();

            if ($activeSessions->isNotEmpty()) {
                $conditions = DB::table('room_manufactured_condition')->get()->keyBy('room_id');
                $now = now();

                foreach ($activeSessions as $dist) {
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
                        'captured_at' => $now,
                        'recorded_type' => 'auto',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $this->line("[{$now->format('H:i:s')}] [Phòng: {$dist->room_id} / Dist ID: {$dist->id}] -> Ghi nhận: Temp={$reading['temperature']}°C, Humid={$reading['humidity']}%, Press={$reading['pressure']}Pa");
                }
            }

            sleep($interval);
        }
    }
}
