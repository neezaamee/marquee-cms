<?php

namespace App\Livewire;

use App\Models\Vendor;
use App\Models\VendorCommissionAgreement;
use App\Models\VendorSale;
use App\Models\VendorSettlement;
use Carbon\Carbon;
use Livewire\Component;

class VendorDashboard extends Component
{
    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;

        // 1. Metric Aggregations
        $totalVendors = Vendor::where('marquee_id', $marqueeId)->count();
        $activeVendors = Vendor::where('marquee_id', $marqueeId)->where('status', 'active')->count();

        $salesQuery = VendorSale::where('marquee_id', $marqueeId)->whereIn('status', ['confirmed', 'settled']);
        $totalSales = (float) $salesQuery->sum('sale_amount');
        $monthlySales = (float) (clone $salesQuery)->whereBetween('sale_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum('sale_amount');

        $totalCommission = (float) (clone $salesQuery)->sum('commission_amount');
        $monthlyCommission = (float) (clone $salesQuery)->whereBetween('sale_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum('commission_amount');

        $totalSettled = (float) VendorSettlement::where('marquee_id', $marqueeId)->where('status', 'fully_settled')->sum('paid_amount');
        $totalNetPayable = (float) (clone $salesQuery)->sum('vendor_net_amount');
        $outstandingPayable = max(0.00, $totalNetPayable - $totalSettled);

        // 2. Recent Sales
        $recentSales = VendorSale::where('marquee_id', $marqueeId)
            ->with(['vendor', 'service', 'booking.customer'])
            ->orderBy('sale_date', 'desc')
            ->take(5)
            ->get();

        // 3. Top Commission Vendors
        $topVendors = Vendor::where('marquee_id', $marqueeId)
            ->withCount(['sales as total_sales_count' => function ($q) {
                $q->whereIn('status', ['confirmed', 'settled']);
            }])
            ->withSum(['sales as total_commission_generated' => function ($q) {
                $q->whereIn('status', ['confirmed', 'settled']);
            }], 'commission_amount')
            ->orderBy('total_commission_generated', 'desc')
            ->take(5)
            ->get();

        return view('livewire.vendor-dashboard', [
            'totalVendors' => $totalVendors,
            'activeVendors' => $activeVendors,
            'totalSales' => $totalSales,
            'monthlySales' => $monthlySales,
            'totalCommission' => $totalCommission,
            'monthlyCommission' => $monthlyCommission,
            'outstandingPayable' => $outstandingPayable,
            'recentSales' => $recentSales,
            'topVendors' => $topVendors,
        ]);
    }
}
