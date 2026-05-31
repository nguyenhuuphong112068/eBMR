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
        Schema::create('room_logbooks', function (Blueprint $table) {
            $table->id();
            $table->integer('room_id')->index(); // Assuming room_id is INT to match pms.room
            $table->string('action_type'); // 'producing', 'cleaning', 'maintenance', 'idle'
            $table->string('product_name')->nullable();
            $table->string('batch_number')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->json('employee_ids');
            $table->string('clean_level')->nullable();
            $table->dateTime('clean_expiry_date')->nullable();
            $table->string('previous_status');
            $table->string('current_status')->nullable();
            $table->text('remarks')->nullable();
            $table->integer('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_logbooks');
    }
};
