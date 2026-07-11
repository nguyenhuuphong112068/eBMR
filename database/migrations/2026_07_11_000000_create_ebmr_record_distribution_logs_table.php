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
        // Bảng lịch sử phân phối (append-only): mỗi lần bấm "Xác nhận Phân phối" cho 1 công
        // đoạn sẽ thêm 1 dòng mới ở đây, khác với ebmr_record_distributions chỉ giữ trạng
        // thái hiện tại (overwrite). Dùng để hiển thị "Lịch sử phân phối" trong modal.
        Schema::create('ebmr_record_distribution_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('ebmr_records')->onDelete('cascade');
            $table->string('section_id');
            $table->string('section_label')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('room_code')->nullable();
            $table->string('room_name')->nullable();
            $table->json('user_ids')->nullable();
            $table->unsignedBigInteger('distributed_by')->nullable();
            $table->timestamp('distributed_at')->nullable();
            $table->timestamps();

            $table->index(['record_id', 'section_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebmr_record_distribution_logs');
    }
};
