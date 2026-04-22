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
        // 1. Add log_history to ebmr_templates
        Schema::table('ebmr_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('ebmr_templates', 'log_history')) {
                $table->boolean('log_history')->default(false)->after('status');
            }
        });

        // 2. Create ebmr_revision_history table
        if (!Schema::hasTable('ebmr_revision_history')) {
            Schema::create('ebmr_revision_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')->constrained('ebmr_templates')->onDelete('cascade');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('change_summary');
                $table->json('details')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebmr_revision_history');
        Schema::table('ebmr_templates', function (Blueprint $table) {
            $table->dropColumn('log_history');
        });
    }
};
