<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\Marquee;
use App\Livewire\Administration\RolesManager;
use App\Livewire\Administration\PermissionsManager;
use App\Livewire\Administration\AccessControl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $businessOwner;
    protected User $staffMember;
    protected Role $superAdminRole;
    protected Role $businessOwnerRole;
    protected Role $staffRole;
    protected Permission $testPermission;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a Marquee first
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

        // 1. Create Roles
        $this->superAdminRole = Role::create([
            'name' => 'super_admin',
            'label' => 'Super Administrator'
        ]);

        $this->businessOwnerRole = Role::create([
            'name' => 'business_owner',
            'label' => 'Business Owner'
        ]);

        $this->staffRole = Role::create([
            'name' => 'staff',
            'label' => 'Staff Member'
        ]);

        // 2. Create Permissions
        $this->testPermission = Permission::create([
            'name' => 'manage_staff',
            'label' => 'Manage Staff Members'
        ]);

        // Link permission to business_owner
        $this->businessOwnerRole->permissions()->attach($this->testPermission->id);

        // 3. Create Users
        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@admin.test',
            'password' => bcrypt('password'),
            'role_id' => $this->superAdminRole->id,
            'marquee_id' => null,
        ]);

        $this->businessOwner = User::create([
            'name' => 'Business Owner',
            'email' => 'owner@business.test',
            'password' => bcrypt('password'),
            'role_id' => $this->businessOwnerRole->id,
            'marquee_id' => $marquee->id,
        ]);

        $this->staffMember = User::create([
            'name' => 'Staff Member',
            'email' => 'staff@member.test',
            'password' => bcrypt('password'),
            'role_id' => $this->staffRole->id,
            'marquee_id' => $marquee->id,
        ]);
    }

    public function test_super_admin_can_access_all_three_components()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(RolesManager::class)
            ->assertStatus(200);

        Livewire::actingAs($this->superAdmin)
            ->test(PermissionsManager::class)
            ->assertStatus(200);

        Livewire::actingAs($this->superAdmin)
            ->test(AccessControl::class)
            ->assertStatus(200);
    }

    public function test_business_owner_can_view_components_but_cannot_perform_crud_operations()
    {
        // 1. Roles viewable but mutating fails/blocks
        Livewire::actingAs($this->businessOwner)
            ->test(RolesManager::class)
            ->assertStatus(200);

        $this->get('/admin/roles')->assertStatus(200);

        Livewire::actingAs($this->businessOwner)
            ->test(RolesManager::class)
            ->call('openCreateModal')
            ->assertStatus(403);

        // 2. Permissions viewable but mutating fails/blocks
        Livewire::actingAs($this->businessOwner)
            ->test(PermissionsManager::class)
            ->assertStatus(200);

        $this->get('/admin/permissions')->assertStatus(200);

        Livewire::actingAs($this->businessOwner)
            ->test(PermissionsManager::class)
            ->call('openCreateModal')
            ->assertStatus(403);

        // 3. Access Control viewable but toggling fails/blocks
        Livewire::actingAs($this->businessOwner)
            ->test(AccessControl::class)
            ->assertStatus(200);

        $this->get('/admin/access-control')->assertStatus(200);

        Livewire::actingAs($this->businessOwner)
            ->test(AccessControl::class)
            ->call('togglePermission', $this->businessOwnerRole->id, $this->testPermission->id)
            ->assertStatus(403);
    }

    public function test_unauthorized_user_is_blocked_from_routes()
    {
        // Staff member has no 'manage_staff' permission and is not super_admin or business_owner
        $this->actingAs($this->staffMember)
            ->get('/admin/roles')
            ->assertStatus(403);

        $this->actingAs($this->staffMember)
            ->get('/admin/permissions')
            ->assertStatus(403);

        $this->actingAs($this->staffMember)
            ->get('/admin/access-control')
            ->assertStatus(403);
    }

    public function test_super_admin_can_crud_roles()
    {
        // Create Role
        Livewire::actingAs($this->superAdmin)
            ->test(RolesManager::class)
            ->set('name', 'custom_role')
            ->set('label', 'Custom Role')
            ->set('description', 'Test Role Description')
            ->call('saveRole')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'name' => 'custom_role',
            'label' => 'Custom Role'
        ]);

        $customRole = Role::where('name', 'custom_role')->first();

        // Edit Role
        Livewire::actingAs($this->superAdmin)
            ->test(RolesManager::class)
            ->call('editRole', $customRole->id)
            ->set('label', 'Modified Custom Role')
            ->call('saveRole')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'id' => $customRole->id,
            'label' => 'Modified Custom Role'
        ]);

        // Delete Role
        Livewire::actingAs($this->superAdmin)
            ->test(RolesManager::class)
            ->set('confirmingDeletionId', $customRole->id)
            ->call('deleteRole')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('roles', [
            'name' => 'custom_role'
        ]);
    }

    public function test_super_admin_cannot_delete_builtin_roles()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(RolesManager::class)
            ->set('confirmingDeletionId', $this->staffRole->id)
            ->call('deleteRole');

        $this->assertDatabaseHas('roles', [
            'id' => $this->staffRole->id
        ]);
    }

    public function test_super_admin_cannot_delete_role_with_users_assigned()
    {
        // Create custom role and assign staff member to it
        $customRole = Role::create([
            'name' => 'active_role',
            'label' => 'Active Role'
        ]);

        $this->staffMember->role_id = $customRole->id;
        $this->staffMember->save();

        Livewire::actingAs($this->superAdmin)
            ->test(RolesManager::class)
            ->set('confirmingDeletionId', $customRole->id)
            ->call('deleteRole');

        $this->assertDatabaseHas('roles', [
            'id' => $customRole->id
        ]);
    }

    public function test_super_admin_can_toggle_permissions_in_access_control()
    {
        // Assert initial state is attached
        $this->assertTrue($this->businessOwnerRole->permissions->contains($this->testPermission->id));

        // Toggle permission (should detach)
        Livewire::actingAs($this->superAdmin)
            ->test(AccessControl::class)
            ->call('togglePermission', $this->businessOwnerRole->id, $this->testPermission->id);

        $this->businessOwnerRole->load('permissions');
        $this->assertFalse($this->businessOwnerRole->permissions->contains($this->testPermission->id));

        // Toggle permission again (should attach)
        Livewire::actingAs($this->superAdmin)
            ->test(AccessControl::class)
            ->call('togglePermission', $this->businessOwnerRole->id, $this->testPermission->id);

        $this->businessOwnerRole->load('permissions');
        $this->assertTrue($this->businessOwnerRole->permissions->contains($this->testPermission->id));
    }
}
