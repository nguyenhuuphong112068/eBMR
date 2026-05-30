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
        Schema::create('Realated_Form_of_room', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('ebmr_templace_id');
            $table->string('type')->comment('line_clearance, cleaning, etc.');
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->foreign('room_id')->references('id')->on('room')->onDelete('cascade');
            $table->foreign('ebmr_templace_id')->references('id')->on('ebmr_templates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Realated_Form_of_room');
    }
};
