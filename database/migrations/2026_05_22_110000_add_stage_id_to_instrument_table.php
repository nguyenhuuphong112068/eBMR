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
        Schema::table('instrument', function (Blueprint $table) {
            $table->integer('stage_id')->nullable()->after('name')->comment('1 = cân, 3 = Pha chế, 4 = Trộn Hoàn Tất, 5 = Định Hình, 6 = Bao Phim, 7 = ĐGSC, 8 = ĐGTC');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instrument', function (Blueprint $table) {
            $table->dropColumn('stage_id');
        });
    }
};
