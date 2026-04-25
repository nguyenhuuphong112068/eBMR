<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$request = new \Illuminate\Http\Request(['template_id' => 14]);
$controller = app()->make(\App\Http\Controllers\EbmrDesignerController::class);
$response = $controller->aiTranslate($request);
echo $response->getContent();
