<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ebmr_run_data', function (Blueprint $table) {
            // Xóa index unique cũ
            // Lưu ý: Tên index thường là [table]_[columns]_unique
            $table->dropUnique('ebmr_run_data_record_id_block_uuid_unique');
            
            // Tạo index unique mới bao gồm cell_id
            // Chúng ta dùng index thay vì unique để linh hoạt hơn nếu cell_id có null (trong mysql unique cho phép nhiều null)
            // Tuy nhiên ở đây các cell_id đã được chuẩn hóa thành 'default' hoặc '1_2', nên dùng unique là tốt nhất.
            $table->unique(['record_id', 'block_uuid', 'cell_id'], 'ebmr_run_data_full_unique');
        });
    }

    public function down()
    {
        Schema::table('ebmr_run_data', function (Blueprint $table) {
            $table->dropUnique('ebmr_run_data_full_unique');
            $table->unique(['record_id', 'block_uuid'], 'ebmr_run_data_record_id_block_uuid_unique');
        });
    }
};
