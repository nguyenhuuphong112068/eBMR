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
        if (Schema::hasTable('ebmr_runs') && !Schema::hasTable('ebmr_records')) {
            Schema::rename('ebmr_runs', 'ebmr_records');
        }

        Schema::table('ebmr_records', function (Blueprint $table) {
            if (!Schema::hasColumn('ebmr_records', 'batch_number')) {
                $table->string('batch_number')->nullable()->after('template_id');
            } else {
                $table->string('batch_number')->nullable()->change();
            }

            if (!Schema::hasColumn('ebmr_records', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('template_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ebmr_records', function (Blueprint $table) {
            $table->dropColumn(['batch_number', 'created_by']);
        });
    }
};
