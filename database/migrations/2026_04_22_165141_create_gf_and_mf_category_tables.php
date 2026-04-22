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
        Schema::create('gf_category', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->string('relatived_sop_no', 50)->nullable();
            $table->boolean('active')->default(true);
            $table->string('status_code', 50)->default('Active');
            $table->string('created_by_code', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('mf_category', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->string('stage_name', 50)->nullable();
            $table->tinyInteger('stage_code')->nullable();
            $table->boolean('active')->default(true);
            $table->string('status_code', 50)->default('Active');
            $table->string('created_by_code', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gf_category');
        Schema::dropIfExists('mf_category');
    }
};
