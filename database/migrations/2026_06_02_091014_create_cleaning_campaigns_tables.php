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
        Schema::create('cleaning_room_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');           // ID phòng (từ PMS DB)
            $table->unsignedBigInteger('process_list_id');   // ID quy trình (cleaning_room_processes_list)
            $table->string('status')->default('in_progress'); // in_progress | completed
            $table->unsignedBigInteger('started_by')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cleaning_room_campaign_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('cleaning_room_campaigns')->onDelete('cascade');
            $table->unsignedBigInteger('process_step_id');   // ID bước gốc (cleaning_room_processes)
            $table->integer('step')->default(1);             // Số thứ tự bước
            $table->boolean('is_done')->default(false);
            $table->text('result_note')->nullable();          // Ghi chú kết quả
            $table->unsignedBigInteger('done_by')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cleaning_room_campaign_steps');
        Schema::dropIfExists('cleaning_room_campaigns');
    }
};
