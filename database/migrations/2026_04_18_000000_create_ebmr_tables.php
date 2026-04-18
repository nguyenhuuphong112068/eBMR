<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ebmr_templates', function (Blueprint $col) {
            $col->id();
            $col->string('name');
            $col->json('schema'); // Chứa cấu trúc form (JSON)
            $col->string('category')->nullable();
            $col->timestamps();
        });

        Schema::create('ebmr_records', function (Blueprint $col) {
            $col->id();
            $col->foreignId('template_id')->constrained('ebmr_templates');
            $col->json('data'); // Chứa dữ liệu người dùng nhập (JSON)
            $col->unsignedBigInteger('created_by')->nullable();
            $col->string('status')->default('draft'); // draft, submitted, approved
            $col->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ebmr_records');
        Schema::dropIfExists('ebmr_templates');
    }
};
