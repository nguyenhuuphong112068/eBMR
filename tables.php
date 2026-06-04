<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$tables = Illuminate\Support\Facades\Schema::getTables();
foreach($tables as $t) {
    echo $t['name'] . PHP_EOL;
}
