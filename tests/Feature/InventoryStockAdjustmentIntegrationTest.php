<?php

namespace Tests\Feature;

use App\Livewire\Inventory\StockTakeManager;
use App\Livewire\Inventory\StockView;
use App\Models\Branch;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryStockLedger;
use App\Models\InventoryStockTake;
use App\Models\InventoryStockTakeItem;
use App\Models\InventoryUnit;
use App\Models\Marquee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\DepartmentStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryStockAdjustmentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $marquee;
    protected $branch1;
    protected $branch2;
    protected $user;
    protected $ownerUser;
    protected $category;
    protected $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = SubscriptionPlan::create([
            'name' => 'Pro Enterprise',
            'slug' => 'pro-enterprise',
            'price' => 10000,
            'billing_cycle' => 'monthly',
            'branch_limit' => 5,
            'is_active' => true,
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Grand Crystal Marquee',
            'email' => 'crystal@marquee.com',
            'phone' => '03001234567',
            'address' => 'Main Boulevard',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'ntn' => '1234567',
            'status' => 'active',
            'subscription_plan_id' => $plan->id,
        ]);

        $this->branch1 = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Crystal Branch Lahore',
            'address' => 'Gulberg',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001112233',
            'status' => 'active',
        ]);

        $this->branch2 = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Crystal Branch Islamabad',
            'address' => 'F-7',
            'city' => 'Islamabad',
            'province' => 'Federal',
            'phone' => '03004445566',
            'status' => 'active',
        ]);

        $ownerRole = Role::firstOrCreate(['name' => 'business_owner'], ['label' => 'Business Owner']);
        $managerRole = Role::firstOrCreate(['name' => 'inventory_manager'], ['label' => 'Inventory Manager']);
        $adjustPerm = Permission::firstOrCreate(['name' => 'inventory.adjust'], ['label' => 'Adjust Inventory']);
        $managePerm = Permission::firstOrCreate(['name' => 'inventory.manage'], ['label' => 'Manage Inventory']);
        $ownerRole->permissions()->syncWithoutDetaching([$adjustPerm->id, $managePerm->id]);
        $managerRole->permissions()->syncWithoutDetaching([$adjustPerm->id, $managePerm->id]);

        // Branch manager user (branch assigned)
        $this->user = User::create([
            'name' => 'Store Manager Branch 1',
            'email' => 'manager1@crystal.com',
            'username' => 'manager1',
            'password' => bcrypt('Secret123!'),
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch1->id,
            'role_id' => $managerRole->id,
            'status' => 'active',
        ]);

        // Business owner user (no specific branch assigned)
        $this->ownerUser = User::create([
            'name' => 'Crystal Owner',
            'email' => 'owner@crystal.com',
            'username' => 'owner_crystal',
            'password' => bcrypt('Secret123!'),
            'marquee_id' => $this->marquee->id,
            'branch_id' => null,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        $this->category = InventoryCategory::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Dry Provisions',
            'status' => 'Active',
        ]);

        $this->unit = InventoryUnit::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Kilogram',
            'short_code' => 'Kg',
            'status' => 'Active',
        ]);
    }

    protected function createItem(array $overrides = []): InventoryItem
    {
        return InventoryItem::create(array_merge([
            'marquee_id' => $this->marquee->id,
            'item_code' => 'ITEM-' . uniqid(),
            'name' => 'Basmati Rice Special',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'minimum_stock_level' => 50,
            'reorder_level' => 100,
            'default_purchase_rate' => 0.00,
            'average_cost' => 0.00,
            'last_purchase_cost' => 0.00,
            'status' => 'Active',
        ], $overrides));
    }

    /** @test */
    public function test_opening_stock_entered_via_adjustment_is_reflected_in_stock_view()
    {
        $this->actingAs($this->user);
        $item = $this->createItem();

        // 1. Initial Stock View should show 0 opening and 0 current stock
        Livewire::test(StockView::class)
            ->assertSee($item->name)
            ->assertSeeHtml('Out of Stock');

        // 2. Post Opening Stock via StockTakeManager
        Livewire::test(StockTakeManager::class)
            ->call('openAdjustmentForm', 'Opening')
            ->set('adj_item_id', $item->id)
            ->set('adj_quantity', 250)
            ->set('adj_unit_cost', 320.00)
            ->set('adj_date', now()->format('Y-m-d'))
            ->call('saveAdjustment')
            ->assertHasNoErrors()
            ->assertSee('Opening adjustment recorded successfully');

        // 3. Verify database ledger row
        $this->assertDatabaseHas('inventory_stock_ledgers', [
            'item_id' => $item->id,
            'transaction_type' => 'Opening',
            'branch_id' => $this->branch1->id,
            'qty_in' => 250.00,
            'qty_out' => 0.00,
        ]);

        // 4. Verify item cost was updated from zero
        $item->refresh();
        $this->assertEquals(320.00, $item->average_cost);
        $this->assertEquals(320.00, $item->last_purchase_cost);
        $this->assertEquals(320.00, $item->default_purchase_rate);

        // 5. Verify Central Warehouse Stock service
        $stockService = app(DepartmentStockService::class);
        $this->assertEquals(250.00, $stockService->getCentralWarehouseStock($this->marquee->id, $this->branch1->id, $item->id));

        // 6. Verify StockView now reflects the opening stock and Healthy stock status
        $component = Livewire::test(StockView::class);
        $component->assertSee($item->name);
        $items = $component->viewData('items');
        $viewItem = $items->firstWhere('id', $item->id);

        $this->assertNotNull($viewItem);
        $this->assertEquals(250.00, (float)$viewItem->total_opening);
        $this->assertEquals(0.00, (float)$viewItem->total_adjusted);

        // Net stock = 250.00, which exceeds reorder level (100) -> Status: Good
        $netStock = (float)$viewItem->total_opening + (float)$viewItem->total_received - (float)$viewItem->total_returned - ((float)$viewItem->total_issued - (float)$viewItem->total_dept_returned) + (float)$viewItem->total_adjusted;
        $this->assertEquals(250.00, $netStock);
        $component->assertSeeHtml('Good');
    }

    /** @test */
    public function test_wastage_and_damage_adjustments_reduce_stock_in_stock_view()
    {
        $this->actingAs($this->user);
        $item = $this->createItem(['minimum_stock_level' => 20, 'reorder_level' => 50]);

        // Seed opening stock of 100
        Livewire::test(StockTakeManager::class)
            ->call('openAdjustmentForm', 'Opening')
            ->set('adj_item_id', $item->id)
            ->set('adj_quantity', 100)
            ->set('adj_unit_cost', 150.00)
            ->set('adj_date', now()->format('Y-m-d'))
            ->call('saveAdjustment')
            ->assertHasNoErrors();

        // Write-off 30 units as Wastage
        Livewire::test(StockTakeManager::class)
            ->call('openAdjustmentForm', 'Wastage')
            ->set('adj_item_id', $item->id)
            ->set('adj_quantity', 30)
            ->set('adj_unit_cost', 150.00)
            ->set('adj_date', now()->format('Y-m-d'))
            ->set('adj_reason', 'Kitchen spoiled')
            ->call('saveAdjustment')
            ->assertHasNoErrors();

        // Write-off 40 units as Damage
        Livewire::test(StockTakeManager::class)
            ->call('openAdjustmentForm', 'Damage')
            ->set('adj_item_id', $item->id)
            ->set('adj_quantity', 40)
            ->set('adj_unit_cost', 150.00)
            ->set('adj_date', now()->format('Y-m-d'))
            ->set('adj_reason', 'Water leakage damage')
            ->call('saveAdjustment')
            ->assertHasNoErrors();

        // StockView verification:
        // Opening = 100, Total Adjusted = -70, Net Stock = 30
        // 30 is between min (20) and reorder (50) -> Reorder Req.
        $component = Livewire::test(StockView::class);
        $items = $component->viewData('items');
        $viewItem = $items->firstWhere('id', $item->id);

        $this->assertEquals(100.00, (float)$viewItem->total_opening);
        $this->assertEquals(-70.00, (float)$viewItem->total_adjusted);
        $component->assertSeeHtml('Reorder Req.');
    }

    /** @test */
    public function test_business_owner_without_assigned_branch_can_create_and_approve_stock_take()
    {
        $this->actingAs($this->ownerUser);
        $item = $this->createItem();

        // Seed initial opening stock in branch 2
        InventoryStockLedger::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch2->id,
            'item_id' => $item->id,
            'transaction_date' => now()->format('Y-m-d'),
            'transaction_type' => 'Opening',
            'qty_in' => 80.00,
            'qty_out' => 0.00,
            'running_balance' => 80.00,
            'unit_price' => 200.00,
            'total_cost' => 16000.00,
            'created_by' => $this->ownerUser->id,
        ]);

        // Owner creates stock count sheet for branch 2
        Livewire::test(StockTakeManager::class)
            ->set('selected_branch_id', (string)$this->branch2->id)
            ->call('openCreateForm')
            ->set('formItems', [
                [
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'item_code' => $item->item_code,
                    'system_qty' => 80.00,
                    'physical_qty' => 95.00, // 15 units surplus
                    'reason' => 'Unrecorded donation found',
                ]
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Physical stock take saved as Draft');

        $stockTake = InventoryStockTake::where('marquee_id', $this->marquee->id)->latest('id')->first();
        $this->assertNotNull($stockTake);
        $this->assertEquals($this->branch2->id, $stockTake->branch_id);
        $this->assertEquals('Draft', $stockTake->status);

        // Owner approves the stock take count (previously threw NOT NULL constraint on branch_id)
        Livewire::test(StockTakeManager::class)
            ->call('approveStockTake', $stockTake->id)
            ->assertHasNoErrors()
            ->assertSee('Stock count approved and inventory ledger adjustments posted');

        $stockTake->refresh();
        $this->assertEquals('Approved', $stockTake->status);

        // Verify ledger adjustment row exists for branch 2
        $this->assertDatabaseHas('inventory_stock_ledgers', [
            'item_id' => $item->id,
            'branch_id' => $this->branch2->id,
            'transaction_type' => 'Adjustment',
            'qty_in' => 15.00,
            'qty_out' => 0.00,
        ]);

        // Central stock for branch 2 should now be 95.00
        $stockService = app(DepartmentStockService::class);
        $this->assertEquals(95.00, $stockService->getCentralWarehouseStock($this->marquee->id, $this->branch2->id, $item->id));

        // When viewing StockView filtered by Branch 2, should show 95 stock
        $component = Livewire::test(StockView::class)
            ->set('filterBranch', (string)$this->branch2->id);

        $items = $component->viewData('items');
        $viewItem = $items->firstWhere('id', $item->id);
        $this->assertEquals(80.00, (float)$viewItem->total_opening);
        $this->assertEquals(15.00, (float)$viewItem->total_adjusted);
    }

    /** @test */
    public function test_stock_take_manager_displays_adjustments_log_tab()
    {
        $this->actingAs($this->user);
        $item = $this->createItem();

        // Post an adjustment
        Livewire::test(StockTakeManager::class)
            ->call('openAdjustmentForm', 'Opening')
            ->set('adj_item_id', $item->id)
            ->set('adj_quantity', 120)
            ->set('adj_unit_cost', 450.00)
            ->set('adj_date', now()->format('Y-m-d'))
            ->set('adj_reference', 'Audit-Opening-01')
            ->call('saveAdjustment')
            ->assertHasNoErrors();

        // Visit list view on adjustments tab
        Livewire::test(StockTakeManager::class)
            ->set('activeTab', 'adjustments')
            ->assertSee('Physical Stock Takes')
            ->assertSee('Adjustments')
            ->assertSee($item->name)
            ->assertSee('Audit-Opening-01')
            ->assertSee('+120.00')
            ->assertSee('PKR 450.00');
    }

    /** @test */
    public function test_monthly_stock_take_workflow_with_filters_and_valuation()
    {
        $this->actingAs($this->user);

        $itemA = $this->createItem(['name' => 'Basmati Rice', 'average_cost' => 300.00]);
        $itemB = $this->createItem(['name' => 'Cooking Oil', 'average_cost' => 500.00]);

        // Post stock for itemA = 100 units (Valuation = 30,000)
        Livewire::test(StockTakeManager::class)
            ->call('openAdjustmentForm', 'Opening')
            ->set('adj_item_id', $itemA->id)
            ->set('adj_quantity', 100)
            ->set('adj_unit_cost', 300.00)
            ->set('adj_date', now()->format('Y-m-d'))
            ->call('saveAdjustment')
            ->assertHasNoErrors();

        // 1. Launch Monthly Stock Take preset
        $component = Livewire::test(StockTakeManager::class)
            ->call('startMonthlyStockTake')
            ->assertSet('mode', 'stock-take')
            ->assertSet('notes', 'Monthly Stock Take - ' . now()->format('F Y'))
            ->assertSee('Basmati Rice')
            ->assertSee('Cooking Oil');

        // Check formItems contain unit cost and system quantities
        $formItems = $component->get('formItems');
        $this->assertCount(2, $formItems);
        $itemAForm = collect($formItems)->firstWhere('item_id', $itemA->id);
        $this->assertEquals(100.00, $itemAForm['system_qty']);
        $this->assertEquals(300.00, $itemAForm['unit_cost']);

        // 2. Filter by Stock Scope: in_stock only
        $component->set('stockFilter', 'in_stock');
        $filteredItems = $component->get('formItems');
        $this->assertCount(1, $filteredItems);
        $this->assertEquals($itemA->id, $filteredItems[0]['item_id']);

        // 3. Search filter
        $component->set('stockFilter', 'all')
            ->set('itemSearch', 'Cooking Oil');
        $searchItems = $component->get('formItems');
        $this->assertCount(1, $searchItems);
        $this->assertEquals($itemB->id, $searchItems[0]['item_id']);
    }

    /** @test */
    public function test_stock_view_valuation_and_export_functionality()
    {
        $this->actingAs($this->user);

        $item = $this->createItem(['name' => 'Sugar Premium', 'average_cost' => 150.00]);

        // Post 200 units opening stock (Value = 30,000)
        Livewire::test(StockTakeManager::class)
            ->call('openAdjustmentForm', 'Opening')
            ->set('adj_item_id', $item->id)
            ->set('adj_quantity', 200)
            ->set('adj_unit_cost', 150.00)
            ->set('adj_date', now()->format('Y-m-d'))
            ->call('saveAdjustment');

        // Verify StockView renders total valuation KPI and table columns
        Livewire::test(StockView::class)
            ->assertSee('Total Stock Value')
            ->assertSee('PKR 30,000.00')
            ->assertSee('Sugar Premium')
            ->assertSee('PKR 150.00');

        // Verify Excel Export stream response
        $stockView = new StockView();
        $response = $stockView->exportExcel();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        // Verify PDF and Print routes
        $pdfResponse = $this->get(route('inventory.stock.pdf'));
        $pdfResponse->assertStatus(200);

        $printResponse = $this->get(route('inventory.stock.print'));
        $printResponse->assertStatus(200)
            ->assertSee('Stock Valuation Report')
            ->assertSee('Sugar Premium')
            ->assertSee('PKR 30,000.00');
    }

    /** @test */
    public function test_stock_ledger_and_stock_take_export_and_print_routes()
    {
        $this->actingAs($this->user);

        $item = $this->createItem(['name' => 'Wheat Flour', 'average_cost' => 120.00]);

        // Post opening stock
        Livewire::test(StockTakeManager::class)
            ->call('openAdjustmentForm', 'Opening')
            ->set('adj_item_id', $item->id)
            ->set('adj_quantity', 50)
            ->set('adj_unit_cost', 120.00)
            ->set('adj_date', now()->format('Y-m-d'))
            ->call('saveAdjustment');

        // 1. Stock Ledger PDF & Print routes
        $ledgerPdf = $this->get(route('inventory.stock-ledger.pdf'));
        $ledgerPdf->assertStatus(200);

        $ledgerPrint = $this->get(route('inventory.stock-ledger.print'));
        $ledgerPrint->assertStatus(200)
            ->assertSee('Stock Ledger Statement')
            ->assertSee('Wheat Flour');

        // Stock Ledger Excel export
        $ledgerView = new \App\Livewire\Inventory\StockLedgerView();
        $ledgerExport = $ledgerView->exportExcel();
        $this->assertEquals(200, $ledgerExport->getStatusCode());

        // 2. Physical Stock Count Sheet Blank PDF & Print
        $countSheetPdf = $this->get(route('inventory.stock-takes.count-sheet.pdf'));
        $countSheetPdf->assertStatus(200);

        $countSheetPrint = $this->get(route('inventory.stock-takes.count-sheet.print'));
        $countSheetPrint->assertStatus(200)
            ->assertSee('Physical Count Sheet')
            ->assertSee('Wheat Flour');

        // 3. Create a Stock Take and test its PDF, Print, and Excel export
        $stockTake = InventoryStockTake::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch1->id,
            'stock_take_number' => 'STK-2026-00099',
            'count_date' => now()->format('Y-m-d'),
            'status' => 'Draft',
            'notes' => 'Monthly Audit',
            'created_by' => $this->user->id,
        ]);
        InventoryStockTakeItem::create([
            'inventory_stock_take_id' => $stockTake->id,
            'item_id' => $item->id,
            'system_qty' => 50,
            'physical_qty' => 48,
            'difference' => -2,
            'reason' => 'Package tear',
        ]);

        $stPdf = $this->get(route('inventory.stock-takes.pdf', $stockTake->id));
        $stPdf->assertStatus(200);

        $stPrint = $this->get(route('inventory.stock-takes.print', $stockTake->id));
        $stPrint->assertStatus(200)
            ->assertSee('STK-2026-00099')
            ->assertSee('Wheat Flour')
            ->assertSee('Package tear');

        $stManager = new StockTakeManager();
        $stExport = $stManager->exportStockTakeExcel($stockTake->id);
        $this->assertEquals(200, $stExport->getStatusCode());
    }
}
