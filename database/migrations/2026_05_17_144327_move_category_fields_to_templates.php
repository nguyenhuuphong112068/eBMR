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
        Schema::table('ebmr_templates', function (Blueprint $table) {
            $table->smallInteger('dosage_id')->unsigned()->nullable();
            $table->double('avg_core')->unsigned()->nullable();
            $table->double('average_unit_weight')->unsigned()->nullable();
            $table->string('API_name', 255)->nullable();
            $table->string('content', 30)->nullable();
            $table->text('description')->nullable();
            $table->text('storage_conditions')->nullable();
            $table->string('product_type', 100)->nullable();
            
            $table->boolean('weight_1')->default(0);
            $table->boolean('weight_2')->default(0);
            $table->boolean('prepering')->default(0);
            $table->boolean('blending')->default(0);
            $table->boolean('forming')->default(0);
            $table->boolean('coating')->default(0);
            
            $table->double('quarantine_total')->nullable();
            $table->double('quarantine_weight')->nullable();
            $table->double('quarantine_preparing')->nullable();
            $table->double('quarantine_blending')->nullable();
            $table->double('quarantine_forming')->nullable();
            $table->double('quarantine_coating')->nullable();
        });

        Schema::table('intermediate_category', function (Blueprint $table) {
            $table->dropColumn([
                'dosage_id', 'avg_core', 'average_unit_weight', 'API_name', 'content', 
                'description', 'storage_conditions', 'type', 'weight_1', 'weight_2', 
                'prepering', 'blending', 'forming', 'coating', 'quarantine_total', 
                'quarantine_weight', 'quarantine_preparing', 'quarantine_blending', 
                'quarantine_forming', 'quarantine_coating'
            ]);
        });

        Schema::table('preparation_formula', function (Blueprint $table) {
            $table->renameColumn('intermediate_category_id', 'ebmr_templates_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preparation_formula', function (Blueprint $table) {
            $table->renameColumn('ebmr_templates_id', 'intermediate_category_id');
        });

        Schema::table('ebmr_templates', function (Blueprint $table) {
            $table->dropColumn([
                'dosage_id', 'avg_core', 'average_unit_weight', 'API_name', 'content', 
                'description', 'storage_conditions', 'product_type', 'weight_1', 'weight_2', 
                'prepering', 'blending', 'forming', 'coating', 'quarantine_total', 
                'quarantine_weight', 'quarantine_preparing', 'quarantine_blending', 
                'quarantine_forming', 'quarantine_coating'
            ]);
        });
    }
};
