<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EbmrRecordBmsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\EbmrRecordBms::truncate();

        $now = now();
        $recordId = 1; // Dummy ebmr_record_id
        
        $data = [];
        // Simulate generating data every 5 minutes for 1 hour for Stage 1 (Room 79)
        for ($i = 0; $i < 12; $i++) {
            $time = $now->copy()->subMinutes(60 - ($i * 5));
            $temp = 20.0 + (rand(-15, 25) / 10); // 18.5 to 22.5
            $hum = 45.0 + (rand(-50, 80) / 10); // 40.0 to 53.0
            $pres = 15.0 + (rand(-20, 20) / 10); // 13.0 to 17.0
            
            // Randomly simulate an out of bounds alert
            $isOutOfBounds = ($temp > 22.0 || $hum > 50.0) ? true : false;

            $data[] = [
                'ebmr_record_id' => $recordId,
                'stage_code' => 1,
                'room_id' => 79,
                'temperature' => $temp,
                'humidity' => $hum,
                'pressure' => $pres,
                'is_out_of_bounds' => $isOutOfBounds,
                'captured_at' => $time,
                'recorded_type' => 'auto',
                'created_by' => null,
                'remarks' => $isOutOfBounds ? 'Nhiệt độ/Độ ẩm vượt chuẩn' : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        \App\Models\EbmrRecordBms::insert($data);
    }
}
