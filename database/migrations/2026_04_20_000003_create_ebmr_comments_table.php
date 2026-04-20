<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ebmr_template_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('ebmr_templates')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->text('content');
            $table->string('selection_id')->nullable(); // For mapping to a specific HTML ID in the blocks
            $table->json('selection_data')->nullable(); // For storing range info if needed
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ebmr_template_comments');
    }
};
