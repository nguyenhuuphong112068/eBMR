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
        Schema::create('equipment_in_room', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('equipment_id');
            $table->string('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('room_id')->references('id')->on('room')->onDelete('cascade');
            $table->foreign('equipment_id')->references('id')->on('instrument')->onDelete('cascade');
            $table->unique(['room_id', 'equipment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_in_room');
    }
};
