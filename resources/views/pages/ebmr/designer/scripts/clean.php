<?php
$lines = file('resources/views/pages/ebmr/designer/scripts/ui_handlers.blade.php');
$clean_lines = array_slice($lines, 0, 8466);
file_put_contents('resources/views/pages/ebmr/designer/scripts/ui_handlers.blade.php', implode('', $clean_lines));
echo "Cleaned!";
