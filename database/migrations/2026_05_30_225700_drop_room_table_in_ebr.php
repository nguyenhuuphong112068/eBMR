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
        // 1. Drop foreign key constraints referencing 'room' table in ebr
        Schema::table('equipment_in_room', function (Blueprint $table) {
            $table->dropForeign('equipment_in_room_room_id_foreign');
        });

        Schema::table('manu_condition', function (Blueprint $table) {
            $table->dropForeign('manu_condition_room_id_foreign');
        });

        Schema::table('Realated_Form_of_room', function (Blueprint $table) {
            $table->dropForeign('realated_form_of_room_room_id_foreign');
        });

        // 2. Drop 'room' table in ebr database
        Schema::dropIfExists('room');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-create 'room' table schema
        Schema::create('room', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('main_equiment_name')->nullable();
            $table->double('capacity')->nullable();
            $table->string('stage');
            $table->integer('stage_code');
            $table->boolean('sheet_1')->default(0);
            $table->boolean('sheet_2')->default(0);
            $table->boolean('sheet_3')->default(0);
            $table->boolean('sheet_regular')->default(0);
            $table->string('production_group');
            $table->integer('group_code');
            $table->boolean('active')->default(1);
            $table->integer('order_by')->nullable();
            $table->string('deparment_code', 5);
            $table->string('prepareBy')->nullable();
            $table->timestamps();
        });

        // Re-add foreign key constraints
        Schema::table('equipment_in_room', function (Blueprint $table) {
            $table->foreign('room_id')->references('id')->on('room')->onDelete('cascade');
        });

        Schema::table('manu_condition', function (Blueprint $table) {
            $table->foreign('room_id')->references('id')->on('room')->onDelete('cascade');
        });

        Schema::table('Realated_Form_of_room', function (Blueprint $table) {
            $table->foreign('room_id')->references('id')->on('room')->onDelete('cascade');
        });
    }
};
