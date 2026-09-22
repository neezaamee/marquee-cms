<?php

namespace App\Livewire\Finance;

use App\Models\Branch;
use App\Models\PaymentVoucher;
use App\Services\PaymentVoucherService;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentVoucherList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $filterType = ''; // CPV, BPV
    public $filterStatus = ''; // draft, approved, posted, cancelled
    public $filterBranch = '';
    public $startDate = '';
    public $endDate = '';

    // Disburse & Post Modal State
    public $showDisburseModal = false;
    public $disbursingVoucherId = null;
    public $disbursingVoucherNo = '';
    public $disbursingAmount = 0;
    public $disbursementDate = '';
    public $disbursementNotes = '';

    // Cancel Modal State
    public $showCancelModal = false;
    public $cancellingVoucherId = null;
    public $cancelReason = '';

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterType() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }
    public function updatingFilterBranch() { $this->resetPage(); }
    public function updatingStartDate() { $this->resetPage(); }
    public function updatingEndDate() { $this->resetPage(); }

    public function getMarqueeId(): ?int
    {
        $user = auth()->user();
        return $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
    }

    public function mount()
    {
        $user = auth()->user();
        if ($user->branch_id && !$user->isSuperAdmin()) {
            $this->filterBranch = $user->branch_id;
        }
        $this->disbursementDate = date('Y-m-d');
    }

    public function openDisburseModal($id)
    {
        $voucher = PaymentVoucher::findOrFail($id);
        if ($voucher->status === PaymentVoucher::STATUS_POSTED) {
            session()->flash('error', 'This voucher has already been disbursed and posted.');
            return;
        }

        $this->disbursingVoucherId = $voucher->id;
        $this->disbursingVoucherNo = $voucher->voucher_no;
        $this->disbursingAmount = $voucher->amount;
        $this->disbursementDate = date('Y-m-d');
        $this->disbursementNotes = '';
        $this->showDisburseModal = true;
    }

    public function confirmDisburse()
    {
        if (!$this->disbursingVoucherId) {
            return;
        }

        $service = app(PaymentVoucherService::class);
        $voucher = PaymentVoucher::findOrFail($this->disbursingVoucherId);

        try {
            $service->disburseAndPost($voucher, auth()->id(), [
                'disbursed_date' => $this->disbursementDate ?: date('Y-m-d'),
                'notes' => $this->disbursementNotes,
            ]);

            $this->showDisburseModal = false;
            $this->disbursingVoucherId = null;
            session()->flash('success', "Payment Voucher {$voucher->voucher_no} has been successfully disbursed and posted to General Ledger!");
        } catch (\Throwable $e) {
            session()->flash('error', 'Disbursement failed: ' . $e->getMessage());
        }
    }

    public function approveVoucher($id)
    {
        $service = app(PaymentVoucherService::class);
        $voucher = PaymentVoucher::findOrFail($id);
        try {
            $service->approvePaymentVoucher($voucher, auth()->id());
            session()->flash('success', "Voucher {$voucher->voucher_no} is now approved and ready to print for physical acknowledgment.");
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openCancelModal($id)
    {
        $this->cancellingVoucherId = $id;
        $this->cancelReason = '';
        $this->showCancelModal = true;
    }

    public function confirmCancel()
    {
        if (!$this->cancellingVoucherId) {
            return;
        }

        $service = app(PaymentVoucherService::class);
        $voucher = PaymentVoucher::findOrFail($this->cancellingVoucherId);
        try {
            $service->cancelPaymentVoucher($voucher, auth()->id(), $this->cancelReason);
            $this->showCancelModal = false;
            $this->cancellingVoucherId = null;
            session()->flash('success', "Voucher {$voucher->voucher_no} has been cancelled.");
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $marqueeId = $this->getMarqueeId();

        $query = PaymentVoucher::with(['branch', 'cashBankAccount.account', 'debitAccount', 'preparedBy', 'journalVoucher'])
            ->where('marquee_id', $marqueeId);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('voucher_no', 'like', "%{$this->search}%")
                  ->orWhere('payee_name', 'like', "%{$this->search}%")
                  ->orWhere('reference_no', 'like', "%{$this->search}%")
                  ->orWhere('cheque_no', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterType) {
            $query->where('voucher_type', $this->filterType);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterBranch) {
            $query->where('branch_id', $this->filterBranch);
        }

        if ($this->startDate) {
            $query->where('voucher_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->where('voucher_date', '<=', $this->endDate);
        }

        // Summary Statistics (calculated on filtered tenant/branch)
        $baseStatQuery = PaymentVoucher::where('marquee_id', $marqueeId);
        if ($this->filterBranch) {
            $baseStatQuery->where('branch_id', $this->filterBranch);
        }

        $totalPostedAmount = (clone $baseStatQuery)->where('status', PaymentVoucher::STATUS_POSTED)->sum('amount');
        $totalPendingAmount = (clone $baseStatQuery)->whereIn('status', [PaymentVoucher::STATUS_APPROVED, PaymentVoucher::STATUS_DRAFT])->sum('amount');
        $totalPendingCount = (clone $baseStatQuery)->whereIn('status', [PaymentVoucher::STATUS_APPROVED, PaymentVoucher::STATUS_DRAFT])->count();
        $totalPostedCount = (clone $baseStatQuery)->where('status', PaymentVoucher::STATUS_POSTED)->count();

        $vouchers = $query->orderBy('voucher_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->get();

        return view('livewire.finance.payment-voucher-list', [
            'vouchers' => $vouchers,
            'branches' => $branches,
            'totalPostedAmount' => $totalPostedAmount,
            'totalPendingAmount' => $totalPendingAmount,
            'totalPendingCount' => $totalPendingCount,
            'totalPostedCount' => $totalPostedCount,
            'showDisburseModal' => $this->showDisburseModal,
            'disbursingVoucherId' => $this->disbursingVoucherId,
            'disbursingVoucherNo' => $this->disbursingVoucherNo,
            'disbursingAmount' => $this->disbursingAmount,
            'disbursementDate' => $this->disbursementDate,
            'disbursementNotes' => $this->disbursementNotes,
            'showCancelModal' => $this->showCancelModal,
            'cancellingVoucherId' => $this->cancellingVoucherId,
            'cancelReason' => $this->cancelReason,
        ]);
    }
}
