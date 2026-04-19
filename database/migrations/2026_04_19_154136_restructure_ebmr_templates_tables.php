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
        Schema::dropIfExists('ebmr_records');
        Schema::dropIfExists('ebmr_templates');

        // --- MASTER DATA: TEMPLATES (Cấp 1) ---
        Schema::create('ebmr_templates', function (Blueprint $table) {
            $table->id();
            $table->string('document_code')->nullable(); // Mã hồ sơ
            $table->string('edition')->nullable(); // Ấn bản
            $table->string('name'); // Tên hồ sơ
            $table->unsignedBigInteger('owner_id')->nullable(); // Trưởng phòng/Người tạo
            $table->date('effective_date')->nullable(); // Ngày hiệu lực
            $table->string('dosage_form')->nullable(); // Dạng bào chế
            $table->string('batch_size')->nullable(); // Cỡ lô
            $table->string('status')->default('draft'); // draft, active, archived
            $table->json('schema')->nullable(); // Để tương thích với Designer hiện tại
            $table->timestamps();
        });

        // --- LEVEL 2: SECTIONS (Phân đoạn) ---
        Schema::create('ebmr_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('ebmr_templates')->onDelete('cascade');
            $table->string('name'); // Ví dụ: 1. Thông tin chung
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // --- LEVEL 3: CARDS (Thẻ/Trang) ---
        Schema::create('ebmr_template_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('ebmr_template_sections')->onDelete('cascade');
            $table->string('name'); // Ví dụ: 1.1 Chuẩn bị nguyên liệu
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // --- LEVEL 4: BLOCKS (Khối văn bản/Bảng/Ký) ---
        Schema::create('ebmr_template_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('ebmr_template_cards')->onDelete('cascade');
            $table->string('type'); // 'static-text', 'table', 'signature', ...
            $table->string('label')->nullable();
            $table->integer('order')->default(0);
            $table->longText('content')->nullable(); // Dùng cho static-text
            $table->json('properties')->nullable(); // Cấu hình bảng (số cột, dòng, cell background...)
            $table->timestamps();
        });

        // ==========================================
        // --- EXECUTION: RUNS (Sinh Lô thực tế) ---
        Schema::create('ebmr_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('ebmr_templates')->onDelete('restrict');
            $table->string('batch_number'); // Số lô
            $table->string('product_id')->nullable(); // Liên kết tới sản phẩm
            $table->string('status')->default('started'); // started, in_progress, completed, reviewed
            $table->timestamps();
        });

        // --- EXECUTION: RUN DATA (Dữ liệu nhập) ---
        Schema::create('ebmr_run_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('ebmr_runs')->onDelete('cascade');
            $table->foreignId('block_id')->constrained('ebmr_template_blocks')->onDelete('cascade');
            $table->string('filled_by')->nullable(); // Ai điền
            $table->timestamp('filled_at')->nullable(); // Điền lúc nào
            $table->json('value')->nullable(); // Dữ liệu nhúng JSON (ô tương ứng) hoặc chuỗi text
            $table->timestamps();
            
            // Một khối trong một lô thường chỉ có 1 data entry chính (có thể lưu log history riêng)
            $table->unique(['run_id', 'block_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebmr_run_data');
        Schema::dropIfExists('ebmr_runs');
        Schema::dropIfExists('ebmr_template_blocks');
        Schema::dropIfExists('ebmr_template_cards');
        Schema::dropIfExists('ebmr_template_sections');
        Schema::dropIfExists('ebmr_templates');
    }
};
