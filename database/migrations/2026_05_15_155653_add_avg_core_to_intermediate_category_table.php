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
            $table->float('avg_core')->unsigned()->nullable()->after('dosage_id');
            $table->float('average_unit_weight')->unsigned()->nullable()->after('avg_core');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intermediate_category', function (Blueprint $table) {
            $table->dropColumn(['avg_core', 'average_unit_weight']);
        });
    }
};
