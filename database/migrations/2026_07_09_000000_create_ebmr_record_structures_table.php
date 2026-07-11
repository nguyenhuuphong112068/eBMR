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
        // Cấu trúc PHÁT SINH LÚC THỰC THI của từng lô (không có trong BMR gốc):
        // hiện dùng cho các dòng do người dùng "Thêm dòng (Cấp 2)" tạo thêm trên bảng.
        // Mỗi dòng = 1 overlay cho 1 block của 1 lô — template gốc (ebmr_template_blocks)
        // KHÔNG bị đụng tới; mỗi lô ban hành từ cùng 1 template có overlay riêng.
        Schema::create('ebmr_record_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('ebmr_records')->onDelete('cascade');
            $table->string('block_uuid', 100); // id client của block (nằm trong ebmr_template_blocks.properties)
            $table->string('kind', 30)->default('dynamic_rows'); // dành sẵn cho loại cấu trúc phát sinh khác sau này
            $table->longText('payload'); // JSON: { rows: [...], rowHeights: [...], fieldsConfig: {...} }
            $table->timestamps();

            // 1 block của 1 lô chỉ có đúng 1 overlay mỗi loại — thêm/xoá dòng sẽ update dòng này.
            $table->unique(['record_id', 'block_uuid', 'kind'], 'ebmr_record_structures_uniq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebmr_record_structures');
    }
};
