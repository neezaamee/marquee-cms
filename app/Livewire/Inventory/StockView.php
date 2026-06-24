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

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $user = auth()->user();

        // 1. Base query for active/all items
        $query = InventoryItem::where('marquee_id', $marqueeId)
            ->with(['category', 'unit', 'brand']);

        // 2. Subquery for Received Qty from GRNs
        $receivedSubquery = DB::table('goods_receiving_note_details')
            ->join('goods_receiving_notes', 'goods_receiving_notes.id', '=', 'goods_receiving_note_details.goods_receiving_note_id')
            ->whereNull('goods_receiving_notes.deleted_at')
            ->whereColumn('goods_receiving_note_details.item_id', 'inventory_items.id');

        // 3. Subquery for Returned Qty from Purchase Returns
        $returnedSubquery = DB::table('purchase_return_details')
            ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_details.purchase_return_id')
            ->where('purchase_returns.status', 'Posted')
            ->whereNull('purchase_returns.deleted_at')
            ->whereColumn('purchase_return_details.item_id', 'inventory_items.id');

        // Apply branch filter to subqueries if set
        $branchId = $user->branch_id && !$user->isSuperAdmin() ? $user->branch_id : $this->filterBranch;

        if ($branchId) {
            $receivedSubquery->where('goods_receiving_notes.branch_id', $branchId);
            $returnedSubquery->where('purchase_returns.branch_id', $branchId);
        }

        // Select attributes
        $query->select('inventory_items.*')
            ->selectSub($receivedSubquery->selectRaw('COALESCE(SUM(goods_receiving_note_details.received_qty), 0)'), 'total_received')
            ->selectSub($returnedSubquery->selectRaw('COALESCE(SUM(purchase_return_details.quantity), 0)'), 'total_returned');

        // Apply filters
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('item_code', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->filterCategory)) {
            $query->where('category_id', $this->filterCategory);
        }

        // Apply Stock Status filter in Having clause
        if (!empty($this->filterStatus)) {
            switch ($this->filterStatus) {
                case 'out_of_stock':
                    $query->havingRaw('(total_received - total_returned) <= 0');
                    break;
                case 'low_stock':
                    $query->havingRaw('(total_received - total_returned) > 0 AND (total_received - total_returned) <= minimum_stock_level');
                    break;
                case 'reorder_required':
                    $query->havingRaw('(total_received - total_returned) > minimum_stock_level AND (total_received - total_returned) <= reorder_level');
                    break;
                case 'good':
                    $query->havingRaw('(total_received - total_returned) > reorder_level');
                    break;
            }
        }

        $items = $query->paginate(10);

        // Calculate summary stats (fetched across all items matching search/category/branch filters but unrestricted by pagination/status)
        $summaryQuery = InventoryItem::where('marquee_id', $marqueeId);
        if (!empty($this->search)) {
            $summaryQuery->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('item_code', 'like', '%' . $this->search . '%');
            });
        }
        if (!empty($this->filterCategory)) {
            $summaryQuery->where('category_id', $this->filterCategory);
        }

        // Rebuild subqueries for summary calculations
        $recSubSummary = DB::table('goods_receiving_note_details')
            ->join('goods_receiving_notes', 'goods_receiving_notes.id', '=', 'goods_receiving_note_details.goods_receiving_note_id')
            ->whereNull('goods_receiving_notes.deleted_at')
            ->whereColumn('goods_receiving_note_details.item_id', 'inventory_items.id');

        $retSubSummary = DB::table('purchase_return_details')
            ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_details.purchase_return_id')
            ->where('purchase_returns.status', 'Posted')
            ->whereNull('purchase_returns.deleted_at')
            ->whereColumn('purchase_return_details.item_id', 'inventory_items.id');

        if ($branchId) {
            $recSubSummary->where('goods_receiving_notes.branch_id', $branchId);
            $retSubSummary->where('purchase_returns.branch_id', $branchId);
        }

        $summaryQuery->select('inventory_items.id', 'inventory_items.reorder_level', 'inventory_items.minimum_stock_level')
            ->selectSub($recSubSummary->selectRaw('COALESCE(SUM(goods_receiving_note_details.received_qty), 0)'), 'total_received')
            ->selectSub($retSubSummary->selectRaw('COALESCE(SUM(purchase_return_details.quantity), 0)'), 'total_returned');

        $allSummaryItems = $summaryQuery->get();

        $stats = [
            'total' => $allSummaryItems->count(),
            'good' => 0,
            'reorder' => 0,
            'low' => 0,
            'out' => 0,
        ];

        foreach ($allSummaryItems as $si) {
            $stock = $si->total_received - $si->total_returned;
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

        // Select options
        $categories = InventoryCategory::where('marquee_id', $marqueeId)->where('status', 'Active')->orderBy('name')->get();
        $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('name')->get();

        return view('livewire.inventory.stock-view', compact('items', 'categories', 'branches', 'stats'))
            ->layout('layouts.admin');
    }
}
