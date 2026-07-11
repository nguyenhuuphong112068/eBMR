<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 1 lô ban hành có thể đóng NHIỀU con dấu cùng lúc: thay cột seal_id (1 dấu)
     * bằng seal_ids (mảng JSON id dấu, giữ thứ tự người dùng chọn).
     * Dữ liệu cũ (seal_id đơn) được chuyển thành mảng 1 phần tử.
     */
    public function up(): void
    {
        Schema::table('ebmr_records', function (Blueprint $table) {
            $table->text('seal_ids')->nullable()->after('seal_id');
        });

        foreach (DB::table('ebmr_records')->whereNotNull('seal_id')->get() as $r) {
            DB::table('ebmr_records')->where('id', $r->id)
                ->update(['seal_ids' => json_encode([(int) $r->seal_id])]);
        }

        Schema::table('ebmr_records', function (Blueprint $table) {
            $table->dropColumn('seal_id');
        });
    }

    public function down(): void
    {
        Schema::table('ebmr_records', function (Blueprint $table) {
            $table->unsignedBigInteger('seal_id')->nullable()->after('status');
        });

        foreach (DB::table('ebmr_records')->whereNotNull('seal_ids')->get() as $r) {
            $ids = json_decode($r->seal_ids, true) ?: [];
            DB::table('ebmr_records')->where('id', $r->id)
                ->update(['seal_id' => $ids[0] ?? null]);
        }

        Schema::table('ebmr_records', function (Blueprint $table) {
            $table->dropColumn('seal_ids');
        });
    }
};
