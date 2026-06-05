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
        // Rename existing room workflow table
        Schema::rename('clearance_process_workflows', 'clearance_room_process_workflows');

        // Equipment Processes List
        Schema::create('clearance_equip_processes_list', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_id');
            $table->string('process_code')->nullable();
            $table->string('process_name')->nullable();
            $table->tinyInteger('clearance_type')->default(1)->comment('1: Cấp 1, 2: Cấp 2');
            $table->integer('version')->default(1);
            $table->string('status')->default('draft');
            $table->integer('created_by')->nullable();
            $table->date('effective_date')->nullable();
            $table->timestamps();
        });

        // Equipment Processes Content
        Schema::create('clearance_equip_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_list_id')->constrained('clearance_equip_processes_list')->onDelete('cascade');
            $table->integer('step')->default(1);
            $table->longText('content')->nullable();
            $table->longText('standard')->nullable();
            $table->timestamps();
        });

        // Equipment Process Workflows
        Schema::create('clearance_equip_process_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_list_id')->constrained('clearance_equip_processes_list')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->string('role')->default('reviewer');
            $table->integer('step_order')->default(1);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        // Equipment Campaigns
        Schema::create('clearance_equip_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_id');
            $table->foreignId('process_list_id')->constrained('clearance_equip_processes_list')->onDelete('cascade');
            $table->string('status')->default('in_progress');
            $table->json('employee_ids')->nullable();
            $table->unsignedBigInteger('started_by')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // Equipment Campaign Steps
        Schema::create('clearance_equip_campaign_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('clearance_equip_campaigns')->onDelete('cascade');
            $table->foreignId('process_step_id')->constrained('clearance_equip_processes')->onDelete('cascade');
            $table->boolean('is_checked')->default(false);
            $table->boolean('is_passed')->default(false);
            $table->json('attached_images')->nullable();
            $table->unsignedBigInteger('checked_by')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->longText('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clearance_equip_campaign_steps');
        Schema::dropIfExists('clearance_equip_campaigns');
        Schema::dropIfExists('clearance_equip_process_workflows');
        Schema::dropIfExists('clearance_equip_processes');
        Schema::dropIfExists('clearance_equip_processes_list');
        Schema::rename('clearance_room_process_workflows', 'clearance_process_workflows');
    }
};
