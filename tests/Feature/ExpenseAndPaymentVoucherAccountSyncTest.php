<?php

namespace Tests\Feature;

use App\Livewire\Finance\ChartOfAccounts;
use App\Livewire\Finance\ExpenseForm;
use App\Livewire\Finance\PaymentVoucherForm;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Branch;
use App\Models\CashBankAccount;
use App\Models\ExpenseCategory;
use App\Models\FinancialYear;
use App\Models\Marquee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseAndPaymentVoucherAccountSyncTest extends TestCase
{
    use RefreshDatabase;

    protected $marquee;
    protected $branch;
    protected $ownerUser;
    protected $operatingExpenseType;
    protected $parentExpenseAccount;
    protected $cashAccount;
    protected $cashBankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = SubscriptionPlan::create([
            'name' => 'Premium',
            'slug' => 'premium',
            'price' => 15000,
            'billing_interval' => 'month',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'the ritz marquee',
            'address' => 'Main Boulevard',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'email' => 'ritz@test.com',
            'subscription_plan_id' => $plan->id,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Hall',
            'address' => 'Lahore',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '0421234567',
            'status' => 'active',
        ]);

        $ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner']);
        $perms = [
            'view_expenses', 'create_expenses', 'edit_expenses',
            'manage_accounting', 'manage_expense_settings'
        ];
        foreach ($perms as $pName) {
            $p = Permission::firstOrCreate(['name' => $pName], ['label' => ucfirst($pName)]);
            $ownerRole->permissions()->attach($p->id);
        }

        $this->ownerUser = User::create([
            'name' => 'Ritz Owner',
            'email' => 'owner@ritz.com',
            'password' => bcrypt('password'),
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'role_id' => $ownerRole->id,
        ]);

        $this->operatingExpenseType = AccountType::create([
            'name' => 'Operating Expenses',
            'code' => 'OPERATING_EXPENSES',
            'nature' => 'Expense',
        ]);

        $currentYear = date('Y');
        FinancialYear::create([
            'marquee_id' => $this->marquee->id,
            'name' => "FY {$currentYear}",
            'start_date' => "{$currentYear}-01-01",
            'end_date' => "{$currentYear}-12-31",
            'status' => 'active',
            'is_default' => true,
        ]);

        $this->parentExpenseAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '5000',
            'name' => 'Expenses',
            'account_type_id' => $this->operatingExpenseType->id,
            'nature' => 'Expense',
            'is_active' => true,
            'system_generated' => true,
        ]);

        // Disbursing cash drawer account
        $currentAssetType = AccountType::create([
            'name' => 'Current Assets',
            'code' => 'CURRENT_ASSETS',
            'nature' => 'Asset',
        ]);

        $this->cashAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1001',
            'name' => 'Cash in Hand',
            'account_type_id' => $currentAssetType->id,
            'nature' => 'Asset',
            'is_active' => true,
            'system_generated' => true,
        ]);

        $this->cashBankAccount = CashBankAccount::create([
            'marquee_id' => $this->marquee->id,
            'account_id' => $this->cashAccount->id,
            'type' => 'cash',
            'status' => 'active',
        ]);

        \App\Models\Currency::create([
            'marquee_id' => $this->marquee->id,
            'code' => 'PKR',
            'name' => 'Pakistani Rupee',
            'symbol' => 'Rs',
            'is_base' => true,
        ]);
    }

    public function test_adding_construction_account_5107_in_chart_of_accounts_creates_expense_category(): void
    {
        $this->actingAs($this->ownerUser);

        Livewire::test(ChartOfAccounts::class)
            ->set('account_code', '5107')
            ->set('name', 'Construction')
            ->set('parent_id', $this->parentExpenseAccount->id)
            ->set('account_type_id', $this->operatingExpenseType->id)
            ->set('description', 'Hall renovation and structural construction')
            ->call('save')
            ->assertHasNoErrors();

        $account = Account::where('marquee_id', $this->marquee->id)
            ->where('account_code', '5107')
            ->first();

        $this->assertNotNull($account);
        $this->assertEquals('Construction', $account->name);

        $category = ExpenseCategory::where('marquee_id', $this->marquee->id)
            ->where('default_account_id', $account->id)
            ->first();

        $this->assertNotNull($category, 'ExpenseCategory should have been automatically created for Construction (5107)');
        $this->assertEquals('Construction', $category->name);
        $this->assertEquals('5107', $category->category_code);
        $this->assertTrue((bool)$category->is_active);
    }

    public function test_construction_account_appears_in_expense_form_dropdown(): void
    {
        $this->actingAs($this->ownerUser);

        $account = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '5107',
            'name' => 'Construction',
            'parent_id' => $this->parentExpenseAccount->id,
            'account_type_id' => $this->operatingExpenseType->id,
            'nature' => 'Expense',
            'is_active' => true,
            'system_generated' => false,
        ]);

        $component = Livewire::test(ExpenseForm::class);

        $component->assertSee('[5107] Construction');

        $category = ExpenseCategory::where('default_account_id', $account->id)->first();
        $this->assertNotNull($category);

        $component->set('expense_date', date('Y-m-d'))
            ->set('branch_id', $this->branch->id)
            ->set('expense_category_id', $category->id)
            ->set('amount', 50000)
            ->set('payment_method', 'Cash')
            ->call('saveDraft')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('expenses', [
            'marquee_id' => $this->marquee->id,
            'expense_category_id' => $category->id,
            'amount' => 50000,
        ]);
    }

    public function test_construction_account_falls_under_operating_expenses_in_payment_voucher_form(): void
    {
        $this->actingAs($this->ownerUser);

        $account = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '5107',
            'name' => 'Construction',
            'parent_id' => $this->parentExpenseAccount->id,
            'account_type_id' => $this->operatingExpenseType->id,
            'nature' => 'Expense',
            'is_active' => true,
            'system_generated' => false,
        ]);

        $category = ExpenseCategory::where('default_account_id', $account->id)->first();
        $this->assertNotNull($category);

        $component = Livewire::test(PaymentVoucherForm::class)
            ->set('payee_type', 'expense');

        // Check Operating Expense categories dropdown displays [5107] Construction
        $component->assertSee('[5107] Construction');

        // Check Debit Account dropdown groups by Operating Expenses
        $component->assertSee('Operating Expenses');

        // Selecting category automatically sets debit_account_id to Construction
        $component->set('payee_id', "cat_{$category->id}");
        $this->assertEquals($account->id, $component->get('debit_account_id'));
        $this->assertEquals('Construction', $component->get('payee_name'));

        // Saving payment voucher
        $component->set('voucher_type', 'CPV')
            ->set('voucher_date', date('Y-m-d'))
            ->set('amount', 75000)
            ->set('cash_bank_account_id', $this->cashBankAccount->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('payment_vouchers', [
            'marquee_id' => $this->marquee->id,
            'payee_type' => 'expense',
            'debit_account_id' => $account->id,
            'amount' => 75000,
        ]);
    }
}
