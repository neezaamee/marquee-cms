<?php

namespace Tests\Feature;

use App\Livewire\Inventory\SupplierLedgerView;
use App\Livewire\Purchases\PurchaseInvoiceList;
use App\Models\Account;
use App\Models\AccountOpeningBalance;
use App\Models\Branch;
use App\Models\CashBankAccount;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryUnit;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherItem;
use App\Models\Marquee;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceDetail;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierDirectoryLedgerPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Marquee $marquee;
    protected Branch $branch;
    protected Supplier $supplier;
    protected Account $cashAccount;
    protected Account $bankAccount;
    protected Account $apAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::create(['name' => 'owner', 'label' => 'Marquee Owner']);

        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-plan',
            'price' => 1000,
            'billing_interval' => 'monthly',
            'max_branches' => 5,
            'status' => 'active',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Royal Pearl Marquee',
            'slug' => 'royal-pearl-marquee',
            'status' => 'active',
            'address' => '12 Main Gulberg',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'email' => 'contact@royalpearl.test',
            'subscription_plan_id' => $plan->id,
            'is_setup_completed' => true,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Gulberg Branch',
            'code' => 'GB-01',
            'address' => '12 Main Gulberg',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'status' => 'active',
        ]);

        $this->owner = User::create([
            'name' => 'Sheikh Owner',
            'email' => 'owner@royalpearl.test',
            'password' => bcrypt('password123'),
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
            'is_owner' => true,
        ]);

        // Create Chart of Accounts: Cash (1001), Bank (1002), Accounts Payable (2001)
        $this->cashAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1001',
            'name' => 'Main Cash Drawer',
            'account_type_id' => 3,
            'nature' => 'Asset',
            'is_active' => true,
            'system_generated' => true,
        ]);

        $this->bankAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1002',
            'name' => 'Meezan Bank Current Account',
            'account_type_id' => 3,
            'nature' => 'Asset',
            'is_active' => true,
            'system_generated' => true,
        ]);

        $this->apAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '2001',
            'name' => 'Accounts Payable',
            'account_type_id' => 1,
            'nature' => 'Liability',
            'is_active' => true,
            'system_generated' => true,
        ]);

        $fy = \App\Models\FinancialYear::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'FY 2026-2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'active',
            'is_default' => true,
        ]);

        // Add opening balance to Cash: Rs. 50,000 Debit
        AccountOpeningBalance::create([
            'marquee_id' => $this->marquee->id,
            'financial_year_id' => $fy->id,
            'account_id' => $this->cashAccount->id,
            'debit' => 50000.00,
            'credit' => 0.00,
        ]);

        // Add posted journal voucher movement to Bank: Rs. 120,000 Debit
        $jv = JournalVoucher::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_no' => 'JV-TEST-001',
            'voucher_date' => now()->toDateString(),
            'status' => 'posted',
            'total_debit' => 120000.00,
            'total_credit' => 120000.00,
            'created_by' => $this->owner->id,
        ]);

        JournalVoucherItem::create([
            'journal_voucher_id' => $jv->id,
            'account_id' => $this->bankAccount->id,
            'debit' => 120000.00,
            'credit' => 0.00,
            'narration' => 'Initial bank deposit',
        ]);

        // Create Supplier with opening balance
        $this->supplier = Supplier::create([
            'marquee_id' => $this->marquee->id,
            'supplier_code' => 'SUP-001',
            'name' => 'Lahore Rice Traders',
            'mobile_number' => '03009988776',
            'opening_balance' => 45000.00,
            'status' => 'active',
        ]);
    }

    public function test_supplier_ledger_view_loads_cash_and_bank_accounts_with_accurate_gl_balances(): void
    {
        $this->actingAs($this->owner);

        // When viewing SupplierLedgerView, cash and bank accounts are automatically discovered and have balances
        $component = Livewire::test(SupplierLedgerView::class, ['supplier' => $this->supplier]);

        $cashAccounts = $component->instance()->cashAccounts;
        $this->assertNotEmpty($cashAccounts);
        $this->assertCount(2, $cashAccounts);

        $cash = $cashAccounts->firstWhere('account_id', $this->cashAccount->id);
        $this->assertNotNull($cash);
        $this->assertEquals('Main Cash Drawer', $cash->account_name);
        $this->assertEquals(50000.00, (float) $cash->current_balance);

        $bank = $cashAccounts->firstWhere('account_id', $this->bankAccount->id);
        $this->assertNotNull($bank);
        $this->assertEquals('Meezan Bank Current Account', $bank->account_name);
        $this->assertEquals(120000.00, (float) $bank->current_balance);

        // Assert rendered HTML contains account names and formatted balances when payment modal is opened
        $component->set('showPaymentModal', true)
            ->assertSee('Main Cash Drawer')
            ->assertSee('Bal: Rs. 50,000.00')
            ->assertSee('Meezan Bank Current Account')
            ->assertSee('Bal: Rs. 120,000.00');
    }

    public function test_can_record_supplier_payment_successfully(): void
    {
        $this->actingAs($this->owner);

        $component = Livewire::test(SupplierLedgerView::class, ['supplier' => $this->supplier]);
        $cashAccounts = $component->instance()->cashAccounts;
        $cash = $cashAccounts->firstWhere('account_id', $this->cashAccount->id);

        $component->set('showPaymentModal', true)
            ->set('payment_amount', 15000.00)
            ->set('payment_date', now()->toDateString())
            ->set('cash_bank_account_id', $cash->id)
            ->set('reference_no', 'CHQ-9912')
            ->set('description', 'Part payment for basmati rice invoice')
            ->call('recordPayment')
            ->assertHasNoErrors()
            ->assertSee('Manual vendor payment recorded successfully.');

        // Supplier balance decreased by 15,000 (from 45,000 to 30,000)
        $this->supplier->refresh();
        $this->assertEquals(30000.00, (float) $this->supplier->current_balance);

        // Check supplier ledger transaction
        $this->assertDatabaseHas('supplier_ledgers', [
            'supplier_id' => $this->supplier->id,
            'reference_type' => 'VendorPayment',
            'debit' => 15000.00,
            'credit' => 0.00,
        ]);

        // Check double-entry journal voucher created
        $this->assertDatabaseHas('journal_vouchers', [
            'marquee_id' => $this->marquee->id,
            'status' => 'posted',
        ]);
    }

    public function test_supplier_ledger_shows_supplier_invoice_number_column(): void
    {
        $this->actingAs($this->owner);

        $invoice = PurchaseInvoice::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'PINV-202610-001',
            'reference_number' => 'SUPP-INV-8877',
            'purchase_date' => now()->toDateString(),
            'gross_amount' => 10000.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'net_amount' => 10000.00,
            'status' => 'Posted',
        ]);

        SupplierLedger::create([
            'marquee_id' => $this->marquee->id,
            'supplier_id' => $this->supplier->id,
            'transaction_date' => now()->toDateString(),
            'reference_type' => 'PurchaseInvoice',
            'reference_id' => $invoice->id,
            'voucher_no' => 'JV-PINV-001',
            'supplier_invoice_no' => 'SUPP-INV-8877',
            'debit' => 0.00,
            'credit' => 10000.00,
            'running_balance' => 55000.00,
            'description' => 'Test invoice billing',
        ]);

        Livewire::test(SupplierLedgerView::class, ['supplier' => $this->supplier])
            ->assertSee('Supplier Invoice #')
            ->assertSee('SUPP-INV-8877');
    }

    public function test_purchase_invoice_list_has_increased_visibility_and_post_to_ledger(): void
    {
        $this->actingAs($this->owner);

        // Ensure inventory settings has inventory asset account
        $assetAcc = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '1005',
            'name' => 'Inventory Asset',
            'account_type_id' => 3,
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        \App\Models\InventorySetting::updateOrCreate(
            ['marquee_id' => $this->marquee->id],
            [
                'inventory_asset_account_id' => $assetAcc->id,
                'accounts_payable_account_id' => $this->apAccount->id,
            ]
        );

        $unit = InventoryUnit::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Kilogram',
            'short_code' => 'kg',
            'status' => 'Active',
        ]);

        $category = InventoryCategory::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Grains',
            'status' => 'Active',
        ]);

        $item = InventoryItem::create([
            'marquee_id' => $this->marquee->id,
            'item_code' => 'ITEM-01',
            'name' => 'Basmati Rice',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'default_purchase_rate' => 250.00,
            'status' => 'Active',
        ]);

        $invoice = PurchaseInvoice::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'PINV-202610-999',
            'reference_number' => 'VEND-REF-4422',
            'purchase_date' => now()->toDateString(),
            'gross_amount' => 5000.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'net_amount' => 5000.00,
            'status' => 'Draft',
        ]);

        PurchaseInvoiceDetail::create([
            'purchase_invoice_id' => $invoice->id,
            'item_id' => $item->id,
            'quantity' => 20,
            'unit_cost' => 250.00,
            'amount' => 5000.00,
        ]);

        Livewire::test(PurchaseInvoiceList::class)
            ->assertSee('PINV-202610-999')
            ->assertSee('VEND-REF-4422')
            ->assertSeeHtml('data-bs-target="#postLedgerModal"')
            ->call('confirmPost', $invoice->id)
            ->assertSet('confirmingPostId', $invoice->id)
            ->assertSee('Confirm Post to Ledger')
            ->assertSee('PINV-202610-999')
            ->call('postToLedger')
            ->assertHasNoErrors()
            ->assertSee('has been posted to ledger');

        $invoice->refresh();
        $this->assertEquals('Posted', $invoice->status);

        $this->assertDatabaseHas('supplier_ledgers', [
            'supplier_id' => $this->supplier->id,
            'reference_type' => 'PurchaseInvoice',
            'reference_id' => $invoice->id,
            'supplier_invoice_no' => 'VEND-REF-4422',
        ]);
    }
}
