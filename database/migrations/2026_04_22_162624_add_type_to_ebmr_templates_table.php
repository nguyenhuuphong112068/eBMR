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
        Schema::table('ebmr_templates', function (Blueprint $table) {
            $table->string('type', 10)->default('BMR')->after('id')->comment('GF, BMR, BPR, MF');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ebmr_templates', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
