<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo \App\Services\AIService::getResponse('Cho tôi thông tin lô BATCH-001');
} catch (\Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString();
}
