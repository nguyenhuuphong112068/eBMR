<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mốc "Bắt đầu sản xuất" của từng công đoạn đã phân phối: chỉ được set khi
        // phòng + toàn bộ thiết bị đạt điều kiện vệ sinh & dọn quang tại thời điểm bấm
        // nút. Sau mốc này quyền ghi chép gắn với phiên sản xuất (không kiểm tra lại
        // vệ sinh nữa vì phòng đã chuyển sang trạng thái 'producing').
        Schema::table('ebmr_record_distributions', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('distributed_by');
            $table->unsignedBigInteger('started_by')->nullable()->after('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ebmr_record_distributions', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'started_by']);
        });
    }
};
