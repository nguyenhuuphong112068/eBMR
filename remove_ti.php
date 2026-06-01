<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Finding TI blocks in PMS...\n";
    $pmsTiBlocks = DB::connection('pms')->table('quota_maintenance')
        ->where('block', 'like', '%TI%')
        ->pluck('parent_eqp_id')
        ->filter()
        ->toArray();
    
    echo "Found " . count($pmsTiBlocks) . " records with TI block in PMS.\n";

    if (count($pmsTiBlocks) > 0) {
        $deleted = DB::table('instrument')
            ->whereIn('code', $pmsTiBlocks)
            ->delete();
        echo "Successfully deleted $deleted records from local instrument table.\n";
    } else {
        echo "No records found to delete.\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
