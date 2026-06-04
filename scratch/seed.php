<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$equip = DB::table('instrument')->where('code', 'SE-031/01')->first();

if (!$equip) {
    echo "Not found equipment SE-031/01\n";
    exit;
}

$types = [1, 2, 3];
$names = ['Quy trình vệ sinh thông thường (Cấp 1)', 'Quy trình vệ sinh định kỳ (Cấp 2)', 'Quy trình vệ sinh lại (Làm Sạch Nhanh)'];

foreach ($types as $index => $type) {
    $processId = DB::table('cleaning_equip_processes_list')->insertGetId([
        'equipment_id' => $equip->id,
        'process_code' => 'P-' . str_replace('/', '', $equip->code) . '-' . $type,
        'process_name' => $names[$index],
        'cleaning_type' => $type,
        'version' => 1,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now()
    ]);

    for ($i = 1; $i <= 2; $i++) {
        DB::table('cleaning_equip_processes')->insert([
            'process_list_id' => $processId,
            'step' => $i,
            'content' => 'Bước ' . $i . ': Thực hiện vệ sinh thiết bị ' . $equip->name . ' theo tiêu chuẩn',
            'standard' => 'Bề mặt sạch sẽ bằng mắt thường, không còn vết bẩn.',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}

echo "Created 3 processes for {$equip->code} successfully.\n";
