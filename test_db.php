<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$blocks = \DB::table('ebmr_template_blocks')->orderBy('id', 'desc')->limit(3)->get();
print_r($blocks);
