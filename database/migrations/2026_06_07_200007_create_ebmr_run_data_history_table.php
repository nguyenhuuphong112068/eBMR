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
        Schema::create('ebmr_run_data_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ebmr_run_data_id')->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('block_uuid')->nullable();
            $table->string('cell_id')->nullable();
            $table->text('old_raw_value')->nullable();
            $table->text('new_raw_value')->nullable();
            $table->text('reason')->nullable();
            $table->string('changed_by')->nullable();
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();

            // Note: Since ebmr_run_data_id references ebmr_run_data, we can add a foreign key
            // However, due to previous migration complexities, we will just keep it as unsignedBigInteger to be safe
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebmr_run_data_history');
    }
};
