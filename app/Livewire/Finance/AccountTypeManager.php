<?php

namespace App\Livewire\Finance;

use App\Models\AccountType;
use Livewire\Component;
use Illuminate\Support\Facades\Gate;

class AccountTypeManager extends Component
{
    // Form fields
    public $editId = null;
    public $name = '';
    public $code = '';
    public $nature = '';
    public $showForm = false;
    public $confirmingDeletionId = null;

    // Filters
    public $search = '';
    public $natureFilter = 'all';

    protected $rules = [
        'name' => 'required|string|max:100',
        'code' => 'required|string|max:20',
        'nature' => 'required|in:Asset,Liability,Equity,Income,Expense',
    ];

    public function mount()
    {
        // Any authenticated user with finance access can view
    }

    public function updatingSearch()
    {
        // No pagination but keep it for future use
    }

    public function create()
    {
        $this->authorizeWrite();
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id)
    {
        $this->authorizeWrite();
        $accountType = AccountType::findOrFail($id);
        $this->editId = $accountType->id;
        $this->name = $accountType->name;
        $this->code = $accountType->code;
        $this->nature = $accountType->nature;
        $this->showForm = true;
    }

    public function resetForm()
    {
        $this->editId = null;
        $this->name = '';
        $this->code = '';
        $this->nature = '';
        $this->showForm = false;
        $this->resetErrorBag();
    }

    public function save()
    {
        $this->authorizeWrite();
        $this->validate();
        $marqueeId = $this->getWriteScope();

        // Prevent duplicate code within the same scope
        $exists = AccountType::where('code', strtoupper($this->code))
            ->when($marqueeId, fn($q) => $q->where('marquee_id', $marqueeId), fn($q) => $q->whereNull('marquee_id'))
            ->when($this->editId, fn($q) => $q->where('id', '!=', $this->editId))
            ->exists();

        if ($exists) {
            $this->addError('code', 'This account type code is already in use.');
            return;
        }

        $data = [
            'marquee_id' => $marqueeId,
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'nature' => $this->nature,
        ];

        if ($this->editId) {
            $at = AccountType::findOrFail($this->editId);
            $at->update($data);
            session()->flash('success', 'COA Category updated successfully.');
        } else {
            AccountType::create($data);
            session()->flash('success', 'COA Category created successfully.');
        }

        $this->resetForm();
    }

    public function confirmDeletion(int $id)
    {
        $this->authorizeWrite();
        $this->confirmingDeletionId = $id;
    }

    public function deleteRecord()
    {
        $this->authorizeWrite();
        if ($this->confirmingDeletionId) {
            $at = AccountType::findOrFail($this->confirmingDeletionId);

            // Block deletion if any accounts are mapped to this type
            if ($at->accounts()->exists()) {
                session()->flash('error', 'Cannot delete this COA category because it is referenced by existing accounts.');
                $this->confirmingDeletionId = null;
                return;
            }

            $at->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'COA Category deleted successfully.');
        }
    }

    public function cancelDelete()
    {
        $this->confirmingDeletionId = null;
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;

        $query = AccountType::forTenant($marqueeId)->orderBy('nature')->orderBy('code');

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->natureFilter !== 'all') {
            $query->where('nature', $this->natureFilter);
        }

        $accountTypes = $query->get();

        $isSuperAdmin = auth()->user()->isSuperAdmin();

        return view('livewire.finance.account-type-manager', compact('accountTypes', 'isSuperAdmin'))
            ->layout('layouts.admin');
    }

    /**
     * Determine the marquee_id scope to write records:
     * - Super Admin writes globally (null)
     * - Tenant Owner/Manager writes to their marquee
     */
    private function getWriteScope(): ?int
    {
        if (auth()->user()->isSuperAdmin()) {
            return null;
        }
        return auth()->user()->marquee_id;
    }

    /**
     * Authorize write operations using native Laravel check.
     */
    private function authorizeWrite(): void
    {
        $user = auth()->user();
        abort_unless(
            $user->isSuperAdmin() || $user->hasPermission('manage_accounting'),
            403,
            'You do not have permission to manage COA categories.'
        );
    }
}
