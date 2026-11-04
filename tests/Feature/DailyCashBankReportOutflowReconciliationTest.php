<?php

namespace Tests\Feature;

use App\Livewire\Finance\DailyCashBankReport;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Branch;
use App\Models\CashBankAccount;
use App\Models\Expense;
use App\Models\FinancialYear;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherItem;
use App\Models\Marquee;
use App\Models\PaymentVoucher;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DailyCashBankReportOutflowReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $marquee;
    protected $branch;
    protected $cashAccount;
    protected $bankAccount;
    protected $cashBankRecord;
    protected $bankRecord;
    protected $fy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'SubscriptionPlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $plan = SubscriptionPlan::first();

        $this->marquee = Marquee::create([
            'name' => 'Royal Palace Marquee',
            'email' => 'finance@royalpalace.com',
            'phone' => '03001234567',
            'address' => 'Main Boulevard',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'status' => 'active',
            'subscription_plan_id' => $plan->id,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Executive Hall',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'address' => 'Mall Road, Lahore',
            'phone' => '03001234567',
            'status' => 'active',
        ]);

        $ownerRole = Role::where('name', 'business_owner')->first() ?? Role::first();

        $this->user = User::create([
            'name' => 'Finance Director',
            'email' => 'director@royalpalace.com',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->fy = FinancialYear::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'FY 2026-2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        $assetType = AccountType::firstOrCreate(
            ['code' => 'CURRENT_ASSETS'],
            ['name' => 'Current Assets', 'nature' => 'Asset']
        );

        $payableType = AccountType::firstOrCreate(
            ['code' => 'CURRENT_LIABILITIES'],
            ['name' => 'Current Liabilities', 'nature' => 'Liability']
        );

        $this->cashAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_type_id' => $assetType->id,
            'account_code' => '1001',
            'name' => 'Physical Cash Drawer',
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        $this->bankAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_type_id' => $assetType->id,
            'account_code' => '1002',
            'name' => 'Meezan Bank Current Account',
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        $this->cashBankRecord = CashBankAccount::create([
            'marquee_id' => $this->marquee->id,
            'account_id' => $this->cashAccount->id,
            'type' => 'cash',
            'bank_name' => 'Cash Safe Vault',
            'status' => 'active',
        ]);

        $this->bankRecord = CashBankAccount::create([
            'marquee_id' => $this->marquee->id,
            'account_id' => $this->bankAccount->id,
            'type' => 'bank',
            'bank_name' => 'Meezan Bank Ltd',
            'account_number' => 'PK12MEZN000123456789',
            'status' => 'active',
        ]);
    }

    public function test_standalone_journal_voucher_credit_is_itemized_and_reconciles_with_closing_balance(): void
    {
        $this->actingAs($this->user);
        $reportDate = '2026-11-04';

        // Create a standalone Supplier Payment JV that credits Cash 1001
        $apAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_type_id' => AccountType::first()->id,
            'account_code' => '2001',
            'name' => 'Accounts Payable',
            'nature' => 'Liability',
            'is_active' => true,
        ]);

        $jv = JournalVoucher::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'financial_year_id' => $this->fy->id,
            'voucher_no' => 'JV-2026-HO-0048',
            'voucher_date' => $reportDate,
            'reference' => 'PMT-7930',
            'notes' => 'Auto-posted Supplier Payment to: A-H Trader (Beverages). Online Transfer',
            'status' => 'posted',
        ]);

        JournalVoucherItem::create([
            'journal_voucher_id' => $jv->id,
            'account_id' => $apAccount->id,
            'debit' => 50000.00,
            'credit' => 0.00,
            'narration' => 'Debit Accounts Payable - Payment to A-H Trader (Beverages)',
        ]);

        JournalVoucherItem::create([
            'journal_voucher_id' => $jv->id,
            'account_id' => $this->cashAccount->id,
            'debit' => 0.00,
            'credit' => 50000.00,
            'narration' => 'Credit Asset - Paid via Cash',
        ]);

        // Test Livewire component
        $component = Livewire::test(DailyCashBankReport::class, ['reportDate' => $reportDate])
            ->assertSee('JV-2026-HO-0048')
            ->assertSee('A-H Trader (Beverages)')
            ->assertSee('50,000')
            ->assertSee('Supplier')
            ->assertSee('Direct JVs / Settlements:');

        $outflows = $component->viewData('allOutflows');
        $this->assertCount(1, $outflows);
        $item = $outflows->first();

        $this->assertEquals('JV-2026-HO-0048', $item['ref_no']);
        $this->assertEquals('A-H Trader (Beverages)', $item['payee_title']);
        $this->assertEquals('Supplier Payment', $item['category_label']);
        $this->assertEquals(50000.00, $item['amount']);
        $this->assertTrue($item['is_cash']);

        // Check 100% reconciliation
        $totalOutflow = $component->viewData('totalOutflow');
        $totalCashOutflow = $component->viewData('totalCashOutflow');
        $accountPositions = $component->viewData('accountPositions');

        $this->assertEquals(50000.00, $totalOutflow);
        $this->assertEquals(50000.00, $totalCashOutflow);

        $cashPos = $accountPositions->firstWhere('account_id', $this->cashAccount->id);
        $this->assertEquals(50000.00, $cashPos['day_outflow']);
    }

    public function test_vendor_settlement_and_contra_transfer_appear_in_outflows(): void
    {
        $this->actingAs($this->user);
        $reportDate = '2026-11-04';

        // 1. Vendor settlement JV
        $vendorPayableAcc = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_type_id' => AccountType::first()->id,
            'account_code' => '2150-VEN',
            'name' => 'Vendor Payable Clearing',
            'nature' => 'Liability',
            'is_active' => true,
        ]);

        $jvSettlement = JournalVoucher::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'financial_year_id' => $this->fy->id,
            'voucher_no' => 'JV-VPAY-1793626851-627',
            'voucher_date' => $reportDate,
            'reference' => 'PAY-VS-2026-00152',
            'notes' => "Installment payment to Kashif DJ for Booking #13102026-000002",
            'status' => 'posted',
        ]);

        JournalVoucherItem::create([
            'journal_voucher_id' => $jvSettlement->id,
            'account_id' => $vendorPayableAcc->id,
            'debit' => 4000.00,
            'credit' => 0.00,
            'narration' => 'Installment payment to Kashif DJ',
        ]);

        JournalVoucherItem::create([
            'journal_voucher_id' => $jvSettlement->id,
            'account_id' => $this->cashAccount->id,
            'debit' => 0.00,
            'credit' => 4000.00,
            'narration' => 'Disbursed via Cash',
        ]);

        // 2. Contra Cash Deposit into Bank
        $jvContra = JournalVoucher::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'financial_year_id' => $this->fy->id,
            'voucher_no' => 'JV-CONTRA-001',
            'voucher_date' => $reportDate,
            'reference' => 'DEPOSIT-101',
            'notes' => 'Cash safe drawer funds deposited into Meezan Bank',
            'status' => 'posted',
        ]);

        JournalVoucherItem::create([
            'journal_voucher_id' => $jvContra->id,
            'account_id' => $this->bankAccount->id,
            'debit' => 15000.00,
            'credit' => 0.00,
            'narration' => 'Bank deposit',
        ]);

        JournalVoucherItem::create([
            'journal_voucher_id' => $jvContra->id,
            'account_id' => $this->cashAccount->id,
            'debit' => 0.00,
            'credit' => 15000.00,
            'narration' => 'Cash withdrawal for deposit',
        ]);

        $component = Livewire::test(DailyCashBankReport::class, ['reportDate' => $reportDate])
            ->assertSee('Kashif DJ')
            ->assertSee('Vendor')
            ->assertSee('Contra')
            ->assertSee('Transfer to Meezan Bank Current Account');
        $outflows = $component->viewData('allOutflows');

        $this->assertCount(2, $outflows);
        $this->assertEquals(19000.00, $component->viewData('totalOutflow'));

        // Check Kashif DJ vendor settlement
        $vItem = $outflows->firstWhere('ref_no', 'JV-VPAY-1793626851-627');
        $this->assertNotNull($vItem);
        $this->assertEquals('Kashif DJ', $vItem['payee_title']);
        $this->assertEquals('Vendor Settlement', $vItem['category_label']);
        $this->assertEquals(4000.00, $vItem['amount']);

        // Check Contra Transfer
        $cItem = $outflows->firstWhere('ref_no', 'JV-CONTRA-001');
        $this->assertNotNull($cItem);
        $this->assertEquals('Contra / Bank Transfer', $cItem['category_label']);
        $this->assertEquals(15000.00, $cItem['amount']);
    }

    public function test_existing_payment_vouchers_are_not_duplicated(): void
    {
        $this->actingAs($this->user);
        $reportDate = '2026-11-04';

        // Create a Payment Voucher with its linked Journal Voucher
        $jv = JournalVoucher::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'financial_year_id' => $this->fy->id,
            'voucher_no' => 'JV-CPV-001',
            'voucher_date' => $reportDate,
            'reference' => 'CPV-2026-0001',
            'status' => 'posted',
        ]);

        JournalVoucherItem::create([
            'journal_voucher_id' => $jv->id,
            'account_id' => $this->cashAccount->id,
            'debit' => 0.00,
            'credit' => 8000.00,
            'narration' => 'CPV payment',
        ]);

        $pv = PaymentVoucher::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'financial_year_id' => $this->fy->id,
            'voucher_no' => 'CPV-2026-0001',
            'voucher_type' => 'CPV',
            'voucher_date' => $reportDate,
            'payee_name' => 'City Power Generator',
            'cash_bank_account_id' => $this->cashBankRecord->id,
            'debit_account_id' => $this->cashAccount->id,
            'amount' => 8000.00,
            'status' => PaymentVoucher::STATUS_POSTED,
            'journal_voucher_id' => $jv->id,
        ]);

        $component = Livewire::test(DailyCashBankReport::class, ['reportDate' => $reportDate])
            ->assertSee('CPV-2026-0001')
            ->assertSee('City Power Generator')
            ->assertSee('8,000');
        $outflows = $component->viewData('allOutflows');

        // Must only have 1 item, NOT 2 (no duplicate from GL discovery)
        $this->assertCount(1, $outflows);
        $this->assertEquals(8000.00, $component->viewData('totalOutflow'));
        $this->assertEquals('CPV-2026-0001', $outflows->first()['ref_no']);
        $this->assertEquals('City Power Generator', $outflows->first()['payee_title']);
    }
}
