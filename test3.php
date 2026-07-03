<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$t3 = DB::table('ebmr_template_blocks')->where('id', 1203)->first();
print_r($t3);
