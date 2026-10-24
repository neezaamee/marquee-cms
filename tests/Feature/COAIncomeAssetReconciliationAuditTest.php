<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountOpeningBalance;
use App\Models\AccountType;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\CashBankAccount;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\FinancialYear;
use App\Models\Hall;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherItem;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\Slot;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\BookingFinancialService;
use App\Services\RevenueRecognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class COAIncomeAssetReconciliationAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Marquee $marquee;
    protected Branch $branch;
    protected User $user;
    protected FinancialYear $financialYear;
    protected AccountingService $accountingService;

    // Accounts
    protected Account $cashAccount;
    protected Account $bankAccount;
    protected Account $advanceLiabilityAccount;
    protected Account $equityAccount;
    protected Account $revenueAccount;
    protected Account $expenseAccount;
    protected Account $supplierAdvanceAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountingService = app(AccountingService::class);

        // 1. Create Marquee and Branch
        $this->marquee = Marquee::create([
            'name' => 'Audit Test Marquee',
            'slug' => 'audit-test-marquee',
            'is_active' => true,
            'status' => 'active',
            'email' => 'audit@example.com',
            'phone' => '03001234567',
            'address' => 'Test Address',
            'city' => 'Lahore',
            'province' => 'Punjab',
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Branch',
            'code' => 'MB-01',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'address' => 'Test Branch Address',
            'phone' => '042-3123456',
            'status' => 'active',
            'is_head_office' => true,
            'tax_rate' => 0.00,
        ]);

        // 2. User & Authentication
        $ownerRole = Role::firstOrCreate(['name' => 'owner'], ['label' => 'Owner', 'marquee_id' => $this->marquee->id]);
        $this->user = User::create([
            'name' => 'Audit Auditor',
            'email' => 'auditor@example.com',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->actingAs($this->user);

        // 3. Financial Year
        $this->financialYear = FinancialYear::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'is_default' => true,
        ]);

        // 4. Account Types
        $assetType = AccountType::firstOrCreate(['code' => 'CURRENT_ASSETS'], ['name' => 'Current Assets', 'nature' => 'Asset']);
        $liabType = AccountType::firstOrCreate(['code' => 'CURRENT_LIABILITIES'], ['name' => 'Current Liabilities', 'nature' => 'Liability']);
        $equityType = AccountType::firstOrCreate(['code' => 'OWNER_EQUITY'], ['name' => 'Owner Equity', 'nature' => 'Equity']);
        $incomeType = AccountType::firstOrCreate(['code' => 'OPERATING_REVENUE'], ['name' => 'Operating Revenue', 'nature' => 'Income']);
        $expenseType = AccountType::firstOrCreate(['code' => 'OPERATING_EXPENSES'], ['name' => 'Operating Expenses', 'nature' => 'Expense']);

        // 5. Chart of Accounts
        $this->cashAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1001',
            'name' => 'Cash in Hand',
            'account_type_id' => $assetType->id,
            'nature' => 'Asset',
            'is_active' => true,
            'system_generated' => true,
        ]);

        $this->bankAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1002',
            'name' => 'Bank Account',
            'account_type_id' => $assetType->id,
            'nature' => 'Asset',
            'is_active' => true,
            'system_generated' => true,
        ]);

        $this->supplierAdvanceAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1005',
            'name' => 'Supplier Advances',
            'account_type_id' => $assetType->id,
            'nature' => 'Asset',
            'is_active' => true,
            'system_generated' => false,
        ]);

        $this->advanceLiabilityAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '2003',
            'name' => 'Customer Advance Liability',
            'account_type_id' => $liabType->id,
            'nature' => 'Liability',
            'is_active' => true,
            'system_generated' => true,
        ]);

        $this->equityAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '3001',
            'name' => 'Opening Equity',
            'account_type_id' => $equityType->id,
            'nature' => 'Equity',
            'is_active' => true,
            'system_generated' => true,
        ]);

        $this->revenueAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '4001',
            'name' => 'Hall Booking Revenue',
            'account_type_id' => $incomeType->id,
            'nature' => 'Income',
            'is_active' => true,
            'system_generated' => true,
        ]);

        $this->expenseAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '5501',
            'name' => 'Operational Expenses',
            'account_type_id' => $expenseType->id,
            'nature' => 'Expense',
            'is_active' => true,
            'system_generated' => true,
        ]);

        session(['active_marquee_id' => $this->marquee->id]);
    }

    /**
     * Phase 9 Test: Controlled test dataset reconciliation.
     */
    public function test_phase_9_reconciliation_dataset(): void
    {
        // 1. Setup Opening Balances:
        // Cash: Debit 100,000
        // Bank: Debit 200,000
        // Customer Advance Liability: Credit 30,000
        // Opening Equity: Credit 270,000
        // Total Dr: 300,000 == Total Cr: 300,000
        AccountOpeningBalance::create([
            'marquee_id' => $this->marquee->id,
            'financial_year_id' => $this->financialYear->id,
            'account_id' => $this->cashAccount->id,
            'debit' => 100000.00,
            'credit' => 0.00,
        ]);
        AccountOpeningBalance::create([
            'marquee_id' => $this->marquee->id,
            'financial_year_id' => $this->financialYear->id,
            'account_id' => $this->bankAccount->id,
            'debit' => 200000.00,
            'credit' => 0.00,
        ]);
        AccountOpeningBalance::create([
            'marquee_id' => $this->marquee->id,
            'financial_year_id' => $this->financialYear->id,
            'account_id' => $this->advanceLiabilityAccount->id,
            'debit' => 0.00,
            'credit' => 30000.00,
        ]);
        AccountOpeningBalance::create([
            'marquee_id' => $this->marquee->id,
            'financial_year_id' => $this->financialYear->id,
            'account_id' => $this->equityAccount->id,
            'debit' => 0.00,
            'credit' => 270000.00,
        ]);

        // Transaction A: Customer advance received in cash 20,000
        $this->accountingService->createJournalVoucher([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_date' => '2026-03-01',
            'reference' => 'TX-A',
            'notes' => 'Customer advance received in cash',
            'status' => 'posted',
        ], [
            ['account_id' => $this->cashAccount->id, 'debit' => 20000.00, 'credit' => 0.00, 'narration' => 'Debit Cash'],
            ['account_id' => $this->advanceLiabilityAccount->id, 'debit' => 0.00, 'credit' => 20000.00, 'narration' => 'Credit Advance Liability'],
        ]);

        // Transaction B: Recognized booking revenue 50,000 (releasing 50,000 of advance liability)
        $this->accountingService->createJournalVoucher([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_date' => '2026-03-05',
            'reference' => 'TX-B',
            'notes' => 'Recognized booking revenue from advance',
            'status' => 'posted',
        ], [
            ['account_id' => $this->advanceLiabilityAccount->id, 'debit' => 50000.00, 'credit' => 0.00, 'narration' => 'Debit Advance Liability'],
            ['account_id' => $this->revenueAccount->id, 'debit' => 0.00, 'credit' => 50000.00, 'narration' => 'Credit Booking Revenue'],
        ]);

        // Transaction C: Cash payment for an expense 5,000
        $this->accountingService->createJournalVoucher([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_date' => '2026-03-10',
            'reference' => 'TX-C',
            'notes' => 'Cash payment for expense',
            'status' => 'posted',
        ], [
            ['account_id' => $this->expenseAccount->id, 'debit' => 5000.00, 'credit' => 0.00, 'narration' => 'Debit Expense'],
            ['account_id' => $this->cashAccount->id, 'debit' => 0.00, 'credit' => 5000.00, 'narration' => 'Credit Cash'],
        ]);

        // Transaction D: Transfer from Cash to Bank 10,000
        $this->accountingService->createJournalVoucher([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_date' => '2026-03-15',
            'reference' => 'TX-D',
            'notes' => 'Cash to Bank transfer',
            'status' => 'posted',
        ], [
            ['account_id' => $this->bankAccount->id, 'debit' => 10000.00, 'credit' => 0.00, 'narration' => 'Debit Bank'],
            ['account_id' => $this->cashAccount->id, 'debit' => 0.00, 'credit' => 10000.00, 'narration' => 'Credit Cash'],
        ]);

        // Transaction E: Supplier advance paid from Bank 15,000
        $this->accountingService->createJournalVoucher([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_date' => '2026-03-20',
            'reference' => 'TX-E',
            'notes' => 'Supplier advance paid from Bank',
            'status' => 'posted',
        ], [
            ['account_id' => $this->supplierAdvanceAccount->id, 'debit' => 15000.00, 'credit' => 0.00, 'narration' => 'Debit Supplier Advances'],
            ['account_id' => $this->bankAccount->id, 'debit' => 0.00, 'credit' => 15000.00, 'narration' => 'Credit Bank'],
        ]);

        // 2. Validate Ledger Closing Balances:
        // Cash: 100,000 + 20,000 (Tx A) - 5,000 (Tx C) - 10,000 (Tx D) = 105,000
        $glCash = $this->accountingService->getGeneralLedger($this->cashAccount->id, '2026-01-01', '2026-12-31', null, $this->financialYear->id);
        $this->assertEquals(105000.00, $glCash['closing_balance']);

        // Bank: 200,000 + 10,000 (Tx D) - 15,000 (Tx E) = 195,000
        $glBank = $this->accountingService->getGeneralLedger($this->bankAccount->id, '2026-01-01', '2026-12-31', null, $this->financialYear->id);
        $this->assertEquals(195000.00, $glBank['closing_balance']);

        // Supplier Advances: 15,000 (Tx E)
        $glSuppAdv = $this->accountingService->getGeneralLedger($this->supplierAdvanceAccount->id, '2026-01-01', '2026-12-31', null, $this->financialYear->id);
        $this->assertEquals(15000.00, $glSuppAdv['closing_balance']);

        // Total Assets = Cash (105,000) + Bank (195,000) + Supplier Advances (15,000) = 315,000
        $expectedTotalAssets = 315000.00;

        // Customer Advance Liability: Opening Credit 30,000 + Tx A Credit 20,000 - Tx B Debit 50,000 = 0
        $glAdvLiab = $this->accountingService->getGeneralLedger($this->advanceLiabilityAccount->id, '2026-01-01', '2026-12-31', null, $this->financialYear->id);
        $this->assertEquals(0.00, $glAdvLiab['closing_balance']);

        // Revenue: Tx B Credit 50,000
        $glRev = $this->accountingService->getGeneralLedger($this->revenueAccount->id, '2026-01-01', '2026-12-31', null, $this->financialYear->id);
        $this->assertEquals(50000.00, $glRev['closing_balance']);

        // Expense: Tx C Debit 5,000
        $glExp = $this->accountingService->getGeneralLedger($this->expenseAccount->id, '2026-01-01', '2026-12-31', null, $this->financialYear->id);
        $this->assertEquals(5000.00, $glExp['closing_balance']);

        // P&L: Revenue (50,000) - Expense (5,000) = 45,000 Net Profit
        $pl = $this->accountingService->getProfitAndLoss($this->marquee->id, $this->financialYear->id, '2026-01-01', '2026-12-31');
        $this->assertEquals(50000.00, $pl['total_income']);
        $this->assertEquals(5000.00, $pl['total_expense']);
        $this->assertEquals(45000.00, $pl['net_profit_loss']);

        // Trial Balance:
        // Cash: Dr 105,000
        // Bank: Dr 195,000
        // Supplier Advances: Dr 15,000
        // Expense: Dr 5,000
        // Total Debits = 105,000 + 195,000 + 15,000 + 5,000 = 320,000
        // Equity: Cr 270,000
        // Revenue: Cr 50,000
        // Total Credits = 270,000 + 50,000 = 320,000
        $tb = $this->accountingService->getTrialBalance($this->marquee->id, $this->financialYear->id, '2026-12-31');
        $this->assertTrue($tb['is_balanced']);
        $this->assertEquals(320000.00, $tb['total_debit']);
        $this->assertEquals(320000.00, $tb['total_credit']);

        // Balance Sheet:
        // Assets: 315,000
        // Liabilities: 0
        // Equity: Opening Equity (270,000) + Current Period Earnings (45,000) = 315,000
        // Total Liabilities & Equity = 315,000
        $bs = $this->accountingService->getBalanceSheet($this->marquee->id, $this->financialYear->id, '2026-12-31');
        $this->assertTrue($bs['is_balanced']);
        $this->assertEquals($expectedTotalAssets, $bs['total_assets']);
        $this->assertEquals(0.00, $bs['total_liabilities']);
        $this->assertEquals(315000.00, $bs['total_equity']);
        $this->assertEquals(315000.00, $bs['total_liabilities_and_equity']);
        $this->assertEquals(0.00, $bs['difference']);
    }

    /**
     * Audit Test for Finding 1: Multi-branch opening balance summation bug in getGeneralLedger.
     * When branch_id is NULL, branch-specific opening balances are omitted by whereNull('branch_id').
     */
    public function test_audit_finding_1_consolidated_opening_balances(): void
    {
        $branch2 = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Second Branch',
            'code' => 'SB-02',
            'address' => 'Second Branch Address',
            'phone' => '042-3998877',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'status' => 'active',
        ]);

        // Branch 1 has 50,000 Cash opening balance
        AccountOpeningBalance::create([
            'marquee_id' => $this->marquee->id,
            'financial_year_id' => $this->financialYear->id,
            'branch_id' => $this->branch->id,
            'account_id' => $this->cashAccount->id,
            'debit' => 50000.00,
            'credit' => 0.00,
        ]);

        // Branch 2 has 30,000 Cash opening balance
        AccountOpeningBalance::create([
            'marquee_id' => $this->marquee->id,
            'financial_year_id' => $this->financialYear->id,
            'branch_id' => $branch2->id,
            'account_id' => $this->cashAccount->id,
            'debit' => 30000.00,
            'credit' => 0.00,
        ]);

        // Querying Branch 1 specific ledger correctly gets 50,000
        $glBranch1 = $this->accountingService->getGeneralLedger($this->cashAccount->id, '2026-01-01', '2026-12-31', $this->branch->id, $this->financialYear->id);
        $this->assertEquals(50000.00, $glBranch1['opening_balance']);

        // Querying Branch 2 specific ledger correctly gets 30,000
        $glBranch2 = $this->accountingService->getGeneralLedger($this->cashAccount->id, '2026-01-01', '2026-12-31', $branch2->id, $this->financialYear->id);
        $this->assertEquals(30000.00, $glBranch2['opening_balance']);

        // When querying Consolidated (branchId = null), it now aggregates across all branches (50,000 + 30,000 = 80,000)
        $glConsolidated = $this->accountingService->getGeneralLedger($this->cashAccount->id, '2026-01-01', '2026-12-31', null, $this->financialYear->id);
        
        $this->assertEquals(80000.00, $glConsolidated['opening_balance'], 'Consolidated ledger correctly sums branch opening balances');
    }

    /**
     * Test Finding 3 Resolution: Inactive accounts with historical transactions are preserved in Trial Balance.
     */
    public function test_audit_finding_3_inactive_account_unbalances_trial_balance(): void
    {
        // Post a balanced transaction between Cash and an Account
        $this->accountingService->createJournalVoucher([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_date' => '2026-02-01',
            'reference' => 'TX-INACTIVE',
            'notes' => 'Testing inactive account',
            'status' => 'posted',
        ], [
            ['account_id' => $this->cashAccount->id, 'debit' => 10000.00, 'credit' => 0.00, 'narration' => 'Dr Cash'],
            ['account_id' => $this->revenueAccount->id, 'debit' => 0.00, 'credit' => 10000.00, 'narration' => 'Cr Revenue'],
        ]);

        // Prior to deactivating revenueAccount, Trial Balance is balanced at 10,000
        $tbActive = $this->accountingService->getTrialBalance($this->marquee->id, $this->financialYear->id, '2026-12-31');
        $this->assertTrue($tbActive['is_balanced']);

        // Deactivate revenue account
        $this->revenueAccount->update(['is_active' => false]);

        // Trial Balance includes inactive accounts that have transactions/balances in the period
        $tbInactive = $this->accountingService->getTrialBalance($this->marquee->id, $this->financialYear->id, '2026-12-31');
        
        $this->assertTrue($tbInactive['is_balanced'], 'Trial Balance remains balanced even if an account is marked inactive');
        $this->assertEquals(10000.00, $tbInactive['total_debit']);
        $this->assertEquals(10000.00, $tbInactive['total_credit']);
    }

    /**
     * Test Finding 4 Resolution: Service-level validation guards against direct postings to parent header accounts.
     */
    public function test_audit_finding_4_parent_account_direct_posting_prevented(): void
    {
        $parentAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1000',
            'name' => 'Current Assets Header',
            'account_type_id' => $this->cashAccount->account_type_id,
            'nature' => 'Asset',
            'is_active' => true,
            'system_generated' => true,
        ]);

        // Make cashAccount a child of parentAccount
        $this->cashAccount->update(['parent_id' => $parentAccount->id]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot post directly to header/parent account");

        // Attempting to post to parentAccount must throw an InvalidArgumentException
        $this->accountingService->createJournalVoucher([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_date' => '2026-02-15',
            'reference' => 'TX-PARENT',
            'notes' => 'Direct post to parent',
            'status' => 'posted',
        ], [
            ['account_id' => $parentAccount->id, 'debit' => 5000.00, 'credit' => 0.00, 'narration' => 'Post to parent'],
            ['account_id' => $this->cashAccount->id, 'debit' => 0.00, 'credit' => 5000.00, 'narration' => 'Post to child'],
        ]);
    }

    /**
     * Test Finding 2 Resolution: AccountantDashboard isolates opening balances to the active financial year.
     */
    public function test_audit_finding_2_accountant_dashboard_opening_balance_scoping(): void
    {
        // Link cash account to a CashBankAccount record
        $cashBank = CashBankAccount::create([
            'marquee_id' => $this->marquee->id,
            'account_id' => $this->cashAccount->id,
            'type' => 'cash',
            'status' => 'active',
        ]);

        // Prior FY (Closed)
        $priorFy = FinancialYear::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'FY 2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'closed',
            'is_default' => false,
        ]);

        // Opening balance recorded in Prior FY = 50,000
        AccountOpeningBalance::create([
            'marquee_id' => $this->marquee->id,
            'financial_year_id' => $priorFy->id,
            'account_id' => $this->cashAccount->id,
            'debit' => 50000.00,
            'credit' => 0.00,
        ]);

        // Opening balance recorded in Active FY (2026) = 75,000
        AccountOpeningBalance::create([
            'marquee_id' => $this->marquee->id,
            'financial_year_id' => $this->financialYear->id,
            'account_id' => $this->cashAccount->id,
            'debit' => 75000.00,
            'credit' => 0.00,
        ]);

        // Prior to fix, AccountantDashboard summed across all financial years (50k + 75k = 125k).
        // With active FY scoping, it must only count the active FY opening balance (75,000).
        $component = new \App\Livewire\Finance\AccountantDashboard();
        $component->marqueeId = $this->marquee->id;
        $component->mount();
        $view = $component->render();

        $cashAccounts = $view->getData()['cashBankAccounts'];
        $cashItem = $cashAccounts->firstWhere('id', $cashBank->id);

        $this->assertEquals(75000.00, $cashItem->current_balance, 'AccountantDashboard only counts active FY opening balance');
    }

    /**
     * Test Finding 5 Resolution: Security deposit payments route to 2002 and damage deductions to 4006.
     */
    public function test_audit_finding_5_security_deposit_account_separation(): void
    {
        $customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'first_name' => 'Deposit',
            'last_name' => 'Client',
            'phone_number' => '03001234999',
            'email' => 'client@test.com',
            'status' => 'Active',
        ]);

        $hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Royal Arena',
            'hall_code' => 'H-DEP-01',
            'capacity' => 400,
            'hall_type' => 'Marquee',
            'default_booking_price' => 50000.00,
            'status' => 'Active',
        ]);

        $slot = Slot::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'slot_name' => 'Dinner Slot',
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'status' => 'Active',
        ]);

        $eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Wedding Dinner',
            'event_type_code' => 'EV-DEP-01',
            'status' => 'active',
        ]);

        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'hall_id' => $hall->id,
            'slot_id' => $slot->id,
            'event_type_id' => $eventType->id,
            'booking_number' => 'BK-DEP-001',
            'booking_date' => '2026-06-01',
            'event_date' => '2026-06-15',
            'start_time' => now()->setTime(19, 0),
            'end_time' => now()->setTime(23, 30),
            'guest_count' => 300,
            'subtotal' => 100000.00,
            'grand_total' => 100000.00,
            'total_amount' => 100000.00,
            'advance_amount' => 0.00,
            'security_deposit' => 20000.00,
            'deposit_status' => 'Held',
            'booking_status' => 'Confirmed',
            'status' => 'confirmed',
            'is_revenue_recognized' => false,
            'created_by' => $this->user->id,
        ]);

        $bookingFinancialService = app(BookingFinancialService::class);

        // 1. Customer pays security deposit of 20,000
        $payment = $bookingFinancialService->recordPayment($booking, [
            'amount' => 20000.00,
            'payment_date' => '2026-06-01',
            'payment_method' => 'Cash',
            'payment_type' => 'security_deposit',
            'account_id' => $this->cashAccount->id,
        ]);

        $postedPayment = $bookingFinancialService->postPayment($payment, [
            'account_id' => $this->cashAccount->id,
            'posting_date' => '2026-06-01',
        ]);

        // Verify JV lines: Debit Cash 1001, Credit Security Deposits 2002
        $jv = JournalVoucher::with('items')->find($postedPayment->journal_voucher_id);
        $jvItems = $jv->items;
        $creditItem = $jvItems->where('credit', '>', 0)->first();
        $creditAccount = Account::find($creditItem->account_id);
        $this->assertEquals('2002', $creditAccount->account_code, 'Dedicated security deposit routes to 2002 (Security Deposits)');

        // 2. Process Security Deposit: Refund 15,000, Deduct 5,000 for damages
        $result = $bookingFinancialService->processSecurityDeposit($booking, [
            'refund_amount' => 15000.00,
            'deducted_amount' => 5000.00,
            'account_id' => $this->cashAccount->id,
            'payment_method' => 'Cash',
            'process_date' => '2026-06-16',
        ]);

        $depJv = $result['journal_voucher'];
        $depItems = $depJv->items;

        // Debit 2002 (Security Deposits) for 20,000
        $debitItem = $depItems->where('debit', '>', 0)->first();
        $this->assertEquals(20000.00, $debitItem->debit);
        $this->assertEquals('2002', Account::find($debitItem->account_id)->account_code);

        // Credit Cash 1001 for 15,000
        $cashRefundItem = $depItems->where('account_id', $this->cashAccount->id)->first();
        $this->assertEquals(15000.00, $cashRefundItem->credit);

        // Credit Damage Income 4006 for 5,000
        $damageItem = $depItems->where('credit', 5000.00)->first();
        $damageAccount = Account::find($damageItem->account_id);
        $this->assertEquals('4006', $damageAccount->account_code, 'Damage deduction routes to 4006 (Security Deposit Forfeiture & Damage Income)');
    }
}
