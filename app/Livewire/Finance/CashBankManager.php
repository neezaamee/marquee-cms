<?php

namespace App\Livewire\Finance;

use App\Models\Account;
use App\Models\CashBankAccount;
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
        $this->validate();

        $marqueeId = $this->getMarqueeId();

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
            $account = CashBankAccount::findOrFail($this->editId);
            $account->update($data);
            session()->flash('success', 'Cash/Bank Account updated successfully.');
        } else {
            CashBankAccount::create($data);
            session()->flash('success', 'Cash/Bank Account registered successfully.');
        }

        $this->closeForm();
    }

    public function edit($id)
    {
        $this->resetInputFields();
        $cb = CashBankAccount::findOrFail($id);
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
        $cb = CashBankAccount::findOrFail($id);
        $cb->delete(); // soft delete
        session()->flash('success', 'Cash/Bank Account registration removed successfully.');
    }

    public function render()
    {
        $marqueeId = $this->getMarqueeId();

        $cashBankAccounts = CashBankAccount::where('marquee_id', $marqueeId)
            ->with(['account'])
            ->orderBy('type')
            ->paginate(10);

        return view('livewire.finance.cash-bank-manager', [
            'cashBankAccounts' => $cashBankAccounts,
        ]);
    }
}
