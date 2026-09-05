<?php

namespace App\Livewire\Finance;

use App\Models\Account;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\CashBankAccount;
use App\Services\BookingFinancialService;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentsList extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $paymentMethod = '';
    public $branchFilter = '';
    public $startDate = '';
    public $endDate = '';

    // Post Payment Modal State
    public $showPostModal = false;
    public $postingPaymentId = null;
    public $targetAccountId = '';
    public $postingDate = '';
    public $accountantNotes = '';

    // Reject Payment Modal State
    public $showRejectModal = false;
    public $rejectingPaymentId = null;
    public $rejectionReason = '';

    // Reverse Payment Modal State
    public $showReverseModal = false;
    public $reversingPaymentId = null;
    public $reversalReason = '';

    // Detail View Modal State
    public $showDetailModal = false;
    public $viewingPaymentId = null;

    protected $paginationTheme = 'bootstrap';

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatingPaymentMethod() { $this->resetPage(); }
    public function updatingBranchFilter() { $this->resetPage(); }
    public function updatingStartDate() { $this->resetPage(); }
    public function updatingEndDate() { $this->resetPage(); }

    public function openPostModal($paymentId)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isBusinessOwner() && !$user->hasPermission('post_payments')) {
            session()->flash('error', 'You are not authorized to post payments to financial accounts.');
            return;
        }

        $payment = BookingPayment::with(['booking', 'booking.customer', 'account'])->findOrFail($paymentId);

        if ($payment->status !== 'pending_posting' && $payment->status !== 'received') {
            session()->flash('error', 'This payment is not awaiting accountant posting.');
            return;
        }

        $this->postingPaymentId = $payment->id;
        $this->postingDate = date('Y-m-d');
        $this->accountantNotes = '';

        // Pre-select account if already chosen or default cash/bank
        $marqueeId = $payment->booking->marquee_id ?? $user->getActiveMarqueeId();
        $targetCode = (strtolower($payment->payment_method) === 'cash') ? '1001' : '1002';
        $defaultAcc = Account::withoutGlobalScope('tenant')
            ->where('marquee_id', $marqueeId)
            ->where('account_code', $targetCode)
            ->first();

        $this->targetAccountId = $payment->account_id ?: ($defaultAcc?->id ?? '');
        $this->showPostModal = true;
    }

    public function confirmPostPayment(BookingFinancialService $financialService)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isBusinessOwner() && !$user->hasPermission('post_payments')) {
            session()->flash('error', 'Unauthorized.');
            return;
        }

        $this->validate([
            'postingPaymentId' => 'required|exists:booking_payments,id',
            'targetAccountId' => 'required|exists:accounts,id',
            'postingDate' => 'required|date',
            'accountantNotes' => 'nullable|string|max:500',
        ]);

        try {
            $payment = BookingPayment::findOrFail($this->postingPaymentId);
            $financialService->postPayment($payment, [
                'account_id' => (int) $this->targetAccountId,
                'posting_date' => $this->postingDate,
                'accountant_notes' => $this->accountantNotes,
                'posted_by' => $user->id,
            ]);

            $this->showPostModal = false;
            $this->postingPaymentId = null;
            session()->flash('success', "Payment #{$payment->payment_number} posted successfully. Cash/Bank and Advance Liability accounts updated.");
        } catch (\Exception $e) {
            session()->flash('error', 'Posting failed: ' . $e->getMessage());
        }
    }

    public function openRejectModal($paymentId)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isBusinessOwner() && !$user->hasPermission('reject_payments')) {
            session()->flash('error', 'You are not authorized to reject payments.');
            return;
        }

        $payment = BookingPayment::findOrFail($paymentId);
        if ($payment->status !== 'pending_posting' && $payment->status !== 'received') {
            session()->flash('error', 'Only pending payments can be rejected.');
            return;
        }

        $this->rejectingPaymentId = $payment->id;
        $this->rejectionReason = '';
        $this->showRejectModal = true;
    }

    public function confirmRejectPayment(BookingFinancialService $financialService)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isBusinessOwner() && !$user->hasPermission('reject_payments')) {
            session()->flash('error', 'Unauthorized.');
            return;
        }

        $this->validate([
            'rejectingPaymentId' => 'required|exists:booking_payments,id',
            'rejectionReason' => 'required|string|min:3|max:500',
        ]);

        try {
            $payment = BookingPayment::findOrFail($this->rejectingPaymentId);
            $financialService->rejectPayment($payment, $this->rejectionReason, $user->id);

            $this->showRejectModal = false;
            $this->rejectingPaymentId = null;
            session()->flash('warning', "Payment #{$payment->payment_number} was rejected.");
        } catch (\Exception $e) {
            session()->flash('error', 'Rejection failed: ' . $e->getMessage());
        }
    }

    public function openReverseModal($paymentId)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isBusinessOwner() && !$user->hasPermission('reverse_payments')) {
            session()->flash('error', 'You are not authorized to reverse posted payments.');
            return;
        }

        $payment = BookingPayment::findOrFail($paymentId);
        if ($payment->status !== 'posted') {
            session()->flash('error', 'Only posted payments can be reversed.');
            return;
        }

        $this->reversingPaymentId = $payment->id;
        $this->reversalReason = '';
        $this->showReverseModal = true;
    }

    public function confirmReversePayment(BookingFinancialService $financialService)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isBusinessOwner() && !$user->hasPermission('reverse_payments')) {
            session()->flash('error', 'Unauthorized.');
            return;
        }

        $this->validate([
            'reversingPaymentId' => 'required|exists:booking_payments,id',
            'reversalReason' => 'required|string|min:3|max:500',
        ]);

        try {
            $payment = BookingPayment::findOrFail($this->reversingPaymentId);
            $financialService->reversePayment($payment, $this->reversalReason, $user->id);

            $this->showReverseModal = false;
            $this->reversingPaymentId = null;
            session()->flash('warning', "Payment #{$payment->payment_number} was reversed. Offsetting journal voucher and customer ledger updated.");
        } catch (\Exception $e) {
            session()->flash('error', 'Reversal failed: ' . $e->getMessage());
        }
    }

    public function openDetailModal($paymentId)
    {
        $this->viewingPaymentId = $paymentId;
        $this->showDetailModal = true;
    }

    public function render()
    {
        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();
        $accessibleBranches = $user->getAccessibleBranches($marqueeId);
        $accessibleBranchIds = $accessibleBranches->pluck('id')->toArray();

        // Base Query scoped to marquee and accessible branches
        $baseQuery = BookingPayment::whereHas('booking', function ($q) use ($marqueeId, $accessibleBranchIds) {
            $q->where('marquee_id', $marqueeId);
            if (!empty($accessibleBranchIds)) {
                $q->whereIn('branch_id', $accessibleBranchIds);
            }
        });

        // Compute Metric Summary Statistics
        $metrics = [
            'total_received' => (clone $baseQuery)->sum('amount'),
            'total_received_count' => (clone $baseQuery)->count(),

            'pending_posting_amount' => (clone $baseQuery)->whereIn('status', ['pending_posting', 'received'])->sum('amount'),
            'pending_posting_count' => (clone $baseQuery)->whereIn('status', ['pending_posting', 'received'])->count(),
            'pending_cash_amount' => (clone $baseQuery)->whereIn('status', ['pending_posting', 'received'])->where('payment_method', 'Cash')->sum('amount'),
            'pending_bank_amount' => (clone $baseQuery)->whereIn('status', ['pending_posting', 'received'])->where('payment_method', '!=', 'Cash')->sum('amount'),

            'posted_amount' => (clone $baseQuery)->where('status', 'posted')->sum('amount'),
            'posted_count' => (clone $baseQuery)->where('status', 'posted')->count(),
            'posted_cash_amount' => (clone $baseQuery)->where('status', 'posted')->where('payment_method', 'Cash')->sum('amount'),
            'posted_bank_amount' => (clone $baseQuery)->where('status', 'posted')->where('payment_method', '!=', 'Cash')->sum('amount'),

            'rejected_amount' => (clone $baseQuery)->where('status', 'rejected')->sum('amount'),
            'rejected_count' => (clone $baseQuery)->where('status', 'rejected')->count(),

            'reversed_amount' => (clone $baseQuery)->where('status', 'reversed')->sum('amount'),
            'reversed_count' => (clone $baseQuery)->where('status', 'reversed')->count(),
        ];

        // Filtered List Query
        $query = (clone $baseQuery)->with([
            'booking.customer',
            'booking.branch',
            'recorder',
            'poster',
            'rejector',
            'reverser',
            'account',
            'journalVoucher',
        ]);

        if (!empty($this->search)) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('payment_number', 'like', $term)
                  ->orWhere('transaction_reference', 'like', $term)
                  ->orWhere('cheque_number', 'like', $term)
                  ->orWhere('bank_reference', 'like', $term)
                  ->orWhere('notes', 'like', $term)
                  ->orWhereHas('booking', function ($bq) use ($term) {
                      $bq->where('booking_number', 'like', $term)
                         ->orWhereHas('customer', function ($cq) use ($term) {
                             $cq->where('first_name', 'like', $term)
                                ->orWhere('last_name', 'like', $term)
                                ->orWhere('phone_number', 'like', $term)
                                ->orWhere('customer_code', 'like', $term);
                         });
                  });
            });
        }

        if (!empty($this->statusFilter)) {
            if ($this->statusFilter === 'pending_posting') {
                $query->whereIn('status', ['pending_posting', 'received']);
            } else {
                $query->where('status', $this->statusFilter);
            }
        }

        if (!empty($this->paymentMethod)) {
            $query->where('payment_method', $this->paymentMethod);
        }

        if (!empty($this->branchFilter)) {
            $query->whereHas('booking', function ($q) {
                $q->where('branch_id', $this->branchFilter);
            });
        }

        if (!empty($this->startDate)) {
            $query->where('payment_date', '>=', $this->startDate);
        }
        if (!empty($this->endDate)) {
            $query->where('payment_date', '<=', $this->endDate);
        }

        $payments = $query->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        // Accounts available for posting (Cash & Bank COA accounts)
        $accounts = Account::withoutGlobalScope('tenant')
            ->where('marquee_id', $marqueeId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('account_code', 'like', '1001%')
                  ->orWhere('account_code', 'like', '1002%')
                  ->orWhereHas('accountType', function ($tq) {
                      $tq->whereIn('code', ['CURRENT_ASSETS']);
                  });
            })
            ->orderBy('account_code')
            ->get();

        $postingPayment = $this->postingPaymentId ? BookingPayment::with(['booking', 'booking.customer'])->find($this->postingPaymentId) : null;
        $rejectingPayment = $this->rejectingPaymentId ? BookingPayment::with(['booking', 'booking.customer'])->find($this->rejectingPaymentId) : null;
        $reversingPayment = $this->reversingPaymentId ? BookingPayment::with(['booking', 'booking.customer', 'account', 'journalVoucher'])->find($this->reversingPaymentId) : null;
        $viewingPayment = $this->viewingPaymentId ? BookingPayment::with([
            'booking.customer', 'booking.branch', 'recorder', 'poster', 'rejector', 'reverser', 'account', 'journalVoucher.items.account', 'reversalJournalVoucher'
        ])->find($this->viewingPaymentId) : null;

        return view('livewire.finance.payments-list', [
            'payments' => $payments,
            'metrics' => $metrics,
            'branches' => $accessibleBranches,
            'accounts' => $accounts,
            'postingPayment' => $postingPayment,
            'rejectingPayment' => $rejectingPayment,
            'reversingPayment' => $reversingPayment,
            'viewingPayment' => $viewingPayment,
        ]);
    }
}
