<?php 
require __DIR__."/vendor/autoload.php"; 
$app = require_once __DIR__."/bootstrap/app.php"; 
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); 

$r = DB::table('ebmr_template_blocks')->where('id', 1339)->first();
$f = json_decode($r->properties, true);
echo json_encode($f, JSON_PRETTY_PRINT);
