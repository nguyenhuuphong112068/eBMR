<?php

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$rooms = DB::connection('pms')->table('room')->get();

$adminId = DB::table('user_management')->where('username', 'admin')->value('id') ?? 1;

foreach ($rooms as $room) {
    // Tạo 1 logbook cho phòng: trạng thái Cần vệ sinh
    DB::table('room_logbooks')->insert([
        'room_id' => $room->id,
        'equipment_id' => null,
        'action_type' => 'manufacturing',
        'current_status' => 'dirty',
        'stage' => 'Sản xuất viên nén',
        'lot_number' => 'L01',
        'product_name' => 'Paracetamol 500mg',
        'batch_number' => 'B20231001',
        'clean_level' => 'level_1',
        'clean_expiry_date' => null,
        'to_be_cleaned_before' => Carbon::now()->addDays(1),
        'start_time' => Carbon::now()->subHours(5),
        'end_time' => Carbon::now()->subHours(1),
        'next_product_name' => null,
        'next_batch_number' => null,
        'employee_ids' => '[]',
        'previous_status' => 'ready',
        'created_by' => $adminId,
        'checked_by' => null,
        'attached_by' => null,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ]);

    // Lấy các thiết bị trong phòng
    $equipments = DB::table('equipment_in_room')->where('room_id', $room->id)->get();
    
    foreach ($equipments as $idx => $eq) {
        // Một nửa thiết bị đã vệ sinh, một nửa cần vệ sinh
        if ($idx % 2 == 0) {
            // Đã vệ sinh (Màu xanh)
            DB::table('room_logbooks')->insert([
                'room_id' => $room->id,
                'equipment_id' => $eq->equipment_id,
                'action_type' => 'cleaning',
                'current_status' => 'cleaned',
                'stage' => 'Vệ sinh định kỳ',
                'lot_number' => null,
                'product_name' => null,
                'batch_number' => null,
                'clean_level' => 'level_2',
                'clean_expiry_date' => Carbon::now()->addDays(7),
                'to_be_cleaned_before' => null,
                'start_time' => Carbon::now()->subHours(2),
                'end_time' => Carbon::now()->subMinutes(30),
                'next_product_name' => 'Amoxicillin 250mg',
                'next_batch_number' => 'B20231002',
                'employee_ids' => '[]',
                'previous_status' => 'ready',
                'created_by' => $adminId,
                'checked_by' => $adminId,
                'attached_by' => $adminId,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        } else {
            // Đang bẩn / Cần vệ sinh (Màu vàng)
            DB::table('room_logbooks')->insert([
                'room_id' => $room->id,
                'equipment_id' => $eq->equipment_id,
                'action_type' => 'manufacturing',
                'current_status' => 'ready', // ready but dirty technically, will trigger yellow label in our logic since it's not 'cleaned'
                'stage' => 'Đóng gói',
                'lot_number' => 'L02',
                'product_name' => 'Ibuprofen 400mg',
                'batch_number' => 'B20231003',
                'clean_level' => 're_cleaning',
                'clean_expiry_date' => null,
                'to_be_cleaned_before' => Carbon::now()->addHours(24),
                'start_time' => Carbon::now()->subDays(1),
                'end_time' => Carbon::now()->subHours(2),
                'next_product_name' => null,
                'next_batch_number' => null,
                'employee_ids' => '[]',
                'previous_status' => 'ready',
                'created_by' => $adminId,
                'checked_by' => null,
                'attached_by' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}

echo "Seeded room_logbooks successfully.\n";
