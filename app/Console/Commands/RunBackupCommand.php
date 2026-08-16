<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class RunBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run {--type=db_only : Backup type (db_only or full)} {--scheduled : Flag if run by scheduler}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a database or full application backup archive';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $type = $this->option('type') === 'full' ? 'full' : 'db_only';
        $trigger = $this->option('scheduled') ? 'scheduled' : 'manual';

        $this->info("Starting {$type} backup generation ({$trigger})...");

        try {
            $backup = $backupService->createBackup(
                type: $type,
                trigger: $trigger,
                notes: $trigger === 'scheduled' ? 'Automated scheduled backup execution' : 'Triggered via Artisan CLI command'
            );

            $this->info("Backup completed successfully! File: {$backup->file_name} ({$backup->human_size})");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Backup failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
