<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('document:activate-issued')->dailyAt('00:01');
\Illuminate\Support\Facades\Schedule::command('approvals:send-reminders')->dailyAt('08:00');
// Chạy mỗi phút — command tự so sánh với tần suất cấu hình ở Chính Sách Chung (mốc phút
// nhỏ nhất scheduler hỗ trợ) để quyết định có ghi nhận reading mới cho từng phòng hay không.
\Illuminate\Support\Facades\Schedule::command('production-env:record')->everyMinute();
