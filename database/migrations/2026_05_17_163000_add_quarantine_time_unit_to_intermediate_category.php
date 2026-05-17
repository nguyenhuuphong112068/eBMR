<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intermediate_category', function (Blueprint $table) {
            if (!Schema::hasColumn('intermediate_category', 'quarantine_time_unit')) {
                $table->boolean('quarantine_time_unit')->default(1)->comment('1: Ngày, 0: Giờ');
            }
        });

        Schema::table('ebmr_templates', function (Blueprint $table) {
            if (Schema::hasColumn('ebmr_templates', 'quarantine_time_unit')) {
                $table->dropColumn('quarantine_time_unit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('intermediate_category', function (Blueprint $table) {
            if (Schema::hasColumn('intermediate_category', 'quarantine_time_unit')) {
                $table->dropColumn('quarantine_time_unit');
            }
        });
    }
};
