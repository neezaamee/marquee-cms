<?php

namespace App\Livewire\Finance;

use App\Models\Branch;
use App\Models\FinancialYear;
use App\Services\AccountingService;
use Livewire\Component;

class BalanceSheet extends Component
{
    public $financial_year_id = '';
    public $branch_id = '';
    public $asOfDate = '';

    public $reportData = null;

    public function mount()
    {
        $marqueeId = auth()->user()->marquee_id;
        $user = auth()->user();

        // Enforce user branch scope
        if ($user->branch_id && !$user->isSuperAdmin()) {
            $this->branch_id = $user->branch_id;
        }

        // Get default active financial year
        $activeFy = FinancialYear::where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->where('is_default', true)
            ->first() ?? FinancialYear::where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->first();

        if ($activeFy) {
            $this->financial_year_id = $activeFy->id;
            $fyEnd = $activeFy->end_date->format('Y-m-d');
            $today = date('Y-m-d');
            $this->asOfDate = $today < $fyEnd ? $today : $fyEnd;
        }
    }

    public function updatedFinancialYearId()
    {
        if ($this->financial_year_id) {
            $fy = FinancialYear::find($this->financial_year_id);
            if ($fy) {
                $this->asOfDate = $fy->end_date->format('Y-m-d');
            }
        }
        $this->reportData = null;
    }

    public function generateReport(AccountingService $accountingService)
    {
        $this->validate([
            'financial_year_id' => 'required|exists:financial_years,id',
            'asOfDate' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $marqueeId = auth()->user()->marquee_id;

        try {
            $this->reportData = $accountingService->getBalanceSheet(
                (int)$marqueeId,
                (int)$this->financial_year_id,
                $this->asOfDate,
                $this->branch_id ? (int)$this->branch_id : null
            );
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->reportData = null;
        }
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $user = auth()->user();

        $financialYears = FinancialYear::where('marquee_id', $marqueeId)->orderBy('start_date', 'desc')->get();
        
        if ($user->branch_id && !$user->isSuperAdmin()) {
            $branches = Branch::where('id', $user->branch_id)->get();
        } else {
            $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('name')->get();
        }

        return view('livewire.finance.balance-sheet', [
            'financialYears' => $financialYears,
            'branches' => $branches,
        ]);
    }
}
