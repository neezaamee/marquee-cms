<?php

namespace App\Livewire\Finance;

use App\Models\Branch;
use App\Models\ExpenseBudget;
use App\Models\ExpenseCategory;
use Livewire\Component;
use Livewire\WithPagination;

class BudgetTracker extends Component
{
    use WithPagination;

    // Form fields
    public $branch_id;
    public $department;
    public $category_id;
    public $year;
    public $month;
    public $allocated_amount = 0.00;

    public $editId = null;
    public $isFormOpen = false;

    // Filters
    public $filterBranch;
    public $filterCategory;
    public $filterYear;

    protected $rules = [
        'branch_id' => 'nullable|exists:branches,id',
        'department' => 'nullable|string|max:100',
        'category_id' => 'required|exists:expense_categories,id',
        'year' => 'required|integer|min:2020|max:2100',
        'month' => 'nullable|integer|min:1|max:12',
        'allocated_amount' => 'required|numeric|min:0.01',
    ];

    public function mount()
    {
        $this->year = (int)date('Y');
        $this->filterYear = date('Y');
    }

    public function openCreateForm()
    {
        $this->resetInputFields();
        $this->isFormOpen = true;
    }

    public function resetInputFields()
    {
        $this->branch_id = '';
        $this->department = '';
        $this->category_id = '';
        $this->year = (int)date('Y');
        $this->month = '';
        $this->allocated_amount = 0.00;
        $this->editId = null;
    }

    public function closeForm()
    {
        $this->isFormOpen = false;
        $this->resetInputFields();
    }

    public function save()
    {
        $this->validate();

        $marqueeId = auth()->user()->marquee_id;

        // Check duplicates
        $dupQuery = ExpenseBudget::where('marquee_id', $marqueeId)
            ->where('category_id', $this->category_id)
            ->where('year', $this->year)
            ->where('month', $this->month ?: null)
            ->where('department', $this->department ?: null)
            ->where('branch_id', $this->branch_id ?: null);

        if ($this->editId) {
            $dupQuery->where('id', '!=', $this->editId);
        }

        if ($dupQuery->exists()) {
            $this->addError('category_id', 'A budget limit for these parameters is already registered.');
            return;
        }

        $data = [
            'marquee_id' => $marqueeId,
            'branch_id' => $this->branch_id ?: null,
            'department' => $this->department ?: null,
            'category_id' => $this->category_id,
            'year' => $this->year,
            'month' => $this->month ?: null,
            'allocated_amount' => $this->allocated_amount,
        ];

        if ($this->editId) {
            $budget = ExpenseBudget::findOrFail($this->editId);
            $budget->update($data);
            session()->flash('success', 'Budget registry updated successfully.');
        } else {
            ExpenseBudget::create($data);
            session()->flash('success', 'Budget registry created successfully.');
        }

        $this->isFormOpen = false;
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $budget = ExpenseBudget::findOrFail($id);
        $this->editId = $budget->id;
        $this->branch_id = $budget->branch_id ?? '';
        $this->department = $budget->department ?? '';
        $this->category_id = $budget->category_id;
        $this->year = $budget->year;
        $this->month = $budget->month ?? '';
        $this->allocated_amount = (float)$budget->allocated_amount;
        $this->isFormOpen = true;
    }

    public function delete($id)
    {
        $budget = ExpenseBudget::findOrFail($id);
        $budget->delete();
        session()->flash('success', 'Budget registry deleted successfully.');
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;

        $query = ExpenseBudget::where('marquee_id', $marqueeId)
            ->with(['branch', 'category']);

        if ($this->filterBranch) {
            $query->where('branch_id', $this->filterBranch);
        }

        if ($this->filterCategory) {
            $query->where('category_id', $this->filterCategory);
        }

        if ($this->filterYear) {
            $query->where('year', $this->filterYear);
        }

        $budgets = $query->paginate(10);

        $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->get();
        $categories = ExpenseCategory::where('marquee_id', $marqueeId)->where('is_active', true)->get();

        $departments = [
            'Administration',
            'Kitchen / Catering',
            'Event Decoration',
            'Housekeeping & Janitorial',
            'Security',
            'Logistics / Transport',
            'Marketing & Sales',
            'Maintenance',
        ];

        return view('livewire.finance.budget-tracker', [
            'budgets' => $budgets,
            'branches' => $branches,
            'categories' => $categories,
            'departments' => $departments,
        ]);
    }
}
