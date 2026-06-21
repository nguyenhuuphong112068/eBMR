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
        Schema::table('formula_preparation', function (Blueprint $table) {
            $table->integer('number_of_lots')->nullable()->default(1);
            $table->decimal('amounts_of_lots', 15, 4)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('formula_preparation', function (Blueprint $table) {
            $table->dropColumn(['number_of_lots', 'amounts_of_lots']);
        });
    }
};
