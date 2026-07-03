<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$b = DB::table('ebmr_template_blocks')->where('id', 1201)->first();
$f = json_decode($b->properties, true);
$f['section_id'] = $b->section_id;
$obj = (object)$f;
echo json_encode($obj);
