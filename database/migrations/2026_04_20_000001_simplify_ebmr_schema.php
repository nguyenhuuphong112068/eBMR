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
        // 1. Adjust ebmr_template_blocks to be Level 2 (Directly under Template) BEFORE dropping parent tables
        Schema::table('ebmr_template_blocks', function (Blueprint $table) {
            // Drop old foreign key if it exists (might fail if not exists, but we know it's there from previous migration)
            if (Schema::hasColumn('ebmr_template_blocks', 'card_id')) {
                $table->dropForeign(['card_id']);
                $table->dropColumn('card_id');
            }
            
            // Add reference to Level 1
            if (!Schema::hasColumn('ebmr_template_blocks', 'template_id')) {
                $table->foreignId('template_id')->after('id')->constrained('ebmr_templates')->onDelete('cascade');
            }

            // Ensure we have a place for dynamic fields config
            if (!Schema::hasColumn('ebmr_template_blocks', 'fields_config')) {
                $table->json('fields_config')->nullable()->after('properties');
            }
        });

        // 2. Drop unused tables from the previous complex design
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('ebmr_template_cards');
        Schema::dropIfExists('ebmr_template_sections');
        Schema::enableForeignKeyConstraints();

        // 3. Remove the heavy 'schema' column from ebmr_templates to prevent duplication
        Schema::table('ebmr_templates', function (Blueprint $table) {
            if (Schema::hasColumn('ebmr_templates', 'schema')) {
                $table->dropColumn('schema');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ebmr_templates', function (Blueprint $table) {
            $table->json('schema')->nullable();
        });

        Schema::table('ebmr_template_blocks', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn(['template_id', 'fields_config']);
            $table->unsignedBigInteger('card_id')->nullable();
        });
    }
};
