<?php

namespace App\Livewire;

use App\Models\Marquee;
use App\Models\Vendor;
use App\Models\VendorCommissionAgreement;
use App\Models\VendorSale;
use App\Models\VendorSettlement;
use Carbon\Carbon;
use Livewire\Component;

class VendorDashboard extends Component
{
    public function getMarqueeId(): ?int
    {
        $user = auth()->user();
        $id = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
        if (!$id && $user?->isSuperAdmin()) {
            return Marquee::first()?->id;
        }
        return $id;
    }

    public function render()
    {
        $user = auth()->user();
        $marqueeId = $this->getMarqueeId();

        $vendorQuery = Vendor::withoutGlobalScope('tenant');
        $salesBase = VendorSale::withoutGlobalScope('tenant');

        if ($marqueeId) {
            $vendorQuery->where('marquee_id', $marqueeId);
            $salesBase->where('marquee_id', $marqueeId);
        } elseif (!$user?->isSuperAdmin()) {
            $vendorQuery->whereRaw('1 = 0');
            $salesBase->whereRaw('1 = 0');
        }

        // 1. Metric Aggregations
        $totalVendors = (clone $vendorQuery)->count();
        $activeVendors = (clone $vendorQuery)->where('status', 'active')->count();

        $salesQuery = (clone $salesBase)->whereIn('status', ['confirmed', 'settled']);
        $totalSales = (float) $salesQuery->sum('sale_amount');
        $monthlySales = (float) (clone $salesQuery)->whereBetween('sale_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum('sale_amount');

        $totalCommission = (float) (clone $salesQuery)->sum('commission_amount');
        $monthlyCommission = (float) (clone $salesQuery)->whereBetween('sale_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum('commission_amount');

        $outstandingPayable = (float) (clone $vendorQuery)->get()->sum('current_balance');

        // 2. Recent Sales
        $recentSales = (clone $salesBase)
            ->with(['vendor', 'service', 'booking.customer'])
            ->orderBy('sale_date', 'desc')
            ->take(5)
            ->get();

        // 3. Top Commission Vendors
        $topVendors = (clone $vendorQuery)
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
        ])->layout('layouts.admin');
    }
}
