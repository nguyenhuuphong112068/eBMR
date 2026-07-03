<?php
$file = 'resources/views/pages/ebmr/designer/scripts/ui_handlers.blade.php';
$content = file_get_contents($file);
$js = file_get_contents('resources/views/pages/ebmr/designer/scripts/temp_typo.js');

// Find the last occurrence of </script>
$pos = strrpos($content, '</script>');
if ($pos !== false) {
    $new_content = substr_replace($content, $js . "\n</script>", $pos, strlen('</script>'));
    file_put_contents($file, $new_content);
    echo "Successfully injected before last </script>";
} else {
    echo "Error: </script> not found!";
}
