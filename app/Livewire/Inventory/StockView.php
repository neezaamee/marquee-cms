<?php

namespace App\Livewire\Inventory;

use App\Models\Branch;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class StockView extends Component
{
    use WithPagination;

    public $search = '';
    public $filterCategory = '';
    public $filterBranch = '';
    public $filterStatus = '';

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterCategory' => ['except' => ''],
        'filterBranch' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    public function mount()
    {
        $user = auth()->user();
        if ($user->branch_id && !$user->isSuperAdmin()) {
            $this->filterBranch = $user->branch_id;
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterCategory()
    {
        $this->resetPage();
    }

    public function updatingFilterBranch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    protected function getInwardSubquery($branchId)
    {
        return DB::table(function ($q) use ($branchId) {
            $grn = DB::table('goods_receiving_note_details')
                ->join('goods_receiving_notes', 'goods_receiving_notes.id', '=', 'goods_receiving_note_details.goods_receiving_note_id')
                ->whereNull('goods_receiving_notes.deleted_at')
                ->select('goods_receiving_note_details.item_id', 'goods_receiving_note_details.received_qty as qty');
            if ($branchId) {
                $grn->where('goods_receiving_notes.branch_id', $branchId);
            }

            $inv = DB::table('purchase_invoice_details')
                ->join('purchase_invoices', 'purchase_invoices.id', '=', 'purchase_invoice_details.purchase_invoice_id')
                ->where('purchase_invoices.status', 'Posted')
                ->whereNull('purchase_invoices.goods_receiving_note_id')
                ->whereNull('purchase_invoices.deleted_at')
                ->select('purchase_invoice_details.item_id', 'purchase_invoice_details.quantity as qty');
            if ($branchId) {
                $inv->where('purchase_invoices.branch_id', $branchId);
            }

            $q->fromSub($grn->unionAll($inv), 'all_inward');
        }, 'all_inward')
        ->whereColumn('all_inward.item_id', 'inventory_items.id');
    }

    protected function getOpeningSubquery($marqueeId, $branchId)
    {
        $q = DB::table('inventory_stock_ledgers')
            ->where('inventory_stock_ledgers.marquee_id', $marqueeId)
            ->where('inventory_stock_ledgers.transaction_type', 'Opening')
            ->whereColumn('inventory_stock_ledgers.item_id', 'inventory_items.id');

        if ($branchId) {
            $q->where('inventory_stock_ledgers.branch_id', $branchId);
        }

        return $q;
    }

    protected function getAdjustmentSubquery($marqueeId, $branchId)
    {
        $q = DB::table('inventory_stock_ledgers')
            ->where('inventory_stock_ledgers.marquee_id', $marqueeId)
            ->whereIn('inventory_stock_ledgers.transaction_type', ['Adjustment', 'Wastage', 'Damage', 'Expiry'])
            ->whereColumn('inventory_stock_ledgers.item_id', 'inventory_items.id');

        if ($branchId) {
            $q->where('inventory_stock_ledgers.branch_id', $branchId);
        }

        return $q;
    }

    public function buildStockQuery($marqueeId, $branchId, $search = null, $filterCategory = null, $filterStatus = null)
    {
        $query = InventoryItem::where('inventory_items.marquee_id', $marqueeId)
            ->with(['category', 'unit', 'brand']);

        $openingSubquery = $this->getOpeningSubquery($marqueeId, $branchId);
        $receivedSubquery = $this->getInwardSubquery($branchId);

        $returnedSubquery = DB::table('purchase_return_details')
            ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_details.purchase_return_id')
            ->where('purchase_returns.status', 'Posted')
            ->whereNull('purchase_returns.deleted_at')
            ->whereColumn('purchase_return_details.item_id', 'inventory_items.id');

        $issuedSubquery = DB::table('department_stock_issue_items')
            ->join('department_stock_issues', 'department_stock_issues.id', '=', 'department_stock_issue_items.department_stock_issue_id')
            ->whereNull('department_stock_issues.deleted_at')
            ->whereColumn('department_stock_issue_items.item_id', 'inventory_items.id');

        $deptReturnedSubquery = DB::table('department_stock_return_items')
            ->join('department_stock_returns', 'department_stock_returns.id', '=', 'department_stock_return_items.department_stock_return_id')
            ->where('department_stock_returns.status', 'Received')
            ->where('department_stock_return_items.status', 'Good')
            ->whereNull('department_stock_returns.deleted_at')
            ->whereColumn('department_stock_return_items.item_id', 'inventory_items.id');

        $adjustmentSubquery = $this->getAdjustmentSubquery($marqueeId, $branchId);

        if ($branchId) {
            $returnedSubquery->where('purchase_returns.branch_id', $branchId);
            $issuedSubquery->where('department_stock_issues.branch_id', $branchId);
            $deptReturnedSubquery->where('department_stock_returns.branch_id', $branchId);
        }

        $query->select('inventory_items.*')
            ->selectSub($openingSubquery->selectRaw('COALESCE(SUM(inventory_stock_ledgers.qty_in), 0)'), 'total_opening')
            ->selectSub($receivedSubquery->selectRaw('COALESCE(SUM(all_inward.qty), 0)'), 'total_received')
            ->selectSub($returnedSubquery->selectRaw('COALESCE(SUM(purchase_return_details.quantity), 0)'), 'total_returned')
            ->selectSub($issuedSubquery->selectRaw('COALESCE(SUM(department_stock_issue_items.quantity), 0)'), 'total_issued')
            ->selectSub($deptReturnedSubquery->selectRaw('COALESCE(SUM(department_stock_return_items.quantity), 0)'), 'total_dept_returned')
            ->selectSub($adjustmentSubquery->selectRaw('COALESCE(SUM(inventory_stock_ledgers.qty_in - inventory_stock_ledgers.qty_out), 0)'), 'total_adjusted');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('inventory_items.name', 'like', '%' . $search . '%')
                  ->orWhere('inventory_items.item_code', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filterCategory)) {
            $query->where('inventory_items.category_id', $filterCategory);
        }

        $stockFormula = '(COALESCE(total_opening, 0) + total_received - total_returned - total_issued + total_dept_returned + COALESCE(total_adjusted, 0))';
        if (!empty($filterStatus)) {
            switch ($filterStatus) {
                case 'out_of_stock':
                    $query->havingRaw("{$stockFormula} <= 0");
                    break;
                case 'low_stock':
                    $query->havingRaw("{$stockFormula} > 0 AND {$stockFormula} <= minimum_stock_level");
                    break;
                case 'reorder_required':
                    $query->havingRaw("{$stockFormula} > minimum_stock_level AND {$stockFormula} <= reorder_level");
                    break;
                case 'good':
                    $query->havingRaw("{$stockFormula} > reorder_level");
                    break;
            }
        }

        return $query;
    }

    public function calculateStockStats($marqueeId, $branchId, $search = null, $filterCategory = null): array
    {
        $summaryQuery = InventoryItem::where('inventory_items.marquee_id', $marqueeId);
        if (!empty($search)) {
            $summaryQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('item_code', 'like', '%' . $search . '%');
            });
        }
        if (!empty($filterCategory)) {
            $summaryQuery->where('category_id', $filterCategory);
        }

        $opSubSummary = $this->getOpeningSubquery($marqueeId, $branchId);
        $recSubSummary = $this->getInwardSubquery($branchId);
        $retSubSummary = DB::table('purchase_return_details')
            ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_details.purchase_return_id')
            ->where('purchase_returns.status', 'Posted')
            ->whereNull('purchase_returns.deleted_at')
            ->whereColumn('purchase_return_details.item_id', 'inventory_items.id');

        $issSubSummary = DB::table('department_stock_issue_items')
            ->join('department_stock_issues', 'department_stock_issues.id', '=', 'department_stock_issue_items.department_stock_issue_id')
            ->whereNull('department_stock_issues.deleted_at')
            ->whereColumn('department_stock_issue_items.item_id', 'inventory_items.id');

        $deptRetSubSummary = DB::table('department_stock_return_items')
            ->join('department_stock_returns', 'department_stock_returns.id', '=', 'department_stock_return_items.department_stock_return_id')
            ->where('department_stock_returns.status', 'Received')
            ->where('department_stock_return_items.status', 'Good')
            ->whereNull('department_stock_returns.deleted_at')
            ->whereColumn('department_stock_return_items.item_id', 'inventory_items.id');

        $adjSubSummary = $this->getAdjustmentSubquery($marqueeId, $branchId);

        if ($branchId) {
            $retSubSummary->where('purchase_returns.branch_id', $branchId);
            $issSubSummary->where('department_stock_issues.branch_id', $branchId);
            $deptRetSubSummary->where('department_stock_returns.branch_id', $branchId);
        }

        $summaryQuery->select(
            'inventory_items.id',
            'inventory_items.reorder_level',
            'inventory_items.minimum_stock_level',
            'inventory_items.average_cost',
            'inventory_items.last_purchase_cost',
            'inventory_items.default_purchase_rate'
        )
            ->selectSub($opSubSummary->selectRaw('COALESCE(SUM(inventory_stock_ledgers.qty_in), 0)'), 'total_opening')
            ->selectSub($recSubSummary->selectRaw('COALESCE(SUM(all_inward.qty), 0)'), 'total_received')
            ->selectSub($retSubSummary->selectRaw('COALESCE(SUM(purchase_return_details.quantity), 0)'), 'total_returned')
            ->selectSub($issSubSummary->selectRaw('COALESCE(SUM(department_stock_issue_items.quantity), 0)'), 'total_issued')
            ->selectSub($deptRetSubSummary->selectRaw('COALESCE(SUM(department_stock_return_items.quantity), 0)'), 'total_dept_returned')
            ->selectSub($adjSubSummary->selectRaw('COALESCE(SUM(inventory_stock_ledgers.qty_in - inventory_stock_ledgers.qty_out), 0)'), 'total_adjusted');

        $allSummaryItems = $summaryQuery->get();

        $stats = [
            'total'             => $allSummaryItems->count(),
            'good'              => 0,
            'reorder'           => 0,
            'low'               => 0,
            'out'               => 0,
            'total_stock_qty'   => 0.0,
            'total_stock_value' => 0.0,
        ];

        foreach ($allSummaryItems as $si) {
            $stock = (float)$si->total_opening + (float)$si->total_received - (float)$si->total_returned - (float)$si->total_issued + (float)$si->total_dept_returned + (float)$si->total_adjusted;
            $unitCost = (float)($si->average_cost > 0 ? $si->average_cost : ($si->last_purchase_cost > 0 ? $si->last_purchase_cost : $si->default_purchase_rate));

            if ($stock > 0) {
                $stats['total_stock_qty'] += $stock;
                $stats['total_stock_value'] += ($stock * $unitCost);
            }

            if ($stock <= 0) {
                $stats['out']++;
            } elseif ($stock <= $si->minimum_stock_level) {
                $stats['low']++;
            } elseif ($stock <= $si->reorder_level) {
                $stats['reorder']++;
            } else {
                $stats['good']++;
            }
        }

        return $stats;
    }

    public function getStockReportData($marqueeId, $branchId, $search = null, $filterCategory = null, $filterStatus = null): array
    {
        $items = $this->buildStockQuery($marqueeId, $branchId, $search, $filterCategory, $filterStatus)->get();
        $stats = $this->calculateStockStats($marqueeId, $branchId, $search, $filterCategory);
        return [
            'items' => $items,
            'stats' => $stats,
        ];
    }

    public function exportExcel()
    {
        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId() ?: $user->marquee_id;
        $branchId = $user->branch_id && !$user->isSuperAdmin() ? $user->branch_id : $this->filterBranch;

        $items = $this->buildStockQuery($marqueeId, $branchId, $this->search, $this->filterCategory, $this->filterStatus)->get();
        $filename = "Inventory_Stock_Report_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"{$filename}\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        return response()->stream(function () use ($items) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            fputcsv($handle, ['INVENTORY STOCK VALUATION & STATUS REPORT']);
            fputcsv($handle, ['Generated At', date('Y-m-d H:i:s')]);
            fputcsv($handle, ['Generated By', auth()->user()->name ?? 'System']);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Item Code', 'Item Name', 'Category', 'Unit', 'Brand',
                'Min Stock', 'Reorder Level',
                'Opening Stock', 'Purchased Qty', 'Returned Qty', 'Net Issued Qty', 'Adjustments',
                'Current Stock', 'Unit Cost (PKR)', 'Total Value (PKR)', 'Stock Status'
            ]);

            foreach ($items as $item) {
                $opening = (float) ($item->total_opening ?? 0);
                $received = (float) ($item->total_received ?? 0);
                $returned = (float) ($item->total_returned ?? 0);
                $netIssued = (float) (($item->total_issued ?? 0) - ($item->total_dept_returned ?? 0));
                $adjusted = (float) ($item->total_adjusted ?? 0);
                $stock = $opening + $received - $returned - $netIssued + $adjusted;

                $unitCost = (float)($item->average_cost > 0 ? $item->average_cost : ($item->last_purchase_cost > 0 ? $item->last_purchase_cost : $item->default_purchase_rate));
                $valuation = max(0, $stock) * $unitCost;

                if ($stock <= 0) {
                    $status = 'Out of Stock';
                } elseif ($stock <= $item->minimum_stock_level) {
                    $status = 'Critical Low';
                } elseif ($stock <= $item->reorder_level) {
                    $status = 'Reorder Required';
                } else {
                    $status = 'Healthy';
                }

                fputcsv($handle, [
                    $item->item_code,
                    $item->name,
                    $item->category->name ?? '—',
                    $item->unit->short_code ?? 'Pcs',
                    $item->brand->name ?? 'Generic',
                    $item->minimum_stock_level,
                    $item->reorder_level,
                    $opening,
                    $received,
                    $returned,
                    $netIssued,
                    $adjusted,
                    $stock,
                    number_format($unitCost, 2, '.', ''),
                    number_format($valuation, 2, '.', ''),
                    $status,
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

        $items = $this->buildStockQuery($marqueeId, $branchId, $this->search, $this->filterCategory, $this->filterStatus)->paginate(15);
        $stats = $this->calculateStockStats($marqueeId, $branchId, $this->search, $this->filterCategory);

        $categories = InventoryCategory::where('marquee_id', $marqueeId)->where('status', 'Active')->orderBy('name')->get();
        $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('name')->get();

        return view('livewire.inventory.stock-view', compact('items', 'categories', 'branches', 'stats'))
            ->layout('layouts.admin');
    }
}
