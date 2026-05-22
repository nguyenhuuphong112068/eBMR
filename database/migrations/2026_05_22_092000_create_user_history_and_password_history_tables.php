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
        if (!Schema::hasTable('user_history')) {
            Schema::create('user_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('userName')->nullable();
                $table->string('userGroup')->nullable();
                $table->string('fullName')->nullable();
                $table->string('deparment')->nullable();
                $table->string('groupName')->nullable();
                $table->unsignedBigInteger('designation_id')->nullable();
                $table->string('mail')->nullable();
                $table->tinyInteger('isLocked')->default(0);
                $table->tinyInteger('isActive')->default(1);
                $table->date('changePWdate')->nullable();
                $table->string('prepareBy')->nullable();
                $table->longText('signature_image')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('pass_word_history')) {
            Schema::create('pass_word_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('passWord');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_history');
        Schema::dropIfExists('pass_word_history');
    }
};
