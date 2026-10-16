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
        $this->filterDateFrom = '';
        $this->filterDateTo   = '';
        $this->search         = '';
        $this->resetPage();
    }

    public function buildLedgerQuery($marqueeId, $branchId = null, $itemId = null, $filterType = null, $dateFrom = null, $dateTo = null, $search = null)
    {
        $query = InventoryStockLedger::where('inventory_stock_ledgers.marquee_id', $marqueeId)
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
            $query->where('inventory_stock_ledgers.branch_id', $branchId);
        }

        if ($itemId) {
            $query->where('inventory_stock_ledgers.item_id', $itemId);
        }

        if ($filterType) {
            $query->where('inventory_stock_ledgers.transaction_type', $filterType);
        }

        if ($dateFrom) {
            $query->whereDate('inventory_stock_ledgers.transaction_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('inventory_stock_ledgers.transaction_date', '<=', $dateTo);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('inventory_items.name', 'like', '%' . $search . '%')
                  ->orWhere('inventory_items.item_code', 'like', '%' . $search . '%');
            });
        }

        return $query;
    }

    public function calculateLedgerStats($marqueeId, $branchId = null, $itemId = null, $filterType = null, $dateFrom = null, $dateTo = null, $search = null): array
    {
        $statsQuery = InventoryStockLedger::where('inventory_stock_ledgers.marquee_id', $marqueeId)
            ->join('inventory_items', 'inventory_stock_ledgers.item_id', '=', 'inventory_items.id');

        if ($branchId) {
            $statsQuery->where('inventory_stock_ledgers.branch_id', $branchId);
        }
        if ($itemId) {
            $statsQuery->where('inventory_stock_ledgers.item_id', $itemId);
        }
        if ($dateFrom) {
            $statsQuery->whereDate('inventory_stock_ledgers.transaction_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $statsQuery->whereDate('inventory_stock_ledgers.transaction_date', '<=', $dateTo);
        }
        if ($filterType) {
            $statsQuery->where('inventory_stock_ledgers.transaction_type', $filterType);
        }
        if ($search) {
            $statsQuery->where(function ($q) use ($search) {
                $q->where('inventory_items.name', 'like', '%' . $search . '%')
                  ->orWhere('inventory_items.item_code', 'like', '%' . $search . '%');
            });
        }

        return [
            'totalIn'   => (float) (clone $statsQuery)->sum('inventory_stock_ledgers.qty_in'),
            'totalOut'  => (float) (clone $statsQuery)->sum('inventory_stock_ledgers.qty_out'),
            'totalCost' => (float) (clone $statsQuery)->sum('inventory_stock_ledgers.total_cost'),
        ];
    }

    public function getLedgerReportData($marqueeId, $branchId = null, $itemId = null, $filterType = null, $dateFrom = null, $dateTo = null, $search = null): array
    {
        $ledgers = $this->buildLedgerQuery($marqueeId, $branchId, $itemId, $filterType, $dateFrom, $dateTo, $search)
            ->orderBy('inventory_stock_ledgers.transaction_date', 'desc')
            ->orderBy('inventory_stock_ledgers.id', 'desc')
            ->get();

        $stats = $this->calculateLedgerStats($marqueeId, $branchId, $itemId, $filterType, $dateFrom, $dateTo, $search);

        return array_merge(['ledgers' => $ledgers], $stats);
    }

    public function exportExcel()
    {
        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId() ?: $user->marquee_id;
        $branchId = $user->branch_id && !$user->isSuperAdmin() ? $user->branch_id : $this->filterBranch;

        $ledgers = $this->buildLedgerQuery(
            $marqueeId,
            $branchId,
            $this->filterItem,
            $this->filterType,
            $this->filterDateFrom,
            $this->filterDateTo,
            $this->search
        )
            ->orderBy('inventory_stock_ledgers.transaction_date', 'desc')
            ->orderBy('inventory_stock_ledgers.id', 'desc')
            ->get();

        $filename = "Inventory_Stock_Ledger_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"{$filename}\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        return response()->stream(function () use ($ledgers) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            fputcsv($handle, ['INVENTORY STOCK LEDGER AUDIT REPORT']);
            fputcsv($handle, ['Generated At', date('Y-m-d H:i:s')]);
            fputcsv($handle, ['Generated By', auth()->user()->name ?? 'System']);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Date', 'Voucher / Ref', 'Branch', 'Item Code', 'Item Description',
                'Transaction Type', 'Qty In', 'Qty Out', 'Unit Price (PKR)', 'Total Cost (PKR)', 'Running Balance', 'Remarks', 'Logged By'
            ]);

            foreach ($ledgers as $l) {
                fputcsv($handle, [
                    $l->transaction_date ? \Carbon\Carbon::parse($l->transaction_date)->format('Y-m-d') : '—',
                    $l->reference_type ?? ($l->voucher_no ?? '—'),
                    $l->branch_name ?? '—',
                    $l->item_code,
                    $l->item_name,
                    $l->transaction_type,
                    $l->qty_in > 0 ? $l->qty_in : '0.00',
                    $l->qty_out > 0 ? $l->qty_out : '0.00',
                    number_format($l->unit_price, 2, '.', ''),
                    number_format($l->total_cost, 2, '.', ''),
                    $l->running_balance,
                    $l->reason ?: ($l->reference_type ?? ''),
                    $l->creator->name ?? 'System',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function render()
    {
        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId() ?: $user->marquee_id;
        $branchId = $user->branch_id && !$user->isSuperAdmin() ? $user->branch_id : $this->filterBranch;

        $ledger = $this->buildLedgerQuery(
            $marqueeId,
            $branchId,
            $this->filterItem,
            $this->filterType,
            $this->filterDateFrom,
            $this->filterDateTo,
            $this->search
        )
            ->orderBy('inventory_stock_ledgers.transaction_date', 'desc')
            ->orderBy('inventory_stock_ledgers.id', 'desc')
            ->paginate(25);

        $stats = $this->calculateLedgerStats(
            $marqueeId,
            $branchId,
            $this->filterItem,
            $this->filterType,
            $this->filterDateFrom,
            $this->filterDateTo,
            $this->search
        );

        $branches = Branch::where('marquee_id', $marqueeId)->orderBy('name')->get();
        $items    = InventoryItem::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->orderBy('name')
            ->get();

        $transactionTypes = [
            'Opening', 'GRN', 'PurchaseInvoice', 'Issue', 'Return', 'PurchaseReturn',
            'Adjustment', 'Wastage', 'Damage', 'Expiry',
        ];

        return view('livewire.inventory.stock-ledger-view', [
            'ledger'           => $ledger,
            'branches'         => $branches,
            'items'            => $items,
            'transactionTypes' => $transactionTypes,
            'totalIn'          => $stats['totalIn'],
            'totalOut'         => $stats['totalOut'],
            'totalCost'        => $stats['totalCost'],
        ])->layout('layouts.admin');
    }
}
