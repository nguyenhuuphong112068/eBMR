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
        Schema::create('manu_condition', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->string('name')->comment('Tên bộ điều kiện (ví dụ: Tên sản phẩm, mã sản phẩm)');
            
            // Nhiệt độ
            $table->string('temp_op_1', 10)->nullable();
            $table->double('temp_val1_1')->nullable();
            $table->double('temp_val2_1')->nullable();
            $table->double('temp_min_1')->nullable();
            $table->double('temp_max_1')->nullable();
            
            $table->string('temp_op_2', 10)->nullable();
            $table->double('temp_val1_2')->nullable();
            $table->double('temp_val2_2')->nullable();
            $table->double('temp_min_2')->nullable();
            $table->double('temp_max_2')->nullable();
            
            // Độ ẩm
            $table->string('humidity_op_1', 10)->nullable();
            $table->double('humidity_val1_1')->nullable();
            $table->double('humidity_val2_1')->nullable();
            $table->double('humidity_min_1')->nullable();
            $table->double('humidity_max_1')->nullable();
            
            $table->string('humidity_op_2', 10)->nullable();
            $table->double('humidity_val1_2')->nullable();
            $table->double('humidity_val2_2')->nullable();
            $table->double('humidity_min_2')->nullable();
            $table->double('humidity_max_2')->nullable();
            
            // Chênh áp phòng/hành lang (Pa)
            $table->string('diff_press_corridor_op', 10)->nullable();
            $table->double('diff_press_corridor_val1')->nullable();
            $table->double('diff_press_corridor_val2')->nullable();
            $table->double('diff_press_corridor_min')->nullable();
            $table->double('diff_press_corridor_max')->nullable();
            
            // Chênh áp phòng/PAL (Pa)
            $table->string('diff_press_pal_op', 10)->nullable();
            $table->double('diff_press_pal_val1')->nullable();
            $table->double('diff_press_pal_val2')->nullable();
            $table->double('diff_press_pal_min')->nullable();
            $table->double('diff_press_pal_max')->nullable();
            
            // Chênh áp phòng/MAL (Pa)
            $table->string('diff_press_mal_op', 10)->nullable();
            $table->double('diff_press_mal_val1')->nullable();
            $table->double('diff_press_mal_val2')->nullable();
            $table->double('diff_press_mal_min')->nullable();
            $table->double('diff_press_mal_max')->nullable();
            
            // Chênh lệch áp suất trước và sau lọc HEPA / Buồng cân
            $table->string('hepa_filter_op', 10)->nullable();
            $table->double('hepa_filter_val1')->nullable();
            $table->double('hepa_filter_val2')->nullable();
            $table->double('hepa_filter_min')->nullable();
            $table->double('hepa_filter_max')->nullable();
            
            $table->text('note')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->foreign('room_id')->references('id')->on('room')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manu_condition');
    }
};
