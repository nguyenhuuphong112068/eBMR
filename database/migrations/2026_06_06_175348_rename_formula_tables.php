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
        Schema::rename('preparation_formula', 'formula_preparation');
        Schema::rename('ingredient_amount', 'formula_ingredient_amount');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('formula_preparation', 'preparation_formula');
        Schema::rename('formula_ingredient_amount', 'ingredient_amount');
    }
};
