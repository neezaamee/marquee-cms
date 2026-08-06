<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Branch;
use App\Models\FinancialYear;
use App\Models\InventoryCategory;
use App\Models\InventoryUnit;
use App\Models\InventoryBrand;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\PurchaseOrder;
use App\Models\GoodsReceivingNote;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\PurchaseService;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $marquee;
    protected $branch;
    protected $inventoryService;
    protected $purchaseService;
    protected $accountingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventoryService = new InventoryService();
        $this->accountingService = new AccountingService();
        $this->purchaseService = new PurchaseService($this->accountingService, $this->inventoryService);

        // Seed roles & permissions and subscription plans
        $this->artisan('db:seed', ['--class' => 'SubscriptionPlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $plan = SubscriptionPlan::first();

        // Create tenant Marquee
        $this->marquee = Marquee::create([
            'name' => 'Test Inventory Marquee',
            'email' => 'inventory@marquee.com',
            'phone' => '12345678',
            'address' => 'Test Address',
            'city' => 'Test City',
            'province' => 'Test Province',
            'ntn' => '123456',
            'status' => 'active',
            'subscription_plan_id' => $plan->id,
        ]);

        $ownerRole = Role::where('name', 'owner')->first();

        // Create Owner User
        $this->user = User::create([
            'name' => 'Owner User',
            'email' => 'owner@test.com',
            'username' => 'owner',
            'password' => bcrypt('Password123!'),
            'marquee_id' => $this->marquee->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        // Create Branch
        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Test Branch',
            'address' => 'Test Address',
            'city' => 'Test City',
            'province' => 'Test Province',
            'phone' => '123456',
            'status' => 'active',
        ]);

        // Run default accounts seeder
        $this->artisan('db:seed', ['--class' => 'AccountingModuleSeeder']);

        // Create active financial year
        FinancialYear::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'is_default' => true,
        ]);
    }

    /** @test */
    public function test_it_creates_inventory_masters_and_auto_generates_codes()
    {
        $this->actingAs($this->user);

        // 1. Create unit
        $unit = InventoryUnit::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Kilogram',
            'short_code' => 'Kg',
            'status' => 'Active',
        ]);

        // 2. Create category
        $category = InventoryCategory::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Kitchen Ingredients',
            'status' => 'Active',
        ]);

        // 3. Create brand
        $brand = InventoryBrand::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'National Foods',
            'status' => 'Active',
        ]);

        // 4. Generate item code
        $code = $this->inventoryService->generateNextItemCode($this->marquee->id);
        $this->assertEquals('ITEM-00001', $code);

        // 5. Create item
        $item = InventoryItem::create([
            'marquee_id' => $this->marquee->id,
            'item_code' => $code,
            'name' => 'Basmati Rice 50kg',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'brand_id' => $brand->id,
            'default_purchase_rate' => 7500.00,
            'minimum_stock_level' => 5,
            'status' => 'Active',
        ]);

        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'item_code' => 'ITEM-00001',
            'name' => 'Basmati Rice 50kg',
        ]);
    }

    /** @test */
    public function test_it_creates_suppliers_and_handles_ledgers()
    {
        $this->actingAs($this->user);

        $code = $this->inventoryService->generateNextSupplierCode($this->marquee->id);
        $this->assertEquals('SUP-00001', $code);

        $supplier = Supplier::create([
            'marquee_id' => $this->marquee->id,
            'supplier_code' => $code,
            'name' => 'Rice Distributors Ltd',
            'mobile_number' => '03001234567',
            'opening_balance' => 15000.00,
            'current_balance' => 15000.00,
            'status' => 'Active',
        ]);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'supplier_code' => 'SUP-00001',
            'opening_balance' => 15000.00,
        ]);

        // Record a payment transaction (Debit decreases payables)
        $ledger = $this->inventoryService->recordSupplierTransaction(
            $this->marquee->id,
            $supplier->id,
            '2026-06-01',
            5000.00, // Debit
            0.00,    // Credit
            'Payment',
            null,
            'JV-0001',
            'Paid cash deposit'
        );

        $this->assertEquals(10000.00, $ledger->running_balance);

        // Fetch supplier outstanding balance
        $lastLedger = SupplierLedger::where('supplier_id', $supplier->id)->latest()->first();
        $this->assertEquals(10000.00, $lastLedger->running_balance);
    }

    /** @test */
    public function test_purchase_order_flow_grn_and_automatic_jv_postings()
    {
        $this->actingAs($this->user);

        // 1. Initial Setup
        $unit = InventoryUnit::create(['marquee_id' => $this->marquee->id, 'name' => 'Pcs', 'short_code' => 'Pcs']);
        $category = InventoryCategory::create(['marquee_id' => $this->marquee->id, 'name' => 'Furniture']);
        $item = InventoryItem::create([
            'marquee_id' => $this->marquee->id,
            'item_code' => 'ITEM-00001',
            'name' => 'Banquet Chair',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'default_purchase_rate' => 2000.00,
        ]);

        $supplier = Supplier::create([
            'marquee_id' => $this->marquee->id,
            'supplier_code' => 'SUP-00001',
            'name' => 'Furniture Supplier',
            'mobile_number' => '03001234567',
            'opening_balance' => 0.00,
            'current_balance' => 0.00,
        ]);

        // 2. Create and Approve Purchase Order
        $po = PurchaseOrder::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-2026-00001',
            'order_date' => '2026-06-20',
            'status' => 'Draft',
        ]);

        $po->details()->create([
            'item_id' => $item->id,
            'quantity' => 10,
            'unit_price' => 2000.00,
            'amount' => 20000.00,
        ]);

        $po = $this->purchaseService->approvePurchaseOrder($po->id);
        $this->assertEquals('Approved', $po->status);

        // 3. Record Goods Receipt Note (GRN) for 8 of 10 items (Partial receipt)
        $grnData = [
            'received_date' => '2026-06-21',
            'notes' => 'Received 8 chairs',
        ];
        $receivedItems = [
            [
                'item_id' => $item->id,
                'received_qty' => 8,
            ]
        ];

        $grn = $this->purchaseService->recordGoodsReceipt($po->id, $grnData, $receivedItems);
        
        $po->refresh();
        $this->assertEquals('Partially Received', $po->status);
        $this->assertEquals(8, $po->details->first()->received_quantity);

        // 4. Create and Post Purchase Invoice for 8 received items (Net = 16000)
        $invoice = PurchaseInvoice::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PINV-2026-00001',
            'purchase_date' => '2026-06-22',
            'status' => 'Draft',
            'gross_amount' => 16000.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'net_amount' => 16000.00,
        ]);

        $invoice->details()->create([
            'item_id' => $item->id,
            'quantity' => 8,
            'unit_cost' => 2000.00,
            'amount' => 16000.00,
        ]);

        // Post the invoice
        $invoice = $this->purchaseService->postPurchaseInvoice($invoice->id);
        $this->assertEquals('Posted', $invoice->status);
        $this->assertNotNull($invoice->journal_voucher_id);

        // Check that supplier ledger was credited (increases payable outstanding)
        $supplier->refresh();
        // Since opening balance was 0 and credited 16000, current balance should be 16000
        $this->assertEquals(16000.00, $supplier->current_balance);

        // Verify Journal Voucher posting (Debit 1004 Inventory Asset, Credit 2001 Accounts Payable)
        $voucher = $invoice->journalVoucher;
        $this->assertNotNull($voucher);
        $this->assertEquals('posted', $voucher->status);
        $this->assertCount(2, $voucher->items);

        $debitItem = $voucher->items->firstWhere('debit', '>', 0);
        $creditItem = $voucher->items->firstWhere('credit', '>', 0);

        $inventoryAsset = Account::where('marquee_id', $this->marquee->id)->where('account_code', '1004')->first();
        $accountsPayable = Account::where('marquee_id', $this->marquee->id)->where('account_code', '2001')->first();

        $this->assertEquals($inventoryAsset->id, $debitItem->account_id);
        $this->assertEquals(16000.00, $debitItem->debit);

        $this->assertEquals($accountsPayable->id, $creditItem->account_id);
        $this->assertEquals(16000.00, $creditItem->credit);

        // 5. Post a Purchase Return for 2 items (Net = 4000)
        $return = PurchaseReturn::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'supplier_id' => $supplier->id,
            'purchase_invoice_id' => $invoice->id,
            'return_number' => 'PRET-2026-00001',
            'return_date' => '2026-06-23',
            'status' => 'Draft',
            'gross_amount' => 4000.00,
            'tax' => 0.00,
            'net_amount' => 4000.00,
        ]);

        $return->details()->create([
            'item_id' => $item->id,
            'quantity' => 2,
            'unit_cost' => 2000.00,
            'amount' => 4000.00,
        ]);

        // Post the return
        $return = $this->purchaseService->postPurchaseReturn($return->id);
        $this->assertEquals('Posted', $return->status);
        $this->assertNotNull($return->journal_voucher_id);

        // Check that supplier ledger was debited (decreases payable outstanding)
        $supplier->refresh();
        // 16000 - 4000 = 12000
        $this->assertEquals(12000.00, $supplier->current_balance);

        // Verify Journal Voucher posting (Debit 2001 Accounts Payable, Credit 1004 Inventory Asset)
        $retVoucher = $return->journalVoucher;
        $this->assertNotNull($retVoucher);
        $this->assertEquals('posted', $retVoucher->status);
        $this->assertCount(2, $retVoucher->items);

        $retDebitItem = $retVoucher->items->firstWhere('debit', '>', 0);
        $retCreditItem = $retVoucher->items->firstWhere('credit', '>', 0);

        $this->assertEquals($accountsPayable->id, $retDebitItem->account_id);
        $this->assertEquals(4000.00, $retDebitItem->debit);

        $this->assertEquals($inventoryAsset->id, $retCreditItem->account_id);
        $this->assertEquals(4000.00, $retCreditItem->credit);
    }

    /** @test */
    public function test_super_admin_generate_supplier_code_null_safety()
    {
        $code = $this->inventoryService->generateNextSupplierCode(null);
        $this->assertEquals('SUP-00001', $code);

        $itemCode = $this->inventoryService->generateNextItemCode(null);
        $this->assertEquals('ITEM-00001', $itemCode);
    }
}
