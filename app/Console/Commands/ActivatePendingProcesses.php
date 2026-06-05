<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ActivatePendingProcesses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'document:activate-issued';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate issued documents that have reached their effective date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Activating issued documents for today...");
        \App\Services\DocumentActivationService::activateAllIssuedDocuments();
        $this->info("Done activating issued documents.");
    }
}
