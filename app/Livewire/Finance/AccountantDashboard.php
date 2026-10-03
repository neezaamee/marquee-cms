<?php

namespace App\Livewire\Finance;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\CashBankAccount;
use App\Models\Expense;
use App\Models\JournalVoucher;
use App\Models\Marquee;
use App\Models\PaymentVoucher;
use Illuminate\Support\Facades\DB;
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

        // Cash & Bank Balances from GL Ledger & Opening Balances
        $cashBankAccounts = CashBankAccount::with('account')
            ->where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->get();

        $accountIds = $cashBankAccounts->pluck('account_id')->filter()->unique()->toArray();

        // 1. Opening balances from Chart of Accounts
        $openingBalances = !empty($accountIds) ? DB::table('account_opening_balances')
            ->whereIn('account_id', $accountIds)
            ->select('account_id', DB::raw('COALESCE(SUM(debit - credit), 0) as balance'))
            ->groupBy('account_id')
            ->pluck('balance', 'account_id') : collect();

        // 2. Posted journal movements
        $journalMovements = !empty($accountIds) ? DB::table('journal_voucher_items')
            ->join('journal_vouchers', 'journal_voucher_items.journal_voucher_id', '=', 'journal_vouchers.id')
            ->whereNull('journal_vouchers.deleted_at')
            ->where('journal_vouchers.status', 'posted')
            ->where('journal_vouchers.marquee_id', $marqueeId)
            ->whereIn('journal_voucher_items.account_id', $accountIds)
            ->select('journal_voucher_items.account_id', DB::raw('COALESCE(SUM(journal_voucher_items.debit - journal_voucher_items.credit), 0) as balance'))
            ->groupBy('journal_voucher_items.account_id')
            ->pluck('balance', 'account_id') : collect();

        $totalCashBalance = 0.0;
        $totalBankBalance = 0.0;

        foreach ($cashBankAccounts as $cb) {
            $accId = $cb->account_id;
            $balance = (float) (($openingBalances[$accId] ?? 0.0) + ($journalMovements[$accId] ?? 0.0));
            $cb->current_balance = $balance;

            if ($cb->type === 'cash') {
                $totalCashBalance += $balance;
            } else {
                $totalBankBalance += $balance;
            }
        }

        $totalCashBankBalance = $totalCashBalance + $totalBankBalance;

        // Operating Expenses Metrics
        $expenseBase = Expense::where('marquee_id', $marqueeId)
            ->whereNotIn('status', [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED, Expense::STATUS_CANCELLED]);

        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $monthExpensesTotal = (float) (clone $expenseBase)
            ->whereBetween('expense_date', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');

        $todayExpensesTotal = (float) (clone $expenseBase)
            ->whereDate('expense_date', today())
            ->sum('total_amount');

        $monthExpensesCount = (clone $expenseBase)
            ->whereBetween('expense_date', [$startOfMonth, $endOfMonth])
            ->count();

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
            'totalCashBalance' => $totalCashBalance,
            'totalBankBalance' => $totalBankBalance,
            'monthExpensesTotal' => $monthExpensesTotal,
            'todayExpensesTotal' => $todayExpensesTotal,
            'monthExpensesCount' => $monthExpensesCount,
            'pendingPaymentsCount' => $pendingPaymentsCount,
            'pendingPaymentsTotal' => $pendingPaymentsTotal,
            'todayCollectionsTotal' => $todayCollectionsTotal,
            'recentPendingPayments' => $recentPendingPayments,
            'recentExpenses' => $recentExpenses,
            'recentJournalVouchers' => $recentJournalVouchers,
        ]);
    }
}
