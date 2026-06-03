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
        Schema::table('cleaning_room_campaign_steps', function (Blueprint $table) {
            $table->json('attached_images')->nullable()->after('result_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cleaning_room_campaign_steps', function (Blueprint $table) {
            $table->dropColumn('attached_images');
        });
    }
};
