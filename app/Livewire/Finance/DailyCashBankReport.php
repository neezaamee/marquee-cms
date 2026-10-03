<?php

namespace App\Livewire\Finance;

use App\Models\Account;
use App\Models\BookingPayment;
use App\Models\CashBankAccount;
use App\Models\Expense;
use App\Models\JournalVoucherItem;
use App\Models\Marquee;
use App\Models\PaymentVoucher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DailyCashBankReport extends Component
{
    public string $reportDate = '';
    public ?int $branchId = null;
    public string $tab = 'all'; // 'all', 'inflow', 'outflow', 'accounts'
    public string $methodFilter = 'all'; // 'all', 'cash', 'bank'
    public string $search = '';

    protected $queryString = [
        'reportDate' => ['except' => ''],
        'branchId' => ['except' => null],
        'tab' => ['except' => 'all'],
        'methodFilter' => ['except' => 'all'],
    ];

    public function mount()
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->isBusinessOwner() || $user->hasRole(['accountant', 'branch_manager']) || $user->hasPermission('view_payments') || $user->hasPermission('manage_accounting') || $user->hasPermission('manage_finance')), 403, 'Unauthorized access to daily financial report.');

        if (empty($this->reportDate)) {
            $this->reportDate = request()->query('date', date('Y-m-d'));
        }

        if (request()->has('branch_id')) {
            $this->branchId = (int) request()->query('branch_id');
        } elseif ($user && $user->branch_id && !$user->isSuperAdmin() && !$user->isBusinessOwner()) {
            $this->branchId = (int) $user->branch_id;
        }
    }

    public function setDateToday()
    {
        $this->reportDate = date('Y-m-d');
    }

    public function setDateYesterday()
    {
        $this->reportDate = Carbon::yesterday()->format('Y-m-d');
    }

    public function setPreviousDay()
    {
        $this->reportDate = Carbon::parse($this->reportDate)->subDay()->format('Y-m-d');
    }

    public function setNextDay()
    {
        $this->reportDate = Carbon::parse($this->reportDate)->addDay()->format('Y-m-d');
    }

    public function setTab(string $tab)
    {
        $this->tab = $tab;
    }

    public function setMethodFilter(string $filter)
    {
        $this->methodFilter = $filter;
    }

    public function render()
    {
        $user = auth()->user();
        $marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
        $marquee = $marqueeId ? Marquee::find($marqueeId) : null;

        $accessibleBranches = $user ? $user->getAccessibleBranches($marqueeId) : collect();
        $accessibleBranchIds = $accessibleBranches->pluck('id')->toArray();

        $selectedDate = $this->reportDate ?: date('Y-m-d');
        $formattedDate = Carbon::parse($selectedDate)->format('d F Y');
        $isToday = $selectedDate === date('Y-m-d');

        // =========================================================================
        // 1. INFLOWS: INCOMING TRANSACTIONS (BOOKING PAYMENTS & ADVANCES)
        // =========================================================================
        $inflowQuery = BookingPayment::whereHas('booking', function ($q) use ($marqueeId, $accessibleBranchIds) {
            $q->where('marquee_id', $marqueeId);
            if (!empty($accessibleBranchIds)) {
                $q->whereIn('branch_id', $accessibleBranchIds);
            }
            if ($this->branchId) {
                $q->where('branch_id', $this->branchId);
            }
        })
        ->whereDate('payment_date', $selectedDate)
        ->where('payment_type', '!=', 'refund')
        ->whereIn('status', ['posted', 'received', 'pending_posting'])
        ->with([
            'booking.customer',
            'booking.hall',
            'booking.branch',
            'account',
            'recorder',
        ])
        ->orderBy('payment_date', 'asc')
        ->orderBy('id', 'asc');

        $allInflows = $inflowQuery->get();

        // Categorize Cash vs Bank Inflow
        $cashInflows = $allInflows->filter(fn($p) => strtolower($p->payment_method) === 'cash');
        $bankInflows = $allInflows->filter(fn($p) => strtolower($p->payment_method) !== 'cash');

        $totalCashInflow = (float) $cashInflows->sum('amount');
        $totalBankInflow = (float) $bankInflows->sum('amount');
        $totalInflow = $totalCashInflow + $totalBankInflow;

        $postedCashInflow = (float) $cashInflows->where('status', 'posted')->sum('amount');
        $pendingCashInflow = (float) $cashInflows->whereIn('status', ['received', 'pending_posting'])->sum('amount');
        $postedBankInflow = (float) $bankInflows->where('status', 'posted')->sum('amount');
        $pendingBankInflow = (float) $bankInflows->whereIn('status', ['received', 'pending_posting'])->sum('amount');

        // =========================================================================
        // 2. OUTFLOWS: CASH & BANK UTILIZED (PAYMENT VOUCHERS, EXPENSES, REFUNDS)
        // =========================================================================

        // A. Payment Vouchers (CPV & BPV)
        $voucherQuery = PaymentVoucher::where('marquee_id', $marqueeId)
            ->when(!empty($accessibleBranchIds), fn($q) => $q->whereIn('branch_id', $accessibleBranchIds))
            ->when($this->branchId, fn($q) => $q->where('branch_id', $this->branchId))
            ->whereDate('voucher_date', $selectedDate)
            ->where('status', '!=', 'cancelled')
            ->with([
                'cashBankAccount.account',
                'debitAccount',
                'branch',
            ])
            ->orderBy('id', 'asc');

        $allVouchers = $voucherQuery->get();

        $cashVouchers = $allVouchers->filter(function ($v) {
            return $v->voucher_type === 'CPV' || strtolower($v->payment_method) === 'cash';
        });

        $bankVouchers = $allVouchers->filter(function ($v) {
            return $v->voucher_type === 'BPV' || strtolower($v->payment_method) !== 'cash';
        });

        $totalCashVouchersAmount = (float) $cashVouchers->sum('amount');
        $totalBankVouchersAmount = (float) $bankVouchers->sum('amount');

        // B. Operating Expenses (Directly paid, not linked to a Payment Voucher)
        $linkedExpenseIds = $allVouchers->pluck('expense_id')->filter()->unique();

        $expenseQuery = Expense::where('marquee_id', $marqueeId)
            ->when(!empty($accessibleBranchIds), fn($q) => $q->whereIn('branch_id', $accessibleBranchIds))
            ->when($this->branchId, fn($q) => $q->where('branch_id', $this->branchId))
            ->whereDate('expense_date', $selectedDate)
            ->whereIn('payment_status', ['Paid', 'paid'])
            ->whereNotIn('status', [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED, Expense::STATUS_CANCELLED])
            ->whereNotIn('payment_method', ['Credit', 'credit', 'Accounts Payable', 'accounts payable', Expense::METHOD_CREDIT])
            ->when($linkedExpenseIds->isNotEmpty(), fn($q) => $q->whereNotIn('id', $linkedExpenseIds))
            ->with([
                'category',
                'cashBankAccount.account',
                'branch',
                'supplier',
                'employee',
            ])
            ->orderBy('id', 'asc');

        $allExpenses = $expenseQuery->get();

        $cashExpenses = $allExpenses->filter(function ($e) {
            $method = strtolower($e->payment_method ?? '');
            if (in_array($method, ['cash', 'petty_cash', 'petty cash'])) {
                return true;
            }
            if ($e->cashBankAccount && ($e->cashBankAccount->type === 'cash' || str_starts_with($e->cashBankAccount->account?->account_code ?? '', '1001'))) {
                return true;
            }
            return empty($method);
        });

        $bankExpenses = $allExpenses->filter(fn($e) => !$cashExpenses->contains('id', $e->id));

        $totalCashExpensesAmount = (float) $cashExpenses->sum('total_amount');
        $totalBankExpensesAmount = (float) $bankExpenses->sum('total_amount');

        // C. Customer Refunds
        $refundQuery = BookingPayment::whereHas('booking', function ($q) use ($marqueeId, $accessibleBranchIds) {
            $q->where('marquee_id', $marqueeId);
            if (!empty($accessibleBranchIds)) {
                $q->whereIn('branch_id', $accessibleBranchIds);
            }
            if ($this->branchId) {
                $q->where('branch_id', $this->branchId);
            }
        })
        ->whereDate('payment_date', $selectedDate)
        ->where('payment_type', 'refund')
        ->where('status', 'posted')
        ->with(['booking.customer', 'booking.branch', 'account'])
        ->get();

        $cashRefunds = $refundQuery->filter(fn($r) => strtolower($r->payment_method) === 'cash');
        $bankRefunds = $refundQuery->filter(fn($r) => strtolower($r->payment_method) !== 'cash');

        $totalCashRefunds = (float) $cashRefunds->sum('amount');
        $totalBankRefunds = (float) $bankRefunds->sum('amount');

        // Total Outflows
        $totalCashOutflow = $totalCashVouchersAmount + $totalCashExpensesAmount + $totalCashRefunds;
        $totalBankOutflow = $totalBankVouchersAmount + $totalBankExpensesAmount + $totalBankRefunds;
        $totalOutflow = $totalCashOutflow + $totalBankOutflow;

        // Net Positions
        $netCashPosition = $totalCashInflow - $totalCashOutflow;
        $netBankPosition = $totalBankInflow - $totalBankOutflow;
        $netDailyPosition = $totalInflow - $totalOutflow;

        // Build Consolidated Outflow Items Collection for the view
        $outflowItems = collect();

        foreach ($allVouchers as $v) {
            $isCash = ($v->voucher_type === 'CPV' || strtolower($v->payment_method) === 'cash');
            $outflowItems->push([
                'type' => 'voucher',
                'ref_no' => $v->voucher_no,
                'category_label' => $v->voucher_type === 'CPV' ? 'Cash Payment Voucher' : 'Bank Payment Voucher',
                'payee_title' => $v->payee_name ?: ($v->payee_type ? ucfirst($v->payee_type) : 'Vendor/Supplier'),
                'description' => $v->description ?: 'Disbursement voucher',
                'method' => $isCash ? 'Cash' : ($v->payment_method ?: 'Bank'),
                'account_name' => $v->cashBankAccount?->account?->name ?? ($isCash ? 'Cash Drawer' : 'Bank Account'),
                'amount' => (float) $v->amount,
                'status' => $v->status,
                'is_cash' => $isCash,
                'time' => $v->created_at ? $v->created_at->format('h:i A') : '',
            ]);
        }

        foreach ($allExpenses as $e) {
            $isCash = $cashExpenses->contains('id', $e->id);
            $payee = $e->supplier?->name 
                ?: ($e->employee?->name 
                ?: ($e->cost_center 
                ?: ($e->department ?: 'Operations')));
            $categoryName = $e->category?->name ?? 'Operating Expense';

            $outflowItems->push([
                'type' => 'expense',
                'ref_no' => $e->expense_number,
                'category_label' => $categoryName,
                'payee_title' => $payee,
                'description' => $e->description ?: ($categoryName . ' - Operating expense'),
                'method' => $isCash ? 'Cash' : ($e->payment_method ?: 'Bank'),
                'account_name' => $e->cashBankAccount?->account?->name ?? ($isCash ? 'Cash in Hand (1001)' : 'Bank Account'),
                'amount' => (float) $e->total_amount,
                'status' => $e->payment_status ?: 'Paid',
                'is_cash' => $isCash,
                'time' => $e->created_at ? $e->created_at->format('h:i A') : '',
            ]);
        }

        foreach ($refundQuery as $r) {
            $isCash = (strtolower($r->payment_method) === 'cash');
            $outflowItems->push([
                'type' => 'refund',
                'ref_no' => $r->payment_number ?? ('REF-' . $r->id),
                'category_label' => 'Customer Refund',
                'payee_title' => $r->booking?->customer?->full_name ?? 'Customer',
                'description' => "Booking #{$r->booking?->booking_number} deposit/excess refund",
                'method' => $isCash ? 'Cash' : 'Bank',
                'account_name' => $r->account?->name ?? ($isCash ? 'Cash in Hand' : 'Bank Account'),
                'amount' => (float) $r->amount,
                'status' => 'Disbursed',
                'is_cash' => $isCash,
                'time' => $r->created_at ? $r->created_at->format('h:i A') : '',
            ]);
        }

        // =========================================================================
        // 3. ACCOUNT-WISE POSITION & OPENING/CLOSING AUDIT
        // =========================================================================
        $cashBankRecords = CashBankAccount::with(['account.accountType'])
            ->where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->get();

        $accountPositions = collect();
        $totalOpeningCash = 0.0;
        $totalOpeningBank = 0.0;
        $totalClosingCash = 0.0;
        $totalClosingBank = 0.0;

        foreach ($cashBankRecords as $cb) {
            $acc = $cb->account;
            if (!$acc) {
                continue;
            }

            $accId = $acc->id;
            $isCashAcc = ($cb->type === 'cash' || str_starts_with($acc->account_code, '1001'));

            // 1. Calculate Opening Balance prior to $selectedDate
            $initialBal = DB::table('account_opening_balances')
                ->where('account_id', $accId)
                ->first();
            $baseOpening = (float) (($initialBal?->debit ?? 0) - ($initialBal?->credit ?? 0));

            // Sum transactions prior to selected date
            $priorTransactions = DB::table('journal_voucher_items')
                ->join('journal_vouchers', 'journal_voucher_items.journal_voucher_id', '=', 'journal_vouchers.id')
                ->whereNull('journal_vouchers.deleted_at')
                ->where('journal_voucher_items.account_id', $accId)
                ->where('journal_vouchers.voucher_date', '<', $selectedDate)
                ->select(
                    DB::raw('COALESCE(SUM(journal_voucher_items.debit), 0) as prior_debit'),
                    DB::raw('COALESCE(SUM(journal_voucher_items.credit), 0) as prior_credit')
                )
                ->first();

            $openingBal = $baseOpening + (float) (($priorTransactions?->prior_debit ?? 0) - ($priorTransactions?->prior_credit ?? 0));

            // 2. Day transactions on $selectedDate
            $dayTransactions = DB::table('journal_voucher_items')
                ->join('journal_vouchers', 'journal_voucher_items.journal_voucher_id', '=', 'journal_vouchers.id')
                ->whereNull('journal_vouchers.deleted_at')
                ->where('journal_voucher_items.account_id', $accId)
                ->where('journal_vouchers.voucher_date', '=', $selectedDate)
                ->select(
                    DB::raw('COALESCE(SUM(journal_voucher_items.debit), 0) as day_debit'),
                    DB::raw('COALESCE(SUM(journal_voucher_items.credit), 0) as day_credit')
                )
                ->first();

            $dayDebit = (float) ($dayTransactions?->day_debit ?? 0);
            $dayCredit = (float) ($dayTransactions?->day_credit ?? 0);

            // Add unposted collections received today for this account if cash/bank
            $unpostedReceived = $allInflows
                ->whereIn('status', ['received', 'pending_posting'])
                ->filter(function ($p) use ($cb, $isCashAcc) {
                    if ($p->cash_bank_account_id) {
                        return (int) $p->cash_bank_account_id === (int) $cb->id;
                    }
                    return $isCashAcc ? (strtolower($p->payment_method) === 'cash') : (strtolower($p->payment_method) !== 'cash');
                })
                ->sum('amount');

            $totalAccountInflow = $dayDebit + (float) $unpostedReceived;
            $closingBal = $openingBal + $totalAccountInflow - $dayCredit;

            if ($isCashAcc) {
                $totalOpeningCash += $openingBal;
                $totalClosingCash += $closingBal;
            } else {
                $totalOpeningBank += $openingBal;
                $totalClosingBank += $closingBal;
            }

            $accountPositions->push([
                'id' => $cb->id,
                'account_id' => $accId,
                'account_code' => $acc->account_code,
                'account_name' => $acc->name,
                'bank_name' => $cb->bank_name ?: ($isCashAcc ? 'Physical Cash Drawer' : 'Bank Account'),
                'account_number' => $cb->account_number ?: 'N/A',
                'type' => $cb->type,
                'is_cash' => $isCashAcc,
                'opening_balance' => $openingBal,
                'day_inflow' => $totalAccountInflow,
                'day_outflow' => $dayCredit,
                'closing_balance' => $closingBal,
            ]);
        }

        // If no cash/bank accounts were mapped yet, provide default fallback estimates from transactions
        if ($accountPositions->isEmpty()) {
            $totalOpeningCash = 0.0;
            $totalOpeningBank = 0.0;
            $totalClosingCash = $netCashPosition;
            $totalClosingBank = $netBankPosition;
        }

        $totalOpeningBalance = $totalOpeningCash + $totalOpeningBank;
        $totalClosingBalance = $totalClosingCash + $totalClosingBank;

        // Apply Search / Method filter to display lists
        $filteredInflows = $allInflows;
        $filteredOutflows = $outflowItems;

        if ($this->methodFilter === 'cash') {
            $filteredInflows = $filteredInflows->filter(fn($p) => strtolower($p->payment_method) === 'cash');
            $filteredOutflows = $filteredOutflows->filter(fn($o) => $o['is_cash']);
        } elseif ($this->methodFilter === 'bank') {
            $filteredInflows = $filteredInflows->filter(fn($p) => strtolower($p->payment_method) !== 'cash');
            $filteredOutflows = $filteredOutflows->filter(fn($o) => !$o['is_cash']);
        }

        if (!empty($this->search)) {
            $term = strtolower(trim($this->search));
            $filteredInflows = $filteredInflows->filter(function ($p) use ($term) {
                return str_contains(strtolower($p->payment_number ?? ''), $term)
                    || str_contains(strtolower($p->receipt_number ?? ''), $term)
                    || str_contains(strtolower($p->booking?->customer?->full_name ?? ''), $term)
                    || str_contains(strtolower($p->booking?->booking_number ?? ''), $term)
                    || str_contains(strtolower($p->payment_method ?? ''), $term);
            });

            $filteredOutflows = $filteredOutflows->filter(function ($o) use ($term) {
                return str_contains(strtolower($o['ref_no']), $term)
                    || str_contains(strtolower($o['payee_title']), $term)
                    || str_contains(strtolower($o['description']), $term)
                    || str_contains(strtolower($o['category_label']), $term)
                    || str_contains(strtolower($o['method']), $term);
            });
        }

        return view('livewire.finance.daily-cash-bank-report', [
            'marquee' => $marquee,
            'branches' => $accessibleBranches,
            'selectedDate' => $selectedDate,
            'formattedDate' => $formattedDate,
            'isToday' => $isToday,
            // Inflows
            'allInflows' => $filteredInflows,
            'inflowsCount' => $allInflows->count(),
            'totalCashInflow' => $totalCashInflow,
            'totalBankInflow' => $totalBankInflow,
            'totalInflow' => $totalInflow,
            'postedCashInflow' => $postedCashInflow,
            'pendingCashInflow' => $pendingCashInflow,
            'postedBankInflow' => $postedBankInflow,
            'pendingBankInflow' => $pendingBankInflow,
            // Outflows
            'allOutflows' => $filteredOutflows,
            'outflowsCount' => $outflowItems->count(),
            'totalCashOutflow' => $totalCashOutflow,
            'totalBankOutflow' => $totalBankOutflow,
            'totalOutflow' => $totalOutflow,
            'totalCashVouchersAmount' => $totalCashVouchersAmount,
            'totalBankVouchersAmount' => $totalBankVouchersAmount,
            'totalCashExpensesAmount' => $totalCashExpensesAmount,
            'totalBankExpensesAmount' => $totalBankExpensesAmount,
            'totalCashRefunds' => $totalCashRefunds,
            'totalBankRefunds' => $totalBankRefunds,
            // Net Results
            'netCashPosition' => $netCashPosition,
            'netBankPosition' => $netBankPosition,
            'netDailyPosition' => $netDailyPosition,
            // Balances & Accounts
            'accountPositions' => $accountPositions,
            'totalOpeningCash' => $totalOpeningCash,
            'totalOpeningBank' => $totalOpeningBank,
            'totalOpeningBalance' => $totalOpeningBalance,
            'totalClosingCash' => $totalClosingCash,
            'totalClosingBank' => $totalClosingBank,
            'totalClosingBalance' => $totalClosingBalance,
        ]);
    }
}
