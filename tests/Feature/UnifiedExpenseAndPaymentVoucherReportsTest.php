<?php

namespace Tests\Feature;

use App\Livewire\Finance\ExpenseDashboard;
use App\Livewire\Finance\ExpenseReportManager;
use App\Livewire\Owner\BusinessOwnerDashboard;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Branch;
use App\Models\CashBankAccount;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialYear;
use App\Models\Marquee;
use App\Models\PaymentVoucher;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UnifiedExpenseAndPaymentVoucherReportsTest extends TestCase
{
    use RefreshDatabase;

    protected $ownerRole;
    protected $marquee;
    protected $branch;
    protected $user;
    protected $category;
    protected $supplier;
    protected $expenseAccount;
    protected $cashAccount;
    protected $cashBankAccount;
    protected $expenseVoucher;
    protected $pvOperatingExpense;
    protected $pvSettledExpense;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner']);
        $pView = Permission::create(['name' => 'view_expenses', 'label' => 'View Expenses']);
        $pReports = Permission::create(['name' => 'view_reports', 'label' => 'View Reports']);
        $this->ownerRole->permissions()->attach([$pView->id, $pReports->id]);

        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'price' => 15000,
            'billing_interval' => 'month',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Grand Carlton Banquet',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'address' => 'Gulberg, Lahore',
            'phone' => '03001234567',
            'email' => 'contact@grandcarlton.pk',
            'subscription_plan_id' => $plan->id,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Carlton Main Hall',
            'address' => 'Gulberg, Lahore',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '04235778899',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'name' => 'Malik Jahangir',
            'email' => 'jahangir@grandcarlton.pk',
            'password' => bcrypt('password123'),
            'marquee_id' => $this->marquee->id,
            'role_id' => $this->ownerRole->id,
            'status' => 'active',
        ]);

        $accTypeAsset = AccountType::create([
            'name' => 'Current Assets',
            'code' => '100',
            'nature' => 'Asset',
        ]);

        $accTypeExpense = AccountType::create([
            'name' => 'Operating Expenses',
            'code' => '500',
            'nature' => 'Expense',
        ]);

        $this->cashAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Cash in Hand',
            'account_code' => '1001',
            'account_type_id' => $accTypeAsset->id,
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        $this->expenseAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Repairs & Maintenance Head',
            'account_code' => '5010',
            'account_type_id' => $accTypeExpense->id,
            'nature' => 'Expense',
            'is_active' => true,
        ]);

        $this->cashBankAccount = CashBankAccount::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'account_id' => $this->cashAccount->id,
            'account_name' => 'Main Drawer Cash',
            'type' => 'cash',
            'status' => 'active',
            'current_balance' => 200000,
        ]);

        $this->category = ExpenseCategory::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Repairs & Maintenance',
            'category_code' => 'CAT-REP-01',
            'default_account_id' => $this->expenseAccount->id,
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'marquee_id' => $this->marquee->id,
            'supplier_code' => 'SUP-001',
            'name' => 'Al-Madina Electric & Hardware',
            'mobile_number' => '03009988776',
            'status' => 'active',
        ]);

        $currency = Currency::create([
            'marquee_id' => $this->marquee->id,
            'code' => 'PKR',
            'name' => 'Pakistani Rupee',
            'symbol' => 'Rs',
            'exchange_rate' => 1.0,
            'is_base' => true,
            'is_active' => true,
        ]);

        // 1. Direct Expense created in Expense module
        $this->expenseVoucher = Expense::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $currency->id,
            'expense_number' => 'EXP-20261106-00001',
            'expense_date' => date('Y-m-d'),
            'expense_category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 25000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 25000,
            'total_amount_base' => 25000,
            'payment_method' => 'Cash',
            'payment_status' => 'Paid',
            'status' => Expense::STATUS_APPROVED,
            'description' => 'Generator repairs and oil replacement',
        ]);

        // 2. Direct Operating Expense processed via Payment Voucher (payee_type = 'expense')
        $this->pvOperatingExpense = PaymentVoucher::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_type' => 'CPV',
            'voucher_no' => 'CPV-2026-HO-0001',
            'voucher_date' => date('Y-m-d'),
            'payee_type' => PaymentVoucher::PAYEE_EXPENSE,
            'payee_id' => $this->category->id,
            'payee_name' => $this->category->name,
            'cash_bank_account_id' => $this->cashBankAccount->id,
            'debit_account_id' => $this->expenseAccount->id,
            'amount' => 15000,
            'amount_in_words' => 'Rupees Fifteen Thousand Only',
            'payment_method' => 'Cash',
            'description' => 'Air conditioner capacitor replacement',
            'status' => PaymentVoucher::STATUS_APPROVED,
            'expense_id' => null, // Standalone operating expense
            'created_by' => $this->user->id,
        ]);

        // 3. Payment voucher settling an existing expense (expense_id is set) -> should NOT be double counted
        $this->pvSettledExpense = PaymentVoucher::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_type' => 'CPV',
            'voucher_no' => 'CPV-2026-HO-0002',
            'voucher_date' => date('Y-m-d'),
            'payee_type' => PaymentVoucher::PAYEE_EXPENSE,
            'payee_id' => $this->category->id,
            'payee_name' => $this->category->name,
            'cash_bank_account_id' => $this->cashBankAccount->id,
            'debit_account_id' => $this->expenseAccount->id,
            'amount' => 25000,
            'payment_method' => 'Cash',
            'description' => 'Settlement for EXP-20261106-00001',
            'status' => PaymentVoucher::STATUS_POSTED,
            'expense_id' => $this->expenseVoucher->id, // Settling existing expense
            'created_by' => $this->user->id,
        ]);
    }

    public function test_category_summary_consolidates_both_expense_and_payment_voucher(): void
    {
        $this->actingAs($this->user);

        // Expected combined total = 25,000 (Expense) + 15,000 (Payment Voucher) = 40,000
        Livewire::test(ExpenseReportManager::class)
            ->set('reportType', 'category_summary')
            ->set('start_date', date('Y-m-01'))
            ->set('end_date', date('Y-m-t'))
            ->assertStatus(200)
            ->assertSee('Repairs & Maintenance')
            ->assertSee('40,000.00') // Consolidated category total
            ->assertSee('2'); // 2 vouchers consolidated
    }

    public function test_payment_methods_consolidates_both_sources(): void
    {
        $this->actingAs($this->user);

        // Both are Cash: 25,000 + 15,000 = 40,000
        Livewire::test(ExpenseReportManager::class)
            ->set('reportType', 'payment_methods')
            ->set('start_date', date('Y-m-01'))
            ->set('end_date', date('Y-m-t'))
            ->assertStatus(200)
            ->assertSee('Cash in Hand')
            ->assertSee('40,000.00');
    }

    public function test_register_log_displays_both_vouchers_with_source_badges(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ExpenseReportManager::class)
            ->set('reportType', 'register')
            ->set('start_date', date('Y-m-01'))
            ->set('end_date', date('Y-m-t'))
            ->assertStatus(200)
            ->assertSee('EXP-20261106-00001')
            ->assertSee('CPV-2026-HO-0001')
            ->assertSee('Expense')
            ->assertSee('CPV')
            ->assertSee('Brief Note / Purpose')
            ->assertSee('Generator repairs and oil replacement')
            ->assertSee('Air conditioner capacitor replacement')
            ->assertSee('25,000.00')
            ->assertSee('15,000.00')
            ->assertSee('40,000.00'); // Total consolidated footer
    }

    public function test_export_csv_streams_both_expense_and_payment_voucher(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ExpenseReportManager::class)->set('reportType', 'register');
        $response = $component->instance()->exportCSV();

        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());

        ob_start();
        $response->sendContent();
        $csvContent = ob_get_clean();

        $this->assertStringContainsString('Brief Note / Purpose', $csvContent);
        $this->assertStringContainsString('Generator repairs and oil replacement', $csvContent);
        $this->assertStringContainsString('Air conditioner capacitor replacement', $csvContent);
        $this->assertStringContainsString('EXP-20261106-00001', $csvContent);
        $this->assertStringContainsString('CPV-2026-HO-0001', $csvContent);
        $this->assertStringContainsString('Expense Voucher', $csvContent);
        $this->assertStringContainsString('Payment Voucher', $csvContent);
        $this->assertStringContainsString('25000', $csvContent);
        $this->assertStringContainsString('15000', $csvContent);
    }

    public function test_business_owner_dashboard_reflects_consolidated_operating_expenses(): void
    {
        $this->actingAs($this->user);

        // In BusinessOwnerDashboard, operating expenses are rendered as "Rs. 40.0k" (40,000 / 1000)
        Livewire::test(BusinessOwnerDashboard::class)
            ->set('timeframe', 'this_month')
            ->assertStatus(200)
            ->assertSee('Rs. 40.0k') // Consolidated 40k expenses
            ->assertSee('Repairs & Maintenance'); // In top expense categories breakdown
    }

    public function test_expense_dashboard_reflects_consolidated_period_expenses_and_categories(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ExpenseDashboard::class)
            ->set('period', 'this_month')
            ->assertStatus(200)
            ->assertSee('40,000.00 PKR') // Consolidated period expenses
            ->assertSee('Repairs & Maintenance'); // In category breakdown
    }
}
