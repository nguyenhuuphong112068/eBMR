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
        Schema::create('ebmr_record_section_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('ebmr_records')->onDelete('cascade');
            $table->string('section_id'); // Khớp ebmr_template_blocks.section_id / ebmr_record_distributions.section_id
            $table->string('section_label')->nullable(); // Cache tên phân đoạn tại thời điểm đính kèm
            $table->string('title'); // Tên tài liệu do người dùng đặt
            $table->string('file_name'); // Tên file gốc
            $table->string('file_path'); // Đường dẫn public tương đối, vd /upLoadData/doc/ebmr_records/12/xxx.pdf
            $table->unsignedBigInteger('file_size')->nullable(); // bytes
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('uploaded_by_name')->nullable();
            $table->timestamps();

            $table->index(['record_id', 'section_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebmr_record_section_attachments');
    }
};
