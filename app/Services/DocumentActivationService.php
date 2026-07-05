<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DocumentActivationService
{
    /**
     * Activate all issued documents that have reached their effective date.
     * This includes BMR templates and Cleaning/Clearance processes.
     */
    public static function activateAllIssuedDocuments()
    {
        $today = Carbon::today()->toDateString();

        // 1. Activate EBMR Templates
        $toActivateTemplates = DB::table('ebmr_templates')
            ->where('status', 'issued')
            ->whereNotNull('effective_date')
            ->whereDate('effective_date', '<=', $today)
            ->get();

        foreach ($toActivateTemplates as $t) {
            DB::table('ebmr_templates')->where('id', $t->id)->update([
                'status' => 'active',
                'updated_at' => now()
            ]);
            self::expirePreviousEbmrVersions($t->id);
        }

        // 2. Activate Cleaning and Clearance Processes
        $processTables = [
            'cleaning_room_processes_list' => ['type_col' => 'cleaning_type', 'parent_col' => 'room_id'],
            'cleaning_equip_processes_list' => ['type_col' => 'cleaning_type', 'parent_col' => 'equipment_id'],
            'clearance_room_processes_list' => ['type_col' => 'clearance_type', 'parent_col' => 'room_id']
        ];

        foreach ($processTables as $table => $cols) {
            $pending = DB::table($table)
                ->where('status', 'issued')
                ->whereDate('effective_date', '<=', $today)
                ->get();

            foreach ($pending as $process) {
                // First, expire any currently active processes of the same type for the same room/equipment
                DB::table($table)
                    ->where($cols['parent_col'], $process->{$cols['parent_col']})
                    ->where($cols['type_col'], $process->{$cols['type_col']})
                    ->where('id', '!=', $process->id)
                    ->where('status', 'active')
                    ->update(['status' => 'expired', 'updated_at' => now()]);

                // Then, activate the pending process
                DB::table($table)
                    ->where('id', $process->id)
                    ->update(['status' => 'active', 'updated_at' => now()]);
            }
        }
    }

    /**
     * Expire previous versions of the same category and type for BMR templates
     */
    public static function expirePreviousEbmrVersions($templateId)
    {
        $current = DB::table('ebmr_templates')->where('id', $templateId)->first();
        if (!$current) return;

        DB::table('ebmr_templates')
            ->where('caterogy_id', $current->caterogy_id)
            ->where('type', $current->type)
            ->where('version', '<', $current->version)
            ->where('status', 'active')
            ->update([
                'status' => 'expired',
                'updated_at' => now()
            ]);
    }
}
