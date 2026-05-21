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
        Schema::create('testing_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('testing_id');
            $table->string('image_path');
            $table->string('image_name');
            $table->text('image_description')->nullable();
            $table->timestamps();

            $table->foreign('testing_id')
                ->references('id')
                ->on('testing')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testing_images');
    }
};
