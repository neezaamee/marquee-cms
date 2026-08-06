<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Branch;
use App\Models\FinancialYear;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccountingReportsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $marquee;
    protected $branch;
    protected $accountingService;
    protected $fy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountingService = new AccountingService();

        // Seed roles & plans
        $this->artisan('db:seed', ['--class' => 'SubscriptionPlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $plan = SubscriptionPlan::first();

        $this->marquee = Marquee::create([
            'name' => 'Test Marquee',
            'email' => 'test@marquee.com',
            'phone' => '12345678',
            'address' => 'Test Address',
            'city' => 'Test City',
            'province' => 'Test Province',
            'ntn' => '123456',
            'status' => 'active',
            'subscription_plan_id' => $plan->id,
        ]);

        $ownerRole = Role::where('name', 'owner')->first();

        $this->user = User::create([
            'name' => 'Owner User',
            'email' => 'owner@test.com',
            'username' => 'owner',
            'password' => bcrypt('Password123!'),
            'marquee_id' => $this->marquee->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Test Branch',
            'address' => 'Test Address',
            'city' => 'Test City',
            'province' => 'Test Province',
            'phone' => '123456',
            'status' => 'active',
        ]);

        $this->artisan('db:seed', ['--class' => 'AccountingModuleSeeder']);

        $this->fy = FinancialYear::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'is_default' => true,
        ]);
    }

    /** @test */
    public function test_profit_and_loss_calculation()
    {
        $this->actingAs($this->user);

        // Fetch Accounts
        $cash = Account::where('marquee_id', $this->marquee->id)->where('account_code', '1001')->first();
        $revenue = Account::where('marquee_id', $this->marquee->id)->where('account_code', '4001')->first();
        $salaryExpense = Account::where('marquee_id', $this->marquee->id)->where('account_code', '5501')->first();

        // 1. Post Revenue: Debit Cash 50000, Credit Revenue 50000
        $header1 = [
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_date' => '2026-05-15',
            'reference' => 'Booking Receipt',
            'notes' => 'Received booking advance',
            'status' => 'posted',
        ];
        $items1 = [
            ['account_id' => $cash->id, 'debit' => 50000.00, 'credit' => 0.00, 'narration' => 'Debit cash'],
            ['account_id' => $revenue->id, 'debit' => 0.00, 'credit' => 50000.00, 'narration' => 'Credit revenue'],
        ];
        $this->accountingService->createJournalVoucher($header1, $items1);

        // 2. Post Expense: Debit Salary Expense 12000, Credit Cash 12000
        $header2 = [
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_date' => '2026-05-20',
            'reference' => 'May Salaries',
            'notes' => 'Paid helper staff',
            'status' => 'posted',
        ];
        $items2 = [
            ['account_id' => $salaryExpense->id, 'debit' => 12000.00, 'credit' => 0.00, 'narration' => 'Debit salary'],
            ['account_id' => $cash->id, 'debit' => 0.00, 'credit' => 12000.00, 'narration' => 'Credit cash'],
        ];
        $this->accountingService->createJournalVoucher($header2, $items2);

        // Get P&L
        $plData = $this->accountingService->getProfitAndLoss($this->marquee->id, $this->fy->id, '2026-01-01', '2026-12-31');

        $this->assertEquals(50000.00, $plData['total_income']);
        $this->assertEquals(12000.00, $plData['total_expense']);
        $this->assertEquals(38000.00, $plData['net_profit_loss']);

        // Test Livewire component loads
        Livewire::test('finance.profit-loss')
            ->set('financial_year_id', $this->fy->id)
            ->set('startDate', '2026-01-01')
            ->set('endDate', '2026-12-31')
            ->call('generateReport')
            ->assertSet('reportData.net_profit_loss', 38000.00)
            ->assertSee('Rs. 50,000.00')
            ->assertSee('Rs. 12,000.00')
            ->assertSee('Rs. 38,000.00');
    }

    /** @test */
    public function test_balance_sheet_calculation()
    {
        $this->actingAs($this->user);

        // Fetch Accounts
        $cash = Account::where('marquee_id', $this->marquee->id)->where('account_code', '1001')->first();
        $revenue = Account::where('marquee_id', $this->marquee->id)->where('account_code', '4001')->first();
        $expense = Account::where('marquee_id', $this->marquee->id)->where('account_code', '5501')->first();
        $ownerCapital = Account::where('marquee_id', $this->marquee->id)->where('account_code', '3001')->first();

        // 1. Post Owner Capital: Debit Cash 100000, Credit Owner Capital 100000
        $header1 = [
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_date' => '2026-01-10',
            'reference' => 'Initial investment',
            'status' => 'posted',
        ];
        $items1 = [
            ['account_id' => $cash->id, 'debit' => 100000.00, 'credit' => 0.00],
            ['account_id' => $ownerCapital->id, 'debit' => 0.00, 'credit' => 100000.00],
        ];
        $this->accountingService->createJournalVoucher($header1, $items1);

        // 2. Post Revenue: Debit Cash 30000, Credit Revenue 30000
        $header2 = [
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_date' => '2026-03-15',
            'reference' => 'Service payment',
            'status' => 'posted',
        ];
        $items2 = [
            ['account_id' => $cash->id, 'debit' => 30000.00, 'credit' => 0.00],
            ['account_id' => $revenue->id, 'debit' => 0.00, 'credit' => 30000.00],
        ];
        $this->accountingService->createJournalVoucher($header2, $items2);

        // 3. Post Expense: Debit Expense 5000, Credit Cash 5000
        $header3 = [
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_date' => '2026-04-01',
            'reference' => 'Office utilities',
            'status' => 'posted',
        ];
        $items3 = [
            ['account_id' => $expense->id, 'debit' => 5000.00, 'credit' => 0.00],
            ['account_id' => $cash->id, 'debit' => 0.00, 'credit' => 5000.00],
        ];
        $this->accountingService->createJournalVoucher($header3, $items3);

        // Get Balance Sheet
        $bsData = $this->accountingService->getBalanceSheet($this->marquee->id, $this->fy->id, '2026-12-31');

        // Cash balance should be: 100,000 + 30,000 - 5,000 = 125,000 (Assets)
        $this->assertEquals(125000.00, $bsData['total_assets']);

        // Liabilities = 0
        $this->assertEquals(0.00, $bsData['total_liabilities']);

        // Equity = Owner Capital (100k) + Current Period Earnings (30k - 5k = 25k) = 125,000
        $this->assertEquals(125000.00, $bsData['total_equity']);
        $this->assertTrue($bsData['is_balanced']);

        // Test Livewire component loads
        Livewire::test('finance.balance-sheet')
            ->set('financial_year_id', $this->fy->id)
            ->set('asOfDate', '2026-12-31')
            ->call('generateReport')
            ->assertSet('reportData.total_assets', 125000.00)
            ->assertSet('reportData.total_equity', 125000.00)
            ->assertSet('reportData.is_balanced', true)
            ->assertSee('Rs. 125,000.00');
    }
}
