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
        Schema::table('preparation_formula', function (Blueprint $table) {
            $table->boolean('type')->default(0)->comment('0: nguyên liệu pha chế, 1: nguyên liệu bao phim/nang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preparation_formula', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
