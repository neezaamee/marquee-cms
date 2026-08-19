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

class CrudAccessTest extends TestCase
{
    use RefreshDatabase;

    protected $plan;
    protected $superAdminRole;
    protected $ownerRole;
    protected $staffRole;
    protected $manageSettingsPermission;
    protected $manageStaffPermission;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup base roles & permissions
        $this->superAdminRole = Role::create(['name' => 'super_admin', 'label' => 'Super Admin']);
        $this->ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner']);
        $this->staffRole = Role::create(['name' => 'staff', 'label' => 'Staff']);
        
        $this->manageSettingsPermission = Permission::create(['name' => 'manage_settings', 'label' => 'Manage Settings']);
        $this->manageStaffPermission = Permission::create(['name' => 'manage_staff', 'label' => 'Manage Staff']);

        $this->ownerRole->permissions()->attach([$this->manageSettingsPermission->id, $this->manageStaffPermission->id]);

        $this->plan = SubscriptionPlan::create([
            'name' => 'Standard',
            'slug' => 'standard',
            'price' => 10000,
            'billing_interval' => 'month',
        ]);
    }

    public function test_super_admin_can_access_marquees_crud()
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => bcrypt('password'),
            'role_id' => $this->superAdminRole->id
        ]);

        $response = $this->actingAs($superAdmin)->get(route('marquees.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($superAdmin)->get(route('marquees.create'));
        $response->assertStatus(200);
    }

    public function test_non_super_admin_cannot_access_marquees_crud()
    {
        $marquee = Marquee::create([
            'name' => 'Test Marquee',
            'address' => '123 St',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '+923001234567',
            'email' => 'test@marquee.com',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
        ]);

        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'role_id' => $this->ownerRole->id,
            'marquee_id' => $marquee->id,
        ]);

        $response = $this->actingAs($owner)->get(route('marquees.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($owner)->get(route('marquees.create'));
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_access_branches_crud()
    {
        $marquee = Marquee::create([
            'name' => 'Marquee A',
            'address' => 'Addr A', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '1', 'email' => 'a@test.com',
            'subscription_plan_id' => $this->plan->id
        ]);

        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'marquee_id' => $marquee->id,
            'role_id' => $this->ownerRole->id
        ]);

        $response = $this->actingAs($owner)->get(route('branches.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($owner)->get(route('branches.create'));
        $response->assertStatus(200);
    }

    public function test_unauthorized_user_cannot_access_branches_crud()
    {
        $marquee = Marquee::create([
            'name' => 'Marquee A',
            'address' => 'Addr A', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '1', 'email' => 'a@test.com',
            'subscription_plan_id' => $this->plan->id
        ]);

        $staff = User::create([
            'name' => 'Staff',
            'email' => 'staff@test.com',
            'password' => bcrypt('password'),
            'marquee_id' => $marquee->id,
            'role_id' => $this->staffRole->id
        ]);

        $response = $this->actingAs($staff)->get(route('branches.index'));
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_access_users_crud()
    {
        $marquee = Marquee::create([
            'name' => 'Marquee A',
            'address' => 'Addr A', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '1', 'email' => 'a@test.com',
            'subscription_plan_id' => $this->plan->id
        ]);

        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'marquee_id' => $marquee->id,
            'role_id' => $this->ownerRole->id
        ]);

        $response = $this->actingAs($owner)->get(route('users.index'));
        $response->assertStatus(200);
    }

    public function test_super_admin_can_delete_marquee_with_cascade()
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => bcrypt('password'),
            'role_id' => $this->superAdminRole->id
        ]);

        $marquee = Marquee::create([
            'name' => 'Marquee to Delete',
            'address' => 'Addr X', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '1', 'email' => 'x@test.com',
            'subscription_plan_id' => $this->plan->id
        ]);

        $branch = Branch::create([
            'name' => 'Child Branch',
            'marquee_id' => $marquee->id,
            'address' => 'Addr Y', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '1'
        ]);

        $staff = User::create([
            'name' => 'Child Staff',
            'email' => 'staff-child@test.com',
            'password' => bcrypt('password'),
            'marquee_id' => $marquee->id,
            'role_id' => $this->staffRole->id
        ]);

        // Delete the marquee
        $response = $this->actingAs($superAdmin)->delete(route('marquees.destroy', $marquee->id));
        $response->assertRedirect(route('marquees.index'));

        // Assert parent and all child records are soft deleted
        $this->assertSoftDeleted('marquees', ['id' => $marquee->id]);
        $this->assertSoftDeleted('branches', ['id' => $branch->id]);
        $this->assertSoftDeleted('users', ['id' => $staff->id]);

        // Assert restore works and cascades restores too
        $marquee->restore();
        $this->assertDatabaseHas('marquees', ['id' => $marquee->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'deleted_at' => null]);
    }

    public function test_owner_can_delete_branch_of_own_marquee()
    {
        $marquee = Marquee::create([
            'name' => 'Marquee A',
            'address' => 'Addr A', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '1', 'email' => 'a@test.com',
            'subscription_plan_id' => $this->plan->id
        ]);

        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'marquee_id' => $marquee->id,
            'role_id' => $this->ownerRole->id
        ]);

        $branch = Branch::create([
            'name' => 'Branch A',
            'marquee_id' => $marquee->id,
            'address' => 'Addr B', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '1'
        ]);

        $response = $this->actingAs($owner)->delete(route('branches.destroy', $branch->id));
        $response->assertRedirect(route('branches.index'));
        $this->assertSoftDeleted('branches', ['id' => $branch->id]);
    }

    public function test_owner_can_delete_user_of_own_marquee()
    {
        $marquee = Marquee::create([
            'name' => 'Marquee A',
            'address' => 'Addr A', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '1', 'email' => 'a@test.com',
            'subscription_plan_id' => $this->plan->id
        ]);

        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'marquee_id' => $marquee->id,
            'role_id' => $this->ownerRole->id
        ]);

        $staff = User::create([
            'name' => 'Staff to Delete',
            'email' => 'staff-del@test.com',
            'password' => bcrypt('password'),
            'marquee_id' => $marquee->id,
            'role_id' => $this->staffRole->id
        ]);

        $response = $this->actingAs($owner)->delete(route('users.destroy', $staff->id));
        $response->assertRedirect(route('users.index'));
        $this->assertSoftDeleted('users', ['id' => $staff->id]);
    }

    public function test_super_admin_can_create_marquee_with_owner_details()
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => bcrypt('password'),
            'role_id' => $this->superAdminRole->id
        ]);

        $postData = [
            'name' => 'New Premium Venue',
            'address' => 'Test Address 123',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '+923001234567',
            'email' => 'venue@test.com',
            'tax_authority' => 'PRA',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
            'owner_name' => 'John Doe',
            'owner_username' => 'johndoe',
            'owner_email' => 'john.doe@venue.com',
            'owner_password' => 'secretpassword123',
            'owner_phone' => '+923007654321',
        ];

        $response = $this->actingAs($superAdmin)->post(route('marquees.store'), $postData);
        $response->assertRedirect(route('marquees.index'));

        // Assert Marquee was created
        $this->assertDatabaseHas('marquees', [
            'name' => 'New Premium Venue',
            'email' => 'venue@test.com'
        ]);

        $marquee = Marquee::where('email', 'venue@test.com')->first();

        // Assert Owner user account was created and linked to the marquee
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john.doe@venue.com',
            'username' => 'johndoe',
            'marquee_id' => $marquee->id,
            'role_id' => $this->ownerRole->id,
            'phone' => '+923007654321',
            'status' => 'active'
        ]);

        // Assert Default Head Office Branch was created and linked to the marquee
        $this->assertDatabaseHas('branches', [
            'marquee_id' => $marquee->id,
            'name' => 'Head Office',
            'address' => 'Test Address 123',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '+923001234567',
            'status' => 'active'
        ]);
    }

    public function test_super_admin_can_filter_marquees_by_active_and_suspended()
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => bcrypt('password'),
            'role_id' => $this->superAdminRole->id
        ]);

        $ownerRole = Role::where('name', 'owner')->first() ?? Role::where('name', 'business_owner')->first();

        $activeOwner = User::create([
            'name' => 'Active Owner',
            'email' => 'activeowner@test.com',
            'username' => 'activeowner',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole->id,
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
            'subscription_ends_at' => now()->addMonth(),
        ]);

        $expiredOwner = User::create([
            'name' => 'Expired Owner',
            'email' => 'expiredowner@test.com',
            'username' => 'expiredowner',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole->id,
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
            'subscription_ends_at' => now()->subMonth(),
        ]);

        $activeMarquee = Marquee::create([
            'name' => 'Active Venue',
            'address' => 'Addr 1', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '123', 'email' => 'active@venue.com',
            'status' => 'active',
        ]);
        $activeOwner->ownedMarquees()->syncWithoutDetaching([$activeMarquee->id]);

        $suspendedMarquee = Marquee::create([
            'name' => 'Suspended Venue',
            'address' => 'Addr 2', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '456', 'email' => 'suspended@venue.com',
            'status' => 'suspended',
        ]);
        $activeOwner->ownedMarquees()->syncWithoutDetaching([$suspendedMarquee->id]);

        $expiredMarquee = Marquee::create([
            'name' => 'Expired Venue',
            'address' => 'Addr 3', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '789', 'email' => 'expired@venue.com',
            'status' => 'active',
        ]);
        $expiredOwner->ownedMarquees()->syncWithoutDetaching([$expiredMarquee->id]);

        // 1. Get Active filter
        $response = $this->actingAs($superAdmin)->get(route('marquees.index', ['filter' => 'active']));
        $response->assertStatus(200);
        $response->assertSee('Active Venue');
        $response->assertDontSee('Suspended Venue');
        $response->assertDontSee('Expired Venue');

        // 2. Get Suspended filter
        $response = $this->actingAs($superAdmin)->get(route('marquees.index', ['filter' => 'suspended']));
        $response->assertStatus(200);
        $response->assertDontSee('Active Venue');
        $response->assertSee('Suspended Venue');
        $response->assertSee('Expired Venue');
    }
}
