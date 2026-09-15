<?php

namespace App\Livewire\Purchases;

use App\Models\Branch;
use App\Models\GoodsReceivingNote;
use App\Models\InventoryItem;
use App\Models\InventoryStockLedger;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\SupplierCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PurchaseDashboard extends Component
{
    public $filterRange = 'this_month';
    public $customDateFrom = '';
    public $customDateTo = '';
    public $filterBranch = '';
    public $filterSupplier = '';

    protected $queryString = [
        'filterRange' => ['except' => 'this_month'],
        'filterBranch' => ['except' => ''],
        'filterSupplier' => ['except' => ''],
    ];

    public function mount()
    {
        $user = auth()->user();
        if ($user->branch_id && !$user->isSuperAdmin()) {
            $this->filterBranch = $user->branch_id;
        }

        $this->customDateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->customDateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function setFilterRange(string $range)
    {
        $this->filterRange = $range;
    }

    protected function getDateRange(): array
    {
        switch ($this->filterRange) {
            case 'today':
                return [now()->startOfDay(), now()->endOfDay(), 'Today'];
            case 'this_week':
                return [now()->startOfWeek(), now()->endOfWeek(), 'This Week'];
            case 'this_month':
                return [now()->startOfMonth(), now()->endOfMonth(), 'This Month'];
            case 'this_quarter':
                return [now()->startOfQuarter(), now()->endOfQuarter(), 'This Quarter'];
            case 'this_year':
                return [now()->startOfYear(), now()->endOfYear(), 'This Year'];
            case 'last_30_days':
                return [now()->subDays(30)->startOfDay(), now()->endOfDay(), 'Last 30 Days'];
            case 'custom':
                $from = $this->customDateFrom ? Carbon::parse($this->customDateFrom)->startOfDay() : now()->startOfMonth();
                $to = $this->customDateTo ? Carbon::parse($this->customDateTo)->endOfDay() : now()->endOfMonth();
                return [$from, $to, 'Custom Range'];
            default:
                return [now()->startOfMonth(), now()->endOfMonth(), 'This Month'];
        }
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $user = auth()->user();
        $branchId = $this->filterBranch ?: ($user->branch_id && !$user->isSuperAdmin() ? $user->branch_id : null);

        [$startDate, $endDate, $periodLabel] = $this->getDateRange();
        $startStr = $startDate->format('Y-m-d');
        $endStr = $endDate->format('Y-m-d');

        // =========================================================================
        // 1. PURCHASE INVOICES (SPEND & AP)
        // =========================================================================
        $invoiceQuery = PurchaseInvoice::where('marquee_id', $marqueeId)
            ->whereBetween('purchase_date', [$startStr, $endStr])
            ->where('status', '!=', 'Cancelled');

        if ($branchId) {
            $invoiceQuery->where('branch_id', $branchId);
        }
        if ($this->filterSupplier) {
            $invoiceQuery->where('supplier_id', $this->filterSupplier);
        }

        $totalPurchasesNet = (float) (clone $invoiceQuery)->sum('net_amount');
        $totalPurchasesTax = (float) (clone $invoiceQuery)->sum('tax');
        $totalPurchasesGross = (float) (clone $invoiceQuery)->sum('gross_amount');
        $totalInvoicesCount = (clone $invoiceQuery)->count();
        $postedInvoicesCount = (clone $invoiceQuery)->where('status', 'Posted')->count();
        $draftInvoicesCount = (clone $invoiceQuery)->where('status', 'Draft')->count();

        // =========================================================================
        // 2. PURCHASE ORDERS (FULFILLMENT PIPELINE)
        // =========================================================================
        $poQuery = PurchaseOrder::where('marquee_id', $marqueeId)
            ->whereBetween('order_date', [$startStr, $endStr]);

        if ($branchId) {
            $poQuery->where('branch_id', $branchId);
        }
        if ($this->filterSupplier) {
            $poQuery->where('supplier_id', $this->filterSupplier);
        }

        $totalPOsCount = (clone $poQuery)->count();

        // Pending POs awaiting delivery/fulfillment (Draft, Approved, Partially Received)
        $pendingPOsQuery = (clone $poQuery)->whereIn('status', ['Draft', 'Approved', 'Partially Received']);
        $pendingPOsCount = (clone $pendingPOsQuery)->count();

        $pendingPOIds = (clone $pendingPOsQuery)->pluck('id');
        $pendingPOsAmount = (float) DB::table('purchase_order_details')
            ->whereIn('purchase_order_id', $pendingPOIds)
            ->sum('amount');

        $completedPOsCount = (clone $poQuery)->where('status', 'Completed')->count();

        // PO Status Pipeline breakdown
        $statusCounts = [
            'Draft' => (clone $poQuery)->where('status', 'Draft')->count(),
            'Approved' => (clone $poQuery)->where('status', 'Approved')->count(),
            'Partially Received' => (clone $poQuery)->where('status', 'Partially Received')->count(),
            'Completed' => (clone $poQuery)->where('status', 'Completed')->count(),
            'Cancelled' => (clone $poQuery)->where('status', 'Cancelled')->count(),
        ];

        // =========================================================================
        // 3. GOODS RECEIVING (GRN)
        // =========================================================================
        $grnQuery = GoodsReceivingNote::where('marquee_id', $marqueeId)
            ->whereBetween('received_date', [$startStr, $endStr]);

        if ($branchId) {
            $grnQuery->where('branch_id', $branchId);
        }
        if ($this->filterSupplier) {
            $grnQuery->where('supplier_id', $this->filterSupplier);
        }

        $totalGrnsCount = (clone $grnQuery)->count();
        $grnIds = (clone $grnQuery)->pluck('id');
        $totalReceivedQuantity = (float) DB::table('goods_receiving_note_details')
            ->whereIn('goods_receiving_note_id', $grnIds)
            ->sum('received_qty');

        // =========================================================================
        // 4. PURCHASE RETURNS
        // =========================================================================
        $returnQuery = PurchaseReturn::where('marquee_id', $marqueeId)
            ->whereBetween('return_date', [$startStr, $endStr])
            ->where('status', '!=', 'Cancelled');

        if ($branchId) {
            $returnQuery->where('branch_id', $branchId);
        }
        if ($this->filterSupplier) {
            $returnQuery->where('supplier_id', $this->filterSupplier);
        }

        $totalReturnsNet = (float) (clone $returnQuery)->sum('net_amount');
        $totalReturnsCount = (clone $returnQuery)->count();

        // Net Procurement Outflow (Net Invoices - Net Returns)
        $effectiveProcurementSpend = max(0, $totalPurchasesNet - $totalReturnsNet);

        // =========================================================================
        // 5. SUPPLIERS & OUTSTANDING LIABILITIES (AP)
        // =========================================================================
        $supplierQuery = Supplier::where('marquee_id', $marqueeId);
        $totalSuppliersCount = (clone $supplierQuery)->count();
        $activeSuppliersCount = (clone $supplierQuery)->where('status', 'Active')->count();

        // Calculate total outstanding payables across active suppliers
        $suppliersList = (clone $supplierQuery)->where('status', 'Active')->get();
        $totalOutstandingPayables = 0.0;
        foreach ($suppliersList as $supp) {
            $bal = $supp->current_balance;
            if ($bal > 0) {
                $totalOutstandingPayables += $bal;
            }
        }

        // =========================================================================
        // 6. SPEND BY SUPPLIER CATEGORY
        // =========================================================================
        $categoryBreakdown = DB::table('purchase_invoices')
            ->join('suppliers', 'suppliers.id', '=', 'purchase_invoices.supplier_id')
            ->join('supplier_supplier_category', 'supplier_supplier_category.supplier_id', '=', 'suppliers.id')
            ->join('supplier_categories', 'supplier_categories.id', '=', 'supplier_supplier_category.supplier_category_id')
            ->where('purchase_invoices.marquee_id', $marqueeId)
            ->whereBetween('purchase_invoices.purchase_date', [$startStr, $endStr])
            ->where('purchase_invoices.status', '!=', 'Cancelled')
            ->when($branchId, fn($q) => $q->where('purchase_invoices.branch_id', $branchId))
            ->when($this->filterSupplier, fn($q) => $q->where('purchase_invoices.supplier_id', $this->filterSupplier))
            ->select('supplier_categories.name as category_name', DB::raw('SUM(purchase_invoices.net_amount) as total_spend'), DB::raw('COUNT(DISTINCT purchase_invoices.id) as invoice_count'))
            ->groupBy('supplier_categories.id', 'supplier_categories.name')
            ->orderByDesc('total_spend')
            ->take(6)
            ->get();

        // =========================================================================
        // 7. 6-MONTH PROCUREMENT SPEND TREND
        // =========================================================================
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth()->format('Y-m-d');
            $monthEnd = now()->subMonths($i)->endOfMonth()->format('Y-m-d');
            $monthLabel = now()->subMonths($i)->format('M Y');

            $mInvoice = (float) PurchaseInvoice::where('marquee_id', $marqueeId)
                ->whereBetween('purchase_date', [$monthStart, $monthEnd])
                ->where('status', '!=', 'Cancelled')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->when($this->filterSupplier, fn($q) => $q->where('supplier_id', $this->filterSupplier))
                ->sum('net_amount');

            $mReturn = (float) PurchaseReturn::where('marquee_id', $marqueeId)
                ->whereBetween('return_date', [$monthStart, $monthEnd])
                ->where('status', '!=', 'Cancelled')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->when($this->filterSupplier, fn($q) => $q->where('supplier_id', $this->filterSupplier))
                ->sum('net_amount');

            $monthlyTrend[] = [
                'month' => $monthLabel,
                'invoices' => $mInvoice,
                'returns' => $mReturn,
                'net' => max(0, $mInvoice - $mReturn),
            ];
        }

        // =========================================================================
        // 8. OPERATIONAL ACTION LISTS
        // =========================================================================
        // Recent Purchase Orders
        $recentOrders = PurchaseOrder::where('marquee_id', $marqueeId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($this->filterSupplier, fn($q) => $q->where('supplier_id', $this->filterSupplier))
            ->with(['supplier', 'branch'])
            ->latest('order_date')
            ->latest('id')
            ->take(5)
            ->get();

        // Recent Invoices
        $recentInvoices = PurchaseInvoice::where('marquee_id', $marqueeId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($this->filterSupplier, fn($q) => $q->where('supplier_id', $this->filterSupplier))
            ->with(['supplier', 'branch'])
            ->latest('purchase_date')
            ->latest('id')
            ->take(5)
            ->get();

        // Top Suppliers by Spend in this period
        $topSuppliers = DB::table('purchase_invoices')
            ->join('suppliers', 'suppliers.id', '=', 'purchase_invoices.supplier_id')
            ->where('purchase_invoices.marquee_id', $marqueeId)
            ->whereBetween('purchase_invoices.purchase_date', [$startStr, $endStr])
            ->where('purchase_invoices.status', '!=', 'Cancelled')
            ->when($branchId, fn($q) => $q->where('purchase_invoices.branch_id', $branchId))
            ->select(
                'suppliers.id',
                'suppliers.name',
                'suppliers.supplier_code',
                'suppliers.mobile_number',
                DB::raw('COUNT(purchase_invoices.id) as invoice_count'),
                DB::raw('SUM(purchase_invoices.net_amount) as total_purchases')
            )
            ->groupBy('suppliers.id', 'suppliers.name', 'suppliers.supplier_code', 'suppliers.mobile_number')
            ->orderByDesc('total_purchases')
            ->take(5)
            ->get();

        // Attach live balance to top suppliers
        $topSuppliersWithBalance = $topSuppliers->map(function ($s) {
            $suppModel = Supplier::find($s->id);
            $s->current_balance = $suppModel ? $suppModel->current_balance : 0.0;
            return $s;
        });

        // =========================================================================
        // 9. CRITICAL REORDER & LOW STOCK ALERTS
        // =========================================================================
        // Fetch active items with reorder_level > 0
        $itemsWithReorder = InventoryItem::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->where('reorder_level', '>', 0)
            ->with('unit')
            ->take(30)
            ->get();

        $reorderAlerts = collect();
        foreach ($itemsWithReorder as $invItem) {
            // Get current stock balance from last ledger
            $ledgerQuery = InventoryStockLedger::where('marquee_id', $marqueeId)
                ->where('item_id', $invItem->id);
            if ($branchId) {
                $ledgerQuery->where('branch_id', $branchId);
            }
            $lastEntry = $ledgerQuery->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->first();
            $currentStock = $lastEntry ? (float) $lastEntry->running_balance : 0.0;

            if ($currentStock <= $invItem->reorder_level) {
                $reorderAlerts->push([
                    'id' => $invItem->id,
                    'name' => $invItem->name,
                    'item_code' => $invItem->item_code,
                    'unit' => $invItem->unit->short_code ?? 'Pcs',
                    'current_stock' => $currentStock,
                    'reorder_level' => (float) $invItem->reorder_level,
                    'deficit' => max(0, (float) $invItem->reorder_level - $currentStock),
                ]);
            }

            if ($reorderAlerts->count() >= 6) {
                break;
            }
        }

        // Available filter dropdown options
        $branches = Branch::where('marquee_id', $marqueeId)->get();
        $allSuppliers = Supplier::where('marquee_id', $marqueeId)->orderBy('name')->get();

        return view('livewire.purchases.purchase-dashboard', [
            'filterRange' => $this->filterRange,
            'customDateFrom' => $this->customDateFrom,
            'customDateTo' => $this->customDateTo,
            'filterBranch' => $this->filterBranch,
            'filterSupplier' => $this->filterSupplier,
            'periodLabel' => $periodLabel,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalPurchasesNet' => $totalPurchasesNet,
            'totalPurchasesGross' => $totalPurchasesGross,
            'totalPurchasesTax' => $totalPurchasesTax,
            'totalInvoicesCount' => $totalInvoicesCount,
            'postedInvoicesCount' => $postedInvoicesCount,
            'draftInvoicesCount' => $draftInvoicesCount,
            'totalPOsCount' => $totalPOsCount,
            'pendingPOsCount' => $pendingPOsCount,
            'pendingPOsAmount' => $pendingPOsAmount,
            'completedPOsCount' => $completedPOsCount,
            'statusCounts' => $statusCounts,
            'totalGrnsCount' => $totalGrnsCount,
            'totalReceivedQuantity' => $totalReceivedQuantity,
            'totalReturnsNet' => $totalReturnsNet,
            'totalReturnsCount' => $totalReturnsCount,
            'effectiveProcurementSpend' => $effectiveProcurementSpend,
            'totalSuppliersCount' => $totalSuppliersCount,
            'activeSuppliersCount' => $activeSuppliersCount,
            'totalOutstandingPayables' => $totalOutstandingPayables,
            'categoryBreakdown' => $categoryBreakdown,
            'monthlyTrend' => $monthlyTrend,
            'recentOrders' => $recentOrders,
            'recentInvoices' => $recentInvoices,
            'topSuppliers' => $topSuppliersWithBalance,
            'reorderAlerts' => $reorderAlerts,
            'branches' => $branches,
            'allSuppliers' => $allSuppliers,
        ])->layout('layouts.admin');
    }
}
