<?php

namespace App\Livewire\Finance;

use App\Models\Account;
use App\Models\Branch;
use App\Models\FinancialYear;
use App\Services\AccountingService;
use Livewire\Component;

class GeneralLedger extends Component
{
    public $isSaas = false;

    public $account_id = '';
    public $financial_year_id = '';
    public $branch_id = '';
    public $startDate = '';
    public $endDate = '';

    public $ledgerData = null;

    public function getMarqueeId(): ?int
    {
        if ($this->isSaas) {
            return null;
        }
        $user = auth()->user();
        return $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
    }

    public function mount()
    {
        $marqueeId = $this->getMarqueeId();
        $user = auth()->user();

        // Enforce user branch scope
        if (!$this->isSaas && $user->branch_id && !$user->isSuperAdmin()) {
            $this->branch_id = $user->branch_id;
        }

        // Get default active financial year
        $fyQuery = FinancialYear::where('status', 'active');
        if ($this->isSaas) {
            $fyQuery->whereNull('marquee_id');
        } else {
            $fyQuery->where('marquee_id', $marqueeId);
        }
        
        $activeFy = $fyQuery->where('is_default', true)->first() 
            ?? (clone $fyQuery)->orderBy('start_date', 'desc')->first();

        if ($activeFy) {
            $this->financial_year_id = $activeFy->id;
            $this->startDate = $activeFy->start_date->format('Y-m-d');
            
            // Set endDate to current date or end of financial year, whichever is earlier
            $fyEnd = $activeFy->end_date->format('Y-m-d');
            $today = date('Y-m-d');
            $this->endDate = $today < $fyEnd ? $today : $fyEnd;
        }
    }

    public function updatedFinancialYearId()
    {
        if ($this->financial_year_id) {
            $fy = FinancialYear::find($this->financial_year_id);
            if ($fy) {
                $this->startDate = $fy->start_date->format('Y-m-d');
                $this->endDate = $fy->end_date->format('Y-m-d');
            }
        }
        $this->ledgerData = null;
    }

    public function generateReport(AccountingService $accountingService)
    {
        $this->validate([
            'account_id' => 'required|exists:accounts,id',
            'financial_year_id' => 'required|exists:financial_years,id',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        try {
            $this->ledgerData = $accountingService->getGeneralLedger(
                (int)$this->account_id,
                $this->startDate,
                $this->endDate,
                $this->branch_id ? (int)$this->branch_id : null,
                (int)$this->financial_year_id
            );
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->ledgerData = null;
        }
    }

    public function render()
    {
        $marqueeId = $this->getMarqueeId();
        $user = auth()->user();

        // Get leaf accounts
        $accountsQuery = Account::whereDoesntHave('children')
            ->where('is_active', true)
            ->orderBy('account_code');
            
        if ($this->isSaas) {
            $accountsQuery->whereNull('marquee_id');
        } else {
            $accountsQuery->where('marquee_id', $marqueeId);
        }
        $accounts = $accountsQuery->get();

        $fyQuery = FinancialYear::orderBy('start_date', 'desc');
        if ($this->isSaas) {
            $fyQuery->whereNull('marquee_id');
        } else {
            $fyQuery->where('marquee_id', $marqueeId);
        }
        $financialYears = $fyQuery->get();
        
        $branches = collect();
        if (!$this->isSaas) {
            if ($user->branch_id && !$user->isSuperAdmin()) {
                $branches = Branch::where('id', $user->branch_id)->get();
            } else {
                $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('name')->get();
            }
        }

        return view('livewire.finance.general-ledger', [
            'accounts' => $accounts,
            'financialYears' => $financialYears,
            'branches' => $branches,
        ]);
    }
}
