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
        Schema::create('ebmr_template_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('ebmr_templates')->onDelete('cascade');
            $table->string('section_id');
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('condition_id')->nullable();
            $table->timestamps();

            $table->foreign('condition_id')->references('id')->on('manu_condition')->onDelete('set null');

            $table->unique(['template_id', 'section_id', 'room_id'], 'ebmr_tpl_rooms_uniq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebmr_template_rooms');
    }
};
