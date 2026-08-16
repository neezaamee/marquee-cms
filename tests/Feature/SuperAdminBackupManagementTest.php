<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\BackupManager;
use App\Models\BackupActivityLog;
use App\Models\Role;
use App\Models\SystemBackup;
use App\Models\User;
use App\Services\BackupService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminBackupManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdminRole;
    protected $staffRole;
    protected $superAdmin;
    protected $staffUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->superAdminRole = Role::where('name', 'super_admin')->first();
        $this->staffRole = Role::where('name', 'staff')->first();

        $this->superAdmin = User::factory()->create([
            'role_id' => $this->superAdminRole->id,
            'email' => 'superadmin_test@marquee.com',
        ]);

        $this->staffUser = User::factory()->create([
            'role_id' => $this->staffRole->id,
            'email' => 'staff_test@marquee.com',
        ]);
    }

    public function test_non_super_admin_cannot_access_backup_management()
    {
        $this->actingAs($this->staffUser)
            ->get(route('super-admin.backups'))
            ->assertForbidden();
    }

    public function test_super_admin_can_access_backup_management_page()
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.backups'))
            ->assertOk()
            ->assertSee('Backup Management');
    }

    public function test_super_admin_can_create_manual_backup_and_audit_log_is_recorded()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(BackupManager::class)
            ->set('backupType', 'db_only')
            ->set('backupNotes', 'Test backup generation')
            ->call('createBackup');

        $this->assertDatabaseHas('system_backups', [
            'type' => 'db_only',
            'status' => 'completed',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->assertDatabaseHas('backup_activity_logs', [
            'user_id' => $this->superAdmin->id,
            'action' => 'backup_created',
        ]);
    }

    public function test_downloading_backup_streams_file_and_logs_download_activity()
    {
        $backupService = app(BackupService::class);
        $backup = $backupService->createBackup(type: 'db_only', trigger: 'manual', userId: $this->superAdmin->id);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.backups.download', $backup->id));

        $response->assertOk();

        $this->assertDatabaseHas('backup_activity_logs', [
            'system_backup_id' => $backup->id,
            'user_id' => $this->superAdmin->id,
            'action' => 'backup_downloaded',
        ]);
    }

    public function test_restoring_backup_updates_database_and_logs_restoration_activity()
    {
        $backupService = app(BackupService::class);
        $backup = $backupService->createBackup(type: 'db_only', trigger: 'manual', userId: $this->superAdmin->id);

        Livewire::actingAs($this->superAdmin)
            ->test(BackupManager::class)
            ->set('selectedRestoreBackupId', $backup->id)
            ->set('restoreConfirmationText', 'RESTORE')
            ->call('executeRestore');

        $this->assertDatabaseHas('backup_activity_logs', [
            'user_id' => $this->superAdmin->id,
            'action' => 'backup_restored',
        ]);
    }

    public function test_deleting_backup_removes_record_and_logs_activity()
    {
        $backupService = app(BackupService::class);
        $backup = $backupService->createBackup(type: 'db_only', trigger: 'manual', userId: $this->superAdmin->id);

        Livewire::actingAs($this->superAdmin)
            ->test(BackupManager::class)
            ->call('deleteBackup', $backup->id);

        $this->assertDatabaseMissing('system_backups', [
            'id' => $backup->id,
        ]);

        $this->assertDatabaseHas('backup_activity_logs', [
            'user_id' => $this->superAdmin->id,
            'action' => 'backup_deleted',
        ]);
    }

    public function test_updating_schedule_settings_logs_activity()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(BackupManager::class)
            ->set('scheduleFrequency', 'daily')
            ->set('scheduleTime', '02:00')
            ->set('retentionDays', 60)
            ->set('scheduleType', 'full')
            ->set('notificationEmail', 'admin@marquee.com')
            ->call('saveScheduleSettings');

        $this->assertDatabaseHas('backup_activity_logs', [
            'user_id' => $this->superAdmin->id,
            'action' => 'schedule_updated',
        ]);
    }
}
