<?php

namespace Tests\Feature;

use App\Livewire\Finance\AccountantDashboard;
use App\Livewire\Inventory\StorekeeperDashboard;
use App\Livewire\Owner\BusinessOwnerDashboard;
use App\Livewire\SuperAdmin\SuperAdminDashboard;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoleBasedDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Marquee $marquee;
    protected Role $superAdminRole;
    protected Role $ownerRole;
    protected Role $storekeeperRole;
    protected Role $accountantRole;
    protected Role $bookingOfficerRole;
    protected Role $branchManagerRole;

    protected User $superAdmin;
    protected User $owner;
    protected User $storekeeper;
    protected User $accountant;
    protected User $bookingOfficer;
    protected User $branchManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->marquee = Marquee::create([
            'name' => 'Royal Palm Marquee',
            'slug' => 'royal-palm-marquee',
            'status' => 'active',
            'address' => 'Mall Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'email' => 'contact@royalpalm.test',
            'is_setup_completed' => true,
        ]);

        $this->superAdminRole = Role::create(['name' => 'super_admin', 'label' => 'Super Administrator']);
        $this->ownerRole = Role::create(['name' => 'business_owner', 'label' => 'Business Owner']);
        $this->storekeeperRole = Role::create(['name' => 'store_keeper', 'label' => 'Store Keeper / Inventory Manager']);
        $this->accountantRole = Role::create(['name' => 'accountant', 'label' => 'Accountant / Cashier']);
        $this->bookingOfficerRole = Role::create(['name' => 'booking_officer', 'label' => 'Booking Officer']);
        $this->branchManagerRole = Role::create(['name' => 'branch_manager', 'label' => 'Branch Manager']);

        $this->superAdmin = User::create([
            'name' => 'SaaS Admin',
            'email' => 'admin@saas.test',
            'password' => bcrypt('Password123!'),
            'role_id' => $this->superAdminRole->id,
            'status' => 'active',
        ]);

        $this->owner = User::create([
            'name' => 'Marquee Owner',
            'email' => 'owner@marquee.test',
            'password' => bcrypt('Password123!'),
            'role_id' => $this->ownerRole->id,
            'marquee_id' => $this->marquee->id,
            'status' => 'active',
        ]);
        $this->owner->ownedMarquees()->attach($this->marquee->id);

        $this->storekeeper = User::create([
            'name' => 'Store Keeper User',
            'email' => 'store@marquee.test',
            'password' => bcrypt('Password123!'),
            'role_id' => $this->storekeeperRole->id,
            'marquee_id' => $this->marquee->id,
            'status' => 'active',
        ]);

        $this->accountant = User::create([
            'name' => 'Accountant User',
            'email' => 'accountant@marquee.test',
            'password' => bcrypt('Password123!'),
            'role_id' => $this->accountantRole->id,
            'marquee_id' => $this->marquee->id,
            'status' => 'active',
        ]);

        $this->bookingOfficer = User::create([
            'name' => 'Booking Desk User',
            'email' => 'booking@marquee.test',
            'password' => bcrypt('Password123!'),
            'role_id' => $this->bookingOfficerRole->id,
            'marquee_id' => $this->marquee->id,
            'status' => 'active',
        ]);
    }

    public function test_super_admin_sees_executive_saas_dashboard_and_can_preview_role_views()
    {
        $response = $this->actingAs($this->superAdmin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('SaaS Command');
        $response->assertSee('Storekeeper');
        $response->assertSee('Accountant');

        // Test previewing storekeeper view as super admin
        $storePreview = $this->actingAs($this->superAdmin)->get('/dashboard?view=storekeeper');
        $storePreview->assertStatus(200);
        $storePreview->assertSee('Storekeeper Desk');

        // Test previewing accountant view as super admin
        $accPreview = $this->actingAs($this->superAdmin)->get('/dashboard?view=accountant');
        $accPreview->assertStatus(200);
        $accPreview->assertSee('Accounts Desk');
    }

    public function test_storekeeper_lands_directly_on_inventory_and_store_dashboard()
    {
        $response = $this->actingAs($this->storekeeper)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Storekeeper Desk');
        $response->assertSee('Catalog Items');
        $response->assertSee('Pending Requisitions');
        $response->assertSee('Live Stock');
        $response->assertDontSee('SaaS Command Center');
    }

    public function test_accountant_lands_directly_on_finance_and_cashier_dashboard()
    {
        $response = $this->actingAs($this->accountant)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Accounts Desk');
        $response->assertSee('Liquidity');
        $response->assertSee('Unposted Payments');
        $response->assertDontSee('SaaS Command Center');
    }

    public function test_booking_officer_lands_on_booking_operations_desk()
    {
        $response = $this->actingAs($this->bookingOfficer)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Booking Desk');
        $response->assertDontSee('SaaS Command Center');
    }

    public function test_owner_lands_on_executive_business_owner_dashboard()
    {
        $response = $this->actingAs($this->owner)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Live Financials');
        $response->assertDontSee('Storekeeper Desk');
    }

    public function test_storekeeper_and_accountant_livewire_components_render_cleanly()
    {
        Livewire::actingAs($this->storekeeper)
            ->test(StorekeeperDashboard::class)
            ->assertStatus(200)
            ->assertSee('Storekeeper Desk');

        Livewire::actingAs($this->accountant)
            ->test(AccountantDashboard::class)
            ->assertStatus(200)
            ->assertSee('Accounts Desk');
    }
}
