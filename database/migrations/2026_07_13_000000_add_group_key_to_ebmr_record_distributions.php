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
        // Các công đoạn ĐÃ NỐI TRANG (noPageBreak — xem index() trong
        // EbmrExecutionController) luôn được phân phối cùng lúc tới cùng 1 phòng vì về mặt
        // vật lý chúng nằm chung 1 trang in. group_key neo các công đoạn đó lại với nhau
        // (giá trị = section_id của công đoạn "gốc"/đầu nhóm) để trang Sản Xuất gộp hiển
        // thị thành 1 card và các thao tác Bắt đầu/Kết thúc sản xuất áp dụng đồng thời cho
        // cả nhóm thay vì rời rạc từng công đoạn. NULL nghĩa là công đoạn đứng độc lập.
        Schema::table('ebmr_record_distributions', function (Blueprint $table) {
            $table->string('group_key')->nullable()->after('section_id');
            $table->index(['record_id', 'group_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ebmr_record_distributions', function (Blueprint $table) {
            $table->dropIndex(['record_id', 'group_key']);
            $table->dropColumn('group_key');
        });
    }
};
