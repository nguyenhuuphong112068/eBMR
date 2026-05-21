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
        Schema::create('testing', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ebmr_templace_id');
            $table->string('stage');
            $table->integer('stt')->default(1);
            $table->string('name');
            $table->text('specifictions')->nullable(); // Stores JSON array of specifications
            $table->text('limits')->nullable();        // Stores JSON object {operator: '...', value: '...'}
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('ebmr_templace_id')
                ->references('id')
                ->on('ebmr_templates')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testing');
    }
};
