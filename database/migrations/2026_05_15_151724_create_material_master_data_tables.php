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
        Schema::create('material_role', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('material_spec', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_spec');
        Schema::dropIfExists('material_role');
    }
};
