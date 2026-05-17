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
        Schema::create('formula_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('preparation_formula_id');
            $table->string('code')->nullable();
            $table->text('name')->nullable();
            $table->text('manufacturer')->nullable();
            $table->string('Spec')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formula_materials');
    }
};
