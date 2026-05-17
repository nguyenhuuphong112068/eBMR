<?php
$file = 'd:/LEMP/eBMR/resources/views/pages/category/intermediate/dataTable.blade.php';
$content = file_get_contents($file);
$content = preg_replace('/window\.renderBOMRows\s*=\s*function.*?\}\);(?=\s*<\/script>)/s', '', $content);
$content = preg_replace('/\/\/ Sync Summernote div content.*?\}\);/s', '', $content);
file_put_contents($file, $content);
