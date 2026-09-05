<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Department;
use App\Models\DepartmentProduction;
use App\Models\Employee;
use App\Models\FinancialYear;
use App\Models\Hall;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryUnit;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\Slot;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCommissionAgreement;
use App\Models\VendorSale;
use App\Models\VendorService;
use App\Services\AccountingService;
use App\Services\DepartmentStockService;
use App\Services\VendorCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModuleOperationalAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    protected $marquee;
    protected $branch;
    protected $owner;
    protected $accountingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
        $this->artisan('db:seed', ['--class' => 'SubscriptionPlanSeeder']);

        $plan = SubscriptionPlan::first();

        $this->marquee = Marquee::create([
            'name' => 'Audit Fix Marquee',
            'email' => 'audit@marquee.com',
            'phone' => '03001234567',
            'address' => '123 Main Blvd',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'status' => 'active',
            'subscription_plan_id' => $plan->id,
            'is_setup_completed' => true,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Branch',
            'address' => 'Branch Street',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03009876543',
            'status' => 'active',
        ]);

        $ownerRole = Role::firstOrCreate(['name' => 'owner']);
        // Owner with branch_id = null to test multi-branch owner capability
        $this->owner = User::create([
            'name' => 'Marquee Owner',
            'email' => 'owner@audit.com',
            'username' => 'audit_owner',
            'password' => bcrypt('Secret123!'),
            'marquee_id' => $this->marquee->id,
            'branch_id' => null,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        $this->accountingService = app(AccountingService::class);
        $this->accountingService->seedTenantDefaultAccounts($this->marquee->id);
    }

    /** @test */
    public function test_tenant_default_accounts_and_financial_year_are_properly_seeded()
    {
        // Assert root accounts
        $this->assertDatabaseHas('accounts', ['marquee_id' => $this->marquee->id, 'account_code' => '1000', 'nature' => 'Asset']);
        $this->assertDatabaseHas('accounts', ['marquee_id' => $this->marquee->id, 'account_code' => '2000', 'nature' => 'Liability']);
        $this->assertDatabaseHas('accounts', ['marquee_id' => $this->marquee->id, 'account_code' => '3000', 'nature' => 'Equity']);
        $this->assertDatabaseHas('accounts', ['marquee_id' => $this->marquee->id, 'account_code' => '4000', 'nature' => 'Income']);
        $this->assertDatabaseHas('accounts', ['marquee_id' => $this->marquee->id, 'account_code' => '5000', 'nature' => 'Expense']);

        // Assert critical operational sub-accounts
        $this->assertDatabaseHas('accounts', ['marquee_id' => $this->marquee->id, 'account_code' => '1001', 'name' => 'Cash']);
        $this->assertDatabaseHas('accounts', ['marquee_id' => $this->marquee->id, 'account_code' => '1002', 'name' => 'Bank']);
        $this->assertDatabaseHas('accounts', ['marquee_id' => $this->marquee->id, 'account_code' => '1003', 'name' => 'Accounts Receivable']);
        $this->assertDatabaseHas('accounts', ['marquee_id' => $this->marquee->id, 'account_code' => '2001', 'name' => 'Accounts Payable']);
        $this->assertDatabaseHas('accounts', ['marquee_id' => $this->marquee->id, 'account_code' => '2003', 'name' => 'Customer Advances / Contract Liabilities']);
        $this->assertDatabaseHas('accounts', ['marquee_id' => $this->marquee->id, 'account_code' => '2150-VEN', 'name' => 'Vendor Payable Clearing']);
        $this->assertDatabaseHas('accounts', ['marquee_id' => $this->marquee->id, 'account_code' => '4001', 'name' => 'Hall Booking Revenue']);
        $this->assertDatabaseHas('accounts', ['marquee_id' => $this->marquee->id, 'account_code' => '4005', 'name' => 'Vendor Commission Income']);

        // Assert active Financial Year
        $this->assertDatabaseHas('financial_years', [
            'marquee_id' => $this->marquee->id,
            'status' => 'active',
            'is_default' => true,
        ]);

        // Assert default cash bank account mapping
        $cashAcc = Account::where('marquee_id', $this->marquee->id)->where('account_code', '1001')->first();
        $this->assertDatabaseHas('cash_bank_accounts', [
            'marquee_id' => $this->marquee->id,
            'account_id' => $cashAcc->id,
            'type' => 'cash',
        ]);
    }

    /** @test */
    public function test_kitchen_production_manager_renders_without_sql_error_when_bookings_exist()
    {
        $this->actingAs($this->owner);

        $customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'first_name' => 'Tariq',
            'last_name' => 'Mehmood',
            'phone_number' => '03005551234',
            'email' => 'tariq@test.com',
            'status' => 'active',
        ]);

        $hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Grand Ballroom',
            'hall_code' => 'HALL-GB',
            'hall_type' => 'indoor',
            'capacity' => 500,
            'default_booking_price' => 50000.00,
            'status' => 'active',
        ]);

        $slot = Slot::create([
            'marquee_id' => $this->marquee->id,
            'slot_name' => 'Evening Slot',
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'status' => 'active',
        ]);

        // Create booking with booking_status Confirmed
        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'hall_id' => $hall->id,
            'slot_id' => $slot->id,
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'booking_number' => 'BK-AUDIT-001',
            'booking_date' => now()->addDays(2)->format('Y-m-d'),
            'guest_count' => 300,
            'per_head_rate' => 1500,
            'total_amount' => 450000,
            'advance_amount' => 100000,
            'paid_amount' => 100000,
            'balance_amount' => 350000,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Partially Paid',
        ]);

        $dept = Department::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'name' => 'Kitchen Production Dept',
            'department_code' => 'KIT-01',
            'department_type' => 'Kitchen Production',
            'status' => 'Active',
            'display_order' => 1,
        ]);

        // Livewire component render test (must not throw Unknown column 'status')
        Livewire::test(\App\Livewire\DepartmentProductionManager::class)
            ->assertStatus(200)
            ->assertViewHas('bookings', function($bookings) use ($booking) {
                return $bookings->contains('id', $booking->id);
            });
    }

    /** @test */
    public function test_kitchen_production_batch_can_be_saved_by_multi_branch_owner()
    {
        $this->actingAs($this->owner);

        $dept = Department::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'name' => 'Main Kitchen',
            'department_code' => 'KIT-MAIN',
            'department_type' => 'Kitchen Production',
            'status' => 'Active',
            'display_order' => 1,
        ]);

        $unit = InventoryUnit::create(['marquee_id' => $this->marquee->id, 'name' => 'Kg', 'short_code' => 'Kg', 'status' => 'Active']);
        $cat = InventoryCategory::create(['marquee_id' => $this->marquee->id, 'name' => 'Grains', 'status' => 'Active']);

        $rice = InventoryItem::create([
            'marquee_id' => $this->marquee->id,
            'item_code' => 'RICE-01',
            'name' => 'Basmati Rice',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'default_purchase_rate' => 200,
            'status' => 'Active',
        ]);

        // Issue initial stock to department
        \App\Models\DepartmentStockLedger::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'department_id' => $dept->id,
            'item_id' => $rice->id,
            'transaction_date' => now()->format('Y-m-d'),
            'transaction_type' => 'Issue',
            'qty_in' => 100,
            'qty_out' => 0,
            'running_balance' => 100,
            'unit_price' => 200,
            'total_cost' => 20000,
            'created_by' => $this->owner->id,
        ]);

        Livewire::test(\App\Livewire\DepartmentProductionManager::class)
            ->set('department_id', $dept->id)
            ->set('production_date', now()->format('Y-m-d'))
            ->set('produced_qty', 50)
            ->set('wastage_qty', 2)
            ->set('formItems', [
                ['item_id' => $rice->id, 'quantity' => 20],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Production batch logged and raw material consumption recorded.');

        // Department stock ledger must reflect 100 - 2 (wastage) - 20 (consumption) = 78
        $deptService = app(DepartmentStockService::class);
        $balance = $deptService->getDepartmentStockBalance($dept->id, $rice->id);
        $this->assertEquals(78.0, $balance);
    }

    /** @test */
    public function test_vendor_sale_creates_balanced_double_entry_journal_voucher()
    {
        $this->actingAs($this->owner);

        $vendor = Vendor::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'vendor_code' => 'VEND-001',
            'name' => 'Royal Stage Decorators',
            'contact_person' => 'Kamran Ali',
            'phone' => '03001112233',
            'vendor_type' => 'stage_decor',
            'status' => 'active',
        ]);

        $service = VendorService::create([
            'marquee_id' => $this->marquee->id,
            'vendor_id' => $vendor->id,
            'service_name' => 'Royal Floral Stage Decor',
            'service_category' => 'Stage Decor',
            'pricing_type' => 'Fixed',
            'base_price' => 50000.00,
            'status' => 'active',
        ]);

        $agreement = VendorCommissionAgreement::create([
            'marquee_id' => $this->marquee->id,
            'vendor_id' => $vendor->id,
            'commission_type' => 'percentage',
            'commission_percentage' => 20.00, // 20% commission (Rs. 10,000 commission, Rs. 40,000 vendor net)
            'effective_from' => now()->subMonth()->format('Y-m-d'),
            'status' => 'active',
        ]);

        $vendorServiceEngine = app(VendorCommissionService::class);

        $sale = $vendorServiceEngine->createVendorSale([
            'vendor_id' => $vendor->id,
            'vendor_service_id' => $service->id,
            'branch_id' => $this->branch->id,
            'sale_date' => now()->format('Y-m-d'),
            'event_date' => now()->format('Y-m-d'),
            'quantity' => 1,
            'unit' => 'Event',
            'sale_amount' => 50000.00,
            'advance_amount' => 0.00,
            'include_in_invoice' => true,
        ]);

        $this->assertEquals(50000.00, $sale->sale_amount);
        $this->assertEquals(10000.00, $sale->commission_amount);
        $this->assertEquals(40000.00, $sale->vendor_net_amount);

        // Find Journal Voucher created for this vendor sale
        $jv = \App\Models\JournalVoucher::where('marquee_id', $this->marquee->id)
            ->where('reference', $sale->vendor_sale_number)
            ->with('items.account')
            ->first();

        $this->assertNotNull($jv, 'Journal voucher for vendor sale should exist');
        $this->assertEquals('posted', $jv->status);

        $totalDebits = $jv->items->sum('debit');
        $totalCredits = $jv->items->sum('credit');

        // Balanced double-entry check
        $this->assertEquals(50000.00, $totalDebits);
        $this->assertEquals(50000.00, $totalCredits);
        $this->assertEquals($totalDebits, $totalCredits);

        // Assert debit is on Accounts Receivable
        $debitItem = $jv->items->where('debit', '>', 0)->first();
        $this->assertEquals('1003', $debitItem->account->account_code);
        $this->assertEquals(50000.00, (float)$debitItem->debit);

        // Assert credits are on Commission Income (10,000) and Vendor Payable Clearing (40,000)
        $incomeItem = $jv->items->where('credit', 10000.00)->first();
        $this->assertNotNull($incomeItem);
        $this->assertStringContainsString('Vendor Commission Income', $incomeItem->account->name);

        $payableItem = $jv->items->where('credit', 40000.00)->first();
        $this->assertNotNull($payableItem);
        $this->assertEquals('2150-VEN', $payableItem->account->account_code);
    }

    /** @test */
    public function test_chart_of_accounts_component_renders_accounts_tree_for_active_marquee_user()
    {
        $this->actingAs($this->owner);

        // Owner has marquee_id = null on user table, relying on getActiveMarqueeId()
        Livewire::test(\App\Livewire\Finance\ChartOfAccounts::class)
            ->assertStatus(200)
            ->assertDontSee('No accounts found in Chart of Accounts.')
            ->assertSee('Assets')
            ->assertSee('1000')
            ->assertSee('Cash')
            ->assertSee('1001')
            ->assertSee('Liabilities')
            ->assertSee('2000')
            ->assertSee('Accounts Payable')
            ->assertSee('2001')
            ->assertSee('Income')
            ->assertSee('4000')
            ->assertSee('Expenses')
            ->assertSee('5000');
    }

    /** @test */
    public function test_vendor_service_manager_populates_service_provider_dropdown_and_saves_service()
    {
        $this->actingAs($this->owner);

        $vendor1 = Vendor::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'vendor_code' => 'VEND-002',
            'name' => 'Elite Photography Studio',
            'contact_person' => 'Hamza Tariq',
            'phone' => '03009998877',
            'vendor_type' => 'Photography',
            'status' => 'active',
        ]);

        $vendor2 = Vendor::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'vendor_code' => 'VEND-003',
            'name' => 'Royal Sound & Lights',
            'contact_person' => 'Bilal Ahmed',
            'phone' => '03008887766',
            'vendor_type' => 'Sound System',
            'status' => 'active',
        ]);

        // Open create modal on VendorServiceManager (standalone catalogue mode)
        Livewire::test(\App\Livewire\VendorServiceManager::class)
            ->assertStatus(200)
            ->call('openCreateModal')
            ->assertSet('showServiceModal', true)
            ->assertSee('Elite Photography Studio (Photography)')
            ->assertSee('Royal Sound & Lights (Sound System)')
            ->assertViewHas('vendors', function ($vendors) use ($vendor1, $vendor2) {
                return $vendors->contains('id', $vendor1->id) && $vendors->contains('id', $vendor2->id);
            })
            ->set('selectedVendorId', $vendor1->id)
            ->set('service_name', 'Full Day Wedding Drone & 4K Video Coverage')
            ->set('unit', 'Event')
            ->set('default_sale_price', 75000.00)
            ->set('status', 'active')
            ->set('description', 'Includes 2 DSLR cameramen, 1 drone operator, and 4K editing.')
            ->call('saveService')
            ->assertHasNoErrors()
            ->assertSet('showServiceModal', false)
            ->assertSee('Full Day Wedding Drone & 4K Video Coverage')
            ->assertSee('Elite Photography Studio');

        $this->assertDatabaseHas('vendor_services', [
            'marquee_id' => $this->marquee->id,
            'vendor_id' => $vendor1->id,
            'service_name' => 'Full Day Wedding Drone & 4K Video Coverage',
            'default_sale_price' => 75000.00,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function test_booking_wizard_and_one_page_forms_use_dd_mm_yyyy_format()
    {
        $this->actingAs($this->owner);

        // 1. Booking Wizard: test initial dd-mm-yyyy default and setting a custom dd-mm-yyyy date
        $testDate = now()->addDays(10)->format('d-m-Y');
        $expectedDbDate = now()->addDays(10)->format('Y-m-d');

        $wizard = Livewire::test(\App\Livewire\BookingWizard::class)
            ->assertStatus(200)
            ->set('currentStep', 2)
            ->assertSee('Event Date (DD-MM-YYYY)')
            ->assertSet('selectedDate', now()->addDay()->format('d-m-Y'))
            ->set('selectedDate', $testDate);

        $this->assertEquals($expectedDbDate, $wizard->instance()->getNormalizedDate());

        // 2. Booking One Page: test initial dd-mm-yyyy default and custom dd-mm-yyyy date
        $onePage = Livewire::test(\App\Livewire\BookingOnePage::class)
            ->assertStatus(200)
            ->assertSee('Event Date (DD-MM-YYYY)')
            ->assertSet('selectedDate', now()->addDay()->format('d-m-Y'))
            ->set('selectedDate', $testDate);

        $this->assertEquals($expectedDbDate, $onePage->instance()->getNormalizedDate());

        // 3. Availability Checker: test dd-mm-yyyy support
        $checker = Livewire::test(\App\Livewire\AvailabilityChecker::class)
            ->assertStatus(200)
            ->assertSee('Booking Date (DD-MM-YYYY)')
            ->assertSet('selectedDate', now()->format('d-m-Y'))
            ->set('selectedDate', $testDate);

        $this->assertEquals($expectedDbDate, $checker->instance()->getNormalizedDate());
    }
}
