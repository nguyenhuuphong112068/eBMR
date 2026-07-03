<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$finalSectionId = 'blk_sec_6a23fe00c52d4';
$id = 34; // template_id
if ($finalSectionId && strpos($finalSectionId, 'blk_sec_') === 0) {
    $secBlock = DB::table('ebmr_template_blocks')
        ->where('template_id', $id)
        ->where('properties', 'LIKE', '%"id":"' . $finalSectionId . '"%')
        ->first();
    if ($secBlock && $secBlock->section_id) {
        echo "Resolved to: " . $secBlock->section_id . "\n";
    }
}
