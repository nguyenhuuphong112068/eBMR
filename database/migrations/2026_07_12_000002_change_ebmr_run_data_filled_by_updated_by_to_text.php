<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mã hoá thêm hai cột nhận dạng người dùng của ebmr_run_data:
 *   - `filled_by`  (varchar 255): id/tên người nhập liệu
 *   - `updated_by` (varchar 100): tên người cập nhật (vd "Huỳnh Anh Tấn")
 *
 * Sau khi qua RunDataEncryptionService::encrypt() (Laravel Crypt AES-256-CBC + HMAC),
 * chuỗi ciphertext dài ~200-240 ký tự — KHÔNG vừa varchar(100) của `updated_by`
 * (gây "Data too long") và chật varchar(255) của `filled_by`. Đổi cả hai sang TEXT
 * để chứa ciphertext, đồng bộ với cột `raw_value` (cũng TEXT + encrypt).
 *
 * Đây chỉ là hai cột lưu trữ/hiển thị — KHÔNG dùng trong WHERE/JOIN/ORDER BY nên
 * mã hoá không phá vỡ truy vấn nào.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `ebmr_run_data` MODIFY `filled_by` TEXT NULL');
        DB::statement('ALTER TABLE `ebmr_run_data` MODIFY `updated_by` TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `ebmr_run_data` MODIFY `filled_by` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `ebmr_run_data` MODIFY `updated_by` VARCHAR(100) NULL');
    }
};
