<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // testing_images
        DB::statement("UPDATE testing_images SET image_path = REPLACE(image_path, '/img/testing_img/', '/upLoadData/img/testing/')");
        DB::statement("UPDATE testing_images SET image_path = REPLACE(image_path, '/uploads/testing/', '/upLoadData/img/testing/')");

        // cleaning_room_processes
        DB::statement("UPDATE cleaning_room_processes SET content = REPLACE(content, '/cleaning_images/', '/upLoadData/img/cleaning_process/') WHERE content IS NOT NULL");
        DB::statement("UPDATE cleaning_room_processes SET standard = REPLACE(standard, '/cleaning_images/', '/upLoadData/img/cleaning_process/') WHERE standard IS NOT NULL");

        // cleaning_equip_processes
        DB::statement("UPDATE cleaning_equip_processes SET content = REPLACE(content, '/cleaning_images/', '/upLoadData/img/cleaning_process/') WHERE content IS NOT NULL");
        DB::statement("UPDATE cleaning_equip_processes SET standard = REPLACE(standard, '/cleaning_images/', '/upLoadData/img/cleaning_process/') WHERE standard IS NOT NULL");

        // cleaning_room_campaign_steps
        DB::statement("UPDATE cleaning_room_campaign_steps SET attached_images = REPLACE(attached_images, '/uploads/cleaning_attachments/', '/upLoadData/img/cleaning_result/') WHERE attached_images IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // down logic can be skipped as it's a one-way cleanup or written reversely
    }
};
