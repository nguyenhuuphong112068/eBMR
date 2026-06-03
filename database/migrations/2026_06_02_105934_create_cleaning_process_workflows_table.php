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
        Schema::create('cleaning_process_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // room, equipment
            $table->unsignedBigInteger('process_list_id');
            $table->string('role'); // reviewer, approver, authorizer
            $table->unsignedBigInteger('user_id');
            $table->integer('step_order')->default(1);
            $table->string('status')->default('pending');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cleaning_process_workflows');
    }
};
