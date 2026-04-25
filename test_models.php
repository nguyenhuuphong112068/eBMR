<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$apiKey = env('GEMINI_API_KEY');
$response = \Illuminate\Support\Facades\Http::withoutVerifying()->get('https://generativelanguage.googleapis.com/v1beta/models?key=' . $apiKey);
$data = json_decode($response->body(), true);
foreach($data['models'] as $m) { echo $m['name'] . "\n"; }
