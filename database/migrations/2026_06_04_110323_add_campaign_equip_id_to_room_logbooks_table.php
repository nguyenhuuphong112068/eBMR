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
        Schema::table('room_logbooks', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_equip_id')->nullable()->after('campaign_id')->comment('ID CleaningEquipCampaign');
        });
    }

    public function down(): void
    {
        Schema::table('room_logbooks', function (Blueprint $table) {
            $table->dropColumn('campaign_equip_id');
        });
    }
};
