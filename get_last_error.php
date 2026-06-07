<?php
$logPath = 'storage/logs/laravel.log';
$content = file_get_contents($logPath);
$errors = explode('[2026-', $content);
$lastError = end($errors);
echo "[2026-" . substr($lastError, 0, 1000);
