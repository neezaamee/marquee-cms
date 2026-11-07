<?php

namespace Tests\Feature;

use App\Livewire\Finance\ExpenseReportManager;
use App\Livewire\Finance\ProfitLoss;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialYear;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherItem;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfitLossDateFilterPeriodTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Marquee $marquee;
    protected Branch $branch;
    protected FinancialYear $fy;
    protected Account $expenseAccount;
    protected ExpenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::firstOrCreate(['name' => 'business_owner'], ['label' => 'Business Owner']);
        $this->marquee = Marquee::factory()->create(['name' => 'Test Sheraton Marquee']);
        $this->branch = Branch::factory()->create(['marquee_id' => $this->marquee->id, 'name' => 'Main Branch']);

        $this->owner = User::create([
            'name' => 'Test Owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('Password123!'),
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marquee->id,
            'branch_id' => null,
            'status' => 'active',
        ]);
        $this->owner->ownedMarquees()->attach($this->marquee->id);

        $this->fy = FinancialYear::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'is_default' => true,
        ]);

        $expType = AccountType::firstOrCreate(['name' => 'Operating Expense'], ['code' => 'EXP', 'nature' => 'Expense']);
        $this->expenseAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '5503',
            'name' => 'Maintenance',
            'account_type_id' => $expType->id,
            'nature' => 'Expense',
            'is_active' => true,
        ]);

        $currency = \App\Models\Currency::create([
            'marquee_id' => $this->marquee->id,
            'code' => 'PKR',
            'name' => 'Pakistani Rupee',
            'symbol' => 'Rs',
            'exchange_rate' => 1.0,
            'is_base' => true,
            'is_active' => true,
        ]);

        $this->category = ExpenseCategory::create([
            'marquee_id' => $this->marquee->id,
            'category_code' => 'CAT-MAIN',
            'name' => 'Maintenance',
            'default_account_id' => $this->expenseAccount->id,
        ]);
    }

    public function test_profit_loss_strictly_reports_period_expenses_without_prior_months()
    {
        $this->actingAs($this->owner);

        $currency = \App\Models\Currency::where('marquee_id', $this->marquee->id)->first();

        // 1. Create October Expense (Prior Month) of Rs. 8,930
        $octExpense = Expense::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $currency->id,
            'expense_number' => 'EXP-20261020-001',
            'expense_date' => '2026-10-20',
            'expense_category_id' => $this->category->id,
            'account_id' => $this->expenseAccount->id,
            'payment_method' => 'Cash',
            'subtotal' => 8930,
            'total_amount' => 8930,
            'status' => 'posted',
        ]);

        $octJv = JournalVoucher::create([
            'marquee_id' => $this->marquee->id,
            'financial_year_id' => $this->fy->id,
            'voucher_no' => 'JV-2026-OCT-001',
            'voucher_date' => '2026-10-20',
            'status' => 'posted',
        ]);
        JournalVoucherItem::create([
            'journal_voucher_id' => $octJv->id,
            'account_id' => $this->expenseAccount->id,
            'debit' => 8930,
            'credit' => 0,
        ]);

        // 2. Create November Expense (Current Month) of Rs. 346,770
        $novExpense = Expense::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $currency->id,
            'expense_number' => 'EXP-20261110-001',
            'expense_date' => '2026-11-10',
            'expense_category_id' => $this->category->id,
            'account_id' => $this->expenseAccount->id,
            'payment_method' => 'Cash',
            'subtotal' => 346770,
            'total_amount' => 346770,
            'status' => 'posted',
        ]);

        $novJv = JournalVoucher::create([
            'marquee_id' => $this->marquee->id,
            'financial_year_id' => $this->fy->id,
            'voucher_no' => 'JV-2026-NOV-001',
            'voucher_date' => '2026-11-10',
            'status' => 'posted',
        ]);
        JournalVoucherItem::create([
            'journal_voucher_id' => $novJv->id,
            'account_id' => $this->expenseAccount->id,
            'debit' => 346770,
            'credit' => 0,
        ]);

        // 3. Test Profit and Loss Statement for November 1 to November 30
        $accountingService = app(AccountingService::class);
        $pl = $accountingService->getProfitAndLoss($this->marquee->id, $this->fy->id, '2026-11-01', '2026-11-30');

        // Profit and Loss for November must ONLY report Rs. 346,770, NOT 355,700!
        $this->assertEquals(346770.0, $pl['total_expense']);

        // 4. Test Expense Report Detailed Register Log for November 1 to November 30
        Livewire::test(ExpenseReportManager::class)
            ->set('reportType', 'register')
            ->set('start_date', '2026-11-01')
            ->set('end_date', '2026-11-30')
            ->call('generateReport')
            ->assertViewHas('reportData', function ($data) {
                $total = array_sum(array_column($data, 'total_amount'));
                return abs($total - 346770.0) < 0.01;
            });
    }
}
