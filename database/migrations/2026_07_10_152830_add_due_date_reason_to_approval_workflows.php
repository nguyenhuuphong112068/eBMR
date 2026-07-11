<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'ebmr_template_workflows',
        'cleaning_process_workflows',
        'clearance_room_process_workflows',
        'clearance_equip_process_workflows',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->date('due_date')->nullable()->after('comment');
                $table->text('reason')->nullable()->after('due_date');
                $table->timestamp('reminder_sent_at')->nullable()->after('reason');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['due_date', 'reason', 'reminder_sent_at']);
            });
        }
    }
};
