<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class CleanBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:clean {--days=30 : Retention period in days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean expired backup files older than retention policy';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $days = (int) $this->option('days');
        $this->info("Cleaning backup files older than {$days} days...");

        $cleanedCount = $backupService->cleanExpiredBackups($days);

        $this->info("Cleaned {$cleanedCount} expired backup file(s).");
        return Command::SUCCESS;
    }
}
