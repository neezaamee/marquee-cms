<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Marquee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    protected $plan;
    protected $superAdminRole;
    protected $ownerRole;
    protected $managerRole;
    protected $permission;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup base roles & permissions
        $this->superAdminRole = Role::create(['name' => 'super_admin', 'label' => 'Super Admin']);
        $this->ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner']);
        $this->managerRole = Role::create(['name' => 'branch_manager', 'label' => 'Manager']);
        
        $this->permission = Permission::create(['name' => 'view_reports', 'label' => 'View Reports']);
        $this->ownerRole->permissions()->attach($this->permission);

        $this->plan = SubscriptionPlan::create([
            'name' => 'Standard',
            'slug' => 'standard',
            'price' => 10000,
            'billing_interval' => 'month',
        ]);
    }

    public function test_tenant_global_scope_restricts_visible_data()
    {
        // Create Marquee A and Marquee B
        $marqueeA = Marquee::create([
            'name' => 'Marquee A',
            'address' => 'Addr A', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '1', 'email' => 'a@test.com',
            'subscription_plan_id' => $this->plan->id
        ]);
        $marqueeB = Marquee::create([
            'name' => 'Marquee B',
            'address' => 'Addr B', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '2', 'email' => 'b@test.com',
            'subscription_plan_id' => $this->plan->id
        ]);

        // Create Branch A (for Marquee A) and Branch B (for Marquee B)
        $branchA = Branch::create([
            'marquee_id' => $marqueeA->id,
            'name' => 'Branch Lahore A',
            'address' => 'Addr', 'city' => 'Lhr', 'province' => 'Pb', 'phone' => '1'
        ]);

        $branchB = Branch::create([
            'marquee_id' => $marqueeB->id,
            'name' => 'Branch Karachi B',
            'address' => 'Addr', 'city' => 'Khi', 'province' => 'Sd', 'phone' => '2'
        ]);

        // Create Tenant A Owner
        $ownerA = User::create([
            'name' => 'Owner A',
            'email' => 'owner.a@test.com',
            'password' => bcrypt('password'),
            'marquee_id' => $marqueeA->id,
            'role_id' => $this->ownerRole->id
        ]);

        // Log in as Owner A
        $this->actingAs($ownerA);

        // Fetch all branches - should ONLY see Branch A
        $branches = Branch::all();

        $this->assertCount(1, $branches);
        $this->assertEquals($branchA->id, $branches->first()->id);
        $this->assertNotEquals($branchB->id, $branches->first()->id);
    }

    public function test_super_admin_can_bypass_tenant_global_scope()
    {
        // Create Marquee A and B
        $marqueeA = Marquee::create([
            'name' => 'Marquee A',
            'address' => 'Addr A', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '1', 'email' => 'a@test.com',
            'subscription_plan_id' => $this->plan->id
        ]);
        $marqueeB = Marquee::create([
            'name' => 'Marquee B',
            'address' => 'Addr B', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '2', 'email' => 'b@test.com',
            'subscription_plan_id' => $this->plan->id
        ]);

        // Create Branch A and B
        $branchA = Branch::create([
            'marquee_id' => $marqueeA->id,
            'name' => 'Branch A',
            'address' => 'Addr', 'city' => 'Lhr', 'province' => 'Pb', 'phone' => '1'
        ]);
        $branchB = Branch::create([
            'marquee_id' => $marqueeB->id,
            'name' => 'Branch B',
            'address' => 'Addr', 'city' => 'Khi', 'province' => 'Sd', 'phone' => '2'
        ]);

        // Create Super Admin User (no marquee_id)
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => bcrypt('password'),
            'marquee_id' => null,
            'role_id' => $this->superAdminRole->id
        ]);

        // Log in as Super Admin
        $this->actingAs($superAdmin);

        // Super Admin should see both branches
        $branches = Branch::all();
        $this->assertCount(2, $branches);
    }

    public function test_role_and_permission_helpers_work_correctly()
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => bcrypt('password'),
            'role_id' => $this->superAdminRole->id
        ]);

        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'role_id' => $this->ownerRole->id
        ]);

        $manager = User::create([
            'name' => 'Manager',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
            'role_id' => $this->managerRole->id
        ]);

        // Role Check assertions
        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($owner->isSuperAdmin());

        $this->assertTrue($owner->hasRole('owner'));
        $this->assertFalse($owner->hasRole('branch_manager'));

        // Permission Check assertions
        $this->assertTrue($superAdmin->hasPermission('view_reports')); // Super admin has all permissions
        $this->assertTrue($owner->hasPermission('view_reports'));      // Owner has view_reports
        $this->assertFalse($manager->hasPermission('view_reports'));   // Manager doesn't have view_reports
    }
}
