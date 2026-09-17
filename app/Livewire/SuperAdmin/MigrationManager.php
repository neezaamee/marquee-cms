<?php

namespace App\Livewire\SuperAdmin;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class MigrationManager extends Component
{
    public $search = '';
    public $statusFilter = 'all'; // 'all', 'pending', 'ran'
    public $showConfirmModal = false;
    public $consoleLogs = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
    ];

    public function mount()
    {
        abort_unless(auth()->check() && auth()->user()->isSuperAdmin(), 403, 'Unauthorized access. Only Super Admins can manage migrations.');

        // Initialize welcome message in console logs
        $this->addConsoleLog('SYSTEM_READY', 'Migration & Maintenance Hub initialized. Ready to execute Artisan commands.');
    }

    /**
     * Add an entry to the live console logs.
     */
    protected function addConsoleLog(string $command, string $output): void
    {
        array_unshift($this->consoleLogs, [
            'timestamp' => now()->format('H:i:s'),
            'command' => $command,
            'output' => trim($output),
        ]);

        // Keep maximum 20 console records in memory
        if (count($this->consoleLogs) > 20) {
            $this->consoleLogs = array_slice($this->consoleLogs, 0, 20);
        }
    }

    /**
     * Clear the console log viewer.
     */
    public function clearConsoleLogs(): void
    {
        $this->consoleLogs = [];
        $this->addConsoleLog('CONSOLE_CLEARED', 'Terminal console logs cleared.');
    }

    /**
     * Open confirmation modal before running migrations.
     */
    public function promptRunMigrations(): void
    {
        $this->showConfirmModal = true;
    }

    /**
     * Cancel confirmation modal.
     */
    public function cancelRunMigrations(): void
    {
        $this->showConfirmModal = false;
    }

    /**
     * Run all pending database migrations via php artisan migrate --force.
     */
    public function runMigrations(): void
    {
        abort_unless(auth()->check() && auth()->user()->isSuperAdmin(), 403);
        $this->showConfirmModal = false;

        try {
            $exitCode = Artisan::call('migrate', [
                '--force' => true,
            ]);

            $output = Artisan::output();
            $logContent = !empty(trim($output)) ? $output : "No new migrations to run. Database schema is already up to date (Exit Code: {$exitCode}).";
            
            $this->addConsoleLog('php artisan migrate --force', $logContent);
            session()->flash('success', 'Database migrations executed successfully.');
        } catch (\Throwable $e) {
            $this->addConsoleLog('php artisan migrate --force [FAILED]', $e->getMessage());
            session()->flash('error', 'Migration failed: ' . $e->getMessage());
        }
    }

    /**
     * Run the Global Default Data Seeder.
     */
    public function runGlobalDefaultsSeeder(): void
    {
        abort_unless(auth()->check() && auth()->user()->isSuperAdmin(), 403);

        try {
            $exitCode = Artisan::call('db:seed', [
                '--class' => 'GlobalDefaultDataSeeder',
                '--force' => true,
            ]);

            $output = Artisan::output();
            $logContent = !empty(trim($output)) ? $output : "Seeder finished with Exit Code: {$exitCode}.";

            $this->addConsoleLog('php artisan db:seed --class=GlobalDefaultDataSeeder --force', $logContent);
            session()->flash('success', 'Global Default Data Seeder completed.');
        } catch (\Throwable $e) {
            $this->addConsoleLog('php artisan db:seed [FAILED]', $e->getMessage());
            session()->flash('error', 'Seeding failed: ' . $e->getMessage());
        }
    }

    /**
     * Clear all application caches (views, routes, config, compiled).
     */
    public function clearSystemCache(): void
    {
        abort_unless(auth()->check() && auth()->user()->isSuperAdmin(), 403);

        try {
            Artisan::call('optimize:clear');
            $output = Artisan::output();

            $this->addConsoleLog('php artisan optimize:clear', $output ?: 'All caches cleared.');
            session()->flash('success', 'System caches, compiled views, routes, and config cleared successfully.');
        } catch (\Throwable $e) {
            $this->addConsoleLog('php artisan optimize:clear [FAILED]', $e->getMessage());
            session()->flash('error', 'Cache clear failed: ' . $e->getMessage());
        }
    }

    /**
     * Create or verify the public storage symbolic link.
     */
    public function relinkStorage(): void
    {
        abort_unless(auth()->check() && auth()->user()->isSuperAdmin(), 403);

        try {
            Artisan::call('storage:link');
            $output = Artisan::output();

            $this->addConsoleLog('php artisan storage:link', $output ?: 'Storage link verified.');
            session()->flash('success', 'Storage link created / verified successfully.');
        } catch (\Throwable $e) {
            $this->addConsoleLog('php artisan storage:link [FAILED]', $e->getMessage());
            session()->flash('error', 'Storage link failed: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve all migrations, both executed and pending.
     */
    protected function getMigrationsData(): array
    {
        $migrationFiles = File::files(database_path('migrations'));
        
        $ranMigrations = [];
        if (Schema::hasTable('migrations')) {
            $ranMigrations = DB::table('migrations')->pluck('batch', 'migration')->toArray();
        }

        $allMigrations = [];
        $totalCount = 0;
        $ranCount = 0;
        $pendingCount = 0;

        foreach ($migrationFiles as $file) {
            $filename = $file->getFilename();
            if (!str_ends_with($filename, '.php')) {
                continue;
            }

            $migrationName = str_replace('.php', '', $filename);
            $isRan = array_key_exists($migrationName, $ranMigrations);
            $batch = $isRan ? $ranMigrations[$migrationName] : null;

            $totalCount++;
            if ($isRan) {
                $ranCount++;
            } else {
                $pendingCount++;
            }

            // Extract human readable name
            // e.g. 2026_01_01_000000_create_users_table -> create users table
            $parts = explode('_', $migrationName, 5);
            $readableName = isset($parts[4]) ? str_replace('_', ' ', $parts[4]) : $migrationName;

            $allMigrations[] = [
                'file_name' => $filename,
                'migration_name' => $migrationName,
                'readable_name' => ucwords($readableName),
                'is_ran' => $isRan,
                'batch' => $batch,
                'path' => $file->getPathname(),
            ];
        }

        // Sort descending by migration name (newest first)
        usort($allMigrations, fn($a, $b) => strcmp($b['migration_name'], $a['migration_name']));

        // Apply filters
        $filtered = array_filter($allMigrations, function ($item) {
            if ($this->statusFilter === 'pending' && $item['is_ran']) {
                return false;
            }
            if ($this->statusFilter === 'ran' && !$item['is_ran']) {
                return false;
            }
            if (!empty($this->search)) {
                $needle = strtolower($this->search);
                return str_contains(strtolower($item['migration_name']), $needle) ||
                       str_contains(strtolower($item['readable_name']), $needle);
            }
            return true;
        });

        return [
            'migrations' => array_values($filtered),
            'stats' => [
                'total' => $totalCount,
                'ran' => $ranCount,
                'pending' => $pendingCount,
                'db_name' => config('database.connections.' . config('database.default') . '.database'),
                'db_driver' => config('database.default'),
                'environment' => app()->environment(),
            ],
        ];
    }

    public function render()
    {
        $data = $this->getMigrationsData();

        return view('livewire.super-admin.migration-manager', [
            'migrations' => $data['migrations'],
            'stats' => $data['stats'],
        ])->layout('layouts.admin');
    }
}
