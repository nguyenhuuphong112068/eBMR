<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ebmr_template_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('ebmr_templates')->onDelete('cascade');
            $table->string('role'); // 'reviewer' (kiểm tra), 'approver' (phê duyệt), 'authorizer' (ban hành)
            $table->unsignedBigInteger('user_id');
            $table->integer('step_order'); // 1: Kiểm tra -> 2: Phê duyệt -> 3: Ban hành
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ebmr_template_workflows');
    }
};
