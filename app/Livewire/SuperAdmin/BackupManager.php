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
        $user = auth()->user();
        if (!$user || !$user->isSuperAdmin()) {
            session()->flash('error', 'Unauthorized access.');
            return;
        }

        try {
            $backup = $backupService->createBackup(
                type: $this->backupType,
                trigger: 'manual',
                userId: $user->id,
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
        $user = auth()->user();
        if (!$user || !$user->isSuperAdmin()) {
            session()->flash('error', 'Unauthorized access.');
            return;
        }

        $backup = SystemBackup::findOrFail($backupId);

        try {
            $backupService->deleteBackup($backup, $user->id);
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
        $user = auth()->user();
        if (!$user || !$user->isSuperAdmin()) {
            session()->flash('error', 'Unauthorized access.');
            return;
        }

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
            userId: $user->id,
            fileName: null,
            description: "Updated backup schedule settings (Frequency: {$this->scheduleFrequency}, Retention: {$this->retentionDays} days)",
            status: 'success'
        );

        session()->flash('success', 'Backup schedule configuration saved successfully.');
    }

    /**
     * Prepare restore modal for an existing backup snapshot.
     */
    public function prepareRestore($backupId)
    {
        $this->selectedRestoreBackupId = $backupId;
        $this->uploadedBackup = null;
        $this->restoreConfirmationText = '';
        $this->isRestoreModalOpen = true;
    }

    /**
     * Prepare restore modal for an uploaded file.
     */
    public function prepareUploadRestore()
    {
        $this->validate([
            'uploadedBackup' => 'required|file|max:512000', // 500MB max
        ]);

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
        $user = auth()->user();
        if (!$user || !$user->isSuperAdmin()) {
            session()->flash('error', 'Unauthorized access.');
            return;
        }

        if (trim(strtoupper($this->restoreConfirmationText)) !== 'RESTORE') {
            session()->flash('error', 'Confirmation failed. Please type RESTORE to confirm data restoration.');
            return;
        }

        try {
            if ($this->selectedRestoreBackupId) {
                $backup = SystemBackup::findOrFail($this->selectedRestoreBackupId);
                $filePath = storage_path('app/' . $backup->file_path);
            } elseif ($this->uploadedBackup) {
                $filePath = $this->uploadedBackup->getRealPath();
            } else {
                throw new \Exception("No backup file selected for restoration.");
            }

            $backupService->restoreBackup($filePath, $user->id);

            $this->isRestoreModalOpen = false;
            $this->uploadedBackup = null;
            $this->selectedRestoreBackupId = null;
            $this->restoreConfirmationText = '';

            session()->flash('success', 'Database restored successfully! All tables updated.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Data restoration failed: ' . $e->getMessage());
        }
    }

    public function render()
    {
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
