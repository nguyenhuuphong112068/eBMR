<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kích thước con dấu (%): 100 = chuẩn, cho phép phóng to/thu nhỏ 50–200%.
     * Áp dụng bằng font-size của khung dấu (các dòng bên trong dùng đơn vị em).
     */
    public function up(): void
    {
        Schema::table('seals', function (Blueprint $table) {
            $table->unsignedSmallInteger('size')->default(100)->after('border_style');
        });
    }

    public function down(): void
    {
        Schema::table('seals', function (Blueprint $table) {
            $table->dropColumn('size');
        });
    }
};
