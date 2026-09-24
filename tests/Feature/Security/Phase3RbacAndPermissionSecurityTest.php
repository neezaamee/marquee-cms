<?php

namespace Tests\Feature\Security;

use App\Livewire\Administration\AccessControl;
use App\Livewire\Administration\PermissionsManager;
use App\Livewire\Administration\RolesManager;
use App\Livewire\SuperAdmin\BackupManager;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Marquee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class Phase3RbacAndPermissionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdminRole;
    protected $ownerRole;
    protected $branchManagerRole;
    protected $staffRole;

    protected $manageStaffPermission;
    protected $viewBookingsPermission;

    protected $marqueeA;
    protected $marqueeB;

    protected $branchA1;
    protected $branchA2;
    protected $branchB1;

    protected $superAdmin;
    protected $ownerA;
    protected $branchManagerA1;
    protected $staffA;
    protected $unauthorizedStaffA;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Roles
        $this->superAdminRole = Role::create(['name' => 'super_admin', 'label' => 'Super Admin']);
        $this->ownerRole = Role::create(['name' => 'business_owner', 'label' => 'Business Owner']);
        $this->branchManagerRole = Role::create(['name' => 'branch_manager', 'label' => 'Branch Manager']);
        $this->staffRole = Role::create(['name' => 'staff', 'label' => 'Staff Member']);

        // 2. Create Permissions
        $this->manageStaffPermission = Permission::create(['name' => 'manage_staff', 'label' => 'Manage Staff']);
        $this->viewBookingsPermission = Permission::create(['name' => 'view_bookings', 'label' => 'View Bookings']);

        // 3. Create Marquees (Tenants)
        $this->marqueeA = Marquee::create([
            'name' => 'Marquee Tenant A',
            'address' => 'Road A',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001111111',
            'email' => 'tenantA@test.com',
            'status' => 'active',
        ]);

        $this->marqueeB = Marquee::create([
            'name' => 'Marquee Tenant B',
            'address' => 'Road B',
            'city' => 'Karachi',
            'province' => 'Sindh',
            'phone' => '03002222222',
            'email' => 'tenantB@test.com',
            'status' => 'active',
        ]);

        // 4. Create Branches
        $this->branchA1 = Branch::create([
            'marquee_id' => $this->marqueeA->id,
            'name' => 'Branch A1',
            'address' => 'Branch A1 Address',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001111112',
            'status' => 'active',
        ]);

        $this->branchA2 = Branch::create([
            'marquee_id' => $this->marqueeA->id,
            'name' => 'Branch A2',
            'address' => 'Branch A2 Address',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001111113',
            'status' => 'active',
        ]);

        $this->branchB1 = Branch::create([
            'marquee_id' => $this->marqueeB->id,
            'name' => 'Branch B1',
            'address' => 'Branch B1 Address',
            'city' => 'Karachi',
            'province' => 'Sindh',
            'phone' => '03002222223',
            'status' => 'active',
        ]);

        // 5. Create Users
        $this->superAdmin = User::create([
            'name' => 'Global Super Admin',
            'email' => 'superadmin@marqueecms.test',
            'username' => 'superadmin',
            'password' => Hash::make('SuperAdminPass123!'),
            'role_id' => $this->superAdminRole->id,
            'status' => 'active',
        ]);

        $this->ownerA = User::create([
            'name' => 'Owner Tenant A',
            'email' => 'ownerA@marqueecms.test',
            'username' => 'ownerA',
            'password' => Hash::make('OwnerPass123!'),
            'role_id' => $this->ownerRole->id,
            'status' => 'active',
        ]);
        $this->ownerA->ownedMarquees()->attach($this->marqueeA->id);

        $this->branchManagerA1 = User::create([
            'name' => 'Manager Branch A1',
            'email' => 'managerA1@marqueecms.test',
            'username' => 'managerA1',
            'password' => Hash::make('ManagerPass123!'),
            'role_id' => $this->branchManagerRole->id,
            'marquee_id' => $this->marqueeA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);

        // Staff with manage_staff permission
        $this->staffRole->permissions()->attach($this->manageStaffPermission->id);

        $this->staffA = User::create([
            'name' => 'Authorized Staff A',
            'email' => 'staffA@marqueecms.test',
            'username' => 'staffA',
            'password' => Hash::make('StaffPass123!'),
            'role_id' => $this->staffRole->id,
            'marquee_id' => $this->marqueeA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);

        // Role without manage_staff
        $unauthorizedRole = Role::create(['name' => 'helper', 'label' => 'Helper']);
        $this->unauthorizedStaffA = User::create([
            'name' => 'Unauthorized Staff A',
            'email' => 'unauthA@marqueecms.test',
            'username' => 'unauthA',
            'password' => Hash::make('HelperPass123!'),
            'role_id' => $unauthorizedRole->id,
            'marquee_id' => $this->marqueeA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);
    }

    public function test_super_admin_bypasses_all_permission_gates()
    {
        $this->actingAs($this->superAdmin);

        $this->assertTrue(Gate::allows('manage_staff'));
        $this->assertTrue(Gate::allows('non_existent_random_permission'));
        $this->assertTrue($this->superAdmin->can('manage_staff'));
    }

    public function test_gate_evaluates_custom_rbac_permissions_for_staff()
    {
        // Staff A has manage_staff permission
        $this->actingAs($this->staffA);
        $this->assertTrue(Gate::allows('manage_staff'));
        $this->assertTrue($this->staffA->can('manage_staff'));

        // Staff A does NOT have view_bookings permission
        $this->assertFalse(Gate::allows('view_bookings'));
        $this->assertFalse($this->staffA->can('view_bookings'));

        // Unauthorized Staff has neither
        $this->actingAs($this->unauthorizedStaffA);
        $this->assertFalse(Gate::allows('manage_staff'));
        $this->assertFalse($this->unauthorizedStaffA->can('manage_staff'));
    }

    public function test_staff_controller_endpoints_reject_unauthorized_users()
    {
        $this->actingAs($this->unauthorizedStaffA);

        $this->get('/staff')->assertStatus(403);
        $this->get('/staff/create')->assertStatus(403);
        $this->post('/staff', [
            'name' => 'New Staff',
            'cnic' => '35201-1111111-1',
            'mobile_number' => '03001234567',
            'designation' => 'Cleaner',
            'joining_date' => '2026-01-01',
            'salary' => 25000,
            'employment_type' => 'Permanent',
            'status' => 'active',
            'branch_id' => $this->branchA1->id,
        ])->assertStatus(403);
    }

    public function test_staff_controller_cross_tenant_access_is_blocked()
    {
        // Create an employee in Tenant B
        $staffB = Employee::create([
            'marquee_id' => $this->marqueeB->id,
            'branch_id' => $this->branchB1->id,
            'name' => 'Staff in Tenant B',
            'cnic' => '35201-2222222-2',
            'mobile_number' => '03009999999',
            'designation' => 'Technician',
            'joining_date' => '2026-01-01',
            'salary' => 30000,
            'employment_type' => 'Permanent',
            'status' => 'active',
        ]);

        // Staff A (Tenant A) attempts to access staff in Tenant B: blocked by tenant scope (404) or permission check (403)
        $this->actingAs($this->staffA);

        $this->assertTrue(in_array($this->get("/staff/{$staffB->id}")->status(), [403, 404]));
        $this->assertTrue(in_array($this->get("/staff/{$staffB->id}/edit")->status(), [403, 404]));

        $this->assertTrue(in_array($this->put("/staff/{$staffB->id}", [
            'name' => 'Hacked Name',
            'cnic' => '35201-2222222-2',
            'mobile_number' => '03009999999',
            'designation' => 'Technician',
            'joining_date' => '2026-01-01',
            'salary' => 30000,
            'employment_type' => 'Permanent',
            'status' => 'active',
            'branch_id' => $this->branchA1->id,
        ])->status(), [403, 404]));

        $this->assertTrue(in_array($this->delete("/staff/{$staffB->id}")->status(), [403, 404]));
    }

    public function test_branch_manager_cannot_manage_or_reassign_staff_outside_their_branch()
    {
        // Employee in Branch A2
        $staffA2 = Employee::create([
            'marquee_id' => $this->marqueeA->id,
            'branch_id' => $this->branchA2->id,
            'name' => 'Staff in Branch A2',
            'cnic' => '35201-3333333-3',
            'mobile_number' => '03008888888',
            'designation' => 'Waiter',
            'joining_date' => '2026-01-01',
            'salary' => 20000,
            'employment_type' => 'Permanent',
            'status' => 'active',
        ]);

        // Manager of Branch A1 cannot manage staff in Branch A2
        $this->actingAs($this->branchManagerA1);

        $this->get("/staff/{$staffA2->id}")->assertStatus(403);
        $this->get("/staff/{$staffA2->id}/edit")->assertStatus(403);
        $this->delete("/staff/{$staffA2->id}")->assertStatus(403);

        // Cannot assign a new staff member to Branch A2
        $this->post('/staff', [
            'name' => 'Foreign Branch Staff',
            'cnic' => '35201-4444444-4',
            'mobile_number' => '03007777777',
            'designation' => 'Waiter',
            'joining_date' => '2026-01-01',
            'salary' => 20000,
            'employment_type' => 'Permanent',
            'status' => 'active',
            'branch_id' => $this->branchA2->id,
        ])->assertStatus(403);
    }

    public function test_user_controller_cross_tenant_access_is_blocked()
    {
        // User belonging to Tenant B
        $userB = User::create([
            'name' => 'User in Tenant B',
            'email' => 'userB@test.com',
            'username' => 'userB',
            'password' => Hash::make('UserBPass123!'),
            'role_id' => $this->staffRole->id,
            'marquee_id' => $this->marqueeB->id,
            'branch_id' => $this->branchB1->id,
            'status' => 'active',
        ]);

        // Staff A attempts to inspect or modify user in Tenant B: blocked by tenant scope (404) or permission check (403)
        $this->actingAs($this->staffA);

        $this->assertTrue(in_array($this->get("/users/{$userB->id}")->status(), [403, 404]));
        $this->assertTrue(in_array($this->get("/users/{$userB->id}/edit")->status(), [403, 404]));

        $this->assertTrue(in_array($this->put("/users/{$userB->id}", [
            'name' => 'Cross Tenant Edit',
            'email' => 'userB@test.com',
            'role_id' => $this->staffRole->id,
            'status' => 'active',
        ])->status(), [403, 404]));

        $this->assertTrue(in_array($this->delete("/users/{$userB->id}")->status(), [403, 404]));
    }

    public function test_user_controller_privilege_escalation_is_prevented()
    {
        $this->actingAs($this->staffA);

        // 1. Staff cannot modify or delete Super Admin
        $this->assertTrue(in_array($this->get("/users/{$this->superAdmin->id}/edit")->status(), [403, 404]));
        $this->assertTrue(in_array($this->delete("/users/{$this->superAdmin->id}")->status(), [403, 404]));

        // 2. Staff cannot modify or delete Business Owner in same tenant
        $ownerInMarquee = User::create([
            'name' => 'Tenant Owner Inside Marquee',
            'email' => 'tenantowner@test.com',
            'username' => 'tenantowner',
            'password' => Hash::make('TenantOwnerPass123!'),
            'role_id' => $this->ownerRole->id,
            'marquee_id' => $this->marqueeA->id,
            'status' => 'active',
        ]);
        $this->get("/users/{$ownerInMarquee->id}/edit")->assertStatus(403);
        $this->delete("/users/{$ownerInMarquee->id}")->assertStatus(403);

        // 3. Staff cannot assign super_admin or owner role to anyone
        $subordinate = User::create([
            'name' => 'Subordinate Staff',
            'email' => 'subordinate@test.com',
            'username' => 'subordinate',
            'password' => Hash::make('SubordinatePass123!'),
            'role_id' => $this->staffRole->id,
            'marquee_id' => $this->marqueeA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);

        // Attempt to escalate to super_admin
        $this->put("/users/{$subordinate->id}", [
            'name' => 'Escalated User',
            'email' => 'subordinate@test.com',
            'role_id' => $this->superAdminRole->id,
            'status' => 'active',
        ])->assertStatus(403);

        // Attempt to escalate to business_owner
        $this->put("/users/{$subordinate->id}", [
            'name' => 'Escalated User',
            'email' => 'subordinate@test.com',
            'role_id' => $this->ownerRole->id,
            'status' => 'active',
        ])->assertStatus(403);
    }

    public function test_role_and_permission_management_requires_authorization()
    {
        $this->actingAs($this->unauthorizedStaffA);

        $this->get('/admin/roles')->assertStatus(403);
        $this->get('/admin/permissions')->assertStatus(403);
        $this->get('/admin/access-control')->assertStatus(403);

        Livewire::actingAs($this->unauthorizedStaffA)
            ->test(RolesManager::class)
            ->assertStatus(403);

        Livewire::actingAs($this->unauthorizedStaffA)
            ->test(PermissionsManager::class)
            ->assertStatus(403);

        Livewire::actingAs($this->unauthorizedStaffA)
            ->test(AccessControl::class)
            ->assertStatus(403);
    }

    public function test_role_and_permission_changes_record_activity_logs()
    {
        // 1. Toggling permission in AccessControl records an ActivityLog
        Livewire::actingAs($this->superAdmin)
            ->test(AccessControl::class)
            ->call('togglePermission', $this->staffRole->id, $this->viewBookingsPermission->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->superAdmin->id,
            'action' => 'role_permission_toggled',
        ]);

        // 2. Creating a role in RolesManager records an ActivityLog
        Livewire::actingAs($this->superAdmin)
            ->test(RolesManager::class)
            ->set('name', 'custom_auditor')
            ->set('label', 'Custom Auditor')
            ->set('description', 'Performs audit')
            ->call('saveRole')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->superAdmin->id,
            'action' => 'role_created',
        ]);
    }

    public function test_livewire_backup_manager_rejects_non_super_admins_at_method_level()
    {
        // Non-super admin calling action methods directly in BackupManager is rejected
        Livewire::actingAs($this->staffA)
            ->test(BackupManager::class)
            ->assertStatus(403);

        Livewire::actingAs($this->ownerA)
            ->test(BackupManager::class)
            ->assertStatus(403);
    }
}
