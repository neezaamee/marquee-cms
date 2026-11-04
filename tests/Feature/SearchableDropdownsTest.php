<?php

namespace Tests\Feature;

use App\Livewire\Finance\ExpenseForm;
use App\Livewire\Finance\PaymentVoucherForm;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Branch;
use App\Models\CashBankAccount;
use App\Models\Currency;
use App\Models\ExpenseCategory;
use App\Models\FinancialYear;
use App\Models\Marquee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SearchableDropdownsTest extends TestCase
{
    use RefreshDatabase;

    protected $marquee;
    protected $branch1;
    protected $branch2;
    protected $ownerUser;
    protected $operatingExpenseType;
    protected $currentAssetType;
    protected $currentLiabilityType;
    protected $cashAccount;
    protected $cashDrawer;
    protected $bankAccount;
    protected $bankCBA;
    protected $apAccount;
    protected $supplier;
    protected $vendor;
    protected $category;

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
            'name' => 'Royal Palace Marquee',
            'address' => 'Mall Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'email' => 'royal@test.com',
            'subscription_plan_id' => $plan->id,
        ]);

        $this->branch1 = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Executive Hall',
            'address' => 'Lahore',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '0421234567',
            'status' => 'active',
        ]);

        $this->branch2 = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Grand Ballroom',
            'address' => 'Lahore',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '0427654321',
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
            'name' => 'Royal Admin',
            'email' => 'admin@royal.com',
            'password' => bcrypt('password'),
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch1->id,
            'role_id' => $ownerRole->id,
        ]);

        $this->operatingExpenseType = AccountType::create([
            'name' => 'Operating Expenses',
            'code' => 'OPERATING_EXPENSES',
            'nature' => 'Expense',
        ]);

        $this->currentAssetType = AccountType::create([
            'name' => 'Current Assets',
            'code' => 'CURRENT_ASSETS',
            'nature' => 'Asset',
        ]);

        $this->currentLiabilityType = AccountType::create([
            'name' => 'Current Liabilities',
            'code' => 'CURRENT_LIABILITIES',
            'nature' => 'Liability',
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

        Currency::create([
            'marquee_id' => $this->marquee->id,
            'code' => 'PKR',
            'name' => 'Pakistani Rupee',
            'symbol' => 'Rs',
            'is_base' => true,
        ]);

        // Cash Drawer Account
        $this->cashAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1001',
            'name' => 'Main Cash Drawer',
            'account_type_id' => $this->currentAssetType->id,
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        $this->cashDrawer = CashBankAccount::create([
            'marquee_id' => $this->marquee->id,
            'account_id' => $this->cashAccount->id,
            'type' => 'cash',
            'status' => 'active',
        ]);

        // Bank Account
        $this->bankAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1002',
            'name' => 'HBL Main Account',
            'account_type_id' => $this->currentAssetType->id,
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        $this->bankCBA = CashBankAccount::create([
            'marquee_id' => $this->marquee->id,
            'account_id' => $this->bankAccount->id,
            'type' => 'bank',
            'bank_name' => 'Habib Bank Ltd',
            'account_number' => '1234567890',
            'status' => 'active',
        ]);

        // Accounts Payable 2001
        $this->apAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '2001',
            'name' => 'Accounts Payable',
            'account_type_id' => $this->currentLiabilityType->id,
            'nature' => 'Liability',
            'is_active' => true,
        ]);

        // Supplier
        $this->supplier = Supplier::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Gourmet Bakers',
            'supplier_code' => 'SUP-001',
            'mobile_number' => '03001234567',
            'opening_balance' => 45000,
            'status' => 'active',
        ]);

        // Vendor
        $this->vendor = Vendor::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'DJ Sound Pro',
            'phone' => '03007654321',
            'vendor_type' => 'Sound & Audio',
            'current_balance' => 15000,
            'status' => 'active',
        ]);

        // Expense Category
        $catAcc = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '5010',
            'name' => 'Stage & Theme Decoration',
            'account_type_id' => $this->operatingExpenseType->id,
            'nature' => 'Expense',
            'is_active' => true,
        ]);

        $this->category = ExpenseCategory::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Stage & Theme Decoration',
            'category_code' => 'CAT-001',
            'default_account_id' => $catAcc->id,
            'is_active' => true,
        ]);
    }

    public function test_expense_form_renders_searchable_dropdowns_and_reacts_to_payment_method(): void
    {
        $this->actingAs($this->ownerUser);

        $test = Livewire::test(ExpenseForm::class);

        $test->assertStatus(200);
        $test->assertSee('Record Expense Voucher');
        $test->assertSee('x-data="{', false);
        $test->assertSee('id="br_id"', false);
        $test->assertSee('id="pay_method"', false);

        // Test payment method reactive switching
        $test->set('payment_method', 'Bank');
        $test->assertSee('id="cb_acc"', false);

        $test->set('payment_method', 'Petty Cash');
        $test->assertSee('id="petty_acc"', false);

        // Test supplier selection
        $test->set('supplier_id', (string) $this->supplier->id);
        $test->assertSet('supplier_id', (string) $this->supplier->id);
    }

    public function test_payment_voucher_form_renders_searchable_dropdowns(): void
    {
        $this->actingAs($this->ownerUser);

        $test = Livewire::test(PaymentVoucherForm::class);

        $test->assertStatus(200);
        $test->assertSee('Create Payment Voucher');
        $test->assertSee('x-data="{', false);
        $test->assertSee('id="pv_branch_id"', false);
        $test->assertSee('id="pv_supplier_select"', false);
        $test->assertSee('id="pv_cash_bank_account"', false);
        $test->assertSee('id="pv_debit_account"', false);
        $test->assertSee('id="pv_payment_method"', false);
    }

    public function test_payment_voucher_form_supplier_selection_populates_payee_details(): void
    {
        $this->actingAs($this->ownerUser);

        $test = Livewire::test(PaymentVoucherForm::class)
            ->set('payee_type', 'supplier')
            ->set('payee_id', (string) $this->supplier->id);

        $test->assertSet('payee_name', 'Gourmet Bakers');
        $test->assertSet('amount', 45000);
        $test->assertSet('amount_in_words', 'Rupees Forty-Five Thousand Only');
    }

    public function test_payment_voucher_form_vendor_selection_populates_service_category(): void
    {
        $this->actingAs($this->ownerUser);

        $test = Livewire::test(PaymentVoucherForm::class)
            ->set('payee_type', 'vendor')
            ->set('payee_id', 'srv_decor');

        $test->assertSet('payee_name', 'Stage & Theme Decoration');
        $test->assertSet('description', 'Payment for Stage & Theme Decoration');
    }

    public function test_payment_voucher_form_expense_category_selection(): void
    {
        $this->actingAs($this->ownerUser);

        $test = Livewire::test(PaymentVoucherForm::class)
            ->set('payee_type', 'expense')
            ->set('payee_id', 'cat_' . $this->category->id);

        $test->assertSet('payee_name', 'Stage & Theme Decoration');
        $test->assertSet('debit_account_id', $this->category->default_account_id);
    }

    public function test_payment_voucher_form_switching_voucher_type(): void
    {
        $this->actingAs($this->ownerUser);

        $test = Livewire::test(PaymentVoucherForm::class);

        // Switch to BPV
        $test->set('voucher_type', 'BPV');
        $test->assertSet('payment_method', 'Cheque');
        $test->assertSee('Disbursing Bank Account');

        // Switch to CPV
        $test->set('voucher_type', 'CPV');
        $test->assertSet('payment_method', 'Cash');
        $test->assertSee('Disbursing Cash Drawer / Petty Cash');
    }
}
