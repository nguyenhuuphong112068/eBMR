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
        Schema::create('stage_production', function (Blueprint $table) {
            $table->id();
            $table->string('workshop_code'); // e.g. PXV1, PXV2, PXVH, PXTN, PXDN
            $table->string('stage_name'); // e.g. Cân NL
            $table->json('stage_codes'); // e.g. [1] or [3,4]
            $table->string('icon_class')->nullable(); // e.g. fa-balance-scale
            $table->string('gradient_class')->nullable(); // e.g. gradient-blue
            $table->string('description')->nullable();
            $table->integer('order_num')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stage_production');
    }
};
