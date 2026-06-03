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
        Schema::table('cleaning_room_processes_list', function (Blueprint $table) {
            $table->tinyInteger('cleaning_type')->default(1)->comment('1: Cấp 1, 2: Cấp 2, 3: Vệ sinh lại');
        });

        Schema::table('cleaning_equip_processes_list', function (Blueprint $table) {
            $table->tinyInteger('cleaning_type')->default(1)->comment('1: Cấp 1, 2: Cấp 2, 3: Vệ sinh lại');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cleaning_room_processes_list', function (Blueprint $table) {
            $table->dropColumn('cleaning_type');
        });

        Schema::table('cleaning_equip_processes_list', function (Blueprint $table) {
            $table->dropColumn('cleaning_type');
        });
    }
};
