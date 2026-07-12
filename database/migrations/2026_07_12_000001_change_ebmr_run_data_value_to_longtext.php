<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cột `value` của ebmr_run_data trước đây là kiểu JSON, nhưng dữ liệu nhập liệu nay
 * được MÃ HOÁ (RunDataEncryptionService::encryptJson -> Crypt::encryptString) nên
 * chuỗi lưu vào là ciphertext base64 — KHÔNG phải JSON hợp lệ. MySQL/MariaDB áp
 * ràng buộc json_valid() lên cột JSON khiến mọi INSERT thất bại:
 *   SQLSTATE[23000] ... CONSTRAINT `ebmr_run_data.value` failed
 * `value` chỉ là cột lưu trữ (đường đọc hiển thị dùng `raw_value` kiểu TEXT), không
 * còn được truy vấn như JSON, nên đổi sang LONGTEXT để chứa ciphertext.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `ebmr_run_data` MODIFY `value` LONGTEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `ebmr_run_data` MODIFY `value` JSON NULL');
    }
};
