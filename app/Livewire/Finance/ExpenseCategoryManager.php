<?php

namespace App\Livewire\Finance;

use App\Models\Account;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseCategoryManager extends Component
{
    use WithPagination;

    public $name;
    public $parent_id;
    public $category_code;
    public $description;
    public $default_account_id;
    public $default_tax_rate = 0.00;
    public $default_budget_amount = 0.00;
    public $display_order = 0;
    public $is_active = true;

    public $editId = null;
    public $confirmingDeletion = null;

    // Filter / search
    public $search = '';
    public $statusFilter = 'all';

    protected $rules = [
        'name' => 'required|string|max:100',
        'parent_id' => 'nullable|exists:expense_categories,id',
        'category_code' => 'required|string|max:20',
        'description' => 'nullable|string',
        'default_account_id' => 'nullable|exists:accounts,id',
        'default_tax_rate' => 'required|numeric|min:0|max:100',
        'default_budget_amount' => 'required|numeric|min:0',
        'display_order' => 'required|integer|min:0',
        'is_active' => 'boolean',
    ];

    public function mount()
    {
        // Reset inputs on initialization
        $this->resetInputFields();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function resetInputFields()
    {
        $this->name = '';
        $this->parent_id = '';
        $this->category_code = '';
        $this->description = '';
        $this->default_account_id = '';
        $this->default_tax_rate = 0.00;
        $this->default_budget_amount = 0.00;
        $this->display_order = 0;
        $this->is_active = true;
        $this->editId = null;
    }

    public function save()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_expense_settings'), 403);
        $this->validate();

        $marqueeId = auth()->user()->marquee_id;

        // Verify unique category code within tenant
        $dupQuery = ExpenseCategory::where('marquee_id', $marqueeId)
            ->where('category_code', $this->category_code);
        if ($this->editId) {
            $dupQuery->where('id', '!=', $this->editId);
        }
        if ($dupQuery->exists()) {
            $this->addError('category_code', 'This category code is already in use.');
            return;
        }

        $data = [
            'marquee_id' => $marqueeId,
            'parent_id' => $this->parent_id ?: null,
            'category_code' => strtoupper($this->category_code),
            'name' => $this->name,
            'description' => $this->description,
            'default_account_id' => $this->default_account_id ?: null,
            'default_tax_rate' => $this->default_tax_rate,
            'default_budget_amount' => $this->default_budget_amount,
            'display_order' => $this->display_order,
            'is_active' => $this->is_active,
        ];

        if ($this->editId) {
            $category = ExpenseCategory::findOrFail($this->editId);
            $category->update($data);
            session()->flash('success', 'Expense Category updated successfully.');
        } else {
            ExpenseCategory::create($data);
            session()->flash('success', 'Expense Category created successfully.');
        }

        $this->resetInputFields();
    }

    public function edit($id)
    {
        $category = ExpenseCategory::findOrFail($id);
        $this->editId = $category->id;
        $this->parent_id = $category->parent_id ?? '';
        $this->category_code = $category->category_code;
        $this->name = $category->name;
        $this->description = $category->description;
        $this->default_account_id = $category->default_account_id ?? '';
        $this->default_tax_rate = (float)$category->default_tax_rate;
        $this->default_budget_amount = (float)$category->default_budget_amount;
        $this->display_order = $category->display_order;
        $this->is_active = $category->is_active;
    }

    public function moveUp($id)
    {
        $category = ExpenseCategory::findOrFail($id);
        if ($category->display_order > 0) {
            $category->decrement('display_order');
        }
    }

    public function moveDown($id)
    {
        $category = ExpenseCategory::findOrFail($id);
        $category->increment('display_order');
    }

    public function confirmDelete($id)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_expense_settings'), 403);
        $this->confirmingDeletion = $id;
    }

    public function deleteCategory()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_expense_settings'), 403);
        if ($this->confirmingDeletion) {
            $category = ExpenseCategory::findOrFail($this->confirmingDeletion);
            
            // Check if there are child subcategories
            if ($category->children()->exists()) {
                session()->flash('error', 'Cannot delete a parent category with subcategories.');
                $this->confirmingDeletion = null;
                return;
            }

            // Block if category is referenced by existing expenses
            $hasExpenses = Expense::where('expense_category_id', $category->id)->exists();
            if ($hasExpenses) {
                session()->flash('error', 'Cannot delete this category because it has recorded expenses. Deactivate it instead.');
                $this->confirmingDeletion = null;
                return;
            }

            $category->delete();
            session()->flash('success', 'Category deleted successfully.');
            $this->confirmingDeletion = null;
            $this->resetInputFields();
        }
    }

    public function cancelDelete()
    {
        $this->confirmingDeletion = null;
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;

        // Fetch leaf general ledger accounts of nature 'Expense'
        $accounts = Account::where('marquee_id', $marqueeId)
            ->where('nature', 'Expense')
            ->whereDoesntHave('children')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        // Get categories formatted hierarchical with search & status filters
        $categoryQuery = ExpenseCategory::where('marquee_id', $marqueeId)
            ->with(['parent', 'defaultAccount'])
            ->orderBy('display_order')
            ->orderBy('id');

        if (!empty($this->search)) {
            $categoryQuery->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('category_code', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter !== 'all') {
            $categoryQuery->where('is_active', $this->statusFilter === 'active');
        }

        $categoriesList = $categoryQuery->get();

        return view('livewire.finance.expense-category-manager', [
            'accounts' => $accounts,
            'categories' => $categoriesList,
        ]);
    }
}
