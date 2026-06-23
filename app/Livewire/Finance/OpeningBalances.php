<?php

namespace App\Livewire\Finance;

use App\Models\Account;
use App\Models\AccountOpeningBalance;
use App\Models\Branch;
use App\Models\FinancialYear;
use Livewire\Component;

class OpeningBalances extends Component
{
    public $financial_year_id = '';
    public $branch_id = '';
    
    // Associative array to hold inputs: [account_id => ['debit' => x, 'credit' => y]]
    public $balances = [];

    protected $rules = [
        'financial_year_id' => 'required|exists:financial_years,id',
        'branch_id' => 'nullable|exists:branches,id',
        'balances.*.debit' => 'nullable|numeric|min:0',
        'balances.*.credit' => 'nullable|numeric|min:0',
    ];

    public function mount()
    {
        $marqueeId = auth()->user()->marquee_id;
        $userBranchId = auth()->user()->branch_id;

        // Default to active default financial year
        $activeFy = FinancialYear::where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->where('is_default', true)
            ->first() ?? FinancialYear::where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->first();

        $this->financial_year_id = $activeFy?->id ?? '';
        
        // Enforce user branch scope
        if ($userBranchId) {
            $this->branch_id = $userBranchId;
        }

        $this->loadOpeningBalances();
    }

    public function updatedFinancialYearId()
    {
        $this->loadOpeningBalances();
    }

    public function updatedBranchId()
    {
        $this->loadOpeningBalances();
    }

    public function loadOpeningBalances()
    {
        $this->balances = [];
        
        if (empty($this->financial_year_id)) {
            return;
        }

        $marqueeId = auth()->user()->marquee_id;

        // Get only leaf accounts (accounts without child accounts)
        $leafAccounts = Account::where('marquee_id', $marqueeId)
            ->whereDoesntHave('children')
            ->where('is_active', true)
            ->get();

        // Get existing opening balances for selected year and branch
        $existingBalances = AccountOpeningBalance::where('financial_year_id', $this->financial_year_id)
            ->where(function($q) {
                if ($this->branch_id) {
                    $q->where('branch_id', $this->branch_id);
                } else {
                    $q->whereNull('branch_id');
                }
            })
            ->get()
            ->keyBy('account_id');

        foreach ($leafAccounts as $account) {
            $existing = $existingBalances->get($account->id);
            $this->balances[$account->id] = [
                'debit' => $existing ? (float)$existing->debit : '',
                'credit' => $existing ? (float)$existing->credit : '',
            ];
        }
    }

    public function save()
    {
        $this->validate();

        if (empty($this->financial_year_id)) {
            session()->flash('error', 'Please select a financial year.');
            return;
        }

        // Check if selected financial year is closed
        $fy = FinancialYear::findOrFail($this->financial_year_id);
        if ($fy->status === 'closed') {
            session()->flash('error', 'Cannot update opening balances for a closed financial year.');
            return;
        }

        $marqueeId = auth()->user()->marquee_id;

        \Illuminate\Support\Facades\DB::transaction(function () use ($marqueeId) {
            foreach ($this->balances as $accountId => $bal) {
                $debit = (float)($bal['debit'] ?: 0);
                $credit = (float)($bal['credit'] ?: 0);

                if ($debit == 0 && $credit == 0) {
                    // Delete existing record if any to keep database clean
                    AccountOpeningBalance::where('financial_year_id', $this->financial_year_id)
                        ->where('account_id', $accountId)
                        ->where(function($q) {
                            if ($this->branch_id) {
                                $q->where('branch_id', $this->branch_id);
                            } else {
                                $q->whereNull('branch_id');
                            }
                        })
                        ->delete();
                } else {
                    AccountOpeningBalance::updateOrCreate(
                        [
                            'marquee_id' => $marqueeId,
                            'financial_year_id' => $this->financial_year_id,
                            'branch_id' => $this->branch_id ?: null,
                            'account_id' => $accountId,
                        ],
                        [
                            'debit' => $debit,
                            'credit' => $credit,
                        ]
                    );
                }
            }
        });

        session()->flash('success', 'Opening balances saved successfully.');
        $this->loadOpeningBalances();
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $user = auth()->user();

        $financialYears = FinancialYear::where('marquee_id', $marqueeId)->orderBy('start_date', 'desc')->get();
        
        // Scope branches selection
        if ($user->branch_id && !$user->isSuperAdmin()) {
            $branches = Branch::where('id', $user->branch_id)->get();
        } else {
            $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('name')->get();
        }

        // Get accounts with their details to show in list
        $accounts = Account::where('marquee_id', $marqueeId)
            ->whereDoesntHave('children')
            ->where('is_active', true)
            ->with(['accountType'])
            ->orderBy('account_code')
            ->get();

        // Calculate sums of inputs for UI display
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($this->balances as $bal) {
            $totalDebit += (float)($bal['debit'] ?: 0);
            $totalCredit += (float)($bal['credit'] ?: 0);
        }

        return view('livewire.finance.opening-balances', [
            'financialYears' => $financialYears,
            'branches' => $branches,
            'accounts' => $accounts,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
        ]);
    }
}
