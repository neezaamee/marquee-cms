<?php

namespace App\Livewire\Finance;

use App\Models\Branch;
use App\Models\FinancialYear;
use App\Models\JournalVoucher;
use Livewire\Component;
use Livewire\WithPagination;

class JournalVoucherList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $filterBranch = '';
    public $filterFinancialYear = '';
    public $filterStatus = '';
    public $startDate = '';
    public $endDate = '';

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterBranch() { $this->resetPage(); }
    public function updatingFilterFinancialYear() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }
    public function updatingStartDate() { $this->resetPage(); }
    public function updatingEndDate() { $this->resetPage(); }

    public function getMarqueeId(): ?int
    {
        $user = auth()->user();
        return $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
    }

    public function mount()
    {
        $marqueeId = $this->getMarqueeId();
        $user = auth()->user();

        // Enforce branch filter for branch managers
        if ($user->branch_id && !$user->isSuperAdmin()) {
            $this->filterBranch = $user->branch_id;
        }

        // Set default financial year filter
        $activeFy = FinancialYear::where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->where('is_default', true)
            ->first();
        if ($activeFy) {
            $this->filterFinancialYear = $activeFy->id;
        }
    }

    public function postVoucher($id)
    {
        $voucher = JournalVoucher::findOrFail($id);

        if ($voucher->status !== 'draft') {
            session()->flash('error', 'Only draft vouchers can be posted to the ledger.');
            return;
        }

        // Run verification: check total debits and credits
        $totalDebit = $voucher->items()->sum('debit');
        $totalCredit = $voucher->items()->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.001) {
            session()->flash('error', 'Voucher is unbalanced and cannot be posted.');
            return;
        }

        $voucher->update(['status' => 'posted']);
        session()->flash('success', 'Journal Voucher posted to ledger successfully.');
    }

    public function cancelVoucher($id)
    {
        $voucher = JournalVoucher::findOrFail($id);
        
        if ($voucher->status === 'cancelled') {
            session()->flash('error', 'Voucher is already cancelled.');
            return;
        }

        $voucher->update(['status' => 'cancelled']);
        session()->flash('success', 'Journal Voucher cancelled successfully.');
    }

    public function deleteVoucher($id)
    {
        $voucher = JournalVoucher::findOrFail($id);

        if ($voucher->status === 'posted') {
            session()->flash('error', 'Posted vouchers cannot be deleted.');
            return;
        }

        $voucher->delete(); // Soft delete
        session()->flash('success', 'Journal Voucher deleted successfully.');
    }

    public function render()
    {
        $marqueeId = $this->getMarqueeId();
        $user = auth()->user();

        $query = JournalVoucher::where('marquee_id', $marqueeId)
            ->withSum('items as total_amount', 'debit')
            ->with(['branch', 'financialYear', 'creator']);

        // Apply search
        if (!empty($this->search)) {
            $term = '%' . $this->search . '%';
            $query->where(function($q) use ($term) {
                $q->where('voucher_no', 'like', $term)
                  ->orWhere('reference', 'like', $term)
                  ->orWhere('notes', 'like', $term);
            });
        }

        // Apply filters
        if (!empty($this->filterBranch)) {
            $query->where('branch_id', $this->filterBranch);
        }
        if (!empty($this->filterFinancialYear)) {
            $query->where('financial_year_id', $this->filterFinancialYear);
        }
        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }
        if (!empty($this->startDate)) {
            $query->where('voucher_date', '>=', $this->startDate);
        }
        if (!empty($this->endDate)) {
            $query->where('voucher_date', '<=', $this->endDate);
        }

        $vouchers = $query->orderBy('voucher_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        // Filter lists
        $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->get();
        $financialYears = FinancialYear::where('marquee_id', $marqueeId)->orderBy('start_date', 'desc')->get();

        return view('livewire.finance.journal-voucher-list', [
            'vouchers' => $vouchers,
            'branches' => $branches,
            'financialYears' => $financialYears,
        ]);
    }
}
