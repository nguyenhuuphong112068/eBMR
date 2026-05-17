<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ebmr_templates', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('ebmr_templates', 'API_name')) {
                $columnsToDrop[] = 'API_name';
            }
            if (Schema::hasColumn('ebmr_templates', 'content')) {
                $columnsToDrop[] = 'content';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('ebmr_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('ebmr_templates', 'API_name')) {
                $table->string('API_name', 255)->nullable();
            }
            if (!Schema::hasColumn('ebmr_templates', 'content')) {
                $table->string('content', 30)->nullable();
            }
        });
    }
};
