<?php

namespace Tests\Feature;

use App\Livewire\Finance\PaymentVoucherForm;
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
use App\Models\Role;
use App\Models\User;
use App\Services\PaymentVoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentVoucherExpenseForeignKeyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Marquee $marquee;
    protected Branch $branch;
    protected FinancialYear $fy;
    protected CashBankAccount $cashAccount;
    protected Account $debitAccount;
    protected ExpenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super_admin'], ['label' => 'Super Administrator']);
        $this->marquee = Marquee::factory()->create(['name' => 'Test Sheraton Marquee']);
        $this->branch = Branch::factory()->create(['marquee_id' => $this->marquee->id, 'name' => 'Main Branch']);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('Password123!'),
            'role_id' => $role->id,
            'marquee_id' => $this->marquee->id,
            'branch_id' => null,
            'status' => 'active',
        ]);
        $this->admin->ownedMarquees()->attach($this->marquee->id);

        $this->fy = FinancialYear::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'is_default' => true,
        ]);

        $assetType = AccountType::firstOrCreate(['name' => 'Cash & Bank'], ['code' => 'CB', 'nature' => 'Asset']);
        $expType = AccountType::firstOrCreate(['name' => 'Operating Expense'], ['code' => 'EXP', 'nature' => 'Expense']);

        $cashGlAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1001',
            'name' => 'Cash in Hand',
            'account_type_id' => $assetType->id,
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        $this->cashAccount = CashBankAccount::create([
            'marquee_id' => $this->marquee->id,
            'account_id' => $cashGlAccount->id,
            'account_title' => 'Main Cash Drawer',
            'type' => 'cash',
            'currency' => 'PKR',
            'status' => 'active',
        ]);

        $this->debitAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '5503',
            'name' => 'Maintenance',
            'account_type_id' => $expType->id,
            'nature' => 'Expense',
            'is_active' => true,
        ]);

        $this->category = ExpenseCategory::create([
            'marquee_id' => $this->marquee->id,
            'category_code' => 'CAT-159',
            'name' => 'Maintenance Category',
            'default_account_id' => $this->debitAccount->id,
            'is_active' => true,
        ]);
    }

    public function test_updating_payment_voucher_for_expense_category_does_not_set_invalid_expense_id()
    {
        $this->actingAs($this->admin);

        $service = app(PaymentVoucherService::class);

        // 1. Create a payment voucher for an Operating Expense Category (payee_id = category->id, expense_id = null)
        $voucher = $service->createPaymentVoucher([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'financial_year_id' => $this->fy->id,
            'voucher_type' => 'CPV',
            'voucher_date' => '2026-11-03',
            'payee_type' => 'expense',
            'payee_id' => $this->category->id,
            'expense_id' => null,
            'payee_name' => $this->category->name,
            'cash_bank_account_id' => $this->cashAccount->id,
            'debit_account_id' => $this->debitAccount->id,
            'amount' => 5000,
            'payment_method' => 'Cash',
            'status' => 'approved',
        ], $this->admin->id);

        $this->assertNull($voucher->expense_id);
        $this->assertEquals($this->category->id, $voucher->payee_id);

        // 2. Edit and save via PaymentVoucherForm
        Livewire::test(PaymentVoucherForm::class, ['id' => $voucher->id])
            ->set('voucher_date', '2026-11-04')
            ->call('save')
            ->assertHasNoErrors();

        // 3. Verify voucher in database: expense_id MUST be null because payee_id is an ExpenseCategory, NOT an Expense record!
        $voucher->refresh();
        $this->assertNull($voucher->expense_id);
        $this->assertEquals('2026-11-04', $voucher->voucher_date->format('Y-m-d'));
        $this->assertEquals($this->category->id, $voucher->payee_id);
    }

    public function test_updating_payment_voucher_for_real_expense_links_valid_expense_id()
    {
        $this->actingAs($this->admin);

        $currency = Currency::create([
            'marquee_id' => $this->marquee->id,
            'code' => 'PKR',
            'name' => 'Pakistani Rupee',
            'symbol' => 'Rs',
            'exchange_rate' => 1.0,
            'is_base' => true,
            'is_active' => true,
        ]);

        $realExpense = Expense::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $currency->id,
            'expense_number' => 'EXP-20261103-0099',
            'expense_date' => '2026-11-03',
            'expense_category_id' => $this->category->id,
            'account_id' => $this->debitAccount->id,
            'payment_method' => 'Cash',
            'subtotal' => 12000,
            'total_amount' => 12000,
            'status' => 'posted',
        ]);

        $service = app(PaymentVoucherService::class);

        $voucher = $service->createPaymentVoucher([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'financial_year_id' => $this->fy->id,
            'voucher_type' => 'CPV',
            'voucher_date' => '2026-11-03',
            'payee_type' => 'expense',
            'payee_id' => $realExpense->id,
            'expense_id' => $realExpense->id,
            'payee_name' => 'Expense Settlement',
            'cash_bank_account_id' => $this->cashAccount->id,
            'debit_account_id' => $this->debitAccount->id,
            'amount' => 12000,
            'payment_method' => 'Cash',
            'status' => 'approved',
        ], $this->admin->id);

        $this->assertEquals($realExpense->id, $voucher->expense_id);

        // Edit via PaymentVoucherForm
        Livewire::test(PaymentVoucherForm::class, ['id' => $voucher->id])
            ->set('description', 'Updated settlement description')
            ->call('save')
            ->assertHasNoErrors();

        $voucher->refresh();
        $this->assertEquals($realExpense->id, $voucher->expense_id);
        $this->assertEquals('Updated settlement description', $voucher->description);
    }
}
