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
        Schema::create('ebmr_records_bms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ebmr_record_id');
            $table->integer('stage_code');
            $table->integer('room_id');
            $table->decimal('temperature', 8, 2)->nullable();
            $table->decimal('humidity', 8, 2)->nullable();
            $table->decimal('pressure', 8, 2)->nullable();
            $table->boolean('is_out_of_bounds')->default(false);
            $table->dateTime('captured_at');
            $table->string('recorded_type')->default('auto'); // auto or manual
            $table->integer('created_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            // Indexes for fast retrieval
            $table->index(['ebmr_record_id', 'stage_code']);
            $table->index(['room_id', 'captured_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebmr_records_bms');
    }
};
