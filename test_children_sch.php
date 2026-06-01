<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $parent_id = 'WBL-009';
    $children = DB::connection('cal1')->table('Inst_Master_1')->where('Parent_Equip_id', $parent_id)->get();
    echo "Children of $parent_id:\n";
    foreach ($children as $child) {
        echo "- " . $child->Inst_id . " : " . $child->Inst_Name . "\n";
        
        $schedules = DB::connection('cal1')->table('Schedule_Master_1')
            ->where('Inst_ID', $child->Inst_id)
            ->orderBy('SCH_ID', 'desc')
            ->limit(2)
            ->get();
        
        foreach ($schedules as $s) {
            echo "  > SCH_ID: " . $s->SCH_ID . ", Status: " . $s->Sch_Result_Status . ", CalDone_On: " . $s->Sch_CalDone_On . ", Next_cal: " . $s->Next_cal_date . "\n";
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
