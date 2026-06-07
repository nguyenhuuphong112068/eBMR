<?php
$lines = file('storage/logs/laravel.log');
$errors = preg_grep('/local\.ERROR/', $lines);
$keys = array_keys($errors);
$last = end($keys);
for($i=$last; $i<count($lines) && $i<$last+5; $i++) {
    echo $lines[$i];
}
