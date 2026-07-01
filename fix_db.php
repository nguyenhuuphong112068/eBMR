<?php 
require __DIR__."/vendor/autoload.php"; 
$app = require_once __DIR__."/bootstrap/app.php"; 
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); 

$affected = DB::table('ebmr_template_blocks')
    ->where('template_id', 34)
    ->where('section_id', 'LIKE', 'blk_sec_%')
    ->update(['section_id' => '6_1']);

echo "Updated $affected blocks to 6_1.\n";

$affectedContent = DB::table('ebmr_content_blocks')
    ->where('template_id', 34)
    ->where('section_id', 'LIKE', 'blk_sec_%')
    ->update(['section_id' => '6_1']);

echo "Updated $affectedContent content blocks to 6_1.\n";
