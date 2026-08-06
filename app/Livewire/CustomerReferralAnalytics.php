<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class CustomerReferralAnalytics extends Component
{
    public $search = '';

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;

        $query = DB::table('customers')
            ->leftJoin('bookings', 'bookings.customer_id', '=', 'customers.id')
            ->where('customers.marquee_id', $marqueeId)
            ->whereNotNull('customers.referred_by_name')
            ->where('customers.referred_by_name', '!=', '');

        if ($this->search) {
            $query->where('customers.referred_by_name', 'like', '%' . $this->search . '%');
        }

        $referrals = $query->select(
                'customers.referred_by_name as referrer_name',
                DB::raw('COUNT(DISTINCT customers.id) as referred_customers_count'),
                DB::raw('COUNT(bookings.id) as bookings_count'),
                DB::raw('SUM(CASE WHEN bookings.booking_status IN ("Reserved", "Confirmed") THEN bookings.grand_total ELSE 0 END) as total_revenue')
            )
            ->groupBy('customers.referred_by_name')
            ->orderBy('total_revenue', 'desc')
            ->orderBy('referred_customers_count', 'desc')
            ->get();

        $totalReferredCustomers = $referrals->sum('referred_customers_count');
        $totalBookings = $referrals->sum('bookings_count');
        $totalRevenue = $referrals->sum('total_revenue');

        return view('livewire.customer-referral-analytics', [
            'referrals' => $referrals,
            'totalReferredCustomers' => $totalReferredCustomers,
            'totalBookings' => $totalBookings,
            'totalRevenue' => $totalRevenue,
        ]);
    }
}
