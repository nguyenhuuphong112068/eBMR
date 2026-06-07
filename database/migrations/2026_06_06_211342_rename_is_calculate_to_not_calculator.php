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
        Schema::table('formula_preparation', function (Blueprint $table) {
            $table->dropColumn('is_calculate');
            $table->boolean('not_calculator')->default(0)->after('total_amount_per_batch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('formula_preparation', function (Blueprint $table) {
            $table->dropColumn('not_calculator');
            $table->boolean('is_calculate')->default(1)->after('total_amount_per_batch');
        });
    }
};
