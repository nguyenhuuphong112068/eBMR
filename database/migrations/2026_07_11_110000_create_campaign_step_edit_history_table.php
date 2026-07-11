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
        // Lịch sử sửa lại kết quả 1 bước ĐÃ xác nhận (Đạt/Không đạt) trong quy trình vệ
        // sinh/dọn quang phòng/thiết bị — dùng chung 1 bảng cho cả 4 loại campaign (phân
        // biệt qua step_type) thay vì 4 bảng gần như giống hệt nhau, theo đúng cách
        // ebmr_run_data_history đã dùng chung 1 bảng cho mọi block/cell của hồ sơ BMR.
        // Chỉ ghi khi SỬA (sau khi đã is_done/is_checked=true lần đầu) — lần xác nhận đầu
        // tiên không ghi lịch sử vì chưa có gì để "so sánh trước/sau".
        Schema::create('campaign_step_edit_history', function (Blueprint $table) {
            $table->id();
            $table->string('step_type'); // cleaning_room | cleaning_equip | clearance_room | clearance_equip
            $table->unsignedBigInteger('campaign_step_id');
            $table->boolean('old_is_passed')->nullable();
            $table->boolean('new_is_passed')->nullable();
            $table->text('old_note')->nullable();
            $table->text('new_note')->nullable();
            $table->json('old_images')->nullable();
            $table->json('new_images')->nullable();
            $table->text('reason'); // Lý do sửa — bắt buộc, phục vụ audit trail GMP
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();

            $table->index(['step_type', 'campaign_step_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_step_edit_history');
    }
};
