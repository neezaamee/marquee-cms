<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryStockTake;
use App\Models\InventoryStockTakeItem;
use App\Models\InventoryStockLedger;
use App\Services\DepartmentStockService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class StockTakeManager extends Component
{
    use WithPagination;

    // ── List filters ────────────────────────────────────────────────────────
    public $search = '';
    public $filterStatus = '';

    // ── View mode: 'list' | 'stock-take' | 'adjustment' ────────────────────
    public $mode = 'list';

    // ── Stock-Take form fields ───────────────────────────────────────────────
    public $stock_take_number;
    public $count_date;
    public $notes;
    public $categoryId = '';
    public $formItems  = [];

    // ── Direct Adjustment form fields (TASK 6 & 7) ──────────────────────────
    public $adj_item_id      = '';
    public $adj_quantity     = '';
    public $adj_type         = 'Opening';  // Opening, Wastage, Damage, Expiry, Adjustment
    public $adj_direction    = 'in';       // 'in' or 'out'
    public $adj_unit_cost    = '';
    public $adj_date         = '';
    public $adj_reference    = '';
    public $adj_reason       = '';
    public $adj_has_existing_opening = false;

    // ── Modal state ──────────────────────────────────────────────────────────
    public $isViewModalOpen = false;
    public $viewStockTake   = null;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search'       => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    // ── Validation rules ─────────────────────────────────────────────────────
    protected function rules(): array
    {
        if ($this->mode === 'stock-take') {
            return [
                'count_date'                => 'required|date',
                'notes'                     => 'nullable|string',
                'formItems'                 => 'required|array|min:1',
                'formItems.*.item_id'       => 'required|exists:inventory_items,id',
                'formItems.*.physical_qty'  => 'required|numeric|min:0',
                'formItems.*.reason'        => 'nullable|string|max:255',
            ];
        }

        // Adjustment form rules
        return [
            'adj_item_id'  => 'required|exists:inventory_items,id',
            'adj_quantity' => 'required|numeric|min:0.01',
            'adj_type'     => 'required|in:Opening,Wastage,Damage,Expiry,Adjustment',
            'adj_unit_cost'=> 'required|numeric|min:0',
            'adj_date'     => 'required|date',
            'adj_reason'   => 'nullable|string|max:500',
            'adj_reference'=> 'nullable|string|max:100',
        ];
    }

    public function mount(): void
    {
        $this->count_date = now()->format('Y-m-d');
        $this->adj_date   = now()->format('Y-m-d');
    }

    // ── Stock-Take methods ────────────────────────────────────────────────────

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->stock_take_number = $this->generateNextStockTakeNumber();
        $this->loadInventoryItemsForCounting();
        $this->mode = 'stock-take';
    }

    public function generateNextStockTakeNumber(): string
    {
        $marqueeId = auth()->user()->marquee_id;
        $count = InventoryStockTake::where('marquee_id', $marqueeId)->count();
        return 'STK-' . date('Y') . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
    }

    public function resetForm(): void
    {
        $this->mode          = 'list';
        $this->count_date    = now()->format('Y-m-d');
        $this->notes         = '';
        $this->categoryId    = '';
        $this->formItems     = [];
        $this->resetErrorBag();
    }

    public function updatedCategoryId(): void
    {
        $this->loadInventoryItemsForCounting();
    }

    public function loadInventoryItemsForCounting(): void
    {
        $marqueeId    = auth()->user()->marquee_id;
        $branchId     = auth()->user()->branch_id;
        $stockService = app(DepartmentStockService::class);

        $query = InventoryItem::where('marquee_id', $marqueeId)
            ->where('status', 'Active');

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        $items = $query->orderBy('name')->get();

        $this->formItems = [];
        foreach ($items as $item) {
            $sysQty = $stockService->getCentralWarehouseStock($marqueeId, $branchId, $item->id);
            $this->formItems[] = [
                'item_id'     => $item->id,
                'name'        => $item->name,
                'item_code'   => $item->item_code,
                'system_qty'  => $sysQty,
                'physical_qty'=> $sysQty,
                'reason'      => '',
            ];
        }
    }

    public function save(): void
    {
        if ($this->mode === 'adjustment') {
            $this->saveAdjustment();
            return;
        }

        $this->validate();

        $marqueeId = auth()->user()->marquee_id;
        $branchId  = auth()->user()->branch_id;

        if (!$branchId) {
            session()->flash('error', 'Please make sure you are logged in to a branch.');
            return;
        }

        DB::transaction(function () use ($marqueeId, $branchId) {
            $stockTake = InventoryStockTake::create([
                'marquee_id'        => $marqueeId,
                'branch_id'         => $branchId,
                'stock_take_number' => $this->stock_take_number,
                'count_date'        => $this->count_date,
                'status'            => 'Draft',
                'notes'             => $this->notes,
                'created_by'        => auth()->id(),
            ]);

            foreach ($this->formItems as $formItem) {
                $diff = (float)$formItem['physical_qty'] - (float)$formItem['system_qty'];
                InventoryStockTakeItem::create([
                    'inventory_stock_take_id' => $stockTake->id,
                    'item_id'                 => $formItem['item_id'],
                    'system_qty'              => $formItem['system_qty'],
                    'physical_qty'            => $formItem['physical_qty'],
                    'difference'              => $diff,
                    'reason'                  => $formItem['reason'] ?: null,
                ]);
            }
        });

        session()->flash('success', 'Physical stock take saved as Draft.');
        $this->resetForm();
    }

    public function viewDetails(int $id): void
    {
        $this->viewStockTake  = InventoryStockTake::with(['items.item.unit', 'creator', 'approver'])->findOrFail($id);
        $this->isViewModalOpen = true;
    }

    public function approveStockTake(int $id): void
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasPermission('inventory.adjust')) {
            session()->flash('error', 'Unauthorized access. Only managers can approve adjustments.');
            return;
        }

        $marqueeId = auth()->user()->marquee_id;
        $branchId  = auth()->user()->branch_id;

        DB::transaction(function () use ($id, $marqueeId, $branchId) {
            $stockTake = InventoryStockTake::with('items.item')->findOrFail($id);

            if ($stockTake->status !== 'Draft') {
                throw new \InvalidArgumentException('Only Draft counts can be approved.');
            }

            $stockService = app(DepartmentStockService::class);

            foreach ($stockTake->items as $stItem) {
                if ((float)$stItem->difference == 0.0) {
                    continue;
                }

                // Idempotency guard for stock take approval
                $alreadyLogged = InventoryStockLedger::where('marquee_id', $marqueeId)
                    ->where('branch_id', $branchId)
                    ->where('item_id', $stItem->item_id)
                    ->where('transaction_type', 'Adjustment')
                    ->where('reference_type', 'App\\Models\\InventoryStockTake')
                    ->where('reference_id', $stockTake->id)
                    ->exists();

                if (!$alreadyLogged) {
                    $diff    = (float)$stItem->difference;
                    $invItem = $stItem->item;
                    $unitCost = $invItem->average_cost ?: ($invItem->default_purchase_rate ?: 1.0);

                    $prevCentralBalance = $stockService->getCentralWarehouseStock($marqueeId, $branchId, $stItem->item_id);
                    $newCentralBalance  = $prevCentralBalance + $diff;

                    InventoryStockLedger::create([
                        'marquee_id'       => $marqueeId,
                        'branch_id'        => $branchId,
                        'item_id'          => $stItem->item_id,
                        'transaction_date' => $stockTake->count_date->format('Y-m-d'),
                        'transaction_type' => 'Adjustment',
                        'reference_type'   => 'App\\Models\\InventoryStockTake',
                        'reference_id'     => $stockTake->id,
                        'qty_in'           => $diff > 0 ? $diff : 0.00,
                        'qty_out'          => $diff < 0 ? abs($diff) : 0.00,
                        'running_balance'  => $newCentralBalance,
                        'unit_price'       => $unitCost,
                        'total_cost'       => abs($diff) * $unitCost,
                        'created_by'       => auth()->id(),
                    ]);
                }
            }

            $stockTake->update([
                'status'      => 'Approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });

        session()->flash('success', 'Stock count approved and inventory ledger adjustments posted.');
        $this->isViewModalOpen = false;
        $this->viewStockTake   = null;
    }

    public function cancelStockTake(int $id): void
    {
        $stockTake = InventoryStockTake::findOrFail($id);
        if ($stockTake->status !== 'Draft') {
            session()->flash('error', 'Only Draft counts can be cancelled.');
            return;
        }

        $stockTake->update(['status' => 'Cancelled']);
        session()->flash('success', 'Stock count cancelled.');
        $this->isViewModalOpen = false;
        $this->viewStockTake   = null;
    }

    // ── Direct Adjustment methods (TASK 6 & 7) ──────────────────────────────

    public function openAdjustmentForm(string $defaultType = 'Adjustment'): void
    {
        $this->resetAdjustmentForm();
        $this->adj_type      = $defaultType;
        $this->adj_direction = in_array($defaultType, ['Opening', 'Adjustment']) ? 'in' : 'out';
        $this->mode          = 'adjustment';
    }

    public function resetAdjustmentForm(): void
    {
        $this->adj_item_id      = '';
        $this->adj_quantity     = '';
        $this->adj_type         = 'Adjustment';
        $this->adj_direction    = 'in';
        $this->adj_unit_cost    = '';
        $this->adj_date         = now()->format('Y-m-d');
        $this->adj_reference    = '';
        $this->adj_reason       = '';
        $this->adj_has_existing_opening = false;
        $this->resetErrorBag();
    }

    public function updatedAdjType(): void
    {
        // OUT transactions for loss types
        $this->adj_direction = in_array($this->adj_type, ['Wastage', 'Damage', 'Expiry']) ? 'out' : 'in';
        // Check opening duplicate if applicable
        if ($this->adj_type === 'Opening' && $this->adj_item_id) {
            $this->checkExistingOpening();
        }
    }

    public function updatedAdjItemId(): void
    {
        // Auto-fill unit cost from item average cost
        if ($this->adj_item_id) {
            $item = InventoryItem::find($this->adj_item_id);
            if ($item) {
                $this->adj_unit_cost = $item->average_cost ?: ($item->default_purchase_rate ?: 0);
            }
            if ($this->adj_type === 'Opening') {
                $this->checkExistingOpening();
            }
        }
    }

    protected function checkExistingOpening(): void
    {
        $marqueeId = auth()->user()->marquee_id;
        $branchId  = auth()->user()->branch_id;

        $this->adj_has_existing_opening = InventoryStockLedger::where('marquee_id', $marqueeId)
            ->where('branch_id', $branchId)
            ->where('item_id', $this->adj_item_id)
            ->where('transaction_type', 'Opening')
            ->exists();
    }

    public function saveAdjustment(): void
    {
        $this->validate();

        $marqueeId = auth()->user()->marquee_id;
        $branchId  = auth()->user()->branch_id;

        if (!$branchId) {
            session()->flash('error', 'Please make sure you are logged in to a branch.');
            return;
        }

        $qty      = (float)$this->adj_quantity;
        $isOut    = $this->adj_direction === 'out';
        $unitCost = (float)$this->adj_unit_cost;

        // Block duplicate Opening Stock at server side
        if ($this->adj_type === 'Opening') {
            $alreadyLogged = InventoryStockLedger::where('marquee_id', $marqueeId)
                ->where('branch_id', $branchId)
                ->where('item_id', $this->adj_item_id)
                ->where('transaction_type', 'Opening')
                ->exists();
            if ($alreadyLogged) {
                $this->addError('adj_type', "An Opening Stock entry already exists for this item in this branch.");
                return;
            }
        }

        // Block negative-stock OUT adjustments
        if ($isOut) {
            $stockService = app(DepartmentStockService::class);
            $currentStock = $stockService->getCentralWarehouseStock($marqueeId, $branchId, (int)$this->adj_item_id);
            if ($qty > $currentStock) {
                $this->addError('adj_quantity',
                    "Cannot write off {$qty}. Current Central Stock is " . number_format($currentStock, 2) . ".");
                return;
            }
        }

        DB::transaction(function () use ($marqueeId, $branchId, $qty, $isOut, $unitCost) {
            $stockService = app(DepartmentStockService::class);
            $prevBalance  = $stockService->getCentralWarehouseStock($marqueeId, $branchId, (int)$this->adj_item_id);
            $newBalance   = $isOut ? $prevBalance - $qty : $prevBalance + $qty;

            InventoryStockLedger::create([
                'marquee_id'       => $marqueeId,
                'branch_id'        => $branchId,
                'item_id'          => (int)$this->adj_item_id,
                'transaction_date' => $this->adj_date,
                'transaction_type' => $this->adj_type,
                'reference_type'   => $this->adj_reference ? 'Manual' : null,
                'reference_id'     => null,
                'qty_in'           => $isOut ? 0.00 : $qty,
                'qty_out'          => $isOut ? $qty : 0.00,
                'running_balance'  => $newBalance,
                'unit_price'       => $unitCost,
                'total_cost'       => $qty * $unitCost,
                'created_by'       => auth()->id(),
            ]);
        });

        $typeLabel = $this->adj_type;
        session()->flash('success', "{$typeLabel} adjustment recorded successfully. Ledger updated.");
        $this->resetAdjustmentForm();
        $this->mode = 'list';
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $branchId  = auth()->user()->branch_id;

        $query = InventoryStockTake::where('marquee_id', $marqueeId)
            ->with(['creator', 'approver']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($this->search) {
            $query->where('stock_take_number', 'like', '%' . $this->search . '%');
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $stockTakes = $query->orderBy('count_date', 'desc')->orderBy('id', 'desc')->paginate(10);
        $categories = InventoryCategory::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->orderBy('name')
            ->get();

        $inventoryItems = InventoryItem::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->orderBy('name')
            ->get();

        return view('livewire.inventory.stock-take-manager', [
            'stockTakes'     => $stockTakes,
            'categories'     => $categories,
            'inventoryItems' => $inventoryItems,
        ])->layout('layouts.admin');
    }
}
