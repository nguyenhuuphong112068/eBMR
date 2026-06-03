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
        Schema::table('room_logbooks', function (Blueprint $table) {
            $table->integer('equipment_id')->nullable()->index()->after('room_id')->comment('ID Thiết bị nếu là nhãn thiết bị');
            $table->string('stage')->nullable()->after('batch_number')->comment('Công đoạn');
            $table->string('lot_number')->nullable()->after('stage')->comment('Số mẻ');
            $table->dateTime('to_be_cleaned_before')->nullable()->after('clean_expiry_date')->comment('Hạn chót phải vệ sinh');
            $table->integer('checked_by')->nullable()->after('created_by')->comment('ID Người kiểm tra vệ sinh');
            $table->string('next_product_name')->nullable()->after('checked_by')->comment('Tên sản phẩm lô tiếp theo');
            $table->string('next_batch_number')->nullable()->after('next_product_name')->comment('Số lô tiếp theo');
            $table->integer('attached_by')->nullable()->after('next_batch_number')->comment('Người thực hiện đính kèm nhãn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_logbooks', function (Blueprint $table) {
            $table->dropColumn([
                'equipment_id',
                'stage',
                'lot_number',
                'to_be_cleaned_before',
                'checked_by',
                'next_product_name',
                'next_batch_number',
                'attached_by'
            ]);
        });
    }
};
