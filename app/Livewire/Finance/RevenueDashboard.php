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

        // 2. Calculate Realized vs Unearned Accounting Metrics
        // A. Earned & Recognized Revenue (from completed events)
        $totalRecognizedRevenue = (float) $bookings->where('is_revenue_recognized', true)->sum('revenue_recognized');

        // B. Customer Advance Liability (held as contract liabilities for upcoming events)
        $totalAdvanceLiabilityHeld = (float) $bookings->where('is_revenue_recognized', false)->sum('advance_received');

        // C. Accounts Receivable (unpaid balance on completed events)
        $totalAccountsReceivable = (float) $bookings->where('is_revenue_recognized', true)->sum('receivable_amount');

        // Held Deposits (Liability, not revenue)
        $securityDepositsHeld = (float) $bookings->where('deposit_status', 'Held')->sum('security_deposit');
        
        // Refunded Deposits
        $securityDepositsRefunded = (float) $bookings->sum('deposit_refunded_amount');
        
        // Deducted Deposits (Deductions due to damage become auxiliary income)
        $securityDepositsDeducted = (float) $bookings->sum('deposit_deducted_amount');

        // Total Billings (Commercial contract sum)
        $totalBillings = (float) $bookings->sum('grand_total');

        // 3. Payments Tracking
        $bookingIds = $bookings->pluck('id')->toArray();
        
        $totalPaymentsCollected = (float) BookingPayment::whereIn('booking_id', $bookingIds)
            ->whereIn('payment_type', ['advance', 'receivable_payment', 'security_deposit'])
            ->sum('amount');

        $totalRefundsDisbursed = (float) BookingPayment::whereIn('booking_id', $bookingIds)
            ->where('payment_type', 'refund')
            ->sum('amount');

        $netPaymentsCollected = max(0.00, $totalPaymentsCollected - $totalRefundsDisbursed);

        // 4. Payment Method breakdown
        $paymentMethodsBreakdown = BookingPayment::whereIn('booking_id', $bookingIds)
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get();

        // 5. Recent Payments
        $recentPayments = BookingPayment::whereIn('booking_id', $bookingIds)
            ->with(['booking.customer', 'recorder', 'account', 'journalVoucher'])
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        return view('livewire.finance.revenue-dashboard', [
            'totalRecognizedRevenue' => $totalRecognizedRevenue,
            'totalAdvanceLiabilityHeld' => $totalAdvanceLiabilityHeld,
            'totalAccountsReceivable' => $totalAccountsReceivable,
            'securityDepositsHeld' => $securityDepositsHeld,
            'securityDepositsRefunded' => $securityDepositsRefunded,
            'securityDepositsDeducted' => $securityDepositsDeducted,
            'totalBillings' => $totalBillings,
            'totalPaymentsCollected' => $netPaymentsCollected,
            'totalRefundsDisbursed' => $totalRefundsDisbursed,
            'paymentMethodsBreakdown' => $paymentMethodsBreakdown,
            'recentPayments' => $recentPayments,
        ]);
    }
}
