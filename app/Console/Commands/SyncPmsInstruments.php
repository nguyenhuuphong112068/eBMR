<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncPmsInstruments extends Command
{
    protected $signature = 'pms:sync-instruments';
    protected $description = 'Sync instruments from PMS database to local eBMR database';

    public function handle()
    {
        $this->info('Starting PMS instruments sync...');

        try {
            // Get data from PMS
            $pmsInstruments = DB::connection('pms')->table('quota_maintenance')
                ->where('active', 1)
                ->where(function($query) {
                    $query->where('block', 'not like', '%TI%')
                          ->orWhereNull('block');
                })
                ->get();

            $this->info('Found ' . $pmsInstruments->count() . ' active records in PMS.');

            $createdCount = 0;
            $updatedCount = 0;

            foreach ($pmsInstruments as $pmsInst) {
                // Determine department_code column name (user mentioned deparment_code or department_code)
                $deptCode = null;
                if (isset($pmsInst->department_code)) {
                    $deptCode = $pmsInst->department_code;
                } elseif (isset($pmsInst->deparment_code)) {
                    $deptCode = $pmsInst->deparment_code;
                }

                $code = $pmsInst->parent_eqp_id;
                $name = $pmsInst->Eqp_name;

                if (empty($code)) {
                    continue; // Skip if code is empty
                }

                // Upsert logic
                $existing = DB::table('instrument')->where('code', $code)->first();

                if ($existing) {
                    DB::table('instrument')->where('code', $code)->update([
                        'name' => $name,
                        'department_code' => $deptCode,
                        'updated_at' => now(),
                    ]);
                    $updatedCount++;
                } else {
                    DB::table('instrument')->insert([
                        'code' => $code,
                        'name' => $name,
                        'department_code' => $deptCode,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'created_by' => 'PMS_Sync',
                    ]);
                    $createdCount++;
                }
            }

            $this->info("Sync completed successfully!");
            $this->info("Created: $createdCount | Updated: $updatedCount");
            
        } catch (\Exception $e) {
            $this->error('Error during sync: ' . $e->getMessage());
        }
    }
}
