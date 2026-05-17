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
        Schema::table('intermediate_category', function (Blueprint $table) {
            if (Schema::hasColumn('intermediate_category', 'quarantine_time_unit')) {
                $table->dropColumn('quarantine_time_unit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intermediate_category', function (Blueprint $table) {
            $table->boolean('quarantine_time_unit')->default(1);
        });
    }
};
