<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Checking Inst_Master_2...\n";
    $inst = DB::connection('cal1')->table('Inst_Master_2')->first();
    print_r((array)$inst);

    echo "Checking Schedule_Master_2...\n";
    $sch = DB::connection('cal1')->table('Schedule_Master_2')->first();
    print_r((array)$sch);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
