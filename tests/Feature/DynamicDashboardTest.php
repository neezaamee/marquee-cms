<?php

namespace Tests\Feature;

use App\Livewire\Owner\BusinessOwnerDashboard;
use App\Livewire\SuperAdmin\SuperAdminDashboard;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Hall;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryStockLedger;
use App\Models\InventoryUnit;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\AccountingModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DynamicDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $businessOwner;
    protected Marquee $marquee;
    protected Branch $branch1;
    protected Branch $branch2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SubscriptionPlanSeeder::class);
        $this->seed(AccountingModuleSeeder::class);

        $superAdminRole = Role::where('name', 'super_admin')->first();
        $ownerRole = Role::where('name', 'business_owner')->first() ?? Role::where('name', 'owner')->first();
        $plan = SubscriptionPlan::first();

        // 1. Setup Marquee with 2 branches
        $this->marquee = Marquee::factory()->create([
            'name' => 'The Imperial Banquet Suites',
            'city' => 'Lahore',
            'is_setup_completed' => true,
        ]);

        $this->branch1 = Branch::factory()->create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Gulberg Branch',
            'is_head_office' => true,
        ]);

        $this->branch2 = Branch::factory()->create([
            'marquee_id' => $this->marquee->id,
            'name' => 'DHA Phase 5 Branch',
            'is_head_office' => false,
        ]);

        // 2. Setup Super Admin
        $this->superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'marquee_id' => null,
            'email' => 'superadmin@saas.com',
        ]);

        // 3. Setup Business Owner
        $this->businessOwner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marquee->id,
            'subscription_plan_id' => $plan->id,
            'email' => 'owner@imperial.com',
        ]);
        $this->businessOwner->ownedMarquees()->attach($this->marquee->id);
    }

    /** @test */
    public function test_super_admin_sees_saas_executive_dashboard_with_platform_telemetry()
    {
        $this->actingAs($this->superAdmin)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee('SaaS Executive Command Center')
            ->assertSee('Tenant Ecosystem Directory')
            ->assertSee('Synthetic Data Studio')
            ->assertSee('The Imperial Banquet Suites');
    }

    /** @test */
    public function test_super_admin_livewire_component_computes_mrr_and_gmv()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(SuperAdminDashboard::class)
            ->assertSee('Estimated MRR')
            ->assertSee('Platform GMV')
            ->assertSee('The Imperial Banquet Suites');
    }

    /** @test */
    public function test_business_owner_sees_live_financial_pnl_and_banquet_operations()
    {
        // Create sample booking for marquee
        $customer = Customer::factory()->create(['marquee_id' => $this->marquee->id]);
        $hall = Hall::factory()->create(['marquee_id' => $this->marquee->id, 'branch_id' => $this->branch1->id]);

        $booking = Booking::factory()->create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $customer->id,
            'hall_id' => $hall->id,
            'grand_total' => 250000.00,
            'advance_received' => 100000.00,
            'receivable_amount' => 150000.00,
            'is_revenue_recognized' => false,
            'booking_status' => 'Confirmed',
        ]);

        $this->actingAs($this->businessOwner)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee('Live Financials')
            ->assertSee('Realized Revenue')
            ->assertSee('Advances Held')
            ->assertSee('Receivables Due');
    }

    /** @test */
    public function test_business_owner_can_filter_dashboard_by_specific_branch()
    {
        // Branch 1 booking
        $cust1 = Customer::factory()->create(['marquee_id' => $this->marquee->id]);
        $hall1 = Hall::factory()->create(['marquee_id' => $this->marquee->id, 'branch_id' => $this->branch1->id]);
        Booking::factory()->create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $cust1->id,
            'hall_id' => $hall1->id,
            'grand_total' => 300000.00,
            'advance_received' => 100000.00,
        ]);

        // Branch 2 booking
        $cust2 = Customer::factory()->create(['marquee_id' => $this->marquee->id]);
        $hall2 = Hall::factory()->create(['marquee_id' => $this->marquee->id, 'branch_id' => $this->branch2->id]);
        Booking::factory()->create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch2->id,
            'customer_id' => $cust2->id,
            'hall_id' => $hall2->id,
            'grand_total' => 500000.00,
            'advance_received' => 200000.00,
        ]);

        Livewire::actingAs($this->businessOwner)
            ->test(BusinessOwnerDashboard::class)
            ->assertSet('selectedBranchId', null)
            ->set('selectedBranchId', $this->branch1->id)
            ->assertSet('selectedBranchId', $this->branch1->id);
    }

    /** @test */
    public function test_incomplete_onboarding_shows_setup_wizard_checklist()
    {
        $this->marquee->update(['is_setup_completed' => false]);

        $this->actingAs($this->businessOwner)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee('Initial Business Configuration Required')
            ->assertSee('Open Setup Wizard');
    }

    /** @test */
    public function test_business_owner_dashboard_renders_low_stock_alerts_correctly()
    {
        $category = InventoryCategory::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Raw Meat & Poultry',
            'status' => 'Active',
        ]);

        $unit = InventoryUnit::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Kilogram',
            'short_code' => 'kg',
            'status' => 'Active',
        ]);

        $item = InventoryItem::create([
            'marquee_id' => $this->marquee->id,
            'item_code' => 'RAW-CHK-01',
            'name' => 'Chicken Breast Boneless',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'minimum_stock_level' => 20.00,
            'reorder_level' => 30.00,
            'status' => 'Active',
        ]);

        // Stock in 10 kg (which is <= 20 kg minimum stock)
        InventoryStockLedger::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch1->id,
            'item_id' => $item->id,
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'GRN',
            'qty_in' => 10.00,
            'qty_out' => 0.00,
            'running_balance' => 10.00,
            'unit_price' => 800.00,
            'total_cost' => 8000.00,
        ]);

        Livewire::actingAs($this->businessOwner)
            ->test(BusinessOwnerDashboard::class)
            ->assertSee('Low Stock Inventory Items')
            ->assertSee('Chicken Breast Boneless: 10 kg');
    }
}
