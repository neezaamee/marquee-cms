<?php

namespace App\Livewire\SuperAdmin;

use App\Models\BackupActivityLog;
use App\Models\SystemBackup;
use App\Services\BackupService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class BackupManager extends Component
{
    use WithPagination, WithFileUploads;

    protected $paginationTheme = 'bootstrap';

    // Active Navigation Tab
    public $activeTab = 'manual'; // manual, scheduled, restore, activity_logs

    // Manual Backup Form State
    public $backupType = 'db_only';
    public $backupNotes = '';

    // Schedule Settings State
    public $scheduleFrequency = 'daily';
    public $scheduleTime = '02:00';
    public $retentionDays = 30;
    public $scheduleType = 'db_only';
    public $notificationEmail = '';

    // Restore Backup State
    public $uploadedBackup = null;
    public $uploadedBackupName = '';
    public $uploadedBackupSize = '';
    public $selectedRestoreBackupId = null;
    public $restoreConfirmationText = '';
    public $isRestoreModalOpen = false;

    // Activity Log Filters
    public $logActionFilter = '';

    protected $queryString = [
        'activeTab' => ['except' => 'manual'],
        'logActionFilter' => ['except' => ''],
    ];

    public function mount()
    {
        if (!auth()->user() || !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access to Super Admin Backup Management.');
        }

        // Load initial schedule settings from config / defaults
        $this->scheduleFrequency = config('backup.schedule_frequency', 'daily');
        $this->scheduleTime = config('backup.schedule_time', '02:00');
        $this->retentionDays = config('backup.retention_days', 30);
        $this->scheduleType = config('backup.schedule_type', 'db_only');
        $this->notificationEmail = config('backup.notification_email', auth()->user()->email ?? '');
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatedLogActionFilter()
    {
        $this->resetPage();
    }

    /**
     * Create immediate manual backup.
     */
    public function createBackup(BackupService $backupService)
    {
        abort_unless(auth()->user() && auth()->user()->isSuperAdmin(), 403, 'Unauthorized access.');

        try {
            $backup = $backupService->createBackup(
                type: $this->backupType,
                trigger: 'manual',
                userId: auth()->id(),
                notes: $this->backupNotes
            );

            $this->backupNotes = '';
            session()->flash('success', "Manual backup archive '{$backup->file_name}' created successfully!");
        } catch (\Throwable $e) {
            session()->flash('error', "Backup generation failed: " . $e->getMessage());
        }
    }

    /**
     * Delete a backup file.
     */
    public function deleteBackup($backupId, BackupService $backupService)
    {
        abort_unless(auth()->user() && auth()->user()->isSuperAdmin(), 403, 'Unauthorized access.');

        $backup = SystemBackup::findOrFail($backupId);

        try {
            $backupService->deleteBackup($backup, auth()->id());
            session()->flash('success', "Backup archive deleted successfully.");
        } catch (\Throwable $e) {
            session()->flash('error', "Failed deleting backup: " . $e->getMessage());
        }
    }

    /**
     * Save Schedule Settings.
     */
    public function saveScheduleSettings(BackupService $backupService)
    {
        abort_unless(auth()->user() && auth()->user()->isSuperAdmin(), 403, 'Unauthorized access.');

        $this->validate([
            'scheduleFrequency' => 'required|in:disabled,daily,weekly,monthly',
            'scheduleTime' => 'required',
            'retentionDays' => 'required|integer|min:1|max:365',
            'scheduleType' => 'required|in:db_only,full',
            'notificationEmail' => 'nullable|email',
        ]);

        $backupService->logActivity(
            action: 'schedule_updated',
            backupId: null,
            userId: auth()->id(),
            fileName: null,
            description: "Updated backup schedule settings (Frequency: {$this->scheduleFrequency}, Retention: {$this->retentionDays} days)",
            status: 'success'
        );

        session()->flash('success', 'Backup schedule configuration saved successfully.');
    }

    /**
     * Hook triggered when a backup file is uploaded.
     */
    public function updatedUploadedBackup()
    {
        if ($this->uploadedBackup) {
            try {
                $this->uploadedBackupName = $this->uploadedBackup->getClientOriginalName();
                $bytes = $this->uploadedBackup->getSize();
                $this->uploadedBackupSize = number_format($bytes / 1048576, 2) . ' MB';
            } catch (\Throwable $e) {
                $this->uploadedBackupName = '';
                $this->uploadedBackupSize = '';
            }
        } else {
            $this->uploadedBackupName = '';
            $this->uploadedBackupSize = '';
        }
    }

    /**
     * Clear uploaded backup file selection.
     */
    public function clearUploadedBackup()
    {
        $this->uploadedBackup = null;
        $this->uploadedBackupName = '';
        $this->uploadedBackupSize = '';
    }

    /**
     * Prepare restore modal for an existing backup snapshot.
     */
    public function prepareRestore($backupId)
    {
        abort_unless(auth()->user() && auth()->user()->isSuperAdmin(), 403, 'Unauthorized access.');

        $this->selectedRestoreBackupId = $backupId;
        $this->clearUploadedBackup();
        $this->restoreConfirmationText = '';
        $this->isRestoreModalOpen = true;
    }

    /**
     * Prepare restore modal for an uploaded file.
     */
    public function prepareUploadRestore()
    {
        abort_unless(auth()->user() && auth()->user()->isSuperAdmin(), 403, 'Unauthorized access.');

        $this->validate([
            'uploadedBackup' => 'required|file|max:512000', // 500MB max
        ]);

        if ($this->uploadedBackup) {
            try {
                $this->uploadedBackupName = $this->uploadedBackup->getClientOriginalName();
                $bytes = $this->uploadedBackup->getSize();
                $this->uploadedBackupSize = number_format($bytes / 1048576, 2) . ' MB';
            } catch (\Throwable $e) {
                $this->uploadedBackupSize = '';
            }
        }

        $this->selectedRestoreBackupId = null;
        $this->restoreConfirmationText = '';
        $this->isRestoreModalOpen = true;
    }

    public function closeRestoreModal()
    {
        $this->isRestoreModalOpen = false;
        $this->restoreConfirmationText = '';
    }

    /**
     * Execute Database Restoration.
     */
    public function executeRestore(BackupService $backupService)
    {
        abort_unless(auth()->user() && auth()->user()->isSuperAdmin(), 403, 'Unauthorized access.');

        if (trim(strtoupper($this->restoreConfirmationText)) !== 'RESTORE') {
            session()->flash('error', 'Confirmation failed. Please type RESTORE to confirm data restoration.');
            return;
        }

        $cleanUpPath = null;

        try {
            if ($this->selectedRestoreBackupId) {
                $backup = SystemBackup::findOrFail($this->selectedRestoreBackupId);
                $filePath = storage_path('app/' . $backup->file_path);
                if (!file_exists($filePath)) {
                    $filePath = storage_path('app/private/' . $backup->file_path);
                }
            } elseif ($this->uploadedBackup) {
                // Preserve original extension (.zip or .sql) so BackupService extracts properly
                $originalExt = strtolower($this->uploadedBackup->getClientOriginalExtension());
                if (!in_array($originalExt, ['zip', 'sql'])) {
                    throw new \Exception("Unsupported backup file format. Please upload a .zip or .sql file.");
                }

                $tempDir = storage_path('app/backups/temp_restore');
                \Illuminate\Support\Facades\File::ensureDirectoryExists($tempDir);
                $tempName = 'restore_' . time() . '_' . uniqid() . '.' . $originalExt;
                $filePath = $tempDir . '/' . $tempName;
                $cleanUpPath = $filePath;

                // Safely copy file to execution path without deleting Livewire's temporary file
                $copied = false;
                try {
                    $realPath = $this->uploadedBackup->getRealPath();
                    if ($realPath && file_exists($realPath)) {
                        $copied = @copy($realPath, $filePath);
                    }
                } catch (\Throwable $e) {
                    $copied = false;
                }

                if (!$copied) {
                    $disk = config('livewire.temporary_file_upload.disk') ?: config('filesystems.default');
                    $storage = \Illuminate\Support\Facades\Storage::disk($disk);
                    $relPath = method_exists($this->uploadedBackup, 'path') ? $this->uploadedBackup->path() : null;
                    if ($relPath && $storage->exists($relPath)) {
                        \Illuminate\Support\Facades\File::put($filePath, $storage->get($relPath));
                        $copied = true;
                    }
                }

                if (!$copied && method_exists($this->uploadedBackup, 'getPathname') && file_exists($this->uploadedBackup->getPathname())) {
                    @copy($this->uploadedBackup->getPathname(), $filePath);
                    $copied = true;
                }
            } else {
                throw new \Exception("No backup file selected for restoration.");
            }

            if (!file_exists($filePath)) {
                throw new \Exception("Backup file not found on server.");
            }

            $backupService->restoreBackup($filePath, auth()->id());

            if ($cleanUpPath && file_exists($cleanUpPath)) {
                @unlink($cleanUpPath);
            }

            $this->isRestoreModalOpen = false;
            $this->clearUploadedBackup();
            $this->selectedRestoreBackupId = null;
            $this->restoreConfirmationText = '';

            session()->flash('success', 'Database restored successfully! All tables and records updated.');
        } catch (\Throwable $e) {
            if (isset($cleanUpPath) && file_exists($cleanUpPath)) {
                @unlink($cleanUpPath);
            }

            // Reset state so that Livewire view render does not encounter a stale Flysystem reference
            $this->clearUploadedBackup();
            $this->isRestoreModalOpen = false;
            $this->restoreConfirmationText = '';

            session()->flash('error', 'Data restoration failed: ' . $e->getMessage());
        }
    }

    public function render()
    {
        abort_unless(auth()->user() && auth()->user()->isSuperAdmin(), 403, 'Unauthorized access.');
        $backups = SystemBackup::with('creator')
            ->latest()
            ->paginate(10, ['*'], 'backupsPage');

        $activityLogs = BackupActivityLog::with(['user', 'backup'])
            ->when($this->logActionFilter, function ($query) {
                $query->where('action', $this->logActionFilter);
            })
            ->latest()
            ->paginate(15, ['*'], 'logsPage');

        $totalBackupsCount = SystemBackup::count();
        $totalStorageBytes = SystemBackup::sum('file_size');
        $lastBackup = SystemBackup::where('status', 'completed')->latest()->first();

        return view('livewire.super-admin.backup-manager', [
            'backups' => $backups,
            'activityLogs' => $activityLogs,
            'totalBackupsCount' => $totalBackupsCount,
            'totalStorageBytes' => $totalStorageBytes,
            'lastBackup' => $lastBackup,
        ])->layout('layouts.admin');
    }
}
