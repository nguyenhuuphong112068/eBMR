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
        // Ghim (pin) đúng ấn bản GF (biểu mẫu dùng chung) đang áp dụng cho 1 vị trí liên kết
        // (host_block_id = id của block linked-template trong BMR) của 1 lô cụ thể.
        // Trước khi có dòng ghim: BMR luôn tự động dùng bản GF active mới nhất theo doc_code.
        // Khi người dùng cấp 2 ghi dữ liệu lần đầu vào field thuộc GF đó, hệ thống chốt lại
        // đúng bản GF tại thời điểm đó — GF lên ấn bản khác sau này không ảnh hưởng lô đã chốt.
        Schema::create('ebmr_record_linked_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('ebmr_records')->onDelete('cascade');
            $table->unsignedBigInteger('host_block_id'); // ebmr_template_blocks.id của block linked-template trong BMR
            $table->unsignedBigInteger('gf_template_id'); // ebmr_templates.id của GF-version đã chốt
            $table->string('gf_doc_code', 50)->nullable();
            $table->smallInteger('gf_version')->nullable();
            $table->timestamp('pinned_at')->nullable();
            $table->unsignedBigInteger('pinned_by')->nullable();
            $table->timestamps();

            // 1 vị trí liên kết của 1 lô chỉ được chốt đúng 1 lần.
            $table->unique(['record_id', 'host_block_id'], 'ebmr_record_linked_templates_uniq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebmr_record_linked_templates');
    }
};
