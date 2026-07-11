<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cho phép thiết kế format con dấu theo ý (như dấu mộc thật):
     *  - header: dòng tiêu đề phía trên (nhỏ, ngăn cách bằng kẻ ngang) — tuỳ chọn
     *  - content: dòng nội dung chính (to, đậm) — đã có sẵn
     *  - footer: dòng phụ phía dưới (VD "Ngày……tháng……năm 20……") — tuỳ chọn
     *  - border_style: kiểu viền khung dấu (single = viền đơn, double = viền đôi)
     */
    public function up(): void
    {
        Schema::table('seals', function (Blueprint $table) {
            $table->string('header')->nullable()->after('name');
            $table->string('footer')->nullable()->after('content');
            $table->string('border_style', 20)->default('double')->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('seals', function (Blueprint $table) {
            $table->dropColumn(['header', 'footer', 'border_style']);
        });
    }
};
