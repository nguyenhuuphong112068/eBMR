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
        Schema::table('ebmr_template_blocks', function (Blueprint $blueprint) {
            $blueprint->dropColumn('section_id');
        });
        Schema::table('ebmr_template_blocks', function (Blueprint $blueprint) {
            $blueprint->string('section_id', 50)->nullable()->after('template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ebmr_template_blocks', function (Blueprint $blueprint) {
            $blueprint->dropColumn('section_id');
        });
        Schema::table('ebmr_template_blocks', function (Blueprint $blueprint) {
            $blueprint->unsignedBigInteger('section_id')->nullable()->after('template_id');
        });
    }
};
