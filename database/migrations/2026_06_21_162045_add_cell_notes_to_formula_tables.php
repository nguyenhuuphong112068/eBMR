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
            $table->json('cell_notes')->nullable();
        });

        Schema::table('formula_materials', function (Blueprint $table) {
            $table->json('cell_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('formula_preparation', function (Blueprint $table) {
            $table->dropColumn('cell_notes');
        });

        Schema::table('formula_materials', function (Blueprint $table) {
            $table->dropColumn('cell_notes');
        });
    }
};
