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
            if (!Schema::hasColumn('instrument', 'department_code')) {
                $table->string('department_code', 5)->nullable()->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instrument', function (Blueprint $table) {
            if (Schema::hasColumn('instrument', 'department_code')) {
                $table->dropColumn('department_code');
            }
        });
    }
};
