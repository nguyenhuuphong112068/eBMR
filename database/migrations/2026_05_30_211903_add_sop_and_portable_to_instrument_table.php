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
            $table->string('operation_SOP_code', 50)->nullable();
            $table->string('clearing_SOP_code', 50)->nullable();
            $table->boolean('is_Portable_equipment')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instrument', function (Blueprint $table) {
            $table->dropColumn(['operation_SOP_code', 'clearing_SOP_code', 'is_Portable_equipment']);
        });
    }
};
