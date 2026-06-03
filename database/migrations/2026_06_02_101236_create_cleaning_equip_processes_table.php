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
        Schema::create('cleaning_equip_processes_list', function (Blueprint $table) {
            $table->id();
            $table->string('equipment_id');
            $table->string('process_code')->nullable();
            $table->string('process_name')->nullable();
            $table->integer('version')->default(1);
            $table->string('status')->default('draft'); // draft, submitted, approved, active, expired
            $table->integer('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('cleaning_equip_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_list_id')->constrained('cleaning_equip_processes_list')->onDelete('cascade');
            $table->integer('step')->default(1);
            $table->longText('content')->nullable();
            $table->longText('standard')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cleaning_equip_processes');
        Schema::dropIfExists('cleaning_equip_processes_list');
    }
};
