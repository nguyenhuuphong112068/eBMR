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
        Schema::dropIfExists('ebmr_variants');
        
        Schema::create('ebmr_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id')->index();
            $table->string('section_id')->nullable();
            $table->unsignedBigInteger('block_id')->nullable();
            $table->string('field_key')->index(); // e.g., field_12345
            $table->string('name')->nullable();    // unique name for logic
            $table->string('label')->nullable();
            $table->string('type')->nullable();    // text, number, signature...
            $table->longText('config')->nullable(); // JSON for validation, options, formulas
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebmr_variants');
    }
};
