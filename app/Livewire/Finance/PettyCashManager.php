<?php

namespace App\Livewire\Finance;

use App\Models\Account;
use App\Models\Branch;
use App\Models\CashBankAccount;
use App\Models\PettyCashAccount;
use App\Models\User;
use App\Services\ExpenseService;
use Livewire\Component;
use Livewire\WithPagination;

class PettyCashManager extends Component
{
    use WithPagination;

    // Account fields
    public $account_name;
    public $branch_id;
    public $gl_account_id;
    public $custodian_id;
    public $limit_amount = 0.00;
    public $current_balance = 0.00;
    public $is_active = true;

    // Replenishment fields
    public $replenishAccountId;
    public $replenishAmount;
    public $replenishSource = 'Cash';
    public $replenishBankAccountId;

    // Reconciliation fields
    public $reconcileAccountId;
    public $physicalBalance;
    public $reconcileNotes;

    public $editId = null;
    public $isFormOpen = false;
    public $isReplenishOpen = false;
    public $isReconcileOpen = false;

    // Search & Filter
    public $search = '';
    public $filterBranch = '';

    protected $rules = [
        'account_name' => 'required|string|max:100',
        'branch_id' => 'required|exists:branches,id',
        'gl_account_id' => 'nullable|exists:accounts,id',
        'custodian_id' => 'required|exists:users,id',
        'limit_amount' => 'required|numeric|min:0',
        'current_balance' => 'required|numeric|min:0',
        'is_active' => 'boolean',
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterBranch() { $this->resetPage(); }

    public function openCreateForm()
    {
        $this->resetInputFields();
        $this->isFormOpen = true;
    }

    public function resetInputFields()
    {
        $this->account_name = '';
        $this->branch_id = '';
        $this->gl_account_id = '';
        $this->custodian_id = '';
        $this->limit_amount = 0.00;
        $this->current_balance = 0.00;
        $this->is_active = true;
        $this->editId = null;
    }

    public function closeForm()
    {
        $this->isFormOpen = false;
        $this->isReplenishOpen = false;
        $this->isReconcileOpen = false;
    }

    public function save()
    {
        $this->validate();

        $user = auth()->user();
        $marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;

        $resolvedGlId = $this->gl_account_id;
        if (!$resolvedGlId) {
            $resolvedGlId = Account::where('marquee_id', $marqueeId)
                ->where('nature', 'Asset')
                ->where(function ($q) {
                    $q->where('account_code', '1002')
                      ->orWhere('name', 'like', '%Petty%')
                      ->orWhere('account_code', '1001')
                      ->orWhere('name', 'like', '%Cash%');
                })
                ->orderBy('account_code')
                ->value('id');
        }

        $data = [
            'marquee_id' => $marqueeId,
            'branch_id' => $this->branch_id,
            'account_name' => $this->account_name,
            'gl_account_id' => $resolvedGlId,
            'custodian_id' => $this->custodian_id,
            'limit_amount' => (float)$this->limit_amount,
            'current_balance' => (float)$this->current_balance,
            'is_active' => (bool)$this->is_active,
        ];

        if ($this->editId) {
            $account = PettyCashAccount::findOrFail($this->editId);
            $account->update($data);
            session()->flash('success', 'Petty Cash account updated successfully.');
        } else {
            PettyCashAccount::create($data);
            session()->flash('success', 'Petty Cash account created successfully.');
        }

        $this->isFormOpen = false;
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $account = PettyCashAccount::findOrFail($id);
        $this->editId = $account->id;
        $this->account_name = $account->account_name;
        $this->branch_id = $account->branch_id;
        $this->gl_account_id = $account->gl_account_id;
        $this->custodian_id = $account->custodian_id;
        $this->limit_amount = (float)$account->limit_amount;
        $this->current_balance = (float)$account->current_balance;
        $this->is_active = $account->is_active;
        $this->isFormOpen = true;
    }

    public function openReplenish($id)
    {
        $this->replenishAccountId = $id;
        $this->replenishAmount = '';
        $this->replenishSource = 'Cash';
        $this->replenishBankAccountId = '';
        $this->isReplenishOpen = true;
    }

    public function submitReplenish(ExpenseService $expenseService)
    {
        $this->validate([
            'replenishAmount' => 'required|numeric|min:0.01',
            'replenishSource' => 'required|in:Cash,Bank',
            'replenishBankAccountId' => 'required_if:replenishSource,Bank',
        ]);

        try {
            $expenseService->replenishPettyCash(
                $this->replenishAccountId,
                (float)$this->replenishAmount,
                $this->replenishSource,
                $this->replenishBankAccountId ?: null
            );

            session()->flash('success', 'Petty Cash account replenished successfully.');
            $this->isReplenishOpen = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openReconcile($id)
    {
        $this->reconcileAccountId = $id;
        $account = PettyCashAccount::findOrFail($id);
        $this->physicalBalance = (float)$account->current_balance;
        $this->reconcileNotes = '';
        $this->isReconcileOpen = true;
    }

    public function submitReconcile(ExpenseService $expenseService)
    {
        $this->validate([
            'physicalBalance' => 'required|numeric|min:0',
            'reconcileNotes' => 'nullable|string',
        ]);

        try {
            $expenseService->reconcilePettyCash(
                $this->reconcileAccountId,
                (float)$this->physicalBalance,
                $this->reconcileNotes
            );

            session()->flash('success', 'Petty cash drawer reconciled successfully.');
            $this->isReconcileOpen = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $user = auth()->user();
        $marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;

        $query = PettyCashAccount::where('marquee_id', $marqueeId)
            ->with(['branch', 'glAccount', 'custodian']);

        if ($this->search) {
            $query->where('account_name', 'like', '%' . $this->search . '%');
        }

        if ($this->filterBranch) {
            $query->where('branch_id', $this->filterBranch);
        }

        $accounts = $query->paginate(10);

        // KPI metrics
        $totalBalance = (float)PettyCashAccount::where('marquee_id', $marqueeId)->where('is_active', true)->sum('current_balance');
        $activeDrawersCount = PettyCashAccount::where('marquee_id', $marqueeId)->where('is_active', true)->count();
        $lowBalanceCount = PettyCashAccount::where('marquee_id', $marqueeId)->where('is_active', true)->whereRaw('current_balance < (limit_amount * 0.20)')->count();
        $disbursedMtd = (float)\App\Models\Expense::where('marquee_id', $marqueeId)
            ->where('payment_method', 'Petty Cash')
            ->whereBetween('expense_date', [now()->startOfMonth()->format('Y-m-d'), now()->endOfMonth()->format('Y-m-d')])
            ->whereNotIn('status', [\App\Models\Expense::STATUS_DRAFT, \App\Models\Expense::STATUS_REJECTED])
            ->sum('total_amount_base');

        // Fetch dropdown options
        $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->get();
        
        $glAccounts = Account::where('marquee_id', $marqueeId)
            ->where('nature', 'Asset')
            ->whereDoesntHave('children')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $users = User::where('marquee_id', $marqueeId)->where('status', 'active')->get();
        $bankAccounts = CashBankAccount::where('marquee_id', $marqueeId)->get();

        return view('livewire.finance.petty-cash-manager', [
            'accounts' => $accounts,
            'totalBalance' => $totalBalance,
            'activeDrawersCount' => $activeDrawersCount,
            'lowBalanceCount' => $lowBalanceCount,
            'disbursedMtd' => $disbursedMtd,
            'branches' => $branches,
            'glAccounts' => $glAccounts,
            'users' => $users,
            'bankAccounts' => $bankAccounts,
        ]);
    }
}
