<?php

namespace App\Livewire\Inventory;

use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\InventoryStockLedger;
use Livewire\Component;
use Livewire\WithPagination;

class StockLedgerView extends Component
{
    use WithPagination;

    // Filters
    public $filterBranch = '';
    public $filterItem = '';
    public $filterType = '';
    public $filterDateFrom = '';
    public $filterDateTo = '';
    public $search = '';

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'filterBranch'   => ['except' => ''],
        'filterItem'     => ['except' => ''],
        'filterType'     => ['except' => ''],
        'filterDateFrom' => ['except' => ''],
        'filterDateTo'   => ['except' => ''],
        'search'         => ['except' => ''],
    ];

    public function mount(): void
    {
        $user = auth()->user();
        // Auto-scope branch for branch users
        if ($user->branch_id && !$user->isSuperAdmin()) {
            $this->filterBranch = $user->branch_id;
        }
        // Default date range: current month
        $this->filterDateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->filterDateTo   = now()->format('Y-m-d');
    }

    public function updatingSearch(): void      { $this->resetPage(); }
    public function updatingFilterBranch(): void { $this->resetPage(); }
    public function updatingFilterItem(): void   { $this->resetPage(); }
    public function updatingFilterType(): void   { $this->resetPage(); }
    public function updatingFilterDateFrom(): void { $this->resetPage(); }
    public function updatingFilterDateTo(): void   { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->filterItem     = '';
        $this->filterType     = '';
        $this->filterDateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->filterDateTo   = now()->format('Y-m-d');
        $this->search         = '';
        $this->resetPage();
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $user      = auth()->user();

        // ── Base query ─────────────────────────────────────────────────────────
        $query = InventoryStockLedger::where('inventory_stock_ledgers.marquee_id', $marqueeId)
            ->with(['item.unit', 'creator'])
            ->join('inventory_items', 'inventory_stock_ledgers.item_id', '=', 'inventory_items.id')
            ->join('branches', 'inventory_stock_ledgers.branch_id', '=', 'branches.id')
            ->select(
                'inventory_stock_ledgers.*',
                'inventory_items.name as item_name',
                'inventory_items.item_code',
                'branches.name as branch_name'
            );

        // ── Branch filter ──────────────────────────────────────────────────────
        $branchId = $user->branch_id && !$user->isSuperAdmin() ? $user->branch_id : $this->filterBranch;
        if ($branchId) {
            $query->where('inventory_stock_ledgers.branch_id', $branchId);
        }

        // ── Item filter ────────────────────────────────────────────────────────
        if ($this->filterItem) {
            $query->where('inventory_stock_ledgers.item_id', $this->filterItem);
        }

        // ── Type filter ────────────────────────────────────────────────────────
        if ($this->filterType) {
            $query->where('inventory_stock_ledgers.transaction_type', $this->filterType);
        }

        // ── Date range filter ──────────────────────────────────────────────────
        if ($this->filterDateFrom) {
            $query->whereDate('inventory_stock_ledgers.transaction_date', '>=', $this->filterDateFrom);
        }
        if ($this->filterDateTo) {
            $query->whereDate('inventory_stock_ledgers.transaction_date', '<=', $this->filterDateTo);
        }

        // ── Search (item name or item code) ────────────────────────────────────
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('inventory_items.name', 'like', '%' . $this->search . '%')
                  ->orWhere('inventory_items.item_code', 'like', '%' . $this->search . '%');
            });
        }

        $ledger = $query->orderBy('inventory_stock_ledgers.transaction_date', 'desc')
            ->orderBy('inventory_stock_ledgers.id', 'desc')
            ->paginate(25);

        // ── Summary stats ──────────────────────────────────────────────────────
        $statsQuery = InventoryStockLedger::where('inventory_stock_ledgers.marquee_id', $marqueeId)
            ->join('inventory_items', 'inventory_stock_ledgers.item_id', '=', 'inventory_items.id');

        if ($branchId) {
            $statsQuery->where('inventory_stock_ledgers.branch_id', $branchId);
        }
        if ($this->filterDateFrom) {
            $statsQuery->whereDate('inventory_stock_ledgers.transaction_date', '>=', $this->filterDateFrom);
        }
        if ($this->filterDateTo) {
            $statsQuery->whereDate('inventory_stock_ledgers.transaction_date', '<=', $this->filterDateTo);
        }
        if ($this->filterType) {
            $statsQuery->where('inventory_stock_ledgers.transaction_type', $this->filterType);
        }

        $totalIn  = (float) (clone $statsQuery)->sum('inventory_stock_ledgers.qty_in');
        $totalOut = (float) (clone $statsQuery)->sum('inventory_stock_ledgers.qty_out');
        $totalCost = (float) (clone $statsQuery)->sum('inventory_stock_ledgers.total_cost');

        // ── Filter dropdowns ───────────────────────────────────────────────────
        $branches = Branch::where('marquee_id', $marqueeId)->orderBy('name')->get();
        $items    = InventoryItem::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->orderBy('name')
            ->get();

        $transactionTypes = [
            'Opening', 'GRN', 'Issue', 'Return', 'PurchaseReturn',
            'Adjustment', 'Wastage', 'Damage', 'Expiry',
        ];

        return view('livewire.inventory.stock-ledger-view', [
            'ledger'           => $ledger,
            'branches'         => $branches,
            'items'            => $items,
            'transactionTypes' => $transactionTypes,
            'totalIn'          => $totalIn,
            'totalOut'         => $totalOut,
            'totalCost'        => $totalCost,
        ])->layout('layouts.admin');
    }
}
