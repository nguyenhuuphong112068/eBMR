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
        Schema::rename('manu_condition', 'room_manufactured_condition');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('room_manufactured_condition', 'manu_condition');
    }
};
