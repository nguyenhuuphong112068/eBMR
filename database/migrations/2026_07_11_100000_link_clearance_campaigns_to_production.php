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
        // Campaign dọn quang PHÒNG phát sinh khi bấm "Bắt đầu sản xuất" cần biết nó
        // thuộc phiên sản xuất (công đoạn/phòng) nào của lô nào, để: (a) chỉ hiện đúng
        // campaign cho đúng người được phân công, (b) khi hoàn tất thì chốt
        // clearance_completed_at trên ebmr_record_distributions tương ứng. NULL = dọn
        // quang không gắn với lô nào (không phát sinh qua luồng này).
        Schema::table('clearance_room_campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('distribution_id')->nullable()->after('room_id');
            $table->index('distribution_id');
        });

        // Campaign dọn quang THIẾT BỊ luôn phát sinh kèm 1 campaign phòng (mỗi thiết bị
        // trong phòng có 1 campaign riêng) — room_campaign_id là điểm neo duy nhất để
        // biết "các thiết bị nào cùng thuộc 1 lần dọn quang phòng X", khớp với UI
        // campaign_execute.blade.php đã có sẵn (đọc $equipCampaigns qua room_campaign_id).
        Schema::table('clearance_equip_campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('room_campaign_id')->nullable()->after('equipment_id');
            $table->index('room_campaign_id');
        });

        // clearance_equip_campaign_steps thiếu cột `step` (khác với bản phòng) — cần để
        // sắp xếp bước hiển thị đúng thứ tự, khớp cấu trúc clearance_room_campaign_steps.
        Schema::table('clearance_equip_campaign_steps', function (Blueprint $table) {
            $table->integer('step')->default(1)->after('process_step_id');
        });

        // Mốc hoàn tất TOÀN BỘ dọn quang (phòng + mọi thiết bị) của phiên sản xuất —
        // đây là điều kiện thật để mở khóa trang ghi chép, tách biệt với started_at
        // (mốc bấm "Bắt đầu sản xuất").
        Schema::table('ebmr_record_distributions', function (Blueprint $table) {
            $table->timestamp('clearance_completed_at')->nullable()->after('started_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ebmr_record_distributions', function (Blueprint $table) {
            $table->dropColumn('clearance_completed_at');
        });
        Schema::table('clearance_equip_campaign_steps', function (Blueprint $table) {
            $table->dropColumn('step');
        });
        Schema::table('clearance_equip_campaigns', function (Blueprint $table) {
            $table->dropColumn('room_campaign_id');
        });
        Schema::table('clearance_room_campaigns', function (Blueprint $table) {
            $table->dropColumn('distribution_id');
        });
    }
};
