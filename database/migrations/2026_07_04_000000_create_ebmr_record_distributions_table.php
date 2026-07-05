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
        Schema::create('ebmr_record_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('ebmr_records')->onDelete('cascade');
            $table->string('section_id'); // Khớp với ebmr_template_blocks.section_id / ebmr_template_rooms.section_id
            $table->string('section_label')->nullable(); // Cache tên công đoạn để hiển thị nhanh
            $table->unsignedBigInteger('room_id'); // Tham chiếu tới pms.room.id (khác DB connection nên không đặt FK)
            $table->string('room_code')->nullable(); // Cache mã phòng
            $table->string('room_name')->nullable(); // Cache tên phòng
            $table->json('user_ids')->nullable(); // Danh sách user được phép ghi chép công đoạn này của lô này
            $table->unsignedBigInteger('distributed_by')->nullable(); // Người thực hiện phân phối
            $table->timestamps();

            // Mỗi công đoạn của 1 lô chỉ được phân phối tới đúng 1 phòng tại 1 thời điểm;
            // phân phối lại sẽ cập nhật (update) dòng này thay vì tạo thêm dòng mới.
            $table->unique(['record_id', 'section_id'], 'ebmr_record_distributions_uniq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebmr_record_distributions');
    }
};
