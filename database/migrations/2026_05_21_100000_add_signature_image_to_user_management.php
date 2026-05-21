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
        if (!Schema::hasColumn('user_management', 'signature_image')) {
            Schema::table('user_management', function (Blueprint $table) {
                $table->longText('signature_image')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('user_management', 'signature_image')) {
            Schema::table('user_management', function (Blueprint $table) {
                $table->dropColumn('signature_image');
            });
        }
    }
};
