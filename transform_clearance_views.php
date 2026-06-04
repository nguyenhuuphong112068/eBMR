<?php
$files = [
    'd:\LEMP\eBMR\resources\views\pages\manu_env\clearance_process\list.blade.php',
    'd:\LEMP\eBMR\resources\views\pages\manu_env\clearance_process\index.blade.php',
    'd:\LEMP\eBMR\resources\views\pages\manu_env\clearance_process\campaign_execute.blade.php',
];

$replacements = [
    'cleaning_process' => 'clearance_process',
    'cleaning-process' => 'clearance-process',
    'cleaning_type' => 'clearance_type',
    'Vệ sinh' => 'Dọn quang',
    'vệ sinh' => 'dọn quang',
    'VỆ SINH' => 'DỌN QUANG',
    'Vệ Sinh' => 'Dọn Quang',
    'bg-light-warning' => 'bg-light-success', // UI color change for clearance
    'text-warning' => 'text-success',
    'btn-light-warning' => 'btn-light-success',
    'btn-warning' => 'btn-success',
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    $content = str_replace(array_keys($replacements), array_values($replacements), $content);
    file_put_contents($file, $content);
}
echo "Views transformed.";
