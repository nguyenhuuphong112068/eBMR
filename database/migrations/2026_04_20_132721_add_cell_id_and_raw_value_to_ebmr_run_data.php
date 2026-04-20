<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ebmr_run_data', function (Blueprint $table) {
            $table->string('cell_id', 50)->nullable()->after('block_uuid');
            $table->text('raw_value')->nullable()->after('value');
        });
    }

    public function down()
    {
        Schema::table('ebmr_run_data', function (Blueprint $table) {
            $table->dropColumn(['cell_id', 'raw_value']);
        });
    }
};
