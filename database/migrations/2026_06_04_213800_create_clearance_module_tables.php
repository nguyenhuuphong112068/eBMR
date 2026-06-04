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
        Schema::create('clearance_room_processes_list', function (Blueprint $table) {
            $table->id();
            $table->string('room_id');
            $table->string('process_code')->nullable();
            $table->string('process_name')->nullable();
            $table->tinyInteger('clearance_type')->default(1)->comment('1: Cấp 1, 2: Cấp 2');
            $table->integer('version')->default(1);
            $table->string('status')->default('draft'); // draft, submitted, approved, active, expired
            $table->integer('created_by')->nullable();
            $table->date('effective_date')->nullable();
            $table->timestamps();
        });

        Schema::create('clearance_room_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_list_id')->constrained('clearance_room_processes_list')->onDelete('cascade');
            $table->integer('step')->default(1);
            $table->longText('content')->nullable();
            $table->longText('standard')->nullable();
            $table->timestamps();
        });

        Schema::create('clearance_process_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_list_id')->constrained('clearance_room_processes_list')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->string('role')->default('reviewer'); // reviewer, approver, authorizer
            $table->integer('step_order')->default(1);
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->timestamps();
        });

        Schema::create('clearance_room_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->foreignId('process_list_id')->constrained('clearance_room_processes_list')->onDelete('cascade');
            $table->string('status')->default('in_progress'); // in_progress, completed
            $table->json('employee_ids')->nullable(); // Thêm nếu cần lưu người thực hiện
            $table->unsignedBigInteger('started_by')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('clearance_room_campaign_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('clearance_room_campaigns')->onDelete('cascade');
            $table->unsignedBigInteger('process_step_id');
            $table->integer('step')->default(1);
            $table->boolean('is_done')->default(false);
            $table->boolean('is_passed')->default(false);
            $table->text('result_note')->nullable();
            $table->longText('attached_images')->nullable();
            $table->unsignedBigInteger('done_by')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clearance_room_campaign_steps');
        Schema::dropIfExists('clearance_room_campaigns');
        Schema::dropIfExists('clearance_process_workflows');
        Schema::dropIfExists('clearance_room_processes');
        Schema::dropIfExists('clearance_room_processes_list');
    }
};
