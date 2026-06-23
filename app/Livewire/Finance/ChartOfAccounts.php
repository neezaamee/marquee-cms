<?php

namespace App\Livewire\Finance;

use App\Models\Account;
use App\Models\AccountType;
use Livewire\Component;

class ChartOfAccounts extends Component
{
    public $account_code = '';
    public $name = '';
    public $parent_id = '';
    public $account_type_id = '';
    public $description = '';
    public $is_active = true;

    public $editId = null;
    public $isFormOpen = false;

    public function getRules()
    {
        $marqueeId = auth()->user()->marquee_id;
        $ignoreId = $this->editId ?: 'NULL';
        
        return [
            'account_code' => "required|string|max:50|unique:accounts,account_code,{$ignoreId},id,marquee_id,{$marqueeId}",
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
        $marqueeId = auth()->user()->marquee_id;

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
            $account = Account::findOrFail($this->editId);
            
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
        $account = Account::findOrFail($id);
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
        $account = Account::findOrFail($id);

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
        $accounts = Account::with(['accountType', 'parent'])
            ->orderBy('account_code')
            ->get();
            
        $tree = [];
        $this->buildTree($accounts, null, 0, $tree);
        return $tree;
    }

    private function buildTree($accounts, $parentId, $depth, &$tree)
    {
        foreach ($accounts->where('parent_id', $parentId) as $account) {
            $account->depth = $depth;
            $tree[] = $account;
            $this->buildTree($accounts, $account->id, $depth + 1, $tree);
        }
    }

    public function render()
    {
        $accountsTree = $this->getAccountsTree();
        $accountTypes = AccountType::forTenant()->orderBy('nature')->orderBy('name')->get();
        
        // Potential parents should not include the current editing account or its children to prevent cycles
        $potentialParents = Account::where('is_active', true);
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
