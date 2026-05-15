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
        Schema::create('ai_knowledge_base', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 255)->comment('Từ khóa kích hoạt kiến thức này');
            $table->text('content')->comment('Nội dung kiến thức AI cần học');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ai_unhandled_queries', function (Blueprint $table) {
            $table->id();
            $table->string('user_name', 255)->nullable();
            $table->text('query_text');
            $table->enum('status', ['pending', 'resolved'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_tables');
    }
};
