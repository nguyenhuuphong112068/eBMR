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
        Schema::table('instrument_logbooks', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->nullable()->after('instrument_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instrument_logbooks', function (Blueprint $table) {
            $table->dropColumn('campaign_id');
        });
    }
};
