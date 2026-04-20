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
        Schema::table('ebmr_run_data', function (Blueprint $table) {
            $table->dropForeign(['run_id']);
            $table->dropUnique(['run_id', 'block_id']);
            $table->renameColumn('run_id', 'record_id');
        });

        Schema::table('ebmr_run_data', function (Blueprint $table) {
            $table->dropForeign(['block_id']);
            $table->dropColumn('block_id');
            $table->string('block_uuid')->after('record_id')->nullable();
        });

        Schema::table('ebmr_run_data', function (Blueprint $table) {
            $table->unique(['record_id', 'block_uuid']);
        });

        // Drop ebmr_runs since we use ebmr_records
        Schema::dropIfExists('ebmr_runs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way migration for simplicity in this context
    }
};
