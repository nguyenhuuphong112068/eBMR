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
        Schema::table('ebmr_content_blocks', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id')->nullable()->after('ebmr_template_blocks_id');
            $table->string('section_id')->nullable()->after('template_id');
            $table->string('type')->nullable()->after('section_id');
            
            // Optional: Add index for faster filtering
            $table->index('template_id');
            $table->index('section_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ebmr_content_blocks', function (Blueprint $table) {
            $table->dropColumn(['template_id', 'section_id', 'type']);
        });
    }
};
