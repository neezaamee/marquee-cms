<?php

namespace Tests\Feature;

use App\Livewire\Finance\PaymentsList;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class TwoStagePaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Marquee $marquee;
    protected Marquee $otherMarquee;
    protected Branch $branch;
    protected Branch $otherBranch;
    protected User $owner;
    protected User $accountant;
    protected User $bookingManager;
    protected Customer $customer;
    protected Hall $hall;
    protected Slot $slot;
    protected EventType $eventType;
    protected FinancialYear $financialYear;
    protected Account $cashAccount;
    protected Account $bankAccount;
    protected Account $advanceLiabilityAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed Roles & Permissions
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // 2. Setup Tenants
        $this->marquee = Marquee::factory()->create([
            'name' => 'Royal Palm Grand Marquee',
            'status' => 'active',
        ]);

        $this->otherMarquee = Marquee::factory()->create([
            'name' => 'Grand Palace Marquee',
            'status' => 'active',
        ]);

        // 3. Branches
        $this->branch = Branch::factory()->create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main City Branch',
            'status' => 'active',
        ]);

        $this->otherBranch = Branch::factory()->create([
            'marquee_id' => $this->otherMarquee->id,
            'name' => 'North Branch',
            'status' => 'active',
        ]);

        // 4. Users
        $ownerRole = Role::where('name', 'owner')->first();
        $accountantRole = Role::where('name', 'accountant')->first();
        $managerRole = Role::where('name', 'branch_manager')->first();

        $this->owner = User::factory()->create([
            'name' => 'Owner Boss',
            'email' => 'owner@royalpalm.test',
            'marquee_id' => $this->marquee->id,
            'role_id' => $ownerRole->id,
        ]);
        $this->owner->ownedMarquees()->attach($this->marquee->id);

        $this->accountant = User::factory()->create([
            'name' => 'Accountant Tariq',
            'email' => 'accountant@royalpalm.test',
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'role_id' => $accountantRole->id,
        ]);

        $this->bookingManager = User::factory()->create([
            'name' => 'Manager Hamza',
            'email' => 'manager@royalpalm.test',
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'role_id' => $managerRole->id,
        ]);

        // 5. Financial Year & COA
        $this->financialYear = FinancialYear::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'FY 2026-2027',
            'start_date' => Carbon::now()->startOfYear()->format('Y-m-d'),
            'end_date' => Carbon::now()->endOfYear()->format('Y-m-d'),
            'status' => 'active',
            'is_default' => true,
        ]);

        $currentAssetType = AccountType::firstOrCreate(
            ['code' => 'CURRENT_ASSETS'],
            ['name' => 'Current Assets', 'nature' => 'Asset']
        );
        $currentLiabType = AccountType::firstOrCreate(
            ['code' => 'CURRENT_LIABILITIES'],
            ['name' => 'Current Liabilities', 'nature' => 'Liability']
        );

        $this->cashAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1001',
            'name' => 'Cash in Hand',
            'account_type_id' => $currentAssetType->id,
            'nature' => 'Asset',
            'is_active' => true,
            'system_generated' => true,
        ]);

        $this->bankAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1002',
            'name' => 'Meezan Bank Ltd',
            'account_type_id' => $currentAssetType->id,
            'nature' => 'Asset',
            'is_active' => true,
            'system_generated' => true,
        ]);

        $this->advanceLiabilityAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '2003',
            'name' => 'Customer Advances / Contract Liabilities',
            'account_type_id' => $currentLiabType->id,
            'nature' => 'Liability',
            'is_active' => true,
            'system_generated' => true,
        ]);

        // 6. Booking Entities
        $this->customer = Customer::factory()->create([
            'marquee_id' => $this->marquee->id,
            'first_name' => 'Muhammad',
            'last_name' => 'Ali',
        ]);

        $this->hall = Hall::factory()->create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Imperial Hall',
            'capacity' => 500,
        ]);

        $this->slot = Slot::create([
            'marquee_id' => $this->marquee->id,
            'slot_name' => 'Dinner Slot',
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'status' => 'active',
        ]);

        $this->eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Wedding Reception',
            'event_type_code' => 'EVT-WED',
            'status' => 'active',
        ]);
    }

    protected function createTestBooking(float $grandTotal = 500000): Booking
    {
        return Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hall->id,
            'slot_id' => $this->slot->id,
            'booking_date' => Carbon::tomorrow()->format('Y-m-d'),
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'guest_count' => 300,
            'per_plate_price' => 1500,
            'subtotal' => $grandTotal,
            'grand_total' => $grandTotal,
            'advance_received' => 0.00,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Unpaid',
            'financial_status' => 'Pending',
            'created_by' => $this->bookingManager->id,
        ]);
    }

    /**
     * Test 1 & 2: Booking manager records payment -> Status is pending_posting, no JV created.
     */
    public function test_booking_manager_records_payment_with_pending_posting_status(): void
    {
        $booking = $this->createTestBooking(500000);
        $financialService = app(BookingFinancialService::class);

        $payment = $financialService->recordPayment($booking, [
            'amount' => 100000,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'Cash',
            'notes' => 'Token advance from customer',
            'recorded_by' => $this->bookingManager->id,
        ]);

        $this->assertEquals('pending_posting', $payment->status);
        $this->assertNull($payment->journal_voucher_id);
        $this->assertNull($payment->posted_by);
        $this->assertNull($payment->posted_at);
        $this->assertStringStartsWith('PAY-', $payment->payment_number);

        // Booking advance_received must remain 0.00 until accountant posts
        $booking->refresh();
        $this->assertEquals(0.00, (float) $booking->advance_received);
        $this->assertEquals(100000.00, (float) $booking->advance_pending_posting);
        $this->assertEquals(100000.00, (float) $booking->total_received_payments);
        $this->assertEquals(0.00, (float) $booking->total_paid);
    }

    /**
     * Test 3 & 4: Pending payment does NOT create Journal Voucher or affect Cash/Bank ledger.
     */
    public function test_pending_payment_does_not_affect_cash_bank_or_journal_vouchers(): void
    {
        $booking = $this->createTestBooking(500000);
        $financialService = app(BookingFinancialService::class);

        $initialJvCount = JournalVoucher::count();

        $payment = $financialService->recordPayment($booking, [
            'amount' => 150000,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'Cash',
            'recorded_by' => $this->bookingManager->id,
        ]);

        // No new Journal Voucher created
        $this->assertEquals($initialJvCount, JournalVoucher::count());
        $this->assertNull($payment->journal_voucher_id);

        // Customer ledger subledger does not have a posted entry
        $this->assertDatabaseMissing('customer_ledgers', [
            'booking_payment_id' => $payment->id,
        ]);
    }

    /**
     * Test 5, 6 & 7: Accountant posts CASH payment -> DR Cash, CR Customer Advance Liability.
     */
    public function test_accountant_posts_cash_payment_creating_dr_cash_cr_advance_liability(): void
    {
        $booking = $this->createTestBooking(500000);
        $financialService = app(BookingFinancialService::class);

        $payment = $financialService->recordPayment($booking, [
            'amount' => 100000,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'Cash',
            'recorded_by' => $this->bookingManager->id,
        ]);

        // Accountant posts payment
        $postedPayment = $financialService->postPayment($payment, [
            'account_id' => $this->cashAccount->id,
            'posting_date' => date('Y-m-d'),
            'accountant_notes' => 'Physically verified cash in hand and locked in vault.',
            'posted_by' => $this->accountant->id,
        ]);

        $this->assertEquals('posted', $postedPayment->status);
        $this->assertNotNull($postedPayment->journal_voucher_id);
        $this->assertEquals($this->accountant->id, $postedPayment->posted_by);
        $this->assertEquals($this->cashAccount->id, $postedPayment->account_id);

        // Verify Journal Voucher double-entry lines
        $jv = $postedPayment->journalVoucher;
        $this->assertNotNull($jv);
        $this->assertEquals(2, $jv->items()->count());

        // DR Cash in Hand 100,000
        $debitItem = $jv->items()->where('account_id', $this->cashAccount->id)->first();
        $this->assertNotNull($debitItem);
        $this->assertEquals(100000.00, (float) $debitItem->debit);
        $this->assertEquals(0.00, (float) $debitItem->credit);

        // CR Customer Advance Liability 100,000
        $creditItem = $jv->items()->where('account_id', $this->advanceLiabilityAccount->id)->first();
        $this->assertNotNull($creditItem);
        $this->assertEquals(0.00, (float) $creditItem->debit);
        $this->assertEquals(100000.00, (float) $creditItem->credit);

        // Verify Booking Financials Updated
        $booking->refresh();
        $this->assertEquals(100000.00, (float) $booking->advance_received);
        $this->assertEquals(100000.00, (float) $booking->total_paid);
        $this->assertEquals(0.00, (float) $booking->advance_pending_posting);
        $this->assertEquals(400000.00, (float) $booking->remaining_customer_balance);
        $this->assertEquals('Partially Paid', $booking->payment_status);

        // Verify Customer Ledger Updated
        $this->assertDatabaseHas('customer_ledgers', [
            'booking_payment_id' => $payment->id,
            'customer_id' => $booking->customer_id,
            'credit' => 100000.00,
        ]);
    }

    /**
     * Test 8: Accountant posts BANK payment -> DR Bank Account, CR Customer Advance Liability.
     */
    public function test_accountant_posts_bank_payment_creating_dr_bank_cr_advance_liability(): void
    {
        $booking = $this->createTestBooking(500000);
        $financialService = app(BookingFinancialService::class);

        $payment = $financialService->recordPayment($booking, [
            'amount' => 200000,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'Bank Transfer',
            'bank_reference' => 'MEEZAN-FT-998822',
            'recorded_by' => $this->bookingManager->id,
        ]);

        $postedPayment = $financialService->postPayment($payment, [
            'account_id' => $this->bankAccount->id,
            'posting_date' => date('Y-m-d'),
            'accountant_notes' => 'Online statement transfer verified.',
            'posted_by' => $this->accountant->id,
        ]);

        $this->assertEquals('posted', $postedPayment->status);

        $jv = $postedPayment->journalVoucher;
        // DR Meezan Bank
        $debitItem = $jv->items()->where('account_id', $this->bankAccount->id)->first();
        $this->assertEquals(200000.00, (float) $debitItem->debit);

        // CR Customer Advance Liability
        $creditItem = $jv->items()->where('account_id', $this->advanceLiabilityAccount->id)->first();
        $this->assertEquals(200000.00, (float) $creditItem->credit);
    }

    /**
     * Test 9 & 10: Posted payment cannot be posted twice (Idempotency protection).
     */
    public function test_posted_payment_cannot_be_posted_twice(): void
    {
        $booking = $this->createTestBooking(500000);
        $financialService = app(BookingFinancialService::class);

        $payment = $financialService->recordPayment($booking, [
            'amount' => 50000,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'Cash',
            'recorded_by' => $this->bookingManager->id,
        ]);

        $financialService->postPayment($payment, [
            'account_id' => $this->cashAccount->id,
            'posted_by' => $this->accountant->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("This payment has already been posted.");

        // Attempt second posting
        $financialService->postPayment($payment, [
            'account_id' => $this->cashAccount->id,
            'posted_by' => $this->accountant->id,
        ]);
    }

    /**
     * Test 11: Accountant can reject a pending payment -> Status becomes rejected, no Cash/Bank change.
     */
    public function test_accountant_can_reject_pending_payment(): void
    {
        $booking = $this->createTestBooking(500000);
        $financialService = app(BookingFinancialService::class);

        $payment = $financialService->recordPayment($booking, [
            'amount' => 75000,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'Cheque',
            'cheque_number' => 'CHQ-99001',
            'recorded_by' => $this->bookingManager->id,
        ]);

        $rejectedPayment = $financialService->rejectPayment($payment, 'Cheque dishonored by issuing bank.', $this->accountant->id);

        $this->assertEquals('rejected', $rejectedPayment->status);
        $this->assertEquals($this->accountant->id, $rejectedPayment->rejected_by);
        $this->assertEquals('Cheque dishonored by issuing bank.', $rejectedPayment->rejection_reason);
        $this->assertNull($rejectedPayment->journal_voucher_id);

        $booking->refresh();
        $this->assertEquals(0.00, (float) $booking->advance_received);
        $this->assertEquals(0.00, (float) $booking->advance_pending_posting);
        $this->assertEquals(500000.00, (float) $booking->remaining_customer_balance);
    }

    /**
     * Test 12: Authorized user can reverse a posted payment -> Creates offsetting Journal Voucher.
     */
    public function test_authorized_user_can_reverse_posted_payment(): void
    {
        $booking = $this->createTestBooking(500000);
        $financialService = app(BookingFinancialService::class);

        $payment = $financialService->recordPayment($booking, [
            'amount' => 120000,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'Cash',
            'recorded_by' => $this->bookingManager->id,
        ]);

        $postedPayment = $financialService->postPayment($payment, [
            'account_id' => $this->cashAccount->id,
            'posted_by' => $this->accountant->id,
        ]);

        $booking->refresh();
        $this->assertEquals(120000.00, (float) $booking->advance_received);

        // Reverse payment
        $reversedPayment = $financialService->reversePayment($postedPayment, 'Correction of erroneous payment receipt.', $this->owner->id);

        $this->assertEquals('reversed', $reversedPayment->status);
        $this->assertEquals($this->owner->id, $reversedPayment->reversed_by);
        $this->assertNotNull($reversedPayment->reversal_journal_voucher_id);

        // Check Reversal Journal Voucher
        $revJv = $reversedPayment->reversalJournalVoucher;
        $this->assertNotNull($revJv);

        // DR Customer Advance Liability (120,000)
        $drItem = $revJv->items()->where('account_id', $this->advanceLiabilityAccount->id)->first();
        $this->assertEquals(120000.00, (float) $drItem->debit);

        // CR Cash in Hand (120,000)
        $crItem = $revJv->items()->where('account_id', $this->cashAccount->id)->first();
        $this->assertEquals(120000.00, (float) $crItem->credit);

        // Booking advance liability reduced back to 0
        $booking->refresh();
        $this->assertEquals(0.00, (float) $booking->advance_received);
        $this->assertEquals(500000.00, (float) $booking->remaining_customer_balance);
    }

    /**
     * Test 13: Livewire Payment Ledger filters, summary metrics, and accountant posting action.
     */
    public function test_payment_ledger_livewire_component_actions(): void
    {
        $booking = $this->createTestBooking(400000);
        $financialService = app(BookingFinancialService::class);

        $payment = $financialService->recordPayment($booking, [
            'amount' => 80000,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'Cash',
            'recorded_by' => $this->bookingManager->id,
        ]);

        // Accountant views Payment Ledger, searches by phone/name, and posts
        Livewire::actingAs($this->accountant)
            ->test(PaymentsList::class)
            ->set('search', 'Muhammad')
            ->assertSee($payment->payment_number)
            ->set('search', '')
            ->assertSee('Pending Posting')
            ->assertSee('Rs. 80,000')
            ->call('openPostModal', $payment->id)
            ->assertSet('showPostModal', true)
            ->set('targetAccountId', $this->cashAccount->id)
            ->set('accountantNotes', 'Verified cash count')
            ->call('confirmPostPayment')
            ->assertSet('showPostModal', false)
            ->assertSee('posted successfully');

        $payment->refresh();
        $this->assertEquals('posted', $payment->status);
    }

    /**
     * Test 14: Tenant isolation prevents cross-tenant posting.
     */
    public function test_tenant_isolation_prevents_cross_tenant_posting(): void
    {
        $booking = $this->createTestBooking(500000);
        $financialService = app(BookingFinancialService::class);

        $payment = $financialService->recordPayment($booking, [
            'amount' => 60000,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'Cash',
            'recorded_by' => $this->bookingManager->id,
        ]);

        // Other marquee's cash account
        $otherAssetType = AccountType::firstOrCreate(['code' => 'CURRENT_ASSETS'], ['name' => 'Current Assets', 'nature' => 'Asset']);
        $otherMarqueeAccount = Account::create([
            'marquee_id' => $this->otherMarquee->id,
            'account_code' => '1001',
            'name' => 'Other Marquee Cash',
            'account_type_id' => $otherAssetType->id,
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Attempt to post using another tenant's account
        $financialService->postPayment($payment, [
            'account_id' => $otherMarqueeAccount->id,
            'posted_by' => $this->accountant->id,
        ]);
    }

    /**
     * Test 15: Booking Manager without post permission cannot execute postPayment in Livewire.
     */
    public function test_unauthorized_booking_officer_cannot_post_payment(): void
    {
        $booking = $this->createTestBooking(500000);
        $financialService = app(BookingFinancialService::class);

        $payment = $financialService->recordPayment($booking, [
            'amount' => 50000,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'Cash',
            'recorded_by' => $this->bookingManager->id,
        ]);

        // Booking officer role
        $officerRole = Role::where('name', 'booking_officer')->first();
        $bookingOfficer = User::factory()->create([
            'marquee_id' => $this->marquee->id,
            'role_id' => $officerRole->id,
        ]);

        Livewire::actingAs($bookingOfficer)
            ->test(PaymentsList::class)
            ->call('openPostModal', $payment->id)
            ->assertSet('showPostModal', false)
            ->assertSee('You are not authorized to post payments');

        $payment->refresh();
        $this->assertEquals('pending_posting', $payment->status);
    }

    /**
     * Test 16 & 17 & 18: Multiple payments posting accurately accumulate advance, track remaining balance, and never recognize revenue prematurely.
     */
    public function test_multiple_payments_posting_accumulate_correctly_and_revenue_is_not_prematurely_recognized(): void
    {
        $booking = $this->createTestBooking(600000);
        $financialService = app(BookingFinancialService::class);

        // Payment 1: 100,000 Cash
        $pay1 = $financialService->recordPayment($booking, [
            'amount' => 100000,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'Cash',
            'recorded_by' => $this->bookingManager->id,
        ]);

        // Payment 2: 200,000 Bank Transfer
        $pay2 = $financialService->recordPayment($booking, [
            'amount' => 200000,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'Bank Transfer',
            'recorded_by' => $this->bookingManager->id,
        ]);

        $booking->refresh();
        $this->assertEquals(0.00, (float) $booking->advance_received);
        $this->assertEquals(300000.00, (float) $booking->advance_pending_posting);
        $this->assertEquals(600000.00, (float) $booking->remaining_customer_balance);

        // Post Payment 1 only
        $financialService->postPayment($pay1, [
            'account_id' => $this->cashAccount->id,
            'posted_by' => $this->accountant->id,
        ]);

        $booking->refresh();
        $this->assertEquals(100000.00, (float) $booking->advance_received);
        $this->assertEquals(200000.00, (float) $booking->advance_pending_posting);
        $this->assertEquals(500000.00, (float) $booking->remaining_customer_balance);
        $this->assertEquals(0.00, (float) $booking->revenue_recognized);
        $this->assertFalse((bool) $booking->is_revenue_recognized);

        // Post Payment 2
        $financialService->postPayment($pay2, [
            'account_id' => $this->bankAccount->id,
            'posted_by' => $this->accountant->id,
        ]);

        $booking->refresh();
        $this->assertEquals(300000.00, (float) $booking->advance_received);
        $this->assertEquals(0.00, (float) $booking->advance_pending_posting);
        $this->assertEquals(300000.00, (float) $booking->remaining_customer_balance);
        $this->assertEquals(0.00, (float) $booking->revenue_recognized);
        $this->assertFalse((bool) $booking->is_revenue_recognized);
    }

    /**
     * Test 19: Database transaction rolls back completely on posting error.
     */
    public function test_database_transaction_rolls_back_on_posting_failure(): void
    {
        $booking = $this->createTestBooking(500000);
        $financialService = app(BookingFinancialService::class);

        $payment = $financialService->recordPayment($booking, [
            'amount' => 50000,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'Cash',
            'recorded_by' => $this->bookingManager->id,
        ]);

        $initialJvCount = JournalVoucher::count();

        try {
            // Force an invalid account ID to trigger rollback
            $financialService->postPayment($payment, [
                'account_id' => 999999, // Non-existent account
                'posted_by' => $this->accountant->id,
            ]);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Expected
        }

        // Ensure state remains intact
        $payment->refresh();
        $this->assertEquals('pending_posting', $payment->status);
        $this->assertNull($payment->journal_voucher_id);
        $this->assertEquals($initialJvCount, JournalVoucher::count());
    }
}
