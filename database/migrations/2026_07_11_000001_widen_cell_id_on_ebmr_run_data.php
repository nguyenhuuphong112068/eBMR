<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nới cell_id 50 -> 100 ký tự: tính năng "Gạch chéo Không sử dụng" (N/A) lưu
     * mỗi vùng gạch dưới block_uuid='__na__' với cell_id là targetKey
     * "<blockId>:<r>_<c>". Block thuộc GF liên kết có id dạng
     * "<hostBlockId>__gf<blockId>" nên targetKey có thể vượt 50 ký tự.
     */
    public function up(): void
    {
        Schema::table('ebmr_run_data', function (Blueprint $table) {
            $table->string('cell_id', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ebmr_run_data', function (Blueprint $table) {
            $table->string('cell_id', 50)->nullable()->change();
        });
    }
};
