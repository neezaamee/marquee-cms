<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseType;
use App\Models\ExpenseBudget;
use App\Models\ExpenseApprovalRule;
use App\Models\Marquee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\FinancialYear;
use App\Services\ExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected $plan;
    protected $ownerRole;
    protected $managerRole;
    protected $accountantRole;
    protected $marquee;
    protected $branch;
    protected $ownerUser;
    protected $managerUser;
    protected $accountantUser;
    protected $fy;
    protected $expenseType;
    protected $category;
    protected $currency;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Roles
        $this->ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner']);
        $this->managerRole = Role::create(['name' => 'branch_manager', 'label' => 'Manager']);
        $this->accountantRole = Role::create(['name' => 'accountant', 'label' => 'Accountant']);

        // Permissions
        $pView = Permission::create(['name' => 'view_expenses', 'label' => 'View Expenses']);
        $pCreate = Permission::create(['name' => 'create_expenses', 'label' => 'Create Expenses']);
        $pEdit = Permission::create(['name' => 'edit_expenses', 'label' => 'Edit Expenses']);
        $pApprove = Permission::create(['name' => 'approve_expenses', 'label' => 'Approve Expenses']);
        $pSettings = Permission::create(['name' => 'manage_expense_settings', 'label' => 'Manage Expense Settings']);

        $allPerms = [$pView->id, $pCreate->id, $pEdit->id, $pApprove->id, $pSettings->id];
        $this->ownerRole->permissions()->attach($allPerms);
        $this->managerRole->permissions()->attach([$pView->id, $pCreate->id, $pApprove->id]);
        $this->accountantRole->permissions()->attach([$pView->id, $pApprove->id]);

        // 2. Setup Plan & Tenant
        $this->plan = SubscriptionPlan::create([
            'name' => 'Standard',
            'slug' => 'standard',
            'price' => 10000,
            'billing_interval' => 'month',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Test Royal Marquee',
            'address' => 'Lhr', 'city' => 'Lhr', 'province' => 'Pb', 'phone' => '0300', 'email' => 'royal@test.com',
            'subscription_plan_id' => $this->plan->id
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Lahore Branch',
            'address' => 'Lhr', 'city' => 'Lhr', 'province' => 'Pb', 'phone' => '042', 'status' => 'active'
        ]);

        // Users
        $this->ownerUser = User::create([
            'name' => 'Mian Akbar (Owner)',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'marquee_id' => $this->marquee->id,
            'role_id' => $this->ownerRole->id
        ]);

        $this->managerUser = User::create([
            'name' => 'Manager Asif',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'role_id' => $this->managerRole->id
        ]);

        $this->accountantUser = User::create([
            'name' => 'Accountant Cashier',
            'email' => 'accountant@test.com',
            'password' => bcrypt('password'),
            'marquee_id' => $this->marquee->id,
            'role_id' => $this->accountantRole->id
        ]);

        // 3. Setup Financial Year
        $this->fy = FinancialYear::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'FY 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'active',
            'is_default' => true,
        ]);

        // 4. Setup Chart of Accounts (COA) Mappings
        $accType = AccountType::create(['name' => 'Operating Expenses', 'code' => 'OPERATING_EXPENSES', 'nature' => 'Expense']);
        $cashType = AccountType::create(['name' => 'Current Assets', 'code' => 'CURRENT_ASSETS', 'nature' => 'Asset']);

        $rootAsset = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1000',
            'name' => 'Assets',
            'account_type_id' => $cashType->id,
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        $this->cashAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1001',
            'parent_id' => $rootAsset->id,
            'name' => 'Cash in Hand',
            'account_type_id' => $cashType->id,
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        $rootExpense = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '5000',
            'name' => 'Expenses',
            'account_type_id' => $accType->id,
            'nature' => 'Expense',
            'is_active' => true,
        ]);

        $this->expenseAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '5501',
            'parent_id' => $rootExpense->id,
            'name' => 'Operational Expenses',
            'account_type_id' => $accType->id,
            'nature' => 'Expense',
            'is_active' => true,
        ]);

        // 5. Setup currency
        $this->currency = Currency::create([
            'marquee_id' => $this->marquee->id,
            'code' => 'PKR',
            'name' => 'Pakistani Rupee',
            'symbol' => 'Rs.',
            'is_base' => true,
            'exchange_rate' => 1.000000,
        ]);

        // 6. Setup type and category
        $this->expenseType = ExpenseType::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'General Miscellaneous',
            'code' => 'miscellaneous',
        ]);

        $this->category = ExpenseCategory::create([
            'marquee_id' => $this->marquee->id,
            'category_code' => 'MISC',
            'name' => 'Office Overheads',
            'default_account_id' => $this->expenseAccount->id,
            'default_tax_rate' => 0.00,
        ]);
    }

    public function test_expense_crud_access_restricted_by_tenant()
    {
        // Logged in as Owner A
        $this->actingAs($this->ownerUser);

        // Create an expense
        $expense = Expense::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'expense_number' => 'EXP-999',
            'expense_date' => '2026-08-05',
            'expense_category_id' => $this->category->id,
            'expense_type_id' => $this->expenseType->id,
            'currency_id' => $this->currency->id,
            'amount' => 500.00,
            'total_amount' => 500.00,
            'total_amount_base' => 500.00,
            'payment_method' => Expense::METHOD_CASH,
            'status' => Expense::STATUS_DRAFT,
        ]);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'marquee_id' => $this->marquee->id,
        ]);

        // Log in as user from another tenant (Super admin with bypass is tested elsewhere, let's test a distinct tenant)
        $marqueeB = Marquee::create([
            'name' => 'Marquee B', 'address' => 'Khi', 'city' => 'Khi', 'province' => 'Sd', 'phone' => '1', 'email' => 'b@test.com',
            'subscription_plan_id' => $this->plan->id
        ]);
        $ownerB = User::create([
            'name' => 'Owner B', 'email' => 'owner.b@test.com', 'password' => bcrypt('password'),
            'marquee_id' => $marqueeB->id, 'role_id' => $this->ownerRole->id
        ]);

        $this->actingAs($ownerB);
        
        // Retrieve expenses: Owner B should NOT see Owner A's expense
        $visibleExpenses = Expense::all();
        $this->assertCount(0, $visibleExpenses);
    }

    public function test_expense_sequential_workflow_approval_posts_jv()
    {
        // 1. Setup multi-level approval rules
        // Seq 1: Manager approves
        ExpenseApprovalRule::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'min_amount' => 0.00,
            'approver_role_id' => $this->managerRole->id,
            'sequence' => 1,
        ]);
        // Seq 2: Owner approves
        ExpenseApprovalRule::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'min_amount' => 10000.00,
            'approver_role_id' => $this->ownerRole->id,
            'sequence' => 2,
        ]);

        // Create large expense (needs both approvals)
        $expense = Expense::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'expense_number' => 'EXP-WORKFLOW',
            'expense_date' => '2026-08-05',
            'expense_category_id' => $this->category->id,
            'expense_type_id' => $this->expenseType->id,
            'currency_id' => $this->currency->id,
            'amount' => 12000.00,
            'total_amount' => 12000.00,
            'total_amount_base' => 12000.00,
            'payment_method' => Expense::METHOD_CASH,
            'status' => Expense::STATUS_DRAFT,
        ]);

        $service = app(ExpenseService::class);

        // Submit expense
        $service->submitExpense($expense->id);
        $expense->refresh();
        $this->assertEquals(Expense::STATUS_PENDING, $expense->status);

        // Approve Seq 1 as Manager
        $service->approveExpense($expense->id, $this->managerUser->id, 'Manager looks good');
        $expense->refresh();
        // Still pending because owner needs to approve (Seq 2)
        $this->assertEquals(Expense::STATUS_PENDING, $expense->status);

        // Approve Seq 2 as Owner
        $service->approveExpense($expense->id, $this->ownerUser->id, 'Approved by Owner');
        $expense->refresh();

        // Should be Approved, and since Cash method, automatically transitions to Posted & Paid!
        $this->assertEquals(Expense::STATUS_POSTED, $expense->status);
        $this->assertEquals('Paid', $expense->payment_status);

        // Verify Journal Voucher exists in ledger
        $this->assertNotNull($expense->journal_voucher_id);
        $this->assertDatabaseHas('journal_vouchers', [
            'id' => $expense->journal_voucher_id,
            'reference' => 'EXP-WORKFLOW',
        ]);

        // Verify debit/credit balance
        $items = DB::table('journal_voucher_items')->where('journal_voucher_id', $expense->journal_voucher_id)->get();
        $this->assertCount(2, $items);
        $this->assertEquals(12000.00, $items->where('account_id', $this->expenseAccount->id)->first()->debit);
        $this->assertEquals(12000.00, $items->where('account_id', $this->cashAccount->id)->first()->credit);
    }

    public function test_budget_exceeded_dispatches_warnings()
    {
        Notification::fake();

        // Create budget allocation limit of 5,000 PKR
        ExpenseBudget::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'category_id' => $this->category->id,
            'year' => 2026,
            'allocated_amount' => 5000.00,
            'consumed_amount' => 0.00,
        ]);

        // Create expense exceeding budget (6,000 PKR)
        $expense = Expense::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'expense_number' => 'EXP-OVERBUDGET',
            'expense_date' => '2026-08-05',
            'expense_category_id' => $this->category->id,
            'expense_type_id' => $this->expenseType->id,
            'currency_id' => $this->currency->id,
            'amount' => 6000.00,
            'total_amount' => 6000.00,
            'total_amount_base' => 6000.00,
            'payment_method' => Expense::METHOD_CASH,
            'status' => Expense::STATUS_DRAFT,
        ]);

        $service = app(ExpenseService::class);
        $service->submitExpense($expense->id);

        // Verification notifications triggered
        Notification::assertSentTo(
            [$this->ownerUser],
            \App\Notifications\ExpenseNotification::class
        );
    }
}
