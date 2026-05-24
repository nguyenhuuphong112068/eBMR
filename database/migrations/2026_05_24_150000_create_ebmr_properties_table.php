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
        Schema::create('ebmr_properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ebmr_templace_id');
            $table->string('name');
            $table->text('value')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->foreign('ebmr_templace_id')
                ->references('id')
                ->on('ebmr_templates')
                ->onDelete('cascade');
            
            $table->unique(['ebmr_templace_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebmr_properties');
    }
};
