<?php

namespace App\Livewire\Finance;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\CashBankAccount;
use App\Models\Expense;
use App\Models\JournalVoucher;
use App\Models\Marquee;
use App\Models\PaymentVoucher;
use Livewire\Component;

class AccountantDashboard extends Component
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

        // Cash & Bank Balances
        $cashBankAccounts = CashBankAccount::with('account')
            ->where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->get();

        $totalCashBankBalance = $cashBankAccounts->sum(function ($acc) {
            return $acc->account ? (float) $acc->account->current_balance : 0.0;
        });

        // Stage 2 Unposted Payments Queue
        $pendingPaymentsBase = BookingPayment::whereHas('booking', fn($q) => $q->where('marquee_id', $marqueeId))
            ->whereIn('status', ['pending_posting', 'received']);

        $pendingPaymentsCount = (clone $pendingPaymentsBase)->count();
        $pendingPaymentsTotal = (clone $pendingPaymentsBase)->sum('amount');

        // Today's Collections
        $todayCollectionsTotal = BookingPayment::whereHas('booking', fn($q) => $q->where('marquee_id', $marqueeId))
            ->where('status', 'posted')
            ->whereDate('payment_date', today())
            ->sum('amount');

        // Recent Payments Awaiting Verification
        $recentPendingPayments = BookingPayment::with(['booking.customer', 'booking.hall'])
            ->whereHas('booking', fn($q) => $q->where('marquee_id', $marqueeId))
            ->whereIn('status', ['pending_posting', 'received'])
            ->latest('payment_date')
            ->take(6)
            ->get();

        // Recent Daily Expenses
        $recentExpenses = Expense::where('marquee_id', $marqueeId)
            ->latest('expense_date')
            ->take(5)
            ->get();

        // Recent Journal / Payment Vouchers
        $recentJournalVouchers = JournalVoucher::where('marquee_id', $marqueeId)
            ->latest('voucher_date')
            ->take(5)
            ->get();

        return view('livewire.finance.accountant-dashboard', [
            'marquee' => $marquee,
            'cashBankAccounts' => $cashBankAccounts,
            'totalCashBankBalance' => $totalCashBankBalance,
            'pendingPaymentsCount' => $pendingPaymentsCount,
            'pendingPaymentsTotal' => $pendingPaymentsTotal,
            'todayCollectionsTotal' => $todayCollectionsTotal,
            'recentPendingPayments' => $recentPendingPayments,
            'recentExpenses' => $recentExpenses,
            'recentJournalVouchers' => $recentJournalVouchers,
        ]);
    }
}
