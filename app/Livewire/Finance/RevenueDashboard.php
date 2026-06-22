<?php

namespace App\Livewire\Finance;

use App\Models\Booking;
use App\Models\BookingPayment;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueDashboard extends Component
{
    public $filterRange = '90'; // 30, 90, 365, all

    public function updatedFilterRange()
    {
        // Re-renders automatically when filterRange updates
    }

    public function render()
    {
        // 1. Determine date filter
        $query = Booking::whereNotIn('booking_status', ['Cancelled', 'Rejected']);
        
        if ($this->filterRange !== 'all') {
            $days = intval($this->filterRange);
            $query->where('booking_date', '>=', Carbon::now()->subDays($days));
        }

        // Clone query for computing stats
        $bookings = $query->get();

        // 2. Calculate Stats
        // Direct Revenue = Subtotal + Tax (excludes refundable security deposits)
        $totalDirectRevenue = $bookings->sum(function($b) {
            return $b->subtotal + $b->tax_amount;
        });

        // Held Deposits (Liability, not revenue)
        $securityDepositsHeld = $bookings->where('deposit_status', 'Held')->sum('security_deposit');
        
        // Refunded Deposits
        $securityDepositsRefunded = $bookings->sum('deposit_refunded_amount');
        
        // Deducted Deposits (Deductions due to damage become auxiliary income)
        $securityDepositsDeducted = $bookings->sum('deposit_deducted_amount');

        // Total Billings (Direct Revenue + Security Deposits currently held/retained)
        $totalBillings = $bookings->sum('grand_total');

        // 3. Payments Tracking
        // Query payments through scoped bookings
        $bookingIds = $bookings->pluck('id')->toArray();
        
        $totalPaymentsCollected = BookingPayment::whereIn('booking_id', $bookingIds)->sum('amount');
        
        // Outstanding Balance (Total Billings - Payments collected)
        $totalOutstanding = max(0, $totalBillings - $totalPaymentsCollected);

        // 4. Payment Method breakdown
        $paymentMethodsBreakdown = BookingPayment::whereIn('booking_id', $bookingIds)
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get();

        // 5. Recent Payments
        $recentPayments = BookingPayment::whereIn('booking_id', $bookingIds)
            ->with(['booking.customer', 'recorder'])
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        return view('livewire.finance.revenue-dashboard', [
            'totalDirectRevenue' => $totalDirectRevenue,
            'securityDepositsHeld' => $securityDepositsHeld,
            'securityDepositsRefunded' => $securityDepositsRefunded,
            'securityDepositsDeducted' => $securityDepositsDeducted,
            'totalPaymentsCollected' => $totalPaymentsCollected,
            'totalOutstanding' => $totalOutstanding,
            'paymentMethodsBreakdown' => $paymentMethodsBreakdown,
            'recentPayments' => $recentPayments,
        ]);
    }
}
