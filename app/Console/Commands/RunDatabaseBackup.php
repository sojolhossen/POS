<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class RunDatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'pos:backup {--purge : Purge backups older than 365 days}';

    /**
     * The console command description.
     */
    protected $description = 'Generate full database snapshot and attachment manifest for disaster recovery';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $this->info("Initiating Clothing Store POS database backup...");

        try {
            $result = $backupService->createBackup(type: 'scheduled');
            $this->info("Backup completed successfully: {$result['file']}");
            $this->line("File size: " . number_format($result['size_bytes'] / 1024, 2) . " KB");

            if ($this->option('purge')) {
                $purged = $backupService->purgeOldBackups();
                $this->line("Purged {$purged} backups exceeding 365 days retention.");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Backup failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
