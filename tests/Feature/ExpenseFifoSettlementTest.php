<?php

namespace Tests\Feature;

use App\Livewire\Finance\ExpenseList;
use App\Livewire\Inventory\SupplierLedgerView;
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
use App\Services\ExpenseService;
use App\Services\PaymentVoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseFifoSettlementTest extends TestCase
{
    use RefreshDatabase;

    protected $ownerRole;
    protected $marquee;
    protected $branch;
    protected $ownerUser;
    protected $category;
    protected $supplier;
    protected $cashBankAcc;
    protected $apAccount;
    protected $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner']);
        $pView = Permission::create(['name' => 'view_expenses', 'label' => 'View Expenses']);
        $pManageAcc = Permission::create(['name' => 'manage_accounting', 'label' => 'Manage Accounting']);
        $this->ownerRole->permissions()->attach([$pView->id, $pManageAcc->id]);

        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'price' => 15000,
            'billing_interval' => 'month',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Grand Palace Marquee',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'address' => 'Gulberg, Lahore',
            'phone' => '03001234567',
            'email' => 'contact@grandpalace.pk',
            'subscription_plan_id' => $plan->id,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Executive Hall',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'address' => 'Gulberg, Lahore',
            'phone' => '04235778899',
            'status' => 'active',
        ]);

        $this->ownerUser = User::create([
            'name' => 'Haji Iqbal',
            'email' => 'iqbal@grandpalace.pk',
            'password' => bcrypt('secret123'),
            'marquee_id' => $this->marquee->id,
            'role_id' => $this->ownerRole->id,
        ]);

        FinancialYear::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'FY 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'active',
            'is_default' => true,
        ]);

        $this->currency = Currency::create([
            'marquee_id' => $this->marquee->id,
            'code' => 'PKR',
            'name' => 'Pakistani Rupee',
            'symbol' => 'Rs',
            'exchange_rate' => 1.0,
            'is_default' => true,
        ]);

        $assetType = AccountType::create(['name' => 'Current Assets', 'code' => 'CURRENT_ASSETS', 'nature' => 'Asset']);
        $liabType = AccountType::create(['name' => 'Current Liabilities', 'code' => 'CURRENT_LIABILITIES', 'nature' => 'Liability']);
        $expType = AccountType::create(['name' => 'Operating Expenses', 'code' => 'OPERATING_EXPENSES', 'nature' => 'Expense']);

        $cashGL = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1001',
            'name' => 'Cash in Hand',
            'account_type_id' => $assetType->id,
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        $this->apAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '2001',
            'name' => 'Accounts Payable (Trade Creditors)',
            'account_type_id' => $liabType->id,
            'nature' => 'Liability',
            'is_active' => true,
        ]);

        $expAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '5001',
            'name' => 'Decorations & Stage Lightings',
            'account_type_id' => $expType->id,
            'nature' => 'Expense',
            'is_active' => true,
        ]);

        $this->cashBankAcc = CashBankAccount::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'account_id' => $cashGL->id,
            'gl_account_id' => $cashGL->id,
            'account_name' => 'Main Cash Drawer',
            'account_number' => 'CASH-001',
            'type' => 'cash',
            'opening_balance' => 500000.00,
            'current_balance' => 500000.00,
            'status' => 'active',
        ]);

        $this->category = ExpenseCategory::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Floral Decor & Lighting',
            'category_code' => 'FLORAL_01',
            'default_account_id' => $expAccount->id,
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Floral Art Lahore',
            'supplier_code' => 'SUP-FLORAL-01',
            'mobile_number' => '03219988776',
            'current_balance' => 0.00,
            'status' => 'active',
        ]);
    }

    public function test_fifo_settlement_clears_single_credit_expense()
    {
        $this->actingAs($this->ownerUser);

        // 1. Create Accounts Payable expense
        $expense = Expense::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'expense_number' => 'EXP-2026-0010',
            'expense_date' => '2026-10-10',
            'department' => 'Decoration',
            'expense_category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'description' => 'Aisle flowers',
            'amount' => 10000.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => 10000.00,
            'total_amount_base' => 10000.00,
            'paid_amount' => 0.00,
            'payment_method' => 'Accounts Payable',
            'payment_status' => 'Unpaid',
            'status' => Expense::STATUS_APPROVED,
        ]);

        // 2. Disburse Payment Voucher to this supplier
        $pvService = app(PaymentVoucherService::class);
        $voucher = $pvService->createPaymentVoucher([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_type' => 'CPV',
            'voucher_date' => '2026-10-12',
            'payee_type' => PaymentVoucher::PAYEE_SUPPLIER,
            'payee_id' => $this->supplier->id,
            'payee_name' => $this->supplier->name,
            'cash_bank_account_id' => $this->cashBankAcc->id,
            'debit_account_id' => $this->apAccount->id,
            'amount' => 10000.00,
            'payment_method' => 'Cash',
            'status' => PaymentVoucher::STATUS_APPROVED,
        ], $this->ownerUser->id);

        $pvService->disburseAndPost($voucher, $this->ownerUser->id);

        // 3. Verify expense is now Paid
        $expense->refresh();
        $this->assertEquals('Paid', $expense->payment_status);
        $this->assertEquals(10000.00, (float)$expense->paid_amount);
        $this->assertStringContainsString($voucher->voucher_no, $expense->reference_number);
    }

    public function test_fifo_settlement_allocates_chronologically_across_multiple_expenses()
    {
        $this->actingAs($this->ownerUser);

        // Expense 1 (Oldest: Oct 1, Rs. 3,000)
        $exp1 = Expense::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'expense_number' => 'EXP-2026-0001',
            'expense_date' => '2026-10-01',
            'department' => 'Decoration',
            'expense_category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 3000.00,
            'total_amount' => 3000.00,
            'paid_amount' => 0.00,
            'payment_method' => 'Accounts Payable',
            'payment_status' => 'Unpaid',
            'status' => Expense::STATUS_APPROVED,
        ]);

        // Expense 2 (Newer: Oct 05, Rs. 5,000)
        $exp2 = Expense::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'expense_number' => 'EXP-2026-0002',
            'expense_date' => '2026-10-05',
            'department' => 'Decoration',
            'expense_category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 5000.00,
            'total_amount' => 5000.00,
            'paid_amount' => 0.00,
            'payment_method' => 'Accounts Payable',
            'payment_status' => 'Unpaid',
            'status' => Expense::STATUS_APPROVED,
        ]);

        // Payment of Rs. 4,000 to the supplier
        $pvService = app(PaymentVoucherService::class);
        $voucher1 = $pvService->createPaymentVoucher([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_type' => 'CPV',
            'voucher_date' => '2026-10-10',
            'payee_type' => PaymentVoucher::PAYEE_SUPPLIER,
            'payee_id' => $this->supplier->id,
            'payee_name' => $this->supplier->name,
            'cash_bank_account_id' => $this->cashBankAcc->id,
            'debit_account_id' => $this->apAccount->id,
            'amount' => 4000.00,
            'payment_method' => 'Cash',
            'status' => PaymentVoucher::STATUS_APPROVED,
        ], $this->ownerUser->id);

        $pvService->disburseAndPost($voucher1, $this->ownerUser->id);

        $exp1->refresh();
        $exp2->refresh();

        // Exp 1 (Rs. 3000) must be 100% Paid
        $this->assertEquals('Paid', $exp1->payment_status);
        $this->assertEquals(3000.00, (float)$exp1->paid_amount);

        // Exp 2 (Rs. 5000) must be Partially Paid with Rs. 1000 settled
        $this->assertEquals('Partially Paid', $exp2->payment_status);
        $this->assertEquals(1000.00, (float)$exp2->paid_amount);

        // Second payment of Rs. 4,000 settles the remainder of Exp 2
        $voucher2 = $pvService->createPaymentVoucher([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_type' => 'CPV',
            'voucher_date' => '2026-10-15',
            'payee_type' => PaymentVoucher::PAYEE_SUPPLIER,
            'payee_id' => $this->supplier->id,
            'payee_name' => $this->supplier->name,
            'cash_bank_account_id' => $this->cashBankAcc->id,
            'debit_account_id' => $this->apAccount->id,
            'amount' => 4000.00,
            'payment_method' => 'Cash',
            'status' => PaymentVoucher::STATUS_APPROVED,
        ], $this->ownerUser->id);

        $pvService->disburseAndPost($voucher2, $this->ownerUser->id);

        $exp2->refresh();
        $this->assertEquals('Paid', $exp2->payment_status);
        $this->assertEquals(5000.00, (float)$exp2->paid_amount);
    }

    public function test_reconcile_cleared_supplier_in_expense_list()
    {
        $this->actingAs($this->ownerUser);

        // Supplier has current_balance = 0 (debt cleared)
        $this->supplier->update(['current_balance' => 0.00]);

        $exp = Expense::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'expense_number' => 'EXP-2026-0099',
            'expense_date' => '2026-10-01',
            'department' => 'Decoration',
            'expense_category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 2500.00,
            'total_amount' => 2500.00,
            'paid_amount' => 0.00,
            'payment_method' => 'Accounts Payable',
            'payment_status' => 'Unpaid',
            'status' => Expense::STATUS_POSTED,
        ]);

        // When rendering ExpenseList, it auto-reconciles cleared supplier's expenses
        Livewire::test(ExpenseList::class);

        $exp->refresh();
        $this->assertEquals('Paid', $exp->payment_status);
        $this->assertEquals(2500.00, (float)$exp->paid_amount);
    }

    public function test_manual_vendor_payment_in_supplier_directory_settles_credit_expenses_fifo()
    {
        $this->actingAs($this->ownerUser);

        // Create Accounts Payable credit expense
        $expense = Expense::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'expense_number' => 'EXP-2026-0888',
            'expense_date' => '2026-10-10',
            'department' => 'Decoration',
            'expense_category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 15000.00,
            'total_amount' => 15000.00,
            'paid_amount' => 0.00,
            'payment_method' => 'Accounts Payable',
            'payment_status' => 'Unpaid',
            'status' => Expense::STATUS_APPROVED,
        ]);

        // Record credit in supplier ledger
        app(\App\Services\InventoryService::class)->recordSupplierTransaction(
            $this->marquee->id,
            $this->supplier->id,
            '2026-10-10',
            0.00,
            15000.00,
            'Expense',
            $expense->id,
            'JV-001',
            'Credit expense payable'
        );

        $this->assertEquals(15000.00, (float)$this->supplier->fresh()->current_balance);

        // Record Manual Vendor Payment from Supplier Directory > Record Vendor Payment
        $component = Livewire::test(SupplierLedgerView::class, ['supplier' => $this->supplier]);
        $cashAcc = $component->instance()->cashAccounts->first();

        $component->set('showPaymentModal', true)
            ->set('payment_amount', 15000.00)
            ->set('payment_date', '2026-10-12')
            ->set('cash_bank_account_id', $cashAcc->id)
            ->set('reference_no', 'MANUAL-PAY-01')
            ->set('description', 'Manual Vendor Payment from: Cash')
            ->call('recordPayment')
            ->assertHasNoErrors()
            ->assertSee('Manual vendor payment recorded successfully.');

        // Expense must now transition to Paid status and Closed
        $expense->refresh();
        $this->assertEquals('Paid', $expense->payment_status);
        $this->assertEquals(15000.00, (float)$expense->paid_amount);
        $this->assertEquals(Expense::STATUS_CLOSED, $expense->status);

        // Supplier balance is cleared
        $this->assertEquals(0.00, (float)$this->supplier->fresh()->current_balance);
    }

    public function test_partial_vendor_payment_in_supplier_directory_settles_fifo_correctly()
    {
        $this->actingAs($this->ownerUser);

        // Expense 1: Rs. 10,000 (Older)
        $exp1 = Expense::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'expense_number' => 'EXP-2026-0001',
            'expense_date' => '2026-10-01',
            'department' => 'Decoration',
            'expense_category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 10000.00,
            'total_amount' => 10000.00,
            'paid_amount' => 0.00,
            'payment_method' => 'Accounts Payable',
            'payment_status' => 'Unpaid',
            'status' => Expense::STATUS_APPROVED,
        ]);

        // Expense 2: Rs. 20,000 (Newer)
        $exp2 = Expense::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'expense_number' => 'EXP-2026-0002',
            'expense_date' => '2026-10-05',
            'department' => 'Decoration',
            'expense_category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 20000.00,
            'total_amount' => 20000.00,
            'paid_amount' => 0.00,
            'payment_method' => 'Accounts Payable',
            'payment_status' => 'Unpaid',
            'status' => Expense::STATUS_APPROVED,
        ]);

        // Record total liability in supplier ledger (Rs. 30,000)
        app(\App\Services\InventoryService::class)->recordSupplierTransaction(
            $this->marquee->id,
            $this->supplier->id,
            '2026-10-05',
            0.00,
            30000.00,
            'Expense',
            $exp1->id,
            'JV-COMBINED',
            'Total credit expenses'
        );

        // Pay Rs. 15,000 via Supplier Directory Manual Payment
        $component = Livewire::test(SupplierLedgerView::class, ['supplier' => $this->supplier]);
        $cashAcc = $component->instance()->cashAccounts->first();

        $component->set('showPaymentModal', true)
            ->set('payment_amount', 15000.00)
            ->set('payment_date', '2026-10-12')
            ->set('cash_bank_account_id', $cashAcc->id)
            ->set('reference_no', 'PARTIAL-01')
            ->set('description', 'Manual Vendor Payment from: Cash')
            ->call('recordPayment')
            ->assertHasNoErrors();

        $exp1->refresh();
        $exp2->refresh();

        // Exp 1 (Rs. 10,000) is 100% Paid
        $this->assertEquals('Paid', $exp1->payment_status);
        $this->assertEquals(10000.00, (float)$exp1->paid_amount);
        $this->assertEquals(Expense::STATUS_CLOSED, $exp1->status);

        // Exp 2 (Rs. 20,000) is Partially Paid with Rs. 5,000 settled
        $this->assertEquals('Partially Paid', $exp2->payment_status);
        $this->assertEquals(5000.00, (float)$exp2->paid_amount);

        // Remaining supplier balance is Rs. 15,000
        $this->assertEquals(15000.00, (float)$this->supplier->fresh()->current_balance);
    }
}
