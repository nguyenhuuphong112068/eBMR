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
        // 1. Create ebmr_content_blocks table
        Schema::create('ebmr_content_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ebmr_template_blocks_id')->constrained('ebmr_template_blocks')->onDelete('cascade');
            $table->text('vi_contents')->nullable();
            $table->text('en_contents')->nullable();
            $table->timestamps();
        });

        // 2. We keep ebmr_template_blocks as is, but we will change how we store data in it.
        // content: HTML structure with placeholders for content block IDs
        // properties: JSON properties
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebmr_content_blocks');
    }
};
