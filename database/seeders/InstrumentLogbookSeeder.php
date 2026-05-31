<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InstrumentLogbookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\InstrumentLogbook::truncate();

        $now = now();
        
        \App\Models\InstrumentLogbook::insert([
            [
                'instrument_id' => 'MAYCAN-01',
                'action_type' => 'producing',
                'product_name' => 'Paracetamol 500mg',
                'batch_number' => 'LOT2605',
                'start_time' => $now->copy()->subHours(2),
                'end_time' => null,
                'employee_ids' => json_encode([1, 2]),
                'clean_level' => null,
                'clean_expiry_date' => null,
                'previous_status' => 'ready',
                'current_status' => 'producing',
                'remarks' => 'Sản xuất ca 1',
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'instrument_id' => 'MAYTRON-02',
                'action_type' => 'cleaning',
                'product_name' => 'Amoxicillin 250mg',
                'batch_number' => 'LOT2504',
                'start_time' => $now->copy()->subHours(5),
                'end_time' => $now->copy()->subHours(4),
                'employee_ids' => json_encode([3]),
                'clean_level' => 'Toàn diện',
                'clean_expiry_date' => $now->copy()->addDays(3),
                'previous_status' => 'producing',
                'current_status' => 'ready',
                'remarks' => 'Vệ sinh máy trộn chữ V',
                'created_by' => 2,
                'created_at' => $now->copy()->subHours(4),
                'updated_at' => $now->copy()->subHours(4),
            ],
            [
                'instrument_id' => 'MAYBAO-01',
                'action_type' => 'maintenance',
                'product_name' => null,
                'batch_number' => null,
                'start_time' => $now->copy()->subDays(1),
                'end_time' => null,
                'employee_ids' => json_encode([5]),
                'clean_level' => null,
                'clean_expiry_date' => null,
                'previous_status' => 'ready',
                'current_status' => 'maintenance',
                'remarks' => 'Thay thế súng phun',
                'created_by' => 3,
                'created_at' => $now->copy()->subDays(1),
                'updated_at' => $now->copy()->subDays(1),
            ]
        ]);
    }
}
