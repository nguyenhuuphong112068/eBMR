<?php
/*******************************************************************************
 * Migration: Add connection fields to instrument table
 * eBMR Project - Scale integration enhancement
 ******************************************************************************/

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
        Schema::table('instrument', function (Blueprint $table) {
            $table->string('type', 50)->default('other')->after('stage_id')->comment('scale = Cân, other = Khác');
            $table->string('connection_type', 50)->nullable()->after('type')->comment('serial = Cáp vật lý, websocket = WebSocket (Wifi)');
            $table->string('ip', 100)->nullable()->after('connection_type');
            $table->string('port', 10)->nullable()->after('ip');
            $table->string('brand', 50)->nullable()->after('port')->comment('and = A&D, mettler = Mettler Toledo, sartorius = Sartorius, custom = Tùy chỉnh');
            $table->integer('baud_rate')->nullable()->after('brand');
            $table->integer('data_bits')->nullable()->after('baud_rate');
            $table->string('parity', 20)->nullable()->after('data_bits');
            $table->integer('stop_bits')->nullable()->after('parity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instrument', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'connection_type',
                'ip',
                'port',
                'brand',
                'baud_rate',
                'data_bits',
                'parity',
                'stop_bits'
            ]);
        });
    }
};
