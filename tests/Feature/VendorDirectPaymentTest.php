<?php

namespace Tests\Feature;

use App\Livewire\BookingView;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\JournalVoucher;
use App\Models\Marquee;
use App\Models\Package;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Slot;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorLedger;
use App\Models\VendorSale;
use App\Models\VendorService;
use App\Services\VendorCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorDirectPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected Marquee $marquee;
    protected Branch $branch;
    protected User $owner;
    protected Customer $customer;
    protected EventType $eventType;
    protected Hall $hall;
    protected Slot $slot;
    protected Package $package;
    protected Booking $booking;
    protected Vendor $vendor;
    protected VendorService $vendorService;
    protected Account $cashAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->marquee = Marquee::create([
            'name' => 'Royal Palms Grand Banquet',
            'slug' => 'royal-palms',
            'is_active' => true,
            'status' => 'active',
            'email' => 'info@royalpalms.com',
            'phone' => '03001234567',
            'address' => 'Mall Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Gulberg Central',
            'code' => 'BR-01',
            'address' => 'Main Boulevard Gulberg',
            'phone' => '042-3571234',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'status' => 'active',
            'is_head_office' => true,
        ]);

        $ownerRole = Role::firstOrCreate(
            ['name' => 'owner'],
            ['display_name' => 'Owner', 'label' => 'Owner', 'marquee_id' => $this->marquee->id, 'description' => 'Owner']
        );

        $permView = Permission::firstOrCreate(['name' => 'view_bookings'], ['label' => 'View Bookings']);
        $permEdit = Permission::firstOrCreate(['name' => 'edit_bookings'], ['label' => 'Edit Bookings']);
        $ownerRole->permissions()->syncWithoutDetaching([$permView->id, $permEdit->id]);

        $this->owner = User::create([
            'name' => 'Malik Tariq',
            'email' => 'tariq@royalpalms.com',
            'password' => bcrypt('secret123'),
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'first_name' => 'Chaudhry',
            'last_name' => 'Pervaiz',
            'phone_number' => '03219876543',
            'email' => 'pervaiz@gmail.com',
        ]);

        $this->eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Barat Reception',
            'event_type_code' => 'EVT-BARAT',
            'status' => 'active',
        ]);

        $this->hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Grand Crystal Ballroom',
            'hall_code' => 'HALL-01',
            'capacity' => 500,
            'hall_type' => 'Banquet Hall',
            'default_booking_price' => 50000,
            'status' => 'active',
        ]);

        $this->slot = Slot::create([
            'marquee_id' => $this->marquee->id,
            'slot_name' => 'Evening Slot',
            'slot_code' => 'SLOT-EVE',
            'start_time' => '18:00:00',
            'end_time' => '23:00:00',
        ]);

        $this->package = Package::create([
            'marquee_id' => $this->marquee->id,
            'package_name' => 'Royal Dinner Plan',
            'package_code' => 'PKG-ROYAL',
            'per_plate_price' => 2500,
            'status' => 'active',
        ]);

        $this->booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hall->id,
            'slot_id' => $this->slot->id,
            'package_id' => $this->package->id,
            'booking_date' => '2026-10-15',
            'start_time' => '2026-10-15 18:00:00',
            'end_time' => '2026-10-15 23:00:00',
            'guest_count' => 200,
            'tentative_guests' => 200,
            'per_plate_price' => 2500,
            'package_amount' => 500000,
            'hall_charges' => 50000,
            'extra_charges' => 0,
            'discount_amount' => 0,
            'security_deposit' => 25000,
            'tax_amount' => 71500,
            'subtotal' => 550000,
            'grand_total' => 646500,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Unpaid',
            'deposit_status' => 'Held',
            'created_by' => $this->owner->id,
        ]);

        $this->vendor = Vendor::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'name' => 'Al-Makkah Decorators',
            'vendor_code' => 'VEND-001',
            'vendor_type' => 'Stage Decorator',
            'contact_person' => 'Kamran Ali',
            'phone' => '03011234567',
            'status' => 'active',
            'created_by' => $this->owner->id,
        ]);

        $this->vendorService = VendorService::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'vendor_id' => $this->vendor->id,
            'service_name' => 'Grand Floral Stage',
            'default_sale_price' => 50000.00,
            'default_vendor_cost' => 40000.00,
            'status' => 'active',
        ]);

        $assetType = AccountType::firstOrCreate(
            ['name' => 'Current Assets'],
            ['code' => 'CASSET', 'nature' => 'Asset']
        );

        $this->cashAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1001',
            'name' => 'Cash in Hand (Counter)',
            'account_type_id' => $assetType->id,
            'nature' => 'Asset',
            'is_active' => true,
        ]);
    }

    public function test_direct_pay_vendor_sale_has_zero_remaining_payable_and_posts_commission_debit()
    {
        $service = app(VendorCommissionService::class);

        // Create direct pay sale: sale = 50,000, vendor net = 40,000, commission = 10,000
        $sale = $service->createVendorSale([
            'vendor_id' => $this->vendor->id,
            'vendor_service_id' => $this->vendorService->id,
            'booking_id' => $this->booking->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'sale_amount' => 50000.00,
            'commission_rate' => 20.00,
            'include_in_invoice' => false, // Direct Customer Payment
            'notes' => 'Customer pays vendor directly at stage setup',
        ]);

        $this->assertFalse($sale->include_in_invoice);
        $this->assertEquals(0.00, (float) $sale->remaining_amount);
        $this->assertEquals(40000.00, (float) $sale->paid_amount);
        $this->assertEquals('fully_paid', $sale->payment_status);
        $this->assertEquals(50000.00, (float) $sale->customer_paid);
        $this->assertEquals(0.00, (float) $sale->customer_remaining);
        $this->assertEquals(0.00, (float) $sale->vendor_remaining);

        // Check Vendor Ledger: should post commission_debit (NOT sale_credit)
        $ledger = VendorLedger::where('vendor_sale_id', $sale->id)->first();
        $this->assertNotNull($ledger);
        $this->assertEquals('commission_debit', $ledger->transaction_type);
        $this->assertEquals(10000.00, (float) $ledger->commission_amount);
        $this->assertEquals(-10000.00, (float) $ledger->running_balance);

        // Check General Ledger Journal Voucher
        $jv = JournalVoucher::where('reference', $sale->vendor_sale_number)->with('items.account')->first();
        $this->assertNotNull($jv);
        $this->assertEquals(2, $jv->items->count());

        $debitItem = $jv->items->where('debit', '>', 0)->first();
        $creditItem = $jv->items->where('credit', '>', 0)->first();

        // Debit: Vendor Commission Receivable (1005-VEN)
        $this->assertEquals('1005-VEN', $debitItem->account->account_code);
        $this->assertEquals(10000.00, (float) $debitItem->debit);

        // Credit: Vendor Commission Income (4005)
        $this->assertEquals('4005', $creditItem->account->account_code);
        $this->assertEquals(10000.00, (float) $creditItem->credit);

        // Ensure NO Accounts Receivable and NO Vendor Payable Clearing items exist
        $this->assertNull($jv->items->firstWhere('account.account_code', '1003'));
        $this->assertNull($jv->items->firstWhere('account.account_code', '2150-VEN'));
    }

    public function test_updating_invoiced_vendor_sale_to_direct_pay_clears_payable_and_updates_accounting()
    {
        $service = app(VendorCommissionService::class);

        // 1. Create invoiced sale
        $sale = $service->createVendorSale([
            'vendor_id' => $this->vendor->id,
            'vendor_service_id' => $this->vendorService->id,
            'booking_id' => $this->booking->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'sale_amount' => 50000.00,
            'commission_rate' => 20.00,
            'include_in_invoice' => true,
        ]);

        $this->assertTrue($sale->include_in_invoice);
        $this->assertEquals(40000.00, (float) $sale->remaining_amount);
        $this->assertEquals(40000.00, (float) $this->vendor->fresh()->current_balance);

        // 2. Convert to Direct Pay
        $updatedSale = $service->updateVendorSale($sale, [
            'include_in_invoice' => false,
            'notes' => 'Converted to Direct Pay by customer',
        ]);

        $this->assertFalse($updatedSale->include_in_invoice);
        $this->assertEquals(0.00, (float) $updatedSale->remaining_amount);
        $this->assertEquals(40000.00, (float) $updatedSale->paid_amount);
        $this->assertEquals('fully_paid', $updatedSale->payment_status);

        // Vendor running balance should now be -10,000 (vendor owes Marquee commission)
        $this->assertEquals(-10000.00, (float) $this->vendor->fresh()->current_balance);

        // GL Journal Voucher should now have 1005-VEN and 4005 only
        $jv = JournalVoucher::where('reference', $sale->vendor_sale_number)->with('items.account')->first();
        $this->assertNotNull($jv);
        $this->assertNull($jv->items->firstWhere('account.account_code', '1003'));
        $this->assertNull($jv->items->firstWhere('account.account_code', '2150-VEN'));
        $this->assertNotNull($jv->items->firstWhere('account.account_code', '1005-VEN'));
    }

    public function test_collecting_vendor_commission_updates_ledger_and_records_cash_receipt()
    {
        $service = app(VendorCommissionService::class);

        $sale = $service->createVendorSale([
            'vendor_id' => $this->vendor->id,
            'vendor_service_id' => $this->vendorService->id,
            'booking_id' => $this->booking->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'sale_amount' => 50000.00,
            'commission_rate' => 20.00,
            'include_in_invoice' => false,
        ]);

        $this->assertEquals(10000.00, (float) $sale->commission_remaining_amount);
        $this->assertEquals(0.00, (float) $sale->commission_collected);

        // Remit / Collect partial commission of 6,000
        $ledgerEntry = $service->recordVendorCommissionCollection($sale, 6000.00, [
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
            'remarks' => 'Partial cash commission received from vendor',
        ]);

        $sale->refresh();
        $this->assertEquals(6000.00, (float) $sale->commission_collected);
        $this->assertEquals(4000.00, (float) $sale->commission_remaining_amount);
        $this->assertEquals(-4000.00, (float) $this->vendor->fresh()->current_balance);

        // Remit / Collect remaining commission of 4,000
        $service->recordVendorCommissionCollection($sale, 4000.00, [
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
            'remarks' => 'Final commission settlement received',
        ]);

        $sale->refresh();
        $this->assertEquals(10000.00, (float) $sale->commission_collected);
        $this->assertEquals(0.00, (float) $sale->commission_remaining_amount);
        $this->assertEquals(0.00, (float) $this->vendor->fresh()->current_balance);

        // Verify collection JVs: Debit Cash 1001, Credit 1005-VEN
        $colJvs = JournalVoucher::where('reference', 'LIKE', '%COL-COMM-' . $sale->vendor_sale_number . '%')->with('items.account')->get();
        $this->assertEquals(2, $colJvs->count());

        foreach ($colJvs as $colJv) {
            $debitCash = $colJv->items->where('debit', '>', 0)->first();
            $creditReceivable = $colJv->items->where('credit', '>', 0)->first();

            $this->assertEquals('1001', $debitCash->account->account_code);
            $this->assertEquals('1005-VEN', $creditReceivable->account->account_code);
        }
    }

    public function test_booking_view_ui_handles_direct_pay_and_commission_collection()
    {
        $this->actingAs($this->owner);

        $service = app(VendorCommissionService::class);
        $sale = $service->createVendorSale([
            'vendor_id' => $this->vendor->id,
            'vendor_service_id' => $this->vendorService->id,
            'booking_id' => $this->booking->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'sale_amount' => 50000.00,
            'commission_rate' => 20.00,
            'include_in_invoice' => false,
        ]);

        Livewire::test(BookingView::class, ['booking' => $this->booking])
            ->assertSee('Direct Pay')
            ->assertSee('Deducted by Vendor')
            ->assertSee('Cleared')
            ->assertSee('Collect Comm (Rs. 10,000)')
            ->call('openCollectCommissionModal', $sale->id)
            ->assertSet('showCollectCommissionModal', true)
            ->assertSet('ccRemainingCommission', 10000.00)
            ->set('ccCollectionAmount', 10000.00)
            ->set('ccPaymentDate', date('Y-m-d'))
            ->set('ccPaymentMethod', 'Cash')
            ->call('recordVendorCommissionCollection')
            ->assertSet('showCollectCommissionModal', false)
            ->assertSee('Comm Collected');

        $sale->refresh();
        $this->assertEquals(10000.00, (float) $sale->commission_collected);
        $this->assertEquals(0.00, (float) $sale->commission_remaining_amount);
    }

    public function test_booking_view_vendor_payment_modal_can_mark_as_direct_pay()
    {
        $this->actingAs($this->owner);

        $service = app(VendorCommissionService::class);
        // Create an invoiced sale that shows a remaining payable balance
        $sale = $service->createVendorSale([
            'vendor_id' => $this->vendor->id,
            'vendor_service_id' => $this->vendorService->id,
            'booking_id' => $this->booking->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'sale_amount' => 50000.00,
            'commission_rate' => 20.00,
            'include_in_invoice' => true,
        ]);

        $this->assertEquals(40000.00, (float) $sale->remaining_amount);

        // Open Vendor Payment Modal and mark as Direct Customer Payment (Vendor Deducted Fee)
        Livewire::test(BookingView::class, ['booking' => $this->booking])
            ->call('openVendorPaymentModal', $sale->id)
            ->assertSet('showVendorPaymentModal', true)
            ->assertSet('vpRemainingBalance', 40000.00)
            ->set('vpIsDirectDeduction', true)
            ->call('recordVendorInstallmentPayment')
            ->assertSet('showVendorPaymentModal', false)
            ->assertSee('Direct Pay')
            ->assertSee('Deducted by Vendor')
            ->assertSee('Cleared');

        $sale->refresh();
        $this->assertFalse($sale->include_in_invoice);
        $this->assertEquals(0.00, (float) $sale->remaining_amount);
        $this->assertEquals(40000.00, (float) $sale->paid_amount);
        $this->assertEquals('fully_paid', $sale->payment_status);
        $this->assertEquals(-10000.00, (float) $this->vendor->fresh()->current_balance);
    }
}
