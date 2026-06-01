<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Checking Inst_Master_1...\n";
    $inst = DB::connection('cal1')->table('Inst_Master_1')->where('Inst_ID', 'WBL-009')->first();
    print_r((array)$inst);
    
    echo "Checking Inst_Master_1 for parent_Equip_id...\n";
    $children = DB::connection('cal1')->table('Inst_Master_1')->where('parent_Equip_id', 'WBL-009')->get();
    echo "Found " . count($children) . " children.\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
