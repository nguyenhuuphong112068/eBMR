<?php
$lines = file('storage/logs/laravel.log');
echo implode('', array_slice($lines, -100));
