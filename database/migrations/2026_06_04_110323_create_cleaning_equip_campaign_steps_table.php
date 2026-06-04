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
        Schema::create('cleaning_equip_campaign_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('cleaning_equip_campaigns')->onDelete('cascade');
            $table->unsignedBigInteger('process_step_id')->comment('ID bước gốc: cleaning_equip_processes');
            $table->integer('step')->default(1)->comment('Số thứ tự bước');
            $table->boolean('is_done')->default(false);
            $table->boolean('is_passed')->nullable()->comment('Kết quả: null=chưa xác nhận, true=Đạt, false=Không đạt');
            $table->text('result_note')->nullable()->comment('Ghi chú kết quả');
            $table->json('attached_images')->nullable()->comment('Hình ảnh đính kèm');
            $table->unsignedBigInteger('done_by')->nullable()->comment('Người xác nhận bước');
            $table->timestamp('done_at')->nullable()->comment('Thời gian xác nhận bước');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cleaning_equip_campaign_steps');
    }
};
