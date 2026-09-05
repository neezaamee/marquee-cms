<?php

namespace App\Livewire\Finance;

use App\Models\Account;
use App\Models\AccountType;
use App\Services\AccountingService;
use Livewire\Component;

class ChartOfAccounts extends Component
{
    public $isSaas = false;

    public $account_code = '';
    public $name = '';
    public $parent_id = '';
    public $account_type_id = '';
    public $description = '';
    public $is_active = true;

    public $editId = null;
    public $isFormOpen = false;

    public function getMarqueeId(): ?int
    {
        if ($this->isSaas) {
            return null;
        }

        $user = auth()->user();
        if (!$user) {
            return null;
        }

        return $user->getActiveMarqueeId() ?: $user->marquee_id;
    }

    public function getRules()
    {
        $marqueeId = $this->getMarqueeId();
        $ignoreId = $this->editId ?: 'NULL';
        $marqueeCondition = $this->isSaas || is_null($marqueeId) ? 'NULL' : $marqueeId;
        
        return [
            'account_code' => "required|string|max:50|unique:accounts,account_code,{$ignoreId},id,marquee_id,{$marqueeCondition}",
            'name' => 'required|string|max:150',
            'parent_id' => 'nullable|exists:accounts,id',
            'account_type_id' => 'required|exists:account_types,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
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
        $this->account_code = '';
        $this->name = '';
        $this->parent_id = '';
        $this->account_type_id = '';
        $this->description = '';
        $this->is_active = true;
        $this->editId = null;
        $this->resetErrorBag();
    }

    public function save()
    {
        $this->validate();

        $type = AccountType::findOrFail($this->account_type_id);
        $marqueeId = $this->getMarqueeId();

        // Parent validation: cannot make itself its own parent
        if ($this->editId && $this->parent_id == $this->editId) {
            $this->addError('parent_id', 'An account cannot be its own parent.');
            return;
        }

        $data = [
            'marquee_id' => $marqueeId,
            'account_code' => $this->account_code,
            'name' => $this->name,
            'parent_id' => $this->parent_id ?: null,
            'account_type_id' => $this->account_type_id,
            'nature' => $type->nature,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];

        if ($this->editId) {
            $account = Account::withoutGlobalScope('tenant')->findOrFail($this->editId);
            
            // If system generated, protect critical fields
            if ($account->system_generated) {
                unset($data['account_code']);
                unset($data['account_type_id']);
                unset($data['nature']);
                unset($data['parent_id']);
            }

            $account->update($data);
            session()->flash('success', 'Account updated successfully.');
        } else {
            $data['system_generated'] = false;
            Account::create($data);
            session()->flash('success', 'Account created successfully.');
        }

        $this->closeForm();
    }

    public function edit($id)
    {
        $this->resetInputFields();
        $account = Account::withoutGlobalScope('tenant')->findOrFail($id);
        $this->editId = $account->id;
        $this->account_code = $account->account_code;
        $this->name = $account->name;
        $this->parent_id = $account->parent_id ?? '';
        $this->account_type_id = $account->account_type_id;
        $this->description = $account->description;
        $this->is_active = $account->is_active;
        $this->isFormOpen = true;
    }

    public function delete($id)
    {
        $account = Account::withoutGlobalScope('tenant')->findOrFail($id);

        if ($account->system_generated) {
            session()->flash('error', 'System-generated accounts cannot be deleted.');
            return;
        }

        // Check for children
        if ($account->children()->exists()) {
            session()->flash('error', 'Cannot delete this account because it has sub-accounts.');
            return;
        }

        // Check for journal items
        if ($account->journalVoucherItems()->exists()) {
            session()->flash('error', 'Cannot delete this account because it contains posted transactions.');
            return;
        }

        // Check for opening balances
        if ($account->openingBalances()->exists()) {
            session()->flash('error', 'Cannot delete this account because it has opening balance records.');
            return;
        }

        $account->delete();
        session()->flash('success', 'Account deleted successfully.');
    }

    private function getAccountsTree()
    {
        $marqueeId = $this->getMarqueeId();

        $query = Account::withoutGlobalScope('tenant')->with(['accountType', 'parent'])->orderBy('account_code');
        if ($this->isSaas) {
            $query->whereNull('marquee_id');
        } else {
            if ($marqueeId) {
                $query->where('marquee_id', $marqueeId);
            }
        }
        $accounts = $query->get();

        // If for a tenant there are no accounts yet, auto-seed defaults on-the-fly!
        if (!$this->isSaas && $marqueeId && $accounts->isEmpty()) {
            app(AccountingService::class)->seedTenantDefaultAccounts($marqueeId);
            $accounts = Account::withoutGlobalScope('tenant')
                ->with(['accountType', 'parent'])
                ->where('marquee_id', $marqueeId)
                ->orderBy('account_code')
                ->get();
        }
            
        $tree = [];
        $visited = [];
        $this->buildTree($accounts, null, 0, $tree, $visited);
        return $tree;
    }

    private function buildTree($accounts, $parentId, $depth, &$tree, &$visited = [])
    {
        $matched = $accounts->filter(function ($account) use ($parentId) {
            if (is_null($parentId)) {
                return is_null($account->parent_id) || $account->parent_id === 0 || $account->parent_id === '';
            }
            return (int) $account->parent_id === (int) $parentId;
        });

        foreach ($matched as $account) {
            if (in_array($account->id, $visited)) {
                continue;
            }
            $visited[] = $account->id;
            $account->depth = $depth;
            $tree[] = $account;
            $this->buildTree($accounts, $account->id, $depth + 1, $tree, $visited);
        }

        // Top level catch for any orphaned accounts
        if (is_null($parentId)) {
            foreach ($accounts as $account) {
                if (!in_array($account->id, $visited)) {
                    $visited[] = $account->id;
                    $account->depth = 0;
                    $tree[] = $account;
                    $this->buildTree($accounts, $account->id, 1, $tree, $visited);
                }
            }
        }
    }

    public function render()
    {
        $marqueeId = $this->getMarqueeId();
        $accountsTree = $this->getAccountsTree();
        $accountTypes = AccountType::forTenant($marqueeId)->orderBy('nature')->orderBy('name')->get();
        
        // Potential parents should not include the current editing account or its children to prevent cycles
        $potentialParents = Account::withoutGlobalScope('tenant')->where('is_active', true);
        if ($this->isSaas) {
            $potentialParents->whereNull('marquee_id');
        } else {
            if ($marqueeId) {
                $potentialParents->where('marquee_id', $marqueeId);
            }
        }
        if ($this->editId) {
            $potentialParents->where('id', '!=', $this->editId);
        }
        $potentialParents = $potentialParents->orderBy('account_code')->get();

        return view('livewire.finance.chart-of-accounts', [
            'accounts' => $accountsTree,
            'accountTypes' => $accountTypes,
            'potentialParents' => $potentialParents,
        ]);
    }
}
