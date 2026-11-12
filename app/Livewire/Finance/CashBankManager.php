<?php

namespace App\Livewire\Finance;

use App\Models\Account;
use App\Models\AccountOpeningBalance;
use App\Models\CashBankAccount;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class CashBankManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $account_id = '';
    public $type = 'cash';
    public $bank_name = '';
    public $account_number = '';
    public $iban = '';
    public $branch_name = '';
    public $status = 'active';

    public $editId = null;
    public $isFormOpen = false;

    // Master data
    public $accounts = [];

    public function getRules()
    {
        return [
            'account_id' => 'required|exists:accounts,id|unique:cash_bank_accounts,account_id,' . ($this->editId ?: 'NULL') . ',id,deleted_at,NULL',
            'type' => 'required|in:cash,bank',
            'bank_name' => 'required_if:type,bank|nullable|string|max:100',
            'account_number' => 'required_if:type,bank|nullable|string|max:50',
            'iban' => 'nullable|string|max:50',
            'branch_name' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
        ];
    }

    protected $messages = [
        'account_id.unique' => 'This account is already mapped to a cash or bank account.',
        'bank_name.required_if' => 'Bank Name is required for bank accounts.',
        'account_number.required_if' => 'Account Number is required for bank accounts.',
    ];

    public function getMarqueeId(): ?int
    {
        $user = auth()->user();
        return $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
    }

    public function mount()
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->isBusinessOwner() || $user->hasPermission('manage_accounting') || $user->hasPermission('manage_finance')), 403, 'Unauthorized access to cash and bank manager.');

        $marqueeId = $this->getMarqueeId();

        // Fetch leaf accounts under Current Assets to map
        $this->accounts = Account::where('marquee_id', $marqueeId)
            ->whereDoesntHave('children')
            ->where('is_active', true)
            ->whereHas('accountType', function($q) {
                $q->where('code', 'CURRENT_ASSETS');
            })
            ->orderBy('account_code')
            ->get();
    }

    public function openCreateForm()
    {
        $this->resetInputFields();
        $this->isFormOpen = true;
    }

    public function closeForm()
    {
        $this->isFormOpen = false;
        $this->resetInputFields();
    }

    private function resetInputFields()
    {
        $this->account_id = '';
        $this->type = 'cash';
        $this->bank_name = '';
        $this->account_number = '';
        $this->iban = '';
        $this->branch_name = '';
        $this->status = 'active';
        $this->editId = null;
        $this->resetErrorBag();
    }

    public function save()
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->hasPermission('manage_accounting') || $user->hasPermission('manage_finance')), 403, 'Unauthorized.');

        $this->validate();

        $marqueeId = $this->getMarqueeId();

        // Validate selected account belongs to this marquee
        $accountExists = Account::where('marquee_id', $marqueeId)->where('id', $this->account_id)->exists();
        if (!$accountExists) {
            $this->addError('account_id', 'Selected account does not belong to your organization.');
            return;
        }

        $data = [
            'marquee_id' => $marqueeId,
            'account_id' => $this->account_id,
            'type' => $this->type,
            'bank_name' => $this->type === 'bank' ? $this->bank_name : null,
            'account_number' => $this->type === 'bank' ? $this->account_number : null,
            'iban' => $this->type === 'bank' ? $this->iban : null,
            'branch_name' => $this->type === 'bank' ? $this->branch_name : null,
            'status' => $this->status,
        ];

        if ($this->editId) {
            $account = CashBankAccount::where('marquee_id', $marqueeId)->findOrFail($this->editId);
            $account->update($data);

            \App\Models\ActivityLog::create([
                'user_id' => $user->id,
                'marquee_id' => $marqueeId,
                'action' => 'cash_bank_account_updated',
                'description' => "Updated {$account->type} account: {$account->account_number} ({$account->bank_name})",
                'model_type' => CashBankAccount::class,
                'model_id' => $account->id,
            ]);

            session()->flash('success', 'Cash/Bank Account updated successfully.');
        } else {
            $account = CashBankAccount::create($data);

            \App\Models\ActivityLog::create([
                'user_id' => $user->id,
                'marquee_id' => $marqueeId,
                'action' => 'cash_bank_account_created',
                'description' => "Created {$account->type} account: {$account->account_number} ({$account->bank_name})",
                'model_type' => CashBankAccount::class,
                'model_id' => $account->id,
            ]);

            session()->flash('success', 'Cash/Bank Account registered successfully.');
        }

        $this->closeForm();
    }

    public function edit($id)
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->hasPermission('manage_accounting') || $user->hasPermission('manage_finance')), 403, 'Unauthorized.');

        $this->resetInputFields();
        $marqueeId = $this->getMarqueeId();
        $cb = CashBankAccount::where('marquee_id', $marqueeId)->findOrFail($id);
        $this->editId = $cb->id;
        $this->account_id = $cb->account_id;
        $this->type = $cb->type;
        $this->bank_name = $cb->bank_name;
        $this->account_number = $cb->account_number;
        $this->iban = $cb->iban;
        $this->branch_name = $cb->branch_name;
        $this->status = $cb->status;
        $this->isFormOpen = true;
    }

    public function delete($id)
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->hasPermission('manage_accounting') || $user->hasPermission('manage_finance')), 403, 'Unauthorized.');

        $marqueeId = $this->getMarqueeId();
        $cb = CashBankAccount::where('marquee_id', $marqueeId)->findOrFail($id);
        $cb->delete(); // soft delete

        \App\Models\ActivityLog::create([
            'user_id' => $user->id,
            'marquee_id' => $marqueeId,
            'action' => 'cash_bank_account_deleted',
            'description' => "Deleted {$cb->type} account: {$cb->account_number} ({$cb->bank_name})",
            'model_type' => CashBankAccount::class,
            'model_id' => $cb->id,
        ]);

        session()->flash('success', 'Cash/Bank Account registration removed successfully.');
    }

    public function render()
    {
        $marqueeId = $this->getMarqueeId();

        $cashBankAccounts = CashBankAccount::where('marquee_id', $marqueeId)
            ->with(['account'])
            ->orderBy('type')
            ->paginate(15);

        $accountIds = $cashBankAccounts->pluck('account_id')->filter()->unique();

        $openingBalances = AccountOpeningBalance::whereIn('account_id', $accountIds)
            ->groupBy('account_id')
            ->selectRaw('account_id, COALESCE(SUM(debit - credit), 0) as balance')
            ->pluck('balance', 'account_id');

        $jvSummaries = DB::table('journal_voucher_items')
            ->join('journal_vouchers', 'journal_voucher_items.journal_voucher_id', '=', 'journal_vouchers.id')
            ->whereNull('journal_vouchers.deleted_at')
            ->where('journal_vouchers.status', 'posted')
            ->where('journal_vouchers.marquee_id', $marqueeId)
            ->whereIn('journal_voucher_items.account_id', $accountIds)
            ->groupBy('journal_voucher_items.account_id')
            ->selectRaw('journal_voucher_items.account_id, COALESCE(SUM(debit - credit), 0) as balance')
            ->pluck('balance', 'account_id');

        $accountBalances = [];
        foreach ($accountIds as $accId) {
            $opening = (float) ($openingBalances->get($accId, 0));
            $jv = (float) ($jvSummaries->get($accId, 0));
            $accountBalances[$accId] = $opening + $jv;
        }

        // Overall totals for KPI cards
        $allCb = CashBankAccount::where('marquee_id', $marqueeId)->get();
        $allBankAccIds = $allCb->where('type', 'bank')->pluck('account_id')->filter()->unique();
        $allCashAccIds = $allCb->where('type', 'cash')->pluck('account_id')->filter()->unique();

        if ($allBankAccIds->isEmpty()) {
            $allBankAccIds = Account::where('marquee_id', $marqueeId)
                ->where(function ($q) {
                    $q->where('account_code', '1002')->orWhere('name', 'like', '%Bank%');
                })
                ->pluck('id');
        }

        if ($allCashAccIds->isEmpty()) {
            $allCashAccIds = Account::where('marquee_id', $marqueeId)
                ->where(function ($q) {
                    $q->where('account_code', '1001')->orWhere('name', 'like', '%Cash%');
                })
                ->pluck('id');
        }

        $allBankOpening = (float) AccountOpeningBalance::whereIn('account_id', $allBankAccIds)
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
            ->value('balance');
        $allBankJv = (float) DB::table('journal_voucher_items')
            ->join('journal_vouchers', 'journal_voucher_items.journal_voucher_id', '=', 'journal_vouchers.id')
            ->whereNull('journal_vouchers.deleted_at')
            ->where('journal_vouchers.status', 'posted')
            ->where('journal_vouchers.marquee_id', $marqueeId)
            ->whereIn('journal_voucher_items.account_id', $allBankAccIds)
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
            ->value('balance');
        $totalBankBalance = $allBankOpening + $allBankJv;

        $allCashOpening = (float) AccountOpeningBalance::whereIn('account_id', $allCashAccIds)
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
            ->value('balance');
        $allCashJv = (float) DB::table('journal_voucher_items')
            ->join('journal_vouchers', 'journal_voucher_items.journal_voucher_id', '=', 'journal_vouchers.id')
            ->whereNull('journal_vouchers.deleted_at')
            ->where('journal_vouchers.status', 'posted')
            ->where('journal_vouchers.marquee_id', $marqueeId)
            ->whereIn('journal_voucher_items.account_id', $allCashAccIds)
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
            ->value('balance');
        $totalCashBalance = $allCashOpening + $allCashJv;

        return view('livewire.finance.cash-bank-manager', [
            'cashBankAccounts' => $cashBankAccounts,
            'accountBalances' => $accountBalances,
            'totalBankBalance' => $totalBankBalance,
            'totalCashBalance' => $totalCashBalance,
            'totalLiquidBalance' => $totalBankBalance + $totalCashBalance,
        ]);
    }
}
