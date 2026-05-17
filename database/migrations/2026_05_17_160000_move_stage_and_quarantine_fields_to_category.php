<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intermediate_category', function (Blueprint $table) {
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

        Schema::table('ebmr_templates', function (Blueprint $table) {
            $table->dropColumn([
                'weight_1', 'weight_2', 'prepering', 'blending', 'forming', 'coating', 
                'quarantine_total', 'quarantine_weight', 'quarantine_preparing', 'quarantine_blending', 
                'quarantine_forming', 'quarantine_coating'
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('ebmr_templates', function (Blueprint $table) {
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
                'weight_1', 'weight_2', 'prepering', 'blending', 'forming', 'coating', 
                'quarantine_total', 'quarantine_weight', 'quarantine_preparing', 'quarantine_blending', 
                'quarantine_forming', 'quarantine_coating'
            ]);
        });
    }
};
