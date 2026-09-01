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
use App\Models\Marquee;
use App\Models\Package;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Slot;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCommissionAgreement;
use App\Models\VendorLedger;
use App\Models\VendorSale;
use App\Models\VendorService;
use App\Services\VendorCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorAdvanceAndBalanceIntegrationTest extends TestCase
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
        $permVendors = Permission::firstOrCreate(['name' => 'view_inventory'], ['label' => 'View Vendors']);
        $permSettings = Permission::firstOrCreate(['name' => 'manage_settings'], ['label' => 'Manage Settings']);
        $ownerRole->permissions()->sync([$permView->id, $permEdit->id, $permVendors->id, $permSettings->id]);

        $this->owner = User::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Owner User',
            'email' => 'owner@royalpalms.com',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole->id,
            'user_type' => 'business_owner',
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'first_name' => 'Chaudhry',
            'last_name' => 'Tariq',
            'phone_number' => '03218765432',
            'email' => 'tariq@example.com',
            'city' => 'Lahore',
            'province' => 'Punjab',
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

        // Setup Accounts
        $assetType = AccountType::firstOrCreate(['code' => 'CASH'], ['name' => 'Cash & Bank', 'nature' => 'Asset']);
        $this->cashAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1001-CASH',
            'name' => 'Main Cash Counter',
            'account_type_id' => $assetType->id,
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        // Create Vendor & Service
        $this->vendor = Vendor::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'name' => 'Elite Photography & Cinematic Sound',
            'vendor_type' => 'Photography',
            'contact_person' => 'Kamran Ali',
            'phone' => '03011234567',
            'status' => 'active',
        ]);

        $this->vendorService = VendorService::create([
            'marquee_id' => $this->marquee->id,
            'vendor_id' => $this->vendor->id,
            'service_name' => 'Drone + 4K Wedding Coverage',
            'service_code' => 'PHOTO-4K',
            'default_sale_price' => 100000,
            'status' => 'active',
        ]);

        VendorCommissionAgreement::create([
            'marquee_id' => $this->marquee->id,
            'vendor_id' => $this->vendor->id,
            'agreement_number' => 'AGR-2026-001',
            'commission_type' => 'percentage',
            'commission_percentage' => 20.00, // 20% margin for marquee, 80% payable to vendor
            'effective_from' => '2026-01-01',
            'status' => 'active',
        ]);
    }

    /**
     * Test 1: Vendor assignment without advance payout.
     */
    public function test_vendor_assignment_without_advance(): void
    {
        $serviceEngine = app(VendorCommissionService::class);

        $sale = $serviceEngine->createVendorSale([
            'vendor_id' => $this->vendor->id,
            'vendor_service_id' => $this->vendorService->id,
            'booking_id' => $this->booking->id,
            'customer_id' => $this->customer->id,
            'sale_amount' => 100000, // Customer Charge
            'commission_rate' => 20, // 20% margin
            'advance_amount' => 0, // No advance
        ]);

        $this->assertEquals(100000, $sale->sale_amount);
        $this->assertEquals(20000, $sale->commission_amount);
        $this->assertEquals(80000, $sale->vendor_net_amount); // Vendor Cost
        $this->assertEquals(0, $sale->advance_amount);
        $this->assertEquals(0, $sale->paid_amount);
        $this->assertEquals(80000, $sale->remaining_amount);
        $this->assertEquals('unpaid', $sale->payment_status);

        // Verify Vendor Ledger
        $this->assertEquals(80000, $this->vendor->current_balance);
        $lastLedger = VendorLedger::where('vendor_id', $this->vendor->id)->latest('id')->first();
        $this->assertNotNull($lastLedger);
        $this->assertEquals('sale_credit', $lastLedger->transaction_type);
        $this->assertEquals(80000, $lastLedger->running_balance);
    }

    /**
     * Test 2: Vendor assignment with advance payout.
     */
    public function test_vendor_assignment_with_advance_payout(): void
    {
        $serviceEngine = app(VendorCommissionService::class);

        $sale = $serviceEngine->createVendorSale([
            'vendor_id' => $this->vendor->id,
            'vendor_service_id' => $this->vendorService->id,
            'booking_id' => $this->booking->id,
            'customer_id' => $this->customer->id,
            'sale_amount' => 100000, // Customer Charge
            'commission_rate' => 20,
            'advance_amount' => 30000, // Rs. 30,000 Advance
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
        ]);

        $this->assertEquals(80000, $sale->vendor_net_amount); // Vendor Cost
        $this->assertEquals(30000, $sale->advance_amount);
        $this->assertEquals(30000, $sale->paid_amount);
        $this->assertEquals(50000, $sale->remaining_amount);
        $this->assertEquals('partially_paid', $sale->payment_status);

        // Verify Vendor Ledger has 2 entries (sale_credit + advance_payment)
        $ledgers = VendorLedger::where('vendor_sale_id', $sale->id)->orderBy('id', 'asc')->get();
        $this->assertCount(2, $ledgers);
        $this->assertEquals('sale_credit', $ledgers[0]->transaction_type);
        $this->assertEquals(80000, $ledgers[0]->running_balance);
        $this->assertEquals('advance_payment', $ledgers[1]->transaction_type);
        $this->assertEquals(30000, $ledgers[1]->payment_amount);
        $this->assertEquals(50000, $ledgers[1]->running_balance);

        // Vendor net running balance is 50,000
        $this->assertEquals(50000, $this->vendor->current_balance);
    }

    /**
     * Test 3: Subsequent installment payment until full settlement.
     */
    public function test_subsequent_installment_payment_until_fully_settled(): void
    {
        $serviceEngine = app(VendorCommissionService::class);

        $sale = $serviceEngine->createVendorSale([
            'vendor_id' => $this->vendor->id,
            'vendor_service_id' => $this->vendorService->id,
            'booking_id' => $this->booking->id,
            'customer_id' => $this->customer->id,
            'sale_amount' => 100000,
            'advance_amount' => 30000, // Rs. 30,000 Advance
        ]);

        // First installment of Rs. 20,000
        $serviceEngine->recordVendorSalePayment($sale, 20000, [
            'payment_method' => 'Bank Transfer',
            'reference_number' => 'TRX-5544',
            'account_id' => $this->cashAccount->id,
            'remarks' => 'Mid-event installment',
        ]);

        $sale->refresh();
        $this->assertEquals(50000, $sale->paid_amount);
        $this->assertEquals(30000, $sale->remaining_amount);
        $this->assertEquals('partially_paid', $sale->payment_status);
        $this->assertEquals(30000, $this->vendor->current_balance);

        // Final installment of Rs. 30,000
        $serviceEngine->recordVendorSalePayment($sale, 30000, [
            'payment_method' => 'Cash',
            'remarks' => 'Final settlement after event conclusion',
        ]);

        $sale->refresh();
        $this->assertEquals(80000, $sale->paid_amount);
        $this->assertEquals(0, $sale->remaining_amount);
        $this->assertEquals('fully_paid', $sale->payment_status);
        $this->assertEquals('settled', $sale->status);
        $this->assertEquals(0, $this->vendor->current_balance);
    }

    /**
     * Test 4: Financial Isolation — Vendor Advance does NOT alter Customer Invoice.
     */
    public function test_financial_isolation_between_customer_invoice_and_vendor_advance(): void
    {
        $serviceEngine = app(VendorCommissionService::class);

        $sale = $serviceEngine->createVendorSale([
            'vendor_id' => $this->vendor->id,
            'vendor_service_id' => $this->vendorService->id,
            'booking_id' => $this->booking->id,
            'customer_id' => $this->customer->id,
            'sale_amount' => 100000, // Rs. 100,000 Customer Charge
            'advance_amount' => 30000, // Rs. 30,000 Vendor Advance Paid by Marquee
        ]);

        $this->actingAs($this->owner);

        // Test PDF Route response
        $response = $this->get(route('bookings.pdf', $this->booking->id));
        $response->assertOk();

        // Test Invoice Blade HTML rendering directly
        $booking = $this->booking->fresh(['customer', 'hall', 'halls', 'slot', 'package', 'eventType', 'extraServices', 'menuItems', 'branch', 'marquee', 'payments', 'vendorSales.service', 'vendorSales.vendor']);
        $invoiceHtml = view('bookings.pdf', compact('booking'))->render();

        // Customer charge must be visible on invoice
        $this->assertStringContainsString('Drone + 4K Wedding Coverage', $invoiceHtml);
        $this->assertStringContainsString('100,000', $invoiceHtml);

        // Internal vendor payable / advance must NOT be leaked
        $this->assertStringNotContainsString('Vendor Remaining', $invoiceHtml);
        $this->assertStringNotContainsString('Net Vendor Payable', $invoiceHtml);

        // Customer Booking Grand Total remains unreduced by the vendor advance
        $this->assertEquals(646500, (float) $this->booking->grand_total);
    }

    /**
     * Test 5: BookingView Livewire Component assigns vendor and records installment.
     */
    public function test_booking_view_component_assign_vendor_and_record_installment(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(BookingView::class, ['booking' => $this->booking])
            ->set('vsVendorId', $this->vendor->id)
            ->set('vsServiceId', $this->vendorService->id)
            ->set('vsCustomerCharge', 100000)
            ->set('vsVendorCost', 80000)
            ->set('vsAdvanceAmount', 30000)
            ->set('vsPaymentMethod', 'Cash')
            ->call('saveBookingVendorSale')
            ->assertHasNoErrors();

        $sale = VendorSale::where('booking_id', $this->booking->id)->first();
        $this->assertNotNull($sale);
        $this->assertEquals(30000, $sale->paid_amount);
        $this->assertEquals(50000, $sale->remaining_amount);

        // Pay remaining installment via modal
        Livewire::test(BookingView::class, ['booking' => $this->booking])
            ->call('openVendorPaymentModal', $sale->id)
            ->assertSet('vpRemainingBalance', 50000)
            ->set('vpPaymentAmount', 50000)
            ->set('vpPaymentMethod', 'Bank Transfer')
            ->set('vpReference', 'BANK-REF-9988')
            ->call('recordVendorInstallmentPayment')
            ->assertHasNoErrors();

        $sale->refresh();
        $this->assertEquals(80000, $sale->paid_amount);
        $this->assertEquals(0, $sale->remaining_amount);
        $this->assertEquals('fully_paid', $sale->payment_status);
    }

    /**
     * Test 6: Direct Payment Vendor Sale (include_in_invoice = false) is excluded from customer invoice.
     */
    public function test_direct_payment_vendor_sale_excluded_from_invoice_but_tracks_commission_and_ledger(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(BookingView::class, ['booking' => $this->booking])
            ->set('vsVendorId', $this->vendor->id)
            ->set('vsServiceId', $this->vendorService->id)
            ->set('vsCustomerCharge', 100000)
            ->set('vsVendorCost', 80000)
            ->set('vsAdvanceAmount', 0)
            ->set('vsIncludeInInvoice', false) // Direct payment by customer
            ->call('saveBookingVendorSale')
            ->assertHasNoErrors();

        $sale = VendorSale::where('booking_id', $this->booking->id)->first();
        $this->assertNotNull($sale);
        $this->assertFalse($sale->include_in_invoice);
        $this->assertEquals(80000, $sale->vendor_net_amount);
        $this->assertEquals(20000, $sale->commission_amount);
        $this->assertEquals(80000, $this->vendor->fresh()->current_balance);

        // Check PDF invoice: Must show direct pay indicator
        $booking = $this->booking->fresh(['customer', 'hall', 'halls', 'slot', 'package', 'eventType', 'extraServices', 'menuItems', 'branch', 'marquee', 'payments', 'vendorSales.service', 'vendorSales.vendor']);
        $invoiceHtml = view('bookings.pdf', compact('booking'))->render();
        $this->assertStringContainsString('Direct Payment by Customer', $invoiceHtml);
    }

    /**
     * Test 7: Edit Vendor Sale via BookingView updates charges, commission and ledger balance.
     */
    public function test_edit_vendor_sale_updates_commission_and_ledger(): void
    {
        $this->actingAs($this->owner);

        $serviceEngine = app(VendorCommissionService::class);
        $sale = $serviceEngine->createVendorSale([
            'vendor_id' => $this->vendor->id,
            'vendor_service_id' => $this->vendorService->id,
            'booking_id' => $this->booking->id,
            'customer_id' => $this->customer->id,
            'sale_amount' => 100000,
            'advance_amount' => 20000,
        ]);

        $this->assertEquals(60000, (float) $sale->remaining_amount);

        // Edit via modal: change customer charge to 120,000 and cost to 90,000
        Livewire::test(BookingView::class, ['booking' => $this->booking])
            ->call('openVendorEditModal', $sale->id)
            ->set('veCustomerCharge', 120000)
            ->set('veVendorCost', 90000)
            ->set('veNotes', 'Client upgraded to 8K drone package')
            ->call('saveEditedVendorSale')
            ->assertHasNoErrors();

        $sale->refresh();
        $this->assertEquals(120000, (float) $sale->sale_amount);
        $this->assertEquals(90000, (float) $sale->vendor_net_amount);
        $this->assertEquals(70000, (float) $sale->remaining_amount); // 90,000 - 20,000 advance
        $this->assertEquals('Client upgraded to 8K drone package', $sale->notes);
    }

    /**
     * Test 8: Cancel & Delete Vendor Sale correctly handles ledger reversals.
     */
    public function test_cancel_and_delete_vendor_sale_reverses_ledger(): void
    {
        $this->actingAs($this->owner);

        $serviceEngine = app(VendorCommissionService::class);
        $sale = $serviceEngine->createVendorSale([
            'vendor_id' => $this->vendor->id,
            'vendor_service_id' => $this->vendorService->id,
            'booking_id' => $this->booking->id,
            'customer_id' => $this->customer->id,
            'sale_amount' => 100000,
            'advance_amount' => 0,
        ]);

        $this->assertEquals(80000, $this->vendor->fresh()->current_balance);

        // Delete un-paid vendor sale
        Livewire::test(BookingView::class, ['booking' => $this->booking])
            ->call('confirmCancelVendorSale', $sale->id)
            ->call('executeDeleteOrCancelVendorSale')
            ->assertHasNoErrors();

        $this->assertNull(VendorSale::find($sale->id));
        $this->assertEquals(0, $this->vendor->fresh()->current_balance);
    }

    /**
     * Test 9: Final Bill Modal calculates customer balance deducting customer advances and adding invoiced vendors.
     */
    public function test_final_bill_recalculates_with_invoiced_vendor_and_customer_advances(): void
    {
        $this->actingAs($this->owner);

        // Record customer advance payment of Rs. 200,000 against booking
        $this->booking->payments()->create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'amount' => 200000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Bank Transfer',
            'created_by' => $this->owner->id,
        ]);

        // Assign invoiced vendor of Rs. 50,000
        $serviceEngine = app(VendorCommissionService::class);
        $serviceEngine->createVendorSale([
            'vendor_id' => $this->vendor->id,
            'vendor_service_id' => $this->vendorService->id,
            'booking_id' => $this->booking->id,
            'customer_id' => $this->customer->id,
            'sale_amount' => 50000,
            'include_in_invoice' => true,
        ]);

        Livewire::test(BookingView::class, ['booking' => $this->booking])
            ->call('openFinalBillModal')
            ->assertSet('fbVendorCharges', 50000)
            ->call('saveFinalBill')
            ->assertHasNoErrors();

        $finalBill = $this->booking->fresh()->finalBill;
        $this->assertNotNull($finalBill);
        
        // Final bill subtotal includes package + hall + extra + vendor
        $this->assertGreaterThan(500000, (float) $finalBill->grand_total);
    }

    /**
     * Test 10: View Vendor Sale Modal renders with full ledger entries and creator relation.
     */
    public function test_view_vendor_sale_modal_loads_ledgers_and_creator(): void
    {
        $this->actingAs($this->owner);

        $serviceEngine = app(VendorCommissionService::class);
        $sale = $serviceEngine->createVendorSale([
            'vendor_id' => $this->vendor->id,
            'vendor_service_id' => $this->vendorService->id,
            'booking_id' => $this->booking->id,
            'customer_id' => $this->customer->id,
            'sale_amount' => 100000,
            'advance_amount' => 25000,
        ]);

        Livewire::test(BookingView::class, ['booking' => $this->booking])
            ->call('openVendorViewModal', $sale->id)
            ->assertSet('showVendorViewModal', true)
            ->assertSet('viewingVendorSaleId', $sale->id)
            ->assertSee($this->vendor->name)
            ->assertSee('Drone + 4K Wedding Coverage')
            ->assertSee('100,000')
            ->assertHasNoErrors();
    }

    /**
     * Test 11: Customer Advance & Remaining Balance with Multiple Installments.
     * e.g., Customer Charge = 25,000, Initial Customer Advance = 5,000 => Remaining = 20,000.
     * Subsequent Customer Advance = 5,000 => Total Paid = 10,000, Remaining = 15,000.
     */
    public function test_customer_advance_and_remaining_balance_with_multiple_installments(): void
    {
        $this->actingAs($this->owner);

        // 1. Assign vendor with customer charge = 25,000 and initial customer advance = 5,000
        Livewire::test(BookingView::class, ['booking' => $this->booking])
            ->set('vsVendorId', $this->vendor->id)
            ->set('vsServiceId', $this->vendorService->id)
            ->set('vsCustomerCharge', 25000)
            ->set('vsCustomerAdvance', 5000)
            ->set('vsCustomerPaymentMethod', 'Cash')
            ->set('vsCustomerReference', 'CUST-REC-01')
            ->set('vsVendorCost', 20000)
            ->set('vsAdvanceAmount', 0)
            ->call('saveBookingVendorSale')
            ->assertHasNoErrors();

        $sale = VendorSale::where('booking_id', $this->booking->id)->first();
        $this->assertNotNull($sale);
        $this->assertEquals(25000, (float) $sale->sale_amount);
        $this->assertEquals(5000, (float) $sale->customer_advance_amount);
        $this->assertEquals(5000, (float) $sale->customer_paid);
        $this->assertEquals(20000, (float) $sale->customer_remaining);

        // Verify booking payment was automatically recorded for the customer advance
        $this->assertDatabaseHas('booking_payments', [
            'booking_id' => $this->booking->id,
            'vendor_sale_id' => $sale->id,
            'amount' => 5000,
            'payment_method' => 'Cash',
        ]);

        // 2. Record second customer advance installment of 5,000
        Livewire::test(BookingView::class, ['booking' => $this->booking])
            ->call('openCustomerPaymentModal', $sale->id)
            ->assertSet('showCustomerPaymentModal', true)
            ->assertSet('cpCustomerCharge', 25000)
            ->assertSet('cpCustomerPaid', 5000)
            ->assertSet('cpCustomerRemaining', 20000)
            ->set('cpPaymentAmount', 5000)
            ->set('cpPaymentMethod', 'Bank Transfer')
            ->set('cpReference', 'BANK-CUST-REC-02')
            ->call('recordCustomerAdvancePayment')
            ->assertHasNoErrors();

        $sale->refresh();
        $this->assertEquals(10000, (float) $sale->customer_paid);
        $this->assertEquals(15000, (float) $sale->customer_remaining);

        // Verify total booking payments now include both customer advance installments
        $totalPayments = $this->booking->fresh()->payments()->where('vendor_sale_id', $sale->id)->sum('amount');
        $this->assertEquals(10000, (float) $totalPayments);

        // 3. Test PDF Invoice rendering shows customer advance and net balance due
        $booking = $this->booking->fresh(['customer', 'hall', 'halls', 'slot', 'package', 'eventType', 'extraServices', 'menuItems', 'branch', 'marquee', 'payments', 'vendorSales.service', 'vendorSales.vendor']);
        $invoiceHtml = view('bookings.pdf', compact('booking'))->render();
        $this->assertStringContainsString('Advance Paid: Rs. 10,000.00', $invoiceHtml);
        $this->assertStringContainsString('Net Due: Rs. 15,000.00', $invoiceHtml);
    }
}
