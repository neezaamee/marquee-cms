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
use App\Services\AccountingService;
use App\Services\BookingFinancialService;
use App\Services\RevenueRecognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingFinancialAccountingTest extends TestCase
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
    protected Account $advanceLiabilityAccount;
    protected Account $receivableAccount;
    protected Account $revenueAccount;
    protected Account $cancellationIncomeAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Marquee and Branch
        $this->marquee = Marquee::create([
            'name' => 'Grand Palace Marquee',
            'slug' => 'grand-palace',
            'is_active' => true,
            'status' => 'active',
            'email' => 'info@grandpalace.com',
            'phone' => '03001234567',
            'address' => 'Mall Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Gulberg Branch',
            'code' => 'BR-01',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'address' => 'Main Boulevard Gulberg',
            'phone' => '042-3571234',
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
            'email' => 'owner@grandpalace.com',
            'password' => bcrypt('password123'),
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

        $this->cancellationIncomeAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '4004',
            'name' => 'Cancellation Charges Income',
            'account_type_id' => $revType->id,
            'nature' => 'Income',
            'is_active' => true,
        ]);

        CashBankAccount::create([
            'marquee_id' => $this->marquee->id,
            'account_id' => $this->cashAccount->id,
            'type' => 'cash',
            'status' => 'active',
        ]);

        CashBankAccount::create([
            'marquee_id' => $this->marquee->id,
            'account_id' => $this->bankAccount->id,
            'type' => 'bank',
            'bank_name' => 'Meezan Bank',
            'account_number' => '123456789',
            'status' => 'active',
        ]);

        // 5. Booking Master Data
        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_code' => 'CUST-001',
            'customer_type' => 'Individual',
            'first_name' => 'Muhammad',
            'last_name' => 'Ali',
            'phone_number' => '03001234567',
            'email' => 'ali@example.com',
            'address' => 'Gulberg III, Lahore',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'status' => 'Active',
        ]);

        $this->hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Royal Ballroom',
            'hall_code' => 'H-01',
            'capacity' => 500,
            'hall_type' => 'Marquee',
            'default_booking_price' => 50000.00,
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->slot = Slot::create([
            'marquee_id' => $this->marquee->id,
            'slot_name' => 'Night / Dinner',
            'start_time' => '19:00:00',
            'end_time' => '23:30:00',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Wedding Reception (Barat)',
            'event_type_code' => 'EV-01',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    /**
     * Helper to create a test booking.
     */
    protected function createBooking(float $grandTotal = 500000.00, float $taxAmount = 0.00, float $securityDeposit = 0.00): Booking
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
            'tax_amount' => $taxAmount,
            'security_deposit' => $securityDeposit,
            'grand_total' => $grandTotal,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Unpaid',
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * Test 1: Booking Creation does NOT recognize revenue.
     */
    public function test_booking_creation_does_not_recognize_revenue(): void
    {
        $booking = $this->createBooking(500000.00);

        $this->assertEquals(0.00, $booking->advance_received);
        $this->assertEquals(0.00, $booking->revenue_recognized);
        $this->assertEquals(0.00, $booking->receivable_amount);
        $this->assertFalse($booking->is_revenue_recognized);
        $this->assertEquals('Pending', $booking->financial_status);

        // General Ledger should show ZERO revenue vouchers
        $this->assertEquals(0, JournalVoucher::count());
    }

    protected function recordAndPostPayment(BookingFinancialService $service, Booking $booking, array $params): BookingPayment
    {
        $payment = $service->recordPayment($booking, $params);
        $accountId = $params['account_id'] ?? ((($params['payment_method'] ?? 'Cash') === 'Cash') ? $this->cashAccount->id : $this->bankAccount->id);
        return $service->postPayment($payment, [
            'account_id' => $accountId,
            'posting_date' => $params['payment_date'] ?? date('Y-m-d'),
            'posted_by' => $this->user->id,
        ]);
    }

    /**
     * Test 2: Cash Advance payment creates advance liability and debits Cash account.
     */
    public function test_cash_advance_payment_creates_advance_liability_and_debits_cash(): void
    {
        $booking = $this->createBooking(500000.00);
        $service = app(BookingFinancialService::class);

        $payment = $this->recordAndPostPayment($service, $booking, [
            'amount' => 100000.00,
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
            'payment_date' => now()->format('Y-m-d'),
            'notes' => 'First advance cash deposit',
        ]);

        $booking->refresh();

        // 1. Booking State
        $this->assertEquals(100000.00, $booking->advance_received);
        $this->assertEquals(0.00, $booking->revenue_recognized);
        $this->assertFalse($booking->is_revenue_recognized);
        $this->assertEquals('Partially Paid', $booking->payment_status);

        // 2. Payment Record
        $this->assertEquals('advance', $payment->payment_type);
        $this->assertNotNull($payment->journal_voucher_id);

        // 3. Double-entry Journal Voucher
        $jv = $payment->journalVoucher;
        $this->assertNotNull($jv);
        $this->assertEquals('posted', $jv->status);

        $debitItem = $jv->items()->where('account_id', $this->cashAccount->id)->first();
        $creditItem = $jv->items()->where('account_id', $this->advanceLiabilityAccount->id)->first();

        $this->assertNotNull($debitItem, 'Cash account must be debited');
        $this->assertEquals(100000.00, $debitItem->debit);

        $this->assertNotNull($creditItem, 'Customer Advance Liability account must be credited');
        $this->assertEquals(100000.00, $creditItem->credit);

        // Total Debits == Total Credits
        $this->assertEquals($jv->total_debit, $jv->total_credit);

        // 4. Customer Sub-Ledger
        $ledgerEntry = CustomerLedger::where('booking_payment_id', $payment->id)->first();
        $this->assertNotNull($ledgerEntry);
        $this->assertEquals('advance_payment', $ledgerEntry->transaction_type);
        $this->assertEquals(100000.00, $ledgerEntry->credit);
        $this->assertEquals(-100000.00, $ledgerEntry->running_balance);
    }

    /**
     * Test 3: Bank Advance payment creates advance liability and debits Bank account.
     */
    public function test_bank_advance_payment_creates_advance_liability_and_debits_bank(): void
    {
        $booking = $this->createBooking(500000.00);
        $service = app(BookingFinancialService::class);

        $payment = $this->recordAndPostPayment($service, $booking, [
            'amount' => 150000.00,
            'payment_method' => 'Bank Transfer',
            'account_id' => $this->bankAccount->id,
            'transaction_reference' => 'TXN-MEEZAN-991',
        ]);

        $booking->refresh();

        $this->assertEquals(150000.00, $booking->advance_received);
        $this->assertEquals(0.00, $booking->revenue_recognized);

        $jv = $payment->journalVoucher;
        $debitItem = $jv->items()->where('account_id', $this->bankAccount->id)->first();
        $creditItem = $jv->items()->where('account_id', $this->advanceLiabilityAccount->id)->first();

        $this->assertEquals(150000.00, $debitItem->debit);
        $this->assertEquals(150000.00, $creditItem->credit);
    }

    /**
     * Test 4: Event completion triggers revenue recognition, releases advance liability, and creates accounts receivable.
     */
    public function test_event_completion_triggers_revenue_recognition_and_releases_advance_liability(): void
    {
        $booking = $this->createBooking(500000.00);
        $financialService = app(BookingFinancialService::class);
        $recService = app(RevenueRecognitionService::class);

        // Pay Rs. 300,000 advance
        $this->recordAndPostPayment($financialService, $booking, [
            'amount' => 300000.00,
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
        ]);

        $booking->refresh();
        $this->assertEquals(300000.00, $booking->advance_received);
        $this->assertEquals(0.00, $booking->revenue_recognized);

        // Recognize Revenue on Event Completion
        $jv = $recService->recognizeRevenue($booking);

        $booking->refresh();

        // Booking State
        $this->assertTrue($booking->is_revenue_recognized);
        $this->assertEquals(500000.00, $booking->revenue_recognized);
        $this->assertEquals(200000.00, $booking->receivable_amount);
        $this->assertEquals(0.00, $booking->advance_received); // Released from liability
        $this->assertEquals('Completed', $booking->booking_status);
        $this->assertEquals('Partially Paid', $booking->financial_status);

        // Check Balanced Journal Voucher
        $this->assertEquals(500000.00, $jv->total_debit);
        $this->assertEquals(500000.00, $jv->total_credit);

        // Debit Advance Liability (release 300k)
        $drAdvance = $jv->items()->where('account_id', $this->advanceLiabilityAccount->id)->first();
        $this->assertEquals(300000.00, $drAdvance->debit);

        // Debit Accounts Receivable (unpaid balance 200k)
        $drReceivable = $jv->items()->where('account_id', $this->receivableAccount->id)->first();
        $this->assertEquals(200000.00, $drReceivable->debit);

        // Credit Revenue (full earned 500k)
        $crRevenue = $jv->items()->where('account_id', $this->revenueAccount->id)->first();
        $this->assertEquals(500000.00, $crRevenue->credit);
    }

    /**
     * Test 5: Revenue recognition is strictly idempotent (calling twice does not duplicate revenue).
     */
    public function test_revenue_recognition_is_strictly_idempotent(): void
    {
        $booking = $this->createBooking(500000.00);
        $financialService = app(BookingFinancialService::class);
        $recService = app(RevenueRecognitionService::class);

        $this->recordAndPostPayment($financialService, $booking, [
            'amount' => 200000.00,
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
        ]);

        $booking->refresh();

        // First Call
        $jv1 = $recService->recognizeRevenue($booking);
        $initialJvCount = JournalVoucher::count();

        // Second Call (Duplicate trigger)
        $jv2 = $recService->recognizeRevenue($booking);

        $this->assertEquals($jv1->id, $jv2->id);
        $this->assertEquals($initialJvCount, JournalVoucher::count(), 'Journal vouchers count must not increase on duplicate call');

        $booking->refresh();
        $this->assertEquals(500000.00, $booking->revenue_recognized);
    }

    /**
     * Test 6: Final payment after event completion settles Accounts Receivable without inflating revenue.
     */
    public function test_final_payment_after_event_settles_accounts_receivable(): void
    {
        $booking = $this->createBooking(500000.00);
        $financialService = app(BookingFinancialService::class);
        $recService = app(RevenueRecognitionService::class);

        // 300k advance
        $this->recordAndPostPayment($financialService, $booking, [
            'amount' => 300000.00,
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
        ]);

        // Event Completed & Revenue recognized
        $recService->recognizeRevenue($booking);
        $booking->refresh();

        $this->assertEquals(200000.00, $booking->receivable_amount);
        $this->assertEquals(500000.00, $booking->revenue_recognized);

        // Record & Post Final Settlement Payment of Rs. 200,000
        $settlePayment = $this->recordAndPostPayment($financialService, $booking, [
            'amount' => 200000.00,
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
            'notes' => 'Final post-event bill clearance',
        ]);

        $booking->refresh();

        // 1. Financial Status
        $this->assertEquals('receivable_payment', $settlePayment->payment_type);
        $this->assertEquals(0.00, $booking->receivable_amount);
        $this->assertEquals('Settled', $booking->financial_status);
        $this->assertEquals('Paid', $booking->payment_status);
        $this->assertTrue($booking->is_financially_settled);

        // 2. Revenue MUST NOT be inflated (remains 500,000)
        $this->assertEquals(500000.00, $booking->revenue_recognized);

        // 3. Journal Voucher settles Receivable (DR Cash, CR Accounts Receivable)
        $settleJv = $settlePayment->journalVoucher;
        $drCash = $settleJv->items()->where('account_id', $this->cashAccount->id)->first();
        $crReceivable = $settleJv->items()->where('account_id', $this->receivableAccount->id)->first();

        $this->assertEquals(200000.00, $drCash->debit);
        $this->assertEquals(200000.00, $crReceivable->credit);
    }

    /**
     * Test 7: Complete End-to-End Scenario matching the exact Rs. 500,000 specification.
     */
    public function test_full_required_end_to_end_scenario_rs_500000(): void
    {
        $financialService = app(BookingFinancialService::class);
        $recService = app(RevenueRecognitionService::class);

        // Step 1: Create Booking: Grand Total Rs. 500,000
        $booking = $this->createBooking(500000.00);
        $this->assertEquals(0.00, $booking->advance_received);
        $this->assertEquals(0.00, $booking->revenue_recognized);

        // Step 2: Receive 1st Advance: Rs. 100,000 Cash
        $this->recordAndPostPayment($financialService, $booking, [
            'amount' => 100000.00,
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
        ]);
        $booking->refresh();
        $this->assertEquals(100000.00, $booking->advance_received);
        $this->assertEquals(0.00, $booking->revenue_recognized);

        // Step 3: Receive 2nd Advance: Rs. 150,000 Bank
        $this->recordAndPostPayment($financialService, $booking, [
            'amount' => 150000.00,
            'payment_method' => 'Bank Transfer',
            'account_id' => $this->bankAccount->id,
        ]);
        $booking->refresh();
        $this->assertEquals(250000.00, $booking->advance_received);
        $this->assertEquals(0.00, $booking->revenue_recognized);

        // Step 4: Receive 3rd Advance: Rs. 50,000 Cash
        $this->recordAndPostPayment($financialService, $booking, [
            'amount' => 50000.00,
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
        ]);
        $booking->refresh();
        $this->assertEquals(300000.00, $booking->advance_received);
        $this->assertEquals(0.00, $booking->revenue_recognized);

        // Step 5: Event Completed -> Recognize Revenue
        $recService->recognizeRevenue($booking);
        $booking->refresh();

        $this->assertTrue($booking->is_revenue_recognized);
        $this->assertEquals(500000.00, $booking->revenue_recognized);
        $this->assertEquals(0.00, $booking->advance_received);
        $this->assertEquals(200000.00, $booking->receivable_amount);

        // Step 6: Final Customer Payment: Rs. 200,000 Cash
        $this->recordAndPostPayment($financialService, $booking, [
            'amount' => 200000.00,
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
        ]);
        $booking->refresh();

        $this->assertEquals(0.00, $booking->receivable_amount);
        $this->assertEquals(500000.00, $booking->revenue_recognized);
        $this->assertEquals(500000.00, $booking->total_paid);
        $this->assertTrue($booking->is_financially_settled);
    }

    /**
     * Test 8: Pre-event Advance Refund cleanly reverses Advance Liability without affecting revenue.
     */
    public function test_advance_refund_before_event_reverses_advance_liability(): void
    {
        $booking = $this->createBooking(500000.00);
        $financialService = app(BookingFinancialService::class);

        // Receive 100k advance
        $this->recordAndPostPayment($financialService, $booking, [
            'amount' => 100000.00,
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
        ]);

        $booking->refresh();
        $this->assertEquals(100000.00, $booking->advance_received);

        // Refund 40k
        $refundPayment = $financialService->recordRefund($booking, [
            'amount' => 40000.00,
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
            'notes' => 'Partial advance refund upon guest count downscale',
        ]);

        $booking->refresh();

        $this->assertEquals(60000.00, $booking->advance_received);
        $this->assertEquals(0.00, $booking->revenue_recognized);
        $this->assertEquals('refund', $refundPayment->payment_type);

        // Journal Voucher: DR Customer Advance (40k), CR Cash (40k)
        $jv = $refundPayment->journalVoucher;
        $drAdvance = $jv->items()->where('account_id', $this->advanceLiabilityAccount->id)->first();
        $crCash = $jv->items()->where('account_id', $this->cashAccount->id)->first();

        $this->assertEquals(40000.00, $drAdvance->debit);
        $this->assertEquals(40000.00, $crCash->credit);
    }

    /**
     * Test 9: Booking Cancellation Settlement with partial refund and forfeiture to cancellation charges income.
     */
    public function test_booking_cancellation_settlement_with_forfeiture(): void
    {
        $booking = $this->createBooking(500000.00);
        $financialService = app(BookingFinancialService::class);

        // Receive 100k advance
        $this->recordAndPostPayment($financialService, $booking, [
            'amount' => 100000.00,
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
        ]);

        $booking->refresh();
        $this->assertEquals(100000.00, $booking->advance_received);

        // Cancel with 60k refund and 40k cancellation fee retained as earned income
        $result = $financialService->processCancellation($booking, [
            'refund_amount' => 60000.00,
            'cancellation_fee' => 40000.00,
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
            'reason' => 'Customer cancelled wedding',
        ]);

        $booking->refresh();

        $this->assertEquals('Cancelled', $booking->booking_status);
        $this->assertEquals('Cancelled', $booking->financial_status);
        $this->assertEquals(0.00, $booking->advance_received);

        // Check Balanced Cancellation Journal Voucher
        $jv = $result['journal_voucher'];
        $this->assertNotNull($jv);
        $this->assertEquals(100000.00, $jv->total_debit);
        $this->assertEquals(100000.00, $jv->total_credit);

        // DR Customer Advance Liability: 100,000 (fully cleared)
        $drAdvance = $jv->items()->where('account_id', $this->advanceLiabilityAccount->id)->first();
        $this->assertEquals(100000.00, $drAdvance->debit);

        // CR Cash Account: 60,000 (disbursed refund)
        $crCash = $jv->items()->where('account_id', $this->cashAccount->id)->first();
        $this->assertEquals(60000.00, $crCash->credit);

        // CR Cancellation Income: 40,000 (earned penalty)
        $crIncome = $jv->items()->where('account_id', $this->cancellationIncomeAccount->id)->first();
        $this->assertEquals(40000.00, $crIncome->credit);
    }

    /**
     * Test 10: Customer Sub-Ledger running balance mathematical consistency.
     */
    public function test_customer_ledger_mathematical_consistency(): void
    {
        $booking = $this->createBooking(500000.00);
        $financialService = app(BookingFinancialService::class);
        $recService = app(RevenueRecognitionService::class);

        // 1. Advance 200,000 -> Customer Ledger running balance = -200,000 (credit)
        $this->recordAndPostPayment($financialService, $booking, [
            'amount' => 200000.00,
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
        ]);

        $latestLedger = CustomerLedger::where('customer_id', $this->customer->id)->latest('id')->first();
        $this->assertEquals(-200000.00, $latestLedger->running_balance);

        // 2. Revenue Recognition 500,000:
        // Invoice debit 500,000 -> balance becomes +300,000
        // Advance release credit 200,000 -> balance becomes +300,000 (net receivable)
        $recService->recognizeRevenue($booking);

        $latestLedger = CustomerLedger::where('customer_id', $this->customer->id)->latest('id')->first();
        $this->assertEquals(300000.00, $latestLedger->running_balance);

        // 3. Receivable payment of 300,000 -> balance becomes 0.00 (settled)
        $this->recordAndPostPayment($financialService, $booking, [
            'amount' => 300000.00,
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
        ]);

        $latestLedger = CustomerLedger::where('customer_id', $this->customer->id)->latest('id')->first();
        $this->assertEquals(0.00, $latestLedger->running_balance);
    }

    /**
     * Test 11: Sales tax is split cleanly to Sales Tax Payable (2004) without inflating revenue.
     */
    public function test_tax_component_is_recognized_in_sales_tax_payable_account(): void
    {
        $taxType = AccountType::firstOrCreate(['code' => 'CURRENT_LIABILITIES'], ['name' => 'Current Liabilities', 'nature' => 'Liability']);
        $taxAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '2004',
            'name' => 'Sales Tax Payable',
            'account_type_id' => $taxType->id,
            'nature' => 'Liability',
            'is_active' => true,
        ]);

        // Booking: Subtotal 500,000 + Tax 80,000 = Grand Total 580,000
        $booking = $this->createBooking(580000.00, 80000.00);
        $financialService = app(BookingFinancialService::class);
        $recService = app(RevenueRecognitionService::class);

        // 200k advance
        $this->recordAndPostPayment($financialService, $booking, [
            'amount' => 200000.00,
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
        ]);

        // Recognize Revenue on Event Completion
        $jv = $recService->recognizeRevenue($booking);

        $booking->refresh();

        // Total Journal Voucher Debits = Total Credits = 580,000
        $this->assertEquals(580000.00, $jv->total_debit);
        $this->assertEquals(580000.00, $jv->total_credit);

        // Net Revenue Credit = 500,000
        $crRevenue = $jv->items()->where('account_id', $this->revenueAccount->id)->first();
        $this->assertEquals(500000.00, $crRevenue->credit);

        // Sales Tax Payable Credit = 80,000
        $crTax = $jv->items()->where('account_id', $taxAccount->id)->first();
        $this->assertEquals(80000.00, $crTax->credit);

        // Advance Released = 200,000
        $drAdvance = $jv->items()->where('account_id', $this->advanceLiabilityAccount->id)->first();
        $this->assertEquals(200000.00, $drAdvance->debit);

        // Accounts Receivable = 380,000
        $drReceivable = $jv->items()->where('account_id', $this->receivableAccount->id)->first();
        $this->assertEquals(380000.00, $drReceivable->debit);
    }

    /**
     * Test 12: Multi-Tenant isolation prevents financial data cross-contamination.
     */
    public function test_tenant_isolation_prevents_cross_tenant_financial_leakage(): void
    {
        // Tenant 2
        $otherMarquee = Marquee::create([
            'name' => 'Pearl Continental Marquee',
            'slug' => 'pc-marquee',
            'is_active' => true,
            'status' => 'active',
            'email' => 'pc@example.com',
            'phone' => '03111222333',
            'address' => 'Mall Road',
            'city' => 'Rawalpindi',
            'province' => 'Punjab',
        ]);

        $otherBranch = Branch::create([
            'marquee_id' => $otherMarquee->id,
            'name' => 'PC Rawalpindi Branch',
            'code' => 'PC-01',
            'city' => 'Rawalpindi',
            'province' => 'Punjab',
            'address' => 'Saddar',
            'phone' => '051-1234567',
            'status' => 'active',
            'is_head_office' => true,
            'tax_rate' => 16.00,
        ]);

        $otherCustomer = Customer::create([
            'marquee_id' => $otherMarquee->id,
            'customer_code' => 'CUST-PC-01',
            'customer_type' => 'Individual',
            'first_name' => 'Tariq',
            'last_name' => 'Mahmood',
            'phone_number' => '03221234567',
            'email' => 'tariq@example.com',
            'address' => 'Saddar',
            'city' => 'Rawalpindi',
            'province' => 'Punjab',
            'status' => 'Active',
        ]);

        // Customer Ledger entries for Marquee 1
        CustomerLedger::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'transaction_date' => now()->format('Y-m-d'),
            'transaction_type' => 'advance_payment',
            'debit' => 0.00,
            'credit' => 50000.00,
            'running_balance' => -50000.00,
            'created_by' => $this->user->id,
        ]);

        // Customer Ledger entries for Marquee 2
        CustomerLedger::create([
            'marquee_id' => $otherMarquee->id,
            'branch_id' => $otherBranch->id,
            'customer_id' => $otherCustomer->id,
            'transaction_date' => now()->format('Y-m-d'),
            'transaction_type' => 'advance_payment',
            'debit' => 0.00,
            'credit' => 99999.00,
            'running_balance' => -99999.00,
            'created_by' => $this->user->id,
        ]);

        // Assert Marquee 1 query only retrieves Marquee 1 ledgers
        $tenant1Ledgers = CustomerLedger::where('marquee_id', $this->marquee->id)->get();
        $this->assertTrue($tenant1Ledgers->every(fn($l) => $l->marquee_id === $this->marquee->id));
        $this->assertEquals(1, $tenant1Ledgers->count());
        $this->assertEquals(50000.00, $tenant1Ledgers->first()->credit);
    }

    /**
     * Test 13: CustomerAdvanceLiabilityReport Livewire component calculates liabilities and renders accurately.
     */
    public function test_customer_advance_liability_report_livewire_component(): void
    {
        $booking1 = $this->createBooking(500000.00);
        $financialService = app(BookingFinancialService::class);

        $this->recordAndPostPayment($financialService, $booking1, [
            'amount' => 150000.00,
            'payment_method' => 'Cash',
            'account_id' => $this->cashAccount->id,
        ]);

        \Livewire\Livewire::test(\App\Livewire\Finance\CustomerAdvanceLiabilityReport::class)
            ->assertStatus(200)
            ->assertSee('Customer Advance Liability Report')
            ->assertSee('150,000.00')
            ->assertSee($booking1->booking_number)
            ->assertSee('Muhammad Ali');
    }
}
