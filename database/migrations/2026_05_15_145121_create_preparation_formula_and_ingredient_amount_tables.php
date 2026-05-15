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
        Schema::create('preparation_formula', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('intermediate_category_id')->nullable();
            $table->string('code', 20)->nullable();
            $table->string('name', 512)->nullable();
            $table->string('role', 255)->nullable();
            $table->string('manufacturer', 512)->nullable();
            $table->string('Spec', 50)->nullable();
            $table->float('total_amount_per_unit')->nullable();
            $table->float('total_amount_per_batch')->nullable();
            $table->unsignedTinyInteger('version')->default(0);
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('ingredient_amount', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('preparation_formula_id')->nullable();
            $table->float('amount_per_unit')->nullable();
            $table->float('amount_per_batch')->nullable();
            $table->string('note', 255)->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_amount');
        Schema::dropIfExists('preparation_formula');
    }
};
