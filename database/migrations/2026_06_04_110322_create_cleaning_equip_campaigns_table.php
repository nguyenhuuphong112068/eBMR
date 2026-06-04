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
        Schema::create('cleaning_equip_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_id')->comment('ID thiết bị từ PMS instrument');
            $table->unsignedBigInteger('process_list_id')->comment('ID quy trình: cleaning_equip_processes_list');
            $table->unsignedBigInteger('room_campaign_id')->nullable()->comment('FK → cleaning_room_campaigns nếu VS cùng phòng');
            $table->string('clean_location')->default('in_room')->comment('in_room | clearing_room');
            $table->unsignedBigInteger('source_room_id')->nullable()->comment('Phòng xuất phát của thiết bị (PMS room.id)');
            $table->unsignedBigInteger('clearing_room_id')->nullable()->comment('FK → room_clearings, nếu clean_location=clearing_room');
            $table->string('status')->default('in_progress')->comment('in_progress | completed');
            $table->tinyInteger('cleaning_type')->default(1)->comment('1: Cấp 1, 2: Cấp 2, 3: Vệ sinh lại');
            $table->json('employee_ids')->nullable()->comment('Danh sách nhân viên thực hiện');
            $table->unsignedBigInteger('started_by')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cleaning_equip_campaigns');
    }
};
