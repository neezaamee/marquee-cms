<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\CashBankAccount;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\EventType;
use App\Models\FinancialYear;
use App\Models\Hall;
use App\Models\JournalVoucher;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\Slot;
use App\Models\User;
use App\Services\RevenueRecognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingPaymentPostingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Marquee $marquee;
    protected Branch $branch;
    protected User $user;
    protected Customer $customer;
    protected Hall $hall;
    protected Slot $slot;
    protected EventType $eventType;
    protected FinancialYear $financialYear;
    protected Account $cashAccount;
    protected Account $bankAccount;
    protected Account $receivableAccount;
    protected Account $advanceLiabilityAccount;
    protected Account $revenueAccount;
    protected CashBankAccount $cashRecord;
    protected CashBankAccount $bankRecord;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Marquee and Branch
        $this->marquee = Marquee::create([
            'name' => 'Royal Palm Banquet',
            'slug' => 'royal-palm',
            'is_active' => true,
            'status' => 'active',
            'email' => 'info@royalpalm.com',
            'phone' => '03009998877',
            'address' => 'Canal Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Canal View Branch',
            'code' => 'RP-01',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'address' => 'Canal Bank Road',
            'phone' => '042-3599988',
            'status' => 'active',
            'is_head_office' => true,
            'tax_rate' => 16.00,
        ]);

        // 2. Roles and User
        $ownerRole = Role::firstOrCreate(
            ['name' => 'owner'],
            ['display_name' => 'Business Owner', 'label' => 'Owner', 'marquee_id' => $this->marquee->id, 'description' => 'Owner']
        );

        $this->user = User::create([
            'name' => 'Owner Admin',
            'email' => 'admin@royalpalm.com',
            'password' => bcrypt('secret123'),
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        // 3. Setup Financial Year
        $this->financialYear = FinancialYear::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'is_default' => true,
        ]);

        // 4. Setup Chart of Accounts
        $assetType = AccountType::firstOrCreate(['code' => 'CURRENT_ASSETS'], ['name' => 'Current Assets', 'nature' => 'Asset']);
        $liabType = AccountType::firstOrCreate(['code' => 'CURRENT_LIABILITIES'], ['name' => 'Current Liabilities', 'nature' => 'Liability']);
        $revType = AccountType::firstOrCreate(['code' => 'OPERATING_REVENUE'], ['name' => 'Operating Revenue', 'nature' => 'Income']);

        $this->cashAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1001',
            'name' => 'Cash in Hand',
            'account_type_id' => $assetType->id,
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        $this->bankAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1002',
            'name' => 'Meezan Bank Main',
            'account_type_id' => $assetType->id,
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        $this->receivableAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1003',
            'name' => 'Accounts Receivable',
            'account_type_id' => $assetType->id,
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        $this->advanceLiabilityAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '2003',
            'name' => 'Customer Advances / Contract Liabilities',
            'account_type_id' => $liabType->id,
            'nature' => 'Liability',
            'is_active' => true,
        ]);

        $this->revenueAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '4001',
            'name' => 'Hall Booking Revenue',
            'account_type_id' => $revType->id,
            'nature' => 'Income',
            'is_active' => true,
        ]);

        $this->cashRecord = CashBankAccount::create([
            'marquee_id' => $this->marquee->id,
            'account_id' => $this->cashAccount->id,
            'type' => 'cash',
            'status' => 'active',
        ]);

        $this->bankRecord = CashBankAccount::create([
            'marquee_id' => $this->marquee->id,
            'account_id' => $this->bankAccount->id,
            'type' => 'bank',
            'bank_name' => 'Meezan Bank',
            'account_number' => '9988776655',
            'status' => 'active',
        ]);

        // 5. Booking Master Data
        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_code' => 'CUST-009',
            'customer_type' => 'Individual',
            'first_name' => 'Hamza',
            'last_name' => 'Khan',
            'phone_number' => '03123456789',
            'email' => 'hamza@example.com',
            'address' => 'DHA Phase 5',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'status' => 'Active',
        ]);

        $this->hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Imperial Hall',
            'hall_code' => 'H-09',
            'capacity' => 600,
            'hall_type' => 'Marquee',
            'default_booking_price' => 70000.00,
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->slot = Slot::create([
            'marquee_id' => $this->marquee->id,
            'slot_name' => 'Evening / Dinner',
            'start_time' => '19:00:00',
            'end_time' => '23:30:00',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Valima Reception',
            'event_type_code' => 'EV-VAL',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    /**
     * Helper to create a test booking.
     */
    protected function createBooking(float $grandTotal = 500000.00): Booking
    {
        return Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'booking_number' => 'BK-' . rand(1000, 9999),
            'hall_id' => $this->hall->id,
            'slot_id' => $this->slot->id,
            'event_type_id' => $this->eventType->id,
            'booking_date' => now()->format('Y-m-d'),
            'start_time' => now()->setTime(19, 0),
            'end_time' => now()->setTime(23, 30),
            'guest_count' => 300,
            'per_plate_price' => 1500.00,
            'package_amount' => 450000.00,
            'hall_charges' => 50000.00,
            'discount_amount' => 0.00,
            'subtotal' => 500000.00,
            'tax_amount' => 0.00,
            'security_deposit' => 0.00,
            'grand_total' => $grandTotal,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Unpaid',
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * Test 1: Booking List renders the Quick Payment / Collection action and modal.
     */
    public function test_booking_list_renders_quick_payment_action(): void
    {
        $booking = $this->createBooking(500000.00);

        Livewire::test(\App\Livewire\BookingList::class)
            ->assertStatus(200)
            ->assertSee($booking->booking_number)
            ->assertSee('Pay')
            ->call('openPaymentModal', $booking->id)
            ->assertSet('showPaymentModal', true)
            ->assertSet('paymentBookingId', $booking->id)
            ->assertSet('paymentType', 'advance')
            ->assertSet('paymentAmount', 500000.00)
            ->assertSee('Record Payment / Collection')
            ->assertSee('Advance Held')
            ->assertSee('Outstanding');
    }

    /**
     * Test 2: Booking List posts cash advance payment creating Advance Liability and General Ledger Journal Voucher.
     */
    public function test_booking_list_can_post_cash_advance_payment(): void
    {
        $booking = $this->createBooking(500000.00);

        Livewire::test(\App\Livewire\BookingList::class)
            ->call('openPaymentModal', $booking->id)
            ->set('paymentAmount', 150000.00)
            ->set('paymentMethod', 'Cash')
            ->set('paymentAccountId', $this->cashAccount->id)
            ->set('paymentDate', now()->format('Y-m-d'))
            ->set('transactionReference', 'CASH-REC-01')
            ->set('paymentNotes', 'First booking deposit')
            ->call('postPayment')
            ->assertHasNoErrors()
            ->assertSet('showPaymentModal', false);

        $booking->refresh();

        // 1. Booking State
        $this->assertEquals(150000.00, $booking->advance_received);
        $this->assertEquals(0.00, $booking->revenue_recognized);
        $this->assertEquals('Partially Paid', $booking->payment_status);

        // 2. Payment Record
        $payment = BookingPayment::where('booking_id', $booking->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(150000.00, $payment->amount);
        $this->assertEquals('advance', $payment->payment_type);
        $this->assertEquals($this->cashAccount->id, $payment->account_id);

        // 3. Double-entry Journal Voucher
        $jv = $payment->journalVoucher;
        $this->assertNotNull($jv);
        $this->assertEquals('posted', $jv->status);

        $drCash = $jv->items()->where('account_id', $this->cashAccount->id)->first();
        $crAdvance = $jv->items()->where('account_id', $this->advanceLiabilityAccount->id)->first();

        $this->assertEquals(150000.00, $drCash->debit);
        $this->assertEquals(150000.00, $crAdvance->credit);
        $this->assertEquals($jv->total_debit, $jv->total_credit);

        // 4. Customer Sub-Ledger
        $ledger = CustomerLedger::where('booking_payment_id', $payment->id)->first();
        $this->assertNotNull($ledger);
        $this->assertEquals(150000.00, $ledger->credit);
        $this->assertEquals(-150000.00, $ledger->running_balance);
    }

    /**
     * Test 3: Booking List posts bank transfer advance payment debiting Bank account.
     */
    public function test_booking_list_can_post_bank_advance_payment(): void
    {
        $booking = $this->createBooking(500000.00);

        Livewire::test(\App\Livewire\BookingList::class)
            ->call('openPaymentModal', $booking->id)
            ->set('paymentAmount', 200000.00)
            ->set('paymentMethod', 'Bank Transfer')
            ->set('paymentAccountId', $this->bankAccount->id)
            ->set('transactionReference', 'MEEZAN-9921')
            ->call('postPayment')
            ->assertHasNoErrors();

        $booking->refresh();
        $this->assertEquals(200000.00, $booking->advance_received);

        $payment = BookingPayment::where('booking_id', $booking->id)->latest('id')->first();
        $jv = $payment->journalVoucher;

        $drBank = $jv->items()->where('account_id', $this->bankAccount->id)->first();
        $crAdvance = $jv->items()->where('account_id', $this->advanceLiabilityAccount->id)->first();

        $this->assertEquals(200000.00, $drBank->debit);
        $this->assertEquals(200000.00, $crAdvance->credit);
    }

    /**
     * Test 4: Refund validation prevents refund exceeding received payments.
     */
    public function test_booking_list_refund_cannot_exceed_total_payments_received(): void
    {
        $booking = $this->createBooking(500000.00);

        // Receive 100k advance
        app(\App\Services\BookingFinancialService::class)->recordPayment($booking, [
            'amount' => 100000.00,
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
        ]);

        $booking->refresh();

        // Attempt to refund 150k (exceeding 100k)
        Livewire::test(\App\Livewire\BookingList::class)
            ->call('openPaymentModal', $booking->id)
            ->set('paymentType', 'refund')
            ->set('paymentAmount', 150000.00)
            ->set('paymentAccountId', $this->cashAccount->id)
            ->call('postPayment')
            ->assertHasErrors(['paymentAmount'])
            ->assertSet('showPaymentModal', true);

        $this->assertEquals(100000.00, $booking->fresh()->advance_received);
    }

    /**
     * Test 5: Post-event receivable settlement clears Accounts Receivable without inflating revenue.
     */
    public function test_booking_list_post_event_receivable_settlement(): void
    {
        $booking = $this->createBooking(500000.00);
        $financialService = app(\App\Services\BookingFinancialService::class);
        $recService = app(RevenueRecognitionService::class);

        // 300k advance
        $financialService->recordPayment($booking, [
            'amount' => 300000.00,
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
        ]);

        // Recognize Revenue upon event completion
        $recService->recognizeRevenue($booking);
        $booking->refresh();

        $this->assertTrue($booking->is_revenue_recognized);
        $this->assertEquals(500000.00, $booking->revenue_recognized);
        $this->assertEquals(200000.00, $booking->receivable_amount);

        // Open modal from Booking List -> defaults to receivable_payment for 200k
        Livewire::test(\App\Livewire\BookingList::class)
            ->call('openPaymentModal', $booking->id)
            ->assertSet('paymentType', 'receivable_payment')
            ->assertSet('paymentAmount', 200000.00)
            ->set('paymentAccountId', $this->cashAccount->id)
            ->call('postPayment')
            ->assertHasNoErrors();

        $booking->refresh();

        // 1. Receivables Cleared
        $this->assertEquals(0.00, $booking->receivable_amount);
        $this->assertEquals(500000.00, $booking->revenue_recognized);
        $this->assertEquals('Settled', $booking->financial_status);
        $this->assertTrue($booking->is_financially_settled);

        // 2. Journal Voucher: DR Cash (200k), CR Accounts Receivable (200k)
        $settlePayment = BookingPayment::where('booking_id', $booking->id)->latest('id')->first();
        $jv = $settlePayment->journalVoucher;

        $drCash = $jv->items()->where('account_id', $this->cashAccount->id)->first();
        $crReceivable = $jv->items()->where('account_id', $this->receivableAccount->id)->first();

        $this->assertEquals(200000.00, $drCash->debit);
        $this->assertEquals(200000.00, $crReceivable->credit);
    }

    /**
     * Test 6: Multi-Tenant isolation prevents unauthorized cross-tenant payment posting.
     */
    public function test_tenant_isolation_prevents_unauthorized_payment_posting(): void
    {
        $otherMarquee = Marquee::create([
            'name' => 'Pearl Palace',
            'slug' => 'pearl-palace',
            'is_active' => true,
            'status' => 'active',
            'email' => 'pearl@example.com',
            'phone' => '03441112233',
            'address' => 'Ferozepur Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
        ]);

        $otherBranch = Branch::create([
            'marquee_id' => $otherMarquee->id,
            'name' => 'Pearl Main',
            'code' => 'PP-01',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'address' => 'Ferozepur Road',
            'phone' => '042-3588888',
            'status' => 'active',
            'is_head_office' => true,
            'tax_rate' => 16.00,
        ]);

        $otherCustomer = Customer::create([
            'marquee_id' => $otherMarquee->id,
            'customer_code' => 'CUST-PP-01',
            'customer_type' => 'Individual',
            'first_name' => 'Rehman',
            'last_name' => 'Malik',
            'phone_number' => '03331112233',
            'email' => 'rehman@example.com',
            'address' => 'Gulberg',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'status' => 'Active',
        ]);

        $otherHall = Hall::create([
            'marquee_id' => $otherMarquee->id,
            'branch_id' => $otherBranch->id,
            'hall_name' => 'Crystal Hall',
            'hall_code' => 'H-CRYS',
            'capacity' => 400,
            'hall_type' => 'Marquee',
            'default_booking_price' => 60000.00,
            'status' => 'active',
            'is_active' => true,
        ]);

        $otherBooking = Booking::create([
            'marquee_id' => $otherMarquee->id,
            'branch_id' => $otherBranch->id,
            'customer_id' => $otherCustomer->id,
            'booking_number' => 'BK-OTHER-99',
            'hall_id' => $otherHall->id,
            'slot_id' => $this->slot->id,
            'event_type_id' => $this->eventType->id,
            'booking_date' => now()->format('Y-m-d'),
            'start_time' => now()->setTime(19, 0),
            'end_time' => now()->setTime(23, 30),
            'guest_count' => 200,
            'per_plate_price' => 1500.00,
            'package_amount' => 300000.00,
            'hall_charges' => 60000.00,
            'discount_amount' => 0.00,
            'subtotal' => 360000.00,
            'tax_amount' => 0.00,
            'security_deposit' => 0.00,
            'grand_total' => 360000.00,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Unpaid',
            'created_by' => $this->user->id,
        ]);

        // Attempt to access other marquee booking via BookingList of user 1
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(\App\Livewire\BookingList::class)
            ->call('openPaymentModal', $otherBooking->id);
    }
}
