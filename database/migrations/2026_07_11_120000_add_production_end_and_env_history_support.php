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
        // Mốc "Kết thúc sản xuất" — trước đây hệ thống chỉ có started_at (bắt đầu), chưa
        // có điểm dừng vật lý nào cho 1 phiên sản xuất tại phòng. Đây là điều kiện biên
        // bắt buộc để "Lịch sử môi trường sản xuất" biết dừng ghi nhận ở đâu.
        Schema::table('ebmr_record_distributions', function (Blueprint $table) {
            $table->timestamp('production_ended_at')->nullable()->after('clearance_completed_at');
            $table->unsignedBigInteger('ended_by')->nullable()->after('production_ended_at');
        });

        // ebmr_records_bms đã được thiết kế sẵn đúng mục đích (temperature/humidity/
        // pressure/captured_at/is_out_of_bounds/recorded_type) nhưng chưa từng dùng —
        // chỉ thiếu 1 cột liên kết tới ĐÚNG phiên sản xuất (distribution) đang ghi nhận.
        // Không đổi nullability của ebmr_record_id/stage_code (dự án không có
        // doctrine/dbal nên không dùng ->change()); vẫn tiếp tục điền 2 cột đó bình
        // thường vì luôn có sẵn từ distribution, chỉ bổ sung distribution_id làm khoá
        // liên kết chính xác cho từng phiên.
        Schema::table('ebmr_records_bms', function (Blueprint $table) {
            $table->unsignedBigInteger('distribution_id')->nullable()->after('room_id');
            $table->index(['distribution_id', 'captured_at']);
        });

        // Bảng cấu hình chung dạng key-value — nền tảng cho trang "Chính Sách Chung",
        // bắt đầu với tần suất ghi nhận môi trường, sẽ mở rộng thêm tham số khác sau.
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('label')->nullable();
            $table->string('type')->default('string'); // string | integer | select...
            $table->text('description')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::table('ebmr_records_bms', function (Blueprint $table) {
            $table->dropColumn('distribution_id');
        });
        Schema::table('ebmr_record_distributions', function (Blueprint $table) {
            $table->dropColumn(['production_ended_at', 'ended_by']);
        });
    }
};
