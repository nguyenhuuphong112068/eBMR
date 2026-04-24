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
            $table->string('type', 100)->nullable()->after('dosage_id')->comment('Phân loại: Thuốc Kê Đơn, Thuốc Không Kế Đơn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intermediate_category', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
