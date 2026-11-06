<?php

namespace Tests\Feature;

use App\Livewire\VendorReports;
use App\Livewire\VendorSettlementManager;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Package;
use App\Models\Role;
use App\Models\Slot;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorLedger;
use App\Models\VendorSale;
use App\Models\VendorService;
use App\Models\VendorSettlement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorSettlementsAndReportsUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected Marquee $marquee;
    protected Branch $branch;
    protected User $user;
    protected Customer $customer;
    protected Booking $booking;
    protected Vendor $vendor;
    protected VendorService $vendorService;
    protected VendorSale $sale;
    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = SubscriptionPlan::create([
            'name' => 'Elite Plan',
            'slug' => 'elite-plan',
            'price' => 20000,
            'billing_interval' => 'month',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Grand Carlton Banquet',
            'slug' => 'grand-carlton',
            'is_active' => true,
            'status' => 'active',
            'email' => 'info@carlton.pk',
            'phone' => '03001234567',
            'address' => 'Gulberg',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'subscription_plan_id' => $plan->id,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Executive Branch',
            'code' => 'BR-01',
            'address' => 'Lahore',
            'phone' => '042-3571234',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'status' => 'active',
        ]);

        $role = Role::create([
            'name' => 'super_admin',
            'label' => 'Super Administrator',
            'marquee_id' => $this->marquee->id,
        ]);

        $this->user = User::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Carlton Admin',
            'email' => 'admin@carlton.pk',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_super_admin' => true,
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'first_name' => 'Sheikh',
            'last_name' => 'Khurram',
            'phone_number' => '03215554433',
            'email' => 'khurram@example.com',
            'city' => 'Lahore',
            'province' => 'Punjab',
        ]);

        $eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Walima Dinner',
            'event_type_code' => 'EVT-WALIMA',
            'status' => 'active',
        ]);

        $hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Carlton Ballroom',
            'hall_code' => 'HALL-01',
            'hall_type' => 'Banquet Hall',
            'default_booking_price' => 60000,
            'capacity' => 500,
            'status' => 'active',
        ]);

        $slot = Slot::create([
            'marquee_id' => $this->marquee->id,
            'slot_name' => 'Dinner Slot',
            'start_time' => '19:00:00',
            'end_time' => '23:30:00',
        ]);

        $this->booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $eventType->id,
            'hall_id' => $hall->id,
            'slot_id' => $slot->id,
            'booking_number' => 'BK-2026-7777',
            'booking_date' => date('Y-m-d'),
            'start_time' => date('Y-m-d 19:00:00'),
            'end_time' => date('Y-m-d 23:30:00'),
            'guest_count' => 300,
            'tentative_guests' => 300,
            'per_plate_price' => 3000,
            'package_amount' => 900000,
            'hall_charges' => 60000,
            'subtotal' => 960000,
            'total_amount' => 960000,
            'status' => 'Confirmed',
            'created_by' => $this->user->id,
        ]);

        $this->vendor = Vendor::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Royal Sound & Lights',
            'vendor_code' => 'VEN-SOUND-01',
            'vendor_type' => 'Sound System',
            'phone' => '03004443322',
            'status' => 'active',
            'opening_balance' => 0,
        ]);

        $this->vendorService = VendorService::create([
            'marquee_id' => $this->marquee->id,
            'vendor_id' => $this->vendor->id,
            'service_name' => 'Premium DJ & Concert Sound',
            'service_code' => 'SRV-DJ-01',
            'price' => 75000,
            'status' => 'active',
        ]);

        $this->sale = VendorSale::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'vendor_id' => $this->vendor->id,
            'vendor_service_id' => $this->vendorService->id,
            'booking_id' => $this->booking->id,
            'vendor_sale_number' => 'VS-2026-0001',
            'sale_date' => date('Y-m-d'),
            'event_date' => date('Y-m-d'),
            'quantity' => 1,
            'unit' => 'Event',
            'sale_amount' => 75000,
            'commission_rate' => 10,
            'commission_amount' => 7500,
            'vendor_net_amount' => 67500,
            'status' => 'confirmed',
        ]);

        $accType = AccountType::create([
            'name' => 'Bank Accounts',
            'code' => 'BANK_ACC',
            'nature' => 'Asset',
        ]);

        $this->account = Account::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Meezan Bank Operations',
            'account_code' => '1005',
            'account_type_id' => $accType->id,
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        // Create 20 settlements to test pagination
        for ($i = 1; $i <= 20; $i++) {
            VendorSettlement::create([
                'marquee_id' => $this->marquee->id,
                'vendor_id' => $this->vendor->id,
                'settlement_number' => 'SET-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'settlement_date' => date('Y-m-d', strtotime("-{$i} days")),
                'net_payable_amount' => 50000,
                'paid_amount' => 50000,
                'remaining_balance' => 0,
                'payment_method' => 'Bank Transfer',
                'account_id' => $this->account->id,
                'status' => 'fully_settled',
                'remarks' => "Settlement payment payout #{$i}",
            ]);
        }

        // Create vendor ledger record
        VendorLedger::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'vendor_id' => $this->vendor->id,
            'vendor_sale_id' => $this->sale->id,
            'booking_id' => $this->booking->id,
            'transaction_date' => date('Y-m-d'),
            'reference_number' => 'REF-LDR-001',
            'transaction_type' => 'sale_credit',
            'description' => 'Sound system setup for Walima event',
            'sale_amount' => 75000,
            'commission_amount' => 7500,
            'payment_amount' => 0,
            'running_balance' => 67500,
        ]);
    }

    public function test_vendor_settlements_renders_pagination_and_export_buttons(): void
    {
        $this->actingAs($this->user);

        $test = Livewire::test(VendorSettlementManager::class);

        $test->assertStatus(200)
            ->assertSee('SET-2026-0001')
            ->assertSee('Export CSV')
            ->assertSee('Print')
            ->assertSee('@media print', false)
            ->assertSee('print-letterhead', false)
            ->assertSee('Service Provider Settlements', false)
            ->assertSee('TOTALS:')
            ->assertSee('Rs. ' . number_format(1000000))
            ->assertSee('Showing <strong>1</strong> to <strong>15</strong> of <strong>20</strong>', false)
            ->assertSee('class="pagination"', false);

        // Change perPage to 25 -> all 20 records on page 1
        $test->set('perPage', 25);
        $test->assertSee('Showing <strong>1</strong> to <strong>20</strong> of <strong>20</strong>', false);
    }

    public function test_vendor_settlements_export_csv_streams_content(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(VendorSettlementManager::class);

        $response = $component->instance()->exportCsv();
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());

        ob_start();
        $response->sendContent();
        $csvContent = ob_get_clean();

        $this->assertStringContainsString('SERVICE PROVIDER SETTLEMENTS & PAYOUTS', $csvContent);
        $this->assertStringContainsString('SET-2026-0001', $csvContent);
        $this->assertStringContainsString('Royal Sound & Lights', $csvContent);
        $this->assertStringContainsString('Bank Transfer', $csvContent);
    }

    public function test_vendor_reports_displays_customer_name_alongside_booking_number(): void
    {
        $this->actingAs($this->user);

        // 1. Sales Report
        Livewire::test(VendorReports::class)
            ->set('reportType', 'sales')
            ->assertStatus(200)
            ->assertSee('BK-2026-7777')
            ->assertSee('Sheikh Khurram') // Customer Name
            ->assertSee('Export CSV')
            ->assertSee('Print Report');

        // 2. Commission Report
        Livewire::test(VendorReports::class)
            ->set('reportType', 'commission')
            ->assertStatus(200)
            ->assertSee('BK-2026-7777')
            ->assertSee('Sheikh Khurram');

        // 3. Ledger Report
        Livewire::test(VendorReports::class)
            ->set('reportType', 'ledger')
            ->assertStatus(200)
            ->assertSee('BK-2026-7777')
            ->assertSee('Sheikh Khurram');
    }

    public function test_vendor_reports_export_csv_streams_sales_and_ledger(): void
    {
        $this->actingAs($this->user);

        // Test Sales CSV Export
        $componentSales = Livewire::test(VendorReports::class)->set('reportType', 'sales');
        $responseSales = $componentSales->instance()->exportCsv();

        ob_start();
        $responseSales->sendContent();
        $csvSales = ob_get_clean();

        $this->assertStringContainsString('SERVICE PROVIDER SALES REPORT', $csvSales);
        $this->assertStringContainsString('Customer Name', $csvSales);
        $this->assertStringContainsString('BK-2026-7777', $csvSales);
        $this->assertStringContainsString('Sheikh Khurram', $csvSales);

        // Test Ledger CSV Export
        $componentLedger = Livewire::test(VendorReports::class)->set('reportType', 'ledger');
        $responseLedger = $componentLedger->instance()->exportCsv();

        ob_start();
        $responseLedger->sendContent();
        $csvLedger = ob_get_clean();

        $this->assertStringContainsString('SERVICE PROVIDER LEDGER REPORT', $csvLedger);
        $this->assertStringContainsString('Customer Name', $csvLedger);
        $this->assertStringContainsString('BK-2026-7777', $csvLedger);
        $this->assertStringContainsString('Sheikh Khurram', $csvLedger);
    }

    public function test_vendor_reports_accurate_print_letterhead_totals_and_period_labels(): void
    {
        $this->actingAs($this->user);

        // 1. Ledger Print Accuracy & Summary Totals
        Livewire::test(VendorReports::class)
            ->set('reportType', 'ledger')
            ->set('vendor_id', $this->vendor->id)
            ->assertStatus(200)
            ->assertSee('@media print', false)
            ->assertSee('print-letterhead', false)
            ->assertSee($this->marquee->name)
            ->assertSee('Service Provider Financial Ledger Statement')
            ->assertSee('Royal Sound & Lights')
            ->assertSee('TOTALS / CLOSING:')
            ->assertSee('Rs. ' . number_format(75000)) // Sale credit total
            ->assertSee('Rs. ' . number_format(7500))  // Commission debit total
            ->assertSee('Rs. ' . number_format(67500)); // Running balance

        // 2. Settlement Print Accuracy & Summary Totals
        $settlementCount = VendorSettlement::where('vendor_id', $this->vendor->id)
            ->whereBetween('settlement_date', [date('Y-m-01'), date('Y-m-d')])
            ->count();
        $expectedSettlementTotal = $settlementCount * 50000;

        Livewire::test(VendorReports::class)
            ->set('reportType', 'settlement')
            ->set('vendor_id', $this->vendor->id)
            ->assertStatus(200)
            ->assertSee('Service Provider Settlement Report')
            ->assertSee('TOTALS:')
            ->assertSee('Rs. ' . number_format($expectedSettlementTotal)) // Net payable total
            ->assertSee('Rs. ' . number_format($expectedSettlementTotal)) // Paid amount total
            ->assertSee('Rs. 0');                       // Remaining balance total

        // 3. Monthly Period Label Formatting
        Livewire::test(VendorReports::class)
            ->set('reportType', 'monthly')
            ->set('year', '2026')
            ->set('month', '11')
            ->assertStatus(200)
            ->assertSee('November 2026')
            ->assertSee('Monthly Commission Summary Report');
    }
}
