<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$listId = 7;
$list = \DB::table('cleaning_equip_processes_list')->where('id', $listId)->first();
echo "List: " . ($list ? "found" : "not found") . "\n";
if ($list) {
    echo "created_by: " . $list->created_by . "\n";
    $creator = \DB::table('users')->where('id', $list->created_by)->first();
    echo "Creator: " . ($creator ? "found" : "not found") . "\n";
    if ($creator) {
        echo "Creator Name: " . $creator->name . "\n";
    }
}
