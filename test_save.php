<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = \Illuminate\Http\Request::create('/ebmr/store-template', 'POST', [
    'id' => 9,
    'schema' => [
        'fields' => [
            [
                'id' => 'blk_test_1',
                'type' => 'static-text',
                'content' => '<p>New text to see if it changes</p>'
            ]
        ]
    ]
]);

$controller = app()->make(\App\Http\Controllers\Pages\Ebmr\Designer\EbmrDesignerController::class);
$response = $controller->save($request);
echo $response->getContent();
