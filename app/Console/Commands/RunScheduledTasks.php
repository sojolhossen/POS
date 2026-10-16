<?php

namespace App\Console\Commands;

use App\Services\ExportService;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class RunScheduledTasks extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'pos:run-scheduled-tasks';

    /**
     * The console command description.
     */
    protected $description = 'Execute automated retail operations: low-stock scans, transfer delay alerts, cash register reminders, and export retention cleanup';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService, ExportService $exportService)
    {
        $this->info("Running POS automated scheduled tasks...");

        // 1. Scan low stock alerts
        $lowStockCount = $notificationService->checkLowStockAlerts();
        $this->line("- Low stock alerts generated: {$lowStockCount}");

        // 2. Scan transfer delays
        $delayCount = $notificationService->checkTransferDelays();
        $this->line("- In-transit transfer delay alerts generated: {$delayCount}");

        // 3. Scan unclosed cash registers
        $unclosedCount = $notificationService->checkUnclosedCashRegisters();
        $this->line("- Unclosed cash register alerts generated: {$unclosedCount}");

        // 4. Clean up expired export files
        $cleanedFiles = $exportService->cleanupExpiredExports();
        $this->line("- Expired temporary export files purged: {$cleanedFiles}");

        $this->info("All scheduled operations executed successfully.");

        return Command::SUCCESS;
    }
}
