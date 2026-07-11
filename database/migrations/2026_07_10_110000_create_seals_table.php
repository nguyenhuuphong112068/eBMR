<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dữ liệu gốc "Con Dấu": người dùng thiết kế các loại dấu (tên, nội dung, màu).
     * Khi ban hành hồ sơ lô có thể chọn 1 con dấu — dấu được đóng lên góc trên bên
     * phải của mỗi phân đoạn khi xem/ghi chép hồ sơ (ebmr_records.seal_id).
     */
    public function up(): void
    {
        Schema::create('seals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('content');
            $table->string('color', 20)->default('#dc3545');
            $table->boolean('active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::table('ebmr_records', function (Blueprint $table) {
            $table->unsignedBigInteger('seal_id')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('ebmr_records', function (Blueprint $table) {
            $table->dropColumn('seal_id');
        });
        Schema::dropIfExists('seals');
    }
};
