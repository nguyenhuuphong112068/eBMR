<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$doc = DB::table('ebmr_content_blocks')->where('type', 'table')->orderBy('updated_at', 'desc')->first();
file_put_contents('dump.json', json_encode(json_decode($doc->content)));
