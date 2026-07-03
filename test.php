<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$b = DB::table('ebmr_template_blocks')->where('template_id', 34)->where('type', 'section')->first();
$f = json_decode($b->properties, true);
$f['section_id'] = $b->section_id;
print_r($f);
