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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_restoring_backup_with_case_insensitive_text_succeeds()
    {
        $backupService = app(BackupService::class);
        $backup = $backupService->createBackup(type: 'db_only', trigger: 'manual', userId: $this->superAdmin->id);

        Livewire::actingAs($this->superAdmin)
            ->test(BackupManager::class)
            ->set('selectedRestoreBackupId', $backup->id)
            ->set('restoreConfirmationText', 'restore')
            ->call('executeRestore');

        $this->assertDatabaseHas('backup_activity_logs', [
            'user_id' => $this->superAdmin->id,
            'action' => 'backup_restored',
        ]);
    }

    public function test_prepare_upload_restore_modal()
    {
        $uploadedFile = UploadedFile::fake()->create('database_backup.sql', 100);

        Livewire::actingAs($this->superAdmin)
            ->test(BackupManager::class)
            ->set('uploadedBackup', $uploadedFile)
            ->call('prepareUploadRestore')
            ->assertSet('isRestoreModalOpen', true)
            ->assertSet('selectedRestoreBackupId', null);
    }

    public function test_clear_uploaded_backup()
    {
        $uploadedFile = UploadedFile::fake()->create('database_backup.sql', 100);

        Livewire::actingAs($this->superAdmin)
            ->test(BackupManager::class)
            ->set('uploadedBackup', $uploadedFile)
            ->assertSet('uploadedBackupName', 'database_backup.sql')
            ->call('clearUploadedBackup')
            ->assertSet('uploadedBackup', null)
            ->assertSet('uploadedBackupName', '')
            ->assertSet('uploadedBackupSize', '');
    }

    public function test_execute_restore_with_uploaded_sql_backup()
    {
        $sqlContent = "-- Test SQL Dump\nSELECT 1;";
        $uploadedFile = UploadedFile::fake()->createWithContent('test_restore.sql', $sqlContent);

        Livewire::actingAs($this->superAdmin)
            ->test(BackupManager::class)
            ->set('uploadedBackup', $uploadedFile)
            ->set('restoreConfirmationText', 'RESTORE')
            ->call('executeRestore')
            ->assertHasNoErrors()
            ->assertSet('isRestoreModalOpen', false)
            ->assertSet('uploadedBackup', null);

        $this->assertDatabaseHas('backup_activity_logs', [
            'user_id' => $this->superAdmin->id,
            'action' => 'backup_restored',
        ]);
    }

    public function test_execute_restore_with_uploaded_zip_backup_and_view_rendering()
    {
        $tempZip = tempnam(sys_get_temp_dir(), 'test_zip_') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('database_dump.sql', "-- Test SQL Dump in Zip\nSELECT 1;");
        $zip->close();

        $uploadedFile = UploadedFile::fake()->createWithContent('test_backup.zip', file_get_contents($tempZip));

        Livewire::actingAs($this->superAdmin)
            ->test(BackupManager::class)
            ->set('activeTab', 'restore')
            ->set('uploadedBackup', $uploadedFile)
            ->assertSee('File Ready:')
            ->set('restoreConfirmationText', 'RESTORE')
            ->call('executeRestore')
            ->assertHasNoErrors()
            ->assertSet('isRestoreModalOpen', false)
            ->assertSet('uploadedBackup', null);

        $this->assertDatabaseHas('backup_activity_logs', [
            'user_id' => $this->superAdmin->id,
            'action' => 'backup_restored',
        ]);

        if (file_exists($tempZip)) {
            @unlink($tempZip);
        }
    }

    public function test_stale_in_progress_backups_are_auto_cleaned_up()
    {
        $staleBackup = SystemBackup::create([
            'file_name' => 'stale_backup.zip',
            'file_path' => 'backups/stale_backup.zip',
            'type' => 'db_only',
            'file_size' => 0,
            'status' => 'in_progress',
            'trigger_type' => 'manual',
            'created_by' => $this->superAdmin->id,
        ]);
        \Illuminate\Support\Facades\DB::table('system_backups')
            ->where('id', $staleBackup->id)
            ->update(['created_at' => now()->subMinutes(30)]);

        Livewire::actingAs($this->superAdmin)
            ->test(BackupManager::class)
            ->assertOk();

        $staleBackup->refresh();
        $this->assertEquals('failed', $staleBackup->status);
        $this->assertStringContainsString('timed out', $staleBackup->notes);
    }

    public function test_download_controller_provides_content_length_and_instant_headers()
    {
        $backupService = app(BackupService::class);
        $backup = $backupService->createBackup(type: 'db_only', trigger: 'manual', userId: $this->superAdmin->id);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.backups.download', $backup->id));

        $response->assertOk();
        $this->assertTrue($response->headers->has('content-length'));
        $this->assertGreaterThan(0, (int)$response->headers->get('content-length'));
        $this->assertStringContainsString($backup->file_name, $response->headers->get('content-disposition'));
    }

    public function test_manual_backup_creation_displays_instant_download_button()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(BackupManager::class)
            ->set('backupType', 'db_only')
            ->call('createBackup')
            ->assertSee('Download Backup Now')
            ->assertSet('recentlyCreatedBackupId', function ($id) {
                return !empty($id);
            });
    }
}
