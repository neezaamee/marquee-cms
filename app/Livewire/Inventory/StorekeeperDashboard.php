<?php

namespace App\Livewire\Inventory;

use App\Models\DepartmentStockIssue;
use App\Models\DepartmentStockRequest;
use App\Models\GoodsReceivingNote;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Marquee;
use Livewire\Component;

class StorekeeperDashboard extends Component
{
    public ?int $marqueeId = null;

    public function mount()
    {
        $user = auth()->user();
        $this->marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
    }

    public function render()
    {
        $marqueeId = $this->marqueeId ?: auth()->user()->getActiveMarqueeId();
        $marquee = $marqueeId ? Marquee::find($marqueeId) : null;

        // KPI Counts
        $totalItems = InventoryItem::where('marquee_id', $marqueeId)->where('status', 'Active')->count();
        $totalCategories = InventoryCategory::where('marquee_id', $marqueeId)->where('status', 'Active')->count();
        
        $pendingRequisitionsCount = DepartmentStockRequest::where('marquee_id', $marqueeId)
            ->where('status', 'Pending')
            ->count();

        $todayIssuesCount = DepartmentStockIssue::where('marquee_id', $marqueeId)
            ->whereDate('issue_date', today())
            ->count();

        // Pending department requests awaiting dispatch
        $pendingRequests = DepartmentStockRequest::with('department')
            ->where('marquee_id', $marqueeId)
            ->where('status', 'Pending')
            ->latest()
            ->take(5)
            ->get();

        // Recent Stock Dispatches
        $recentIssues = DepartmentStockIssue::with('department')
            ->where('marquee_id', $marqueeId)
            ->latest('issue_date')
            ->take(5)
            ->get();

        // Recent Goods Receiving Notes
        $recentGrns = GoodsReceivingNote::with('supplier')
            ->where('marquee_id', $marqueeId)
            ->latest('received_date')
            ->take(5)
            ->get();

        // Catalog items needing attention (reorder level or min stock)
        $itemsWithThresholds = InventoryItem::with(['category', 'unit'])
            ->where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->where('minimum_stock_level', '>', 0)
            ->orderBy('minimum_stock_level', 'desc')
            ->take(8)
            ->get();

        return view('livewire.inventory.storekeeper-dashboard', [
            'marquee' => $marquee,
            'totalItems' => $totalItems,
            'totalCategories' => $totalCategories,
            'pendingRequisitionsCount' => $pendingRequisitionsCount,
            'todayIssuesCount' => $todayIssuesCount,
            'pendingRequests' => $pendingRequests,
            'recentIssues' => $recentIssues,
            'recentGrns' => $recentGrns,
            'itemsWithThresholds' => $itemsWithThresholds,
        ]);
    }
}
