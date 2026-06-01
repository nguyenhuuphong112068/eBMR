<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $code = 'PDS-251';
    echo "Checking Inst_Master_1 for code: $code\n";
    
    $inst = DB::connection('cal1')->table('Inst_Master_1')->where('Inst_id', $code)->first();
    if (!$inst) {
        echo "=> NOT FOUND in Inst_Master_1\n";
    } else {
        echo "=> FOUND: " . $inst->Inst_Name . "\n";
        $parent_id = $inst->Parent_Equip_id;
        echo "=> Parent_Equip_id: " . $parent_id . "\n";
        
        $children = DB::connection('cal1')->table('Inst_Master_1')
            ->where('Parent_Equip_id', $parent_id)
            ->where('Inst_Status', 'Active')
            ->get();
            
        echo "=> Found " . count($children) . " Active children.\n";
        foreach ($children as $child) {
            echo "   - Child: " . $child->Inst_id . " (" . $child->Inst_Name . ")\n";
            $schedule = DB::connection('cal1')->table('Schedule_Master_1')
                ->where('Inst_ID', $child->Inst_id)
                ->whereNotNull('Next_cal_date')
                ->orderBy('SCH_ID', 'desc')
                ->first();
            if ($schedule) {
                echo "      => Has Schedule! SCH_ID: " . $schedule->SCH_ID . ", Next_cal_date: " . $schedule->Next_cal_date . "\n";
            } else {
                echo "      => NO valid schedule found (Next_cal_date is null or missing).\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
