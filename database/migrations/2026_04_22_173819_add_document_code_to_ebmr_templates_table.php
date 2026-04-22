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
            if (!Schema::hasColumn('ebmr_templates', 'document_code')) {
                $table->string('document_code')->after('name')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ebmr_templates', function (Blueprint $table) {
            $table->dropColumn('document_code');
        });
    }
};
