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
        Schema::table('intermediate_category', function (Blueprint $table) {
            $table->string('API_name', 255)->nullable()->after('dosage_id');
            $table->string('content', 30)->nullable()->after('API_name');
            $table->text('description')->nullable()->after('content');
            $table->string('storage_conditions', 255)->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intermediate_category', function (Blueprint $table) {
            $table->dropColumn(['API_name', 'content', 'description', 'storage_conditions']);
        });
    }
};
