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
    public $selected_branch_id = '';
    public $activeTab = 'counts'; // 'counts' | 'adjustments'

    // ── View mode: 'list' | 'stock-take' | 'adjustment' ────────────────────
    public $mode = 'list';

    // ── Stock-Take form fields ───────────────────────────────────────────────
    public $stock_take_number;
    public $count_date;
    public $notes;
    public $categoryId   = '';
    public $itemSearch   = '';
    public $stockFilter  = 'all'; // 'all' or 'in_stock'
    public $formItems    = [];

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
        'search'             => ['except' => ''],
        'filterStatus'       => ['except' => ''],
        'selected_branch_id' => ['except' => ''],
        'activeTab'          => ['except' => 'counts'],
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

    public function getMarqueeId(): ?int
    {
        $user = auth()->user();
        return $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
    }

    public function getBranchId(): ?int
    {
        if (!empty($this->selected_branch_id)) {
            return (int) $this->selected_branch_id;
        }
        $user = auth()->user();
        if (!$user) return null;
        if ($user->branch_id) return (int) $user->branch_id;
        $firstBranch = $user->getAccessibleBranches($this->getMarqueeId())->first();
        return $firstBranch ? (int) $firstBranch->id : null;
    }

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->hasPermission('inventory.adjust') || $user->hasPermission('inventory.manage') || $user->hasPermission('manage_inventory') || $user->hasPermission('view_inventory')), 403, 'Unauthorized access to stock take.');

        $this->count_date = now()->format('Y-m-d');
        $this->adj_date   = now()->format('Y-m-d');

        $marqueeId = $this->getMarqueeId();
        if ($user->branch_id) {
            $this->selected_branch_id = (string) $user->branch_id;
        } else {
            $firstBranch = $user->getAccessibleBranches($marqueeId)->first();
            $this->selected_branch_id = $firstBranch ? (string) $firstBranch->id : '';
        }
    }

    public function updatedSelectedBranchId(): void
    {
        if ($this->mode === 'stock-take') {
            $this->loadInventoryItemsForCounting();
        } elseif ($this->mode === 'adjustment' && $this->adj_item_id) {
            $this->checkExistingOpening();
        }
        $this->resetPage();
    }

    // ── Stock-Take methods ────────────────────────────────────────────────────

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->stock_take_number = $this->generateNextStockTakeNumber();
        $this->loadInventoryItemsForCounting();
        $this->mode = 'stock-take';
    }

    public function startMonthlyStockTake(): void
    {
        $this->resetForm();
        $this->stock_take_number = $this->generateNextStockTakeNumber();
        $this->count_date        = now()->format('Y-m-d');
        $this->notes             = 'Monthly Stock Take - ' . now()->format('F Y');
        $this->loadInventoryItemsForCounting();
        $this->mode              = 'stock-take';
    }

    public function generateNextStockTakeNumber(): string
    {
        $marqueeId = $this->getMarqueeId();
        $count = InventoryStockTake::where('marquee_id', $marqueeId)->count();
        return 'STK-' . date('Y') . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
    }

    public function resetForm(): void
    {
        $this->mode          = 'list';
        $this->count_date    = now()->format('Y-m-d');
        $this->notes         = '';
        $this->categoryId    = '';
        $this->itemSearch    = '';
        $this->stockFilter   = 'all';
        $this->formItems     = [];
        $this->resetErrorBag();
    }

    public function updatedCategoryId(): void
    {
        $this->loadInventoryItemsForCounting();
    }

    public function updatedItemSearch(): void
    {
        $this->loadInventoryItemsForCounting();
    }

    public function updatedStockFilter(): void
    {
        $this->loadInventoryItemsForCounting();
    }

    public function loadInventoryItemsForCounting(): void
    {
        $marqueeId    = $this->getMarqueeId();
        $branchId     = $this->getBranchId();
        $stockService = app(DepartmentStockService::class);

        $query = InventoryItem::where('marquee_id', $marqueeId)
            ->with(['unit', 'category'])
            ->where('status', 'Active');

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        if ($this->itemSearch) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->itemSearch . '%')
                  ->orWhere('item_code', 'like', '%' . $this->itemSearch . '%');
            });
        }

        $items = $query->orderBy('name')->get();

        $this->formItems = [];
        foreach ($items as $item) {
            $sysQty = $stockService->getCentralWarehouseStock($marqueeId, $branchId, $item->id);

            if ($this->stockFilter === 'in_stock' && $sysQty <= 0) {
                continue;
            }

            $unitCost = (float)($item->average_cost > 0 ? $item->average_cost : ($item->last_purchase_cost > 0 ? $item->last_purchase_cost : $item->default_purchase_rate));

            $this->formItems[] = [
                'item_id'       => $item->id,
                'name'          => $item->name,
                'item_code'     => $item->item_code,
                'category_name' => $item->category->name ?? '—',
                'unit_name'     => $item->unit->short_code ?? 'Pcs',
                'system_qty'    => $sysQty,
                'physical_qty'  => $sysQty,
                'unit_cost'     => $unitCost,
                'reason'        => '',
            ];
        }
    }

    public function getCountSheetItems($marqueeId, $branchId, $categoryId = null): array
    {
        $stockService = app(DepartmentStockService::class);
        $query = InventoryItem::where('marquee_id', $marqueeId)
            ->with(['unit', 'category'])
            ->where('status', 'Active');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();
        $result = [];
        foreach ($items as $item) {
            $sysQty = $stockService->getCentralWarehouseStock($marqueeId, $branchId, $item->id);
            $result[] = [
                'item_id'       => $item->id,
                'item_code'     => $item->item_code,
                'name'          => $item->name,
                'category_name' => $item->category->name ?? '—',
                'unit_name'     => $item->unit->short_code ?? 'Pcs',
                'system_qty'    => $sysQty,
            ];
        }
        return $result;
    }

    public function exportStockTakeExcel(int $id)
    {
        $stockTake = InventoryStockTake::with(['items.item.unit', 'items.item.category', 'branch', 'marquee', 'creator', 'approver'])->findOrFail($id);
        $filename = "Stock_Take_{$stockTake->stock_take_number}_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"{$filename}\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        return response()->stream(function () use ($stockTake) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($handle, ['PHYSICAL STOCK TAKE AUDIT & VALUATION REPORT']);
            fputcsv($handle, ['Stock Take #', $stockTake->stock_take_number]);
            fputcsv($handle, ['Branch', $stockTake->branch->name ?? 'All Branches']);
            fputcsv($handle, ['Count Date', $stockTake->count_date->format('Y-m-d')]);
            fputcsv($handle, ['Status', $stockTake->status]);
            fputcsv($handle, ['Created By', $stockTake->creator->name ?? 'System']);
            fputcsv($handle, ['Approved By', $stockTake->approver->name ?? '—']);
            fputcsv($handle, ['Notes', $stockTake->notes ?: '—']);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Item Code', 'Item Name', 'Category', 'Unit',
                'System Qty', 'Physical Qty', 'Difference',
                'Unit Cost (PKR)', 'System Value (PKR)', 'Physical Value (PKR)', 'Difference Value (PKR)',
                'Reason / Explanation'
            ]);

            foreach ($stockTake->items as $row) {
                $item = $row->item;
                $unitCost = (float)($item->average_cost > 0 ? $item->average_cost : ($item->last_purchase_cost > 0 ? $item->last_purchase_cost : $item->default_purchase_rate));
                $sysVal = (float)$row->system_qty * $unitCost;
                $physVal = (float)$row->physical_qty * $unitCost;
                $diffVal = (float)$row->difference * $unitCost;

                fputcsv($handle, [
                    $item->item_code ?? '—',
                    $item->name ?? '—',
                    $item->category->name ?? '—',
                    $item->unit->short_code ?? 'Pcs',
                    $row->system_qty,
                    $row->physical_qty,
                    $row->difference,
                    number_format($unitCost, 2, '.', ''),
                    number_format($sysVal, 2, '.', ''),
                    number_format($physVal, 2, '.', ''),
                    number_format($diffVal, 2, '.', ''),
                    $row->reason ?: '',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function save(): void
    {
        if ($this->mode === 'adjustment') {
            $this->saveAdjustment();
            return;
        }

        $this->validate();

        $marqueeId = $this->getMarqueeId();
        $branchId  = $this->getBranchId();

        if (!$branchId) {
            session()->flash('error', 'Please select or assign a branch for the stock count.');
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
        $this->activeTab = 'counts';
        $this->resetForm();
    }

    public function viewDetails(int $id): void
    {
        $this->viewStockTake  = InventoryStockTake::with(['items.item.unit', 'creator', 'approver', 'branch'])->findOrFail($id);
        $this->isViewModalOpen = true;
    }

    public function approveStockTake(int $id): void
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('inventory.adjust')) {
            abort(403, 'Unauthorized access. Only managers can approve adjustments.');
        }

        $marqueeId = $this->getMarqueeId();

        DB::transaction(function () use ($id, $marqueeId, $user) {
            $stockTake = InventoryStockTake::with('items.item')->where('marquee_id', $marqueeId)->findOrFail($id);

            if ($stockTake->status !== 'Draft') {
                throw new \InvalidArgumentException('Only Draft counts can be approved.');
            }

            $branchId = $stockTake->branch_id ?: ($user->branch_id ?: $this->getBranchId());
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
                        'created_by'       => $user->id,
                    ]);
                }
            }

            $stockTake->update([
                'status'      => 'Approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            \App\Models\ActivityLog::create([
                'user_id' => $user->id,
                'marquee_id' => $marqueeId,
                'action' => 'stock_take_approved',
                'description' => "Approved Stock Count #{$stockTake->stock_take_number} and posted ledger adjustments",
                'model_type' => InventoryStockTake::class,
                'model_id' => $stockTake->id,
            ]);
        });

        session()->flash('success', 'Stock count approved and inventory ledger adjustments posted.');
        $this->isViewModalOpen = false;
        $this->viewStockTake   = null;
    }

    public function cancelStockTake(int $id): void
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('inventory.adjust')) {
            abort(403, 'Unauthorized access.');
        }

        $marqueeId = $this->getMarqueeId();
        $stockTake = InventoryStockTake::where('marquee_id', $marqueeId)->findOrFail($id);
        if ($stockTake->status !== 'Draft') {
            session()->flash('error', 'Only Draft counts can be cancelled.');
            return;
        }

        $stockTake->update(['status' => 'Cancelled']);

        \App\Models\ActivityLog::create([
            'user_id' => $user->id,
            'marquee_id' => $marqueeId,
            'action' => 'stock_take_cancelled',
            'description' => "Cancelled Stock Count #{$stockTake->stock_take_number}",
            'model_type' => InventoryStockTake::class,
            'model_id' => $stockTake->id,
        ]);

        session()->flash('success', 'Stock count cancelled.');
        $this->isViewModalOpen = false;
        $this->viewStockTake   = null;
    }

    // ── Direct Adjustment methods (TASK 6 & 7) ──────────────────────────────

    public function openAdjustmentForm(string $defaultType = 'Adjustment'): void
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('inventory.adjust')) {
            abort(403, 'Unauthorized access. Only managers can make stock adjustments.');
        }

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
        $marqueeId = $this->getMarqueeId();
        $branchId  = $this->getBranchId();

        $this->adj_has_existing_opening = InventoryStockLedger::where('marquee_id', $marqueeId)
            ->where('branch_id', $branchId)
            ->where('item_id', $this->adj_item_id)
            ->where('transaction_type', 'Opening')
            ->exists();
    }

    public function saveAdjustment(): void
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('inventory.adjust')) {
            abort(403, 'Unauthorized access. Only managers can make stock adjustments.');
        }

        $this->validate();

        $marqueeId = $this->getMarqueeId();
        $branchId  = $this->getBranchId();

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

        DB::transaction(function () use ($marqueeId, $branchId, $qty, $isOut, $unitCost, $user) {
            $stockService = app(DepartmentStockService::class);
            $prevBalance  = $stockService->getCentralWarehouseStock($marqueeId, $branchId, (int)$this->adj_item_id);
            $newBalance   = $isOut ? $prevBalance - $qty : $prevBalance + $qty;

            InventoryStockLedger::create([
                'marquee_id'       => $marqueeId,
                'branch_id'        => $branchId,
                'item_id'          => (int)$this->adj_item_id,
                'transaction_date' => $this->adj_date,
                'transaction_type' => $this->adj_type,
                'reference_type'   => $this->adj_reference ? ($this->adj_reason ? "{$this->adj_reference} - {$this->adj_reason}" : $this->adj_reference) : ($this->adj_reason ?: 'Manual'),
                'reference_id'     => null,
                'qty_in'           => $isOut ? 0.00 : $qty,
                'qty_out'          => $isOut ? $qty : 0.00,
                'running_balance'  => $newBalance,
                'unit_price'       => $unitCost,
                'total_cost'       => $qty * $unitCost,
                'created_by'       => $user->id,
            ]);

            // If Opening stock or unitCost provided, initialize item cost metrics if they were unset
            if ($this->adj_type === 'Opening' && $unitCost > 0) {
                $invItem = InventoryItem::find($this->adj_item_id);
                if ($invItem) {
                    $updates = [];
                    if ((float)$invItem->average_cost <= 0) {
                        $updates['average_cost'] = $unitCost;
                    }
                    if ((float)$invItem->last_purchase_cost <= 0) {
                        $updates['last_purchase_cost'] = $unitCost;
                    }
                    if ((float)$invItem->default_purchase_rate <= 0) {
                        $updates['default_purchase_rate'] = $unitCost;
                    }
                    if (!empty($updates)) {
                        $invItem->update($updates);
                    }
                }
            }

            \App\Models\ActivityLog::create([
                'user_id' => $user->id,
                'marquee_id' => $marqueeId,
                'action' => 'stock_adjustment_posted',
                'description' => "Posted {$this->adj_type} adjustment of {$qty} units for Item ID {$this->adj_item_id}",
                'model_type' => InventoryStockLedger::class,
                'model_id' => null,
            ]);
        });

        $typeLabel = $this->adj_type;
        session()->flash('success', "{$typeLabel} adjustment recorded successfully. Ledger updated.");
        $this->resetAdjustmentForm();
        $this->activeTab = 'adjustments';
        $this->mode = 'list';
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $marqueeId = $this->getMarqueeId();
        $branchId  = $this->getBranchId();

        $query = InventoryStockTake::where('marquee_id', $marqueeId)
            ->with(['creator', 'approver', 'branch']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($this->search) {
            $query->where('stock_take_number', 'like', '%' . $this->search . '%');
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $stockTakes = $query->orderBy('count_date', 'desc')->orderBy('id', 'desc')->paginate(10, ['*'], 'countsPage');

        // Adjustments query for the Adjustments Log tab
        $adjQuery = InventoryStockLedger::where('inventory_stock_ledgers.marquee_id', $marqueeId)
            ->whereIn('inventory_stock_ledgers.transaction_type', ['Opening', 'Adjustment', 'Wastage', 'Damage', 'Expiry'])
            ->with(['item.unit', 'creator'])
            ->join('inventory_items', 'inventory_stock_ledgers.item_id', '=', 'inventory_items.id')
            ->leftJoin('branches', 'inventory_stock_ledgers.branch_id', '=', 'branches.id')
            ->select(
                'inventory_stock_ledgers.*',
                'inventory_items.name as item_name',
                'inventory_items.item_code',
                'branches.name as branch_name'
            );

        if ($branchId) {
            $adjQuery->where('inventory_stock_ledgers.branch_id', $branchId);
        }

        if ($this->search) {
            $adjQuery->where(function ($q) {
                $q->where('inventory_items.name', 'like', '%' . $this->search . '%')
                  ->orWhere('inventory_items.item_code', 'like', '%' . $this->search . '%');
            });
        }

        $adjustments = $adjQuery->orderBy('inventory_stock_ledgers.transaction_date', 'desc')
            ->orderBy('inventory_stock_ledgers.id', 'desc')
            ->paginate(15, ['*'], 'adjustmentsPage');

        $categories = InventoryCategory::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->orderBy('name')
            ->get();

        $inventoryItems = InventoryItem::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->orderBy('name')
            ->get();

        $branches = \App\Models\Branch::where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('livewire.inventory.stock-take-manager', [
            'stockTakes'     => $stockTakes,
            'adjustments'    => $adjustments,
            'categories'     => $categories,
            'inventoryItems' => $inventoryItems,
            'branches'       => $branches,
        ])->layout('layouts.admin');
    }
}
