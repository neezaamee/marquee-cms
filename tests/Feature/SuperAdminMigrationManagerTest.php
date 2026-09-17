<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\MigrationManager;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminMigrationManagerTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $businessOwner;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $marquee = Marquee::create([
            'name' => 'Test Marquee',
            'slug' => 'test-marquee',
            'status' => 'active',
            'address' => 'Test Address',
            'city' => 'Test City',
            'province' => 'Test Province',
            'phone' => '03001112233',
            'email' => 'marquee@test.com',
            'is_setup_completed' => true,
        ]);

        $superAdminRole = Role::create([
            'name' => 'super_admin',
            'label' => 'Super Admin',
        ]);

        $ownerRole = Role::create([
            'name' => 'business_owner',
            'label' => 'Business Owner',
        ]);

        $staffRole = Role::create([
            'name' => 'staff',
            'label' => 'Staff Member',
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $superAdminRole->id,
            'status' => 'active',
        ]);

        $this->businessOwner = User::create([
            'name' => 'Business Owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole->id,
            'marquee_id' => $marquee->id,
            'status' => 'active',
        ]);

        $this->staff = User::create([
            'name' => 'Staff Member',
            'email' => 'staff@test.com',
            'password' => bcrypt('password'),
            'role_id' => $staffRole->id,
            'marquee_id' => $marquee->id,
            'status' => 'active',
        ]);
    }

    public function test_guests_cannot_access_migrations_hub()
    {
        $response = $this->get(route('super-admin.migrations'));
        $response->assertRedirect(route('login'));
    }

    public function test_business_owners_cannot_access_migrations_hub()
    {
        $response = $this->actingAs($this->businessOwner)->get(route('super-admin.migrations'));
        $response->assertStatus(403);
    }

    public function test_staff_cannot_access_migrations_hub()
    {
        $response = $this->actingAs($this->staff)->get(route('super-admin.migrations'));
        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_migrations_hub()
    {
        $response = $this->actingAs($this->superAdmin)->get(route('super-admin.migrations'));
        $response->assertStatus(200);
        $response->assertSee('Database Migrations', false);
        $response->assertSee('Migrations Registry', false);
    }

    public function test_migration_manager_component_loads_stats_and_list()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(MigrationManager::class)
            ->assertSet('statusFilter', 'all')
            ->assertSee('Total Migrations')
            ->assertSee('Executed (Ran)')
            ->assertSee('Pending Updates');
    }

    public function test_super_admin_can_run_migrations_action()
    {
        $component = Livewire::actingAs($this->superAdmin)
            ->test(MigrationManager::class)
            ->call('runMigrations')
            ->assertHasNoErrors();

        $logs = $component->get('consoleLogs');
        $this->assertNotEmpty($logs);
        $this->assertStringContainsString('php artisan migrate --force', $logs[0]['command']);
    }

    public function test_super_admin_can_clear_system_cache()
    {
        $component = Livewire::actingAs($this->superAdmin)
            ->test(MigrationManager::class)
            ->call('clearSystemCache')
            ->assertHasNoErrors();

        $logs = $component->get('consoleLogs');
        $this->assertNotEmpty($logs);
        $this->assertStringContainsString('php artisan optimize:clear', $logs[0]['command']);
    }

    public function test_super_admin_can_verify_storage_link()
    {
        $component = Livewire::actingAs($this->superAdmin)
            ->test(MigrationManager::class)
            ->call('relinkStorage')
            ->assertHasNoErrors();

        $logs = $component->get('consoleLogs');
        $this->assertNotEmpty($logs);
        $this->assertStringContainsString('php artisan storage:link', $logs[0]['command']);
    }

    public function test_console_logs_can_be_cleared()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(MigrationManager::class)
            ->call('clearConsoleLogs')
            ->assertSee('CONSOLE_CLEARED');
    }

    public function test_migration_search_and_status_filtering()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(MigrationManager::class)
            ->set('search', 'create_users_table')
            ->assertSee('users')
            ->set('statusFilter', 'ran')
            ->assertHasNoErrors();
    }
}
