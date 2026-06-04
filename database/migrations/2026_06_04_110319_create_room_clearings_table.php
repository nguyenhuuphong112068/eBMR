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
        Schema::create('room_clearings', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Mã phòng VS chung');
            $table->string('name')->comment('Tên phòng VS chung');
            $table->string('area')->nullable()->comment('Khu vực / Phân xưởng');
            $table->text('description')->nullable()->comment('Mô tả');
            $table->string('status')->default('active')->comment('active | inactive');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_clearings');
    }
};
