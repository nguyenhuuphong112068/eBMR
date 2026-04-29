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
        if (!Schema::hasTable('important_var')) {
            Schema::create('important_var', function (Blueprint $table) {
                $table->id();
                $table->string('name', 10);
                $table->string('description')->nullable();
                $table->timestamps();
            });

            DB::table('important_var')->insert([
                ['name' => 'CPP', 'description' => 'Critical Process Parameters'],
                ['name' => 'CMA', 'description' => 'Critical Material Attributes'],
            ]);
        }

        Schema::table('ebmr_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('ebmr_variants', 'important_var_id')) {
                $table->unsignedBigInteger('important_var_id')->nullable()->after('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ebmr_variants', function (Blueprint $table) {
            $table->dropColumn('important_var_id');
        });
        Schema::dropIfExists('important_var');
    }
};
