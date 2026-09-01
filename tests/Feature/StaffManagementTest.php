<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Marquee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $plan;
    protected $ownerRole;
    protected $marquee;
    protected $branch;
    protected $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner']);
        Role::create(['name' => 'super_admin', 'label' => 'Super Admin']);
        Permission::create(['name' => 'manage_settings', 'label' => 'Manage Settings']);
        Permission::create(['name' => 'manage_staff', 'label' => 'Manage Staff']);

        $this->plan = SubscriptionPlan::create([
            'name'             => 'Standard',
            'slug'             => 'standard',
            'price'            => 10000,
            'billing_interval' => 'month',
        ]);

        $this->marquee = Marquee::create([
            'name'                 => 'Test Marquee Hall',
            'address'             => '123 Main St',
            'city'                => 'Lahore',
            'province'            => 'Punjab',
            'phone'               => '+923001234567',
            'email'               => 'marquee@test.com',
            'status'              => 'active',
            'subscription_plan_id' => $this->plan->id,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name'       => 'Main Branch',
            'address'    => '123 Main St',
            'city'       => 'Lahore',
            'province'   => 'Punjab',
            'phone'      => '+923009999999',
            'status'     => 'active',
        ]);

        $this->owner = User::create([
            'name'       => 'Owner User',
            'email'      => 'owner@test.com',
            'password'   => bcrypt('password'),
            'role_id'    => $this->ownerRole->id,
            'marquee_id' => $this->marquee->id,
            'branch_id'  => $this->branch->id,
        ]);
    }

    public function test_staff_index_is_accessible_to_authenticated_users()
    {
        $response = $this->actingAs($this->owner)->get(route('staff.index'));
        $response->assertStatus(200);
        $response->assertSee('Staff Management');
    }

    public function test_staff_create_form_is_accessible()
    {
        $response = $this->actingAs($this->owner)->get(route('staff.create'));
        $response->assertStatus(200);
        $response->assertSee('Add New Employee');
    }

    public function test_can_create_employee_without_cms_login()
    {
        $response = $this->actingAs($this->owner)->post(route('staff.store'), [
            'name'            => 'John Waiter',
            'cnic'            => '35202-1234567-1',
            'mobile_number'   => '+923001234567',
            'designation'     => 'Waiter',
            'joining_date'    => '2026-01-01',
            'salary'          => 25000,
            'employment_type' => 'Permanent',
            'status'          => 'active',
            'branch_id'       => $this->branch->id,
            'enable_login'    => null,
        ]);

        $response->assertRedirect(route('staff.index'));
        $this->assertDatabaseHas('employees', ['name' => 'John Waiter', 'designation' => 'Waiter']);

        $employee = Employee::where('name', 'John Waiter')->first();
        $this->assertNotNull($employee);
        $this->assertNull($employee->user_id);  // No CMS user created
        $this->assertStringStartsWith('EMP-', $employee->employee_id);
    }

    public function test_employee_id_is_auto_generated_with_correct_format()
    {
        $this->actingAs($this->owner)->post(route('staff.store'), [
            'name'            => 'First Employee',
            'cnic'            => '35202-1111111-1',
            'mobile_number'   => '+923001111111',
            'designation'     => 'Chef / Cook',
            'joining_date'    => '2026-01-01',
            'salary'          => 30000,
            'employment_type' => 'Permanent',
            'status'          => 'active',
            'branch_id'       => $this->branch->id,
        ]);

        $employee = Employee::first();
        $this->assertEquals('EMP-00001', $employee->employee_id);
    }

    public function test_can_create_employee_with_cms_login_via_livewire()
    {
        $managerRole = Role::create(['name' => 'branch_manager', 'label' => 'Branch Manager']);

        $employee = Employee::create([
            'employee_id'     => 'EMP-00002',
            'marquee_id'      => $this->marquee->id,
            'branch_id'       => $this->branch->id,
            'name'            => 'Ali Manager',
            'cnic'            => '35202-9999999-9',
            'mobile_number'   => '+923009999999',
            'designation'     => 'Branch Manager',
            'joining_date'    => '2026-01-01',
            'salary'          => 60000,
            'employment_type' => 'Permanent',
            'status'          => 'active',
        ]);

        \Livewire\Livewire::actingAs($this->owner)
            ->test('manage-staff-logins', ['staff' => $employee])
            ->set('branch_id', $this->branch->id)
            ->set('email', 'ali.manager@test.com')
            ->set('username', 'alimanager')
            ->set('role_id', $managerRole->id)
            ->set('password', 'secret123')
            ->call('addLogin');

        $this->assertDatabaseHas('users', [
            'email' => 'ali.manager@test.com',
            'username' => 'alimanager',
            'employee_id' => $employee->id,
        ]);
    }

    public function test_can_soft_delete_employee()
    {
        $employee = Employee::create([
            'employee_id'     => 'EMP-00001',
            'marquee_id'      => $this->marquee->id,
            'branch_id'       => $this->branch->id,
            'name'            => 'Delete Me',
            'cnic'            => '35202-0000000-0',
            'mobile_number'   => '+923000000000',
            'designation'     => 'Helper / Labor',
            'joining_date'    => '2026-01-01',
            'salary'          => 15000,
            'employment_type' => 'Daily Wages',
            'status'          => 'active',
        ]);

        $response = $this->actingAs($this->owner)->delete(route('staff.destroy', $employee->id));
        $response->assertRedirect(route('staff.index'));

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_deleting_employee_also_soft_deletes_linked_user()
    {
        $staffRole = Role::create(['name' => 'accountant', 'label' => 'Accountant']);

        $employee = Employee::create([
            'employee_id'     => 'EMP-00002',
            'marquee_id'      => $this->marquee->id,
            'branch_id'       => $this->branch->id,
            'name'            => 'Employee With Login',
            'cnic'            => '35202-1212121-2',
            'mobile_number'   => '+923001212121',
            'designation'     => 'Accountant',
            'joining_date'    => '2026-01-01',
            'salary'          => 40000,
            'employment_type' => 'Permanent',
            'status'          => 'active',
        ]);

        $user = User::create([
            'employee_id' => $employee->id,
            'name'       => 'Linked User',
            'email'      => 'linked@test.com',
            'username'   => 'linkeduser',
            'password'   => bcrypt('password'),
            'role_id'    => $staffRole->id,
            'marquee_id' => $this->marquee->id,
            'branch_id'  => $this->branch->id,
        ]);

        $this->actingAs($this->owner)->delete(route('staff.destroy', $employee->id));

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_branch_manager_can_edit_themselves()
    {
        $managerRole = Role::create(['name' => 'branch_manager', 'label' => 'Branch Manager']);

        $employee = Employee::create([
            'employee_id'     => 'EMP-00003',
            'marquee_id'      => $this->marquee->id,
            'branch_id'       => $this->branch->id,
            'name'            => 'Branch Manager User',
            'cnic'            => '35202-3333333-3',
            'mobile_number'   => '+923003333333',
            'designation'     => 'Branch Manager',
            'joining_date'    => '2026-01-01',
            'salary'          => 60000,
            'employment_type' => 'Permanent',
            'status'          => 'active',
        ]);

        $managerUser = User::create([
            'employee_id' => $employee->id,
            'name'       => 'Branch Manager User',
            'email'      => 'manager@test.com',
            'username'   => 'manager',
            'password'   => bcrypt('password'),
            'role_id'    => $managerRole->id,
            'marquee_id' => $this->marquee->id,
            'branch_id'  => $this->branch->id,
        ]);

        // Get the edit view
        $response = $this->actingAs($managerUser)->get(route('staff.edit', $employee->id));
        $response->assertStatus(200);
        $response->assertSee('Branch Manager'); // Verify that the designation option exists and is selected

        // Update the employee profile details
        $response = $this->actingAs($managerUser)->put(route('staff.update', $employee->id), [
            'name'            => 'Branch Manager User Updated',
            'cnic'            => '35202-3333333-3',
            'mobile_number'   => '+923003333333',
            'designation'     => 'Branch Manager', // Submit the designation unchanged
            'joining_date'    => '2026-01-01',
            'salary'          => 65000,
            'employment_type' => 'Permanent',
            'status'          => 'active',
            'branch_id'       => $this->branch->id,
        ]);

        $response->assertRedirect(route('staff.index'));
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'name' => 'Branch Manager User Updated',
            'designation' => 'Branch Manager',
            'salary' => 65000
        ]);

        // Synced booted event will keep the linked user's name in sync
        $this->assertDatabaseHas('users', [
            'id' => $managerUser->id,
            'name' => 'Branch Manager User Updated'
        ]);
    }

    public function test_inactive_user_cannot_log_in()
    {
        $inactiveUser = User::create([
            'name'       => 'Inactive Staff',
            'email'      => 'inactive@test.com',
            'username'   => 'inactivestaff',
            'password'   => bcrypt('password123'),
            'role_id'    => $this->ownerRole->id,
            'marquee_id' => $this->marquee->id,
            'branch_id'  => $this->branch->id,
            'status'     => 'inactive',
        ]);

        $response = $this->post(route('login'), [
            'login'    => 'inactivestaff',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_active_user_can_log_in()
    {
        $activeUser = User::create([
            'name'       => 'Active Staff',
            'email'      => 'active@test.com',
            'username'   => 'activestaff',
            'password'   => bcrypt('password123'),
            'role_id'    => $this->ownerRole->id,
            'marquee_id' => $this->marquee->id,
            'branch_id'  => $this->branch->id,
            'status'     => 'active',
        ]);

        $response = $this->post(route('login'), [
            'login'    => 'activestaff',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($activeUser);
    }

    public function test_tenant_a_cannot_view_or_edit_tenant_b_staff()
    {
        $marqueeB = Marquee::create([
            'name'                 => 'Other Marquee',
            'address'             => '456 Other St',
            'city'                => 'Karachi',
            'province'            => 'Sindh',
            'phone'               => '+923007654321',
            'email'               => 'other@test.com',
            'status'              => 'active',
            'subscription_plan_id' => $this->plan->id,
        ]);

        $branchB = Branch::create([
            'marquee_id' => $marqueeB->id,
            'name'       => 'Karachi Branch',
            'address'    => '456 Other St',
            'city'       => 'Karachi',
            'province'   => 'Sindh',
            'phone'      => '+923008888888',
            'status'     => 'active',
        ]);

        $employeeB = Employee::create([
            'employee_id'     => 'EMP-99999',
            'marquee_id'      => $marqueeB->id,
            'branch_id'       => $branchB->id,
            'name'            => 'Tenant B Employee',
            'cnic'            => '42101-9999999-9',
            'mobile_number'   => '+923009998877',
            'designation'     => 'Chef / Cook',
            'joining_date'    => '2026-01-01',
            'salary'          => 45000,
            'employment_type' => 'Permanent',
            'status'          => 'active',
        ]);

        // Attempt to edit employee B as Owner A (scoped out by BelongsToTenant route binding)
        $response = $this->actingAs($this->owner)->get(route('staff.edit', $employeeB->id));
        $response->assertNotFound();
    }
}
