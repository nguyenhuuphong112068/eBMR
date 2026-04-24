<?php
include 'vendor/autoload.php';
$app = include 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$contents = DB::table('ebmr_content_blocks')->where('template_id', 12)->limit(10)->get();
foreach ($contents as $c) {
    echo "ID: " . $c->id . "\n";
    echo "VI: " . $c->vi_contents . "\n";
    echo "EN: " . $c->en_contents . "\n";
    echo "-------------------\n";
}
