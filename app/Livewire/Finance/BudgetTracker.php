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

        $user = auth()->user();
        $marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;

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
            'year' => (int)$this->year,
            'month' => $this->month ? (int)$this->month : null,
            'allocated_amount' => (float)$this->allocated_amount,
        ];

        if ($this->editId) {
            $budget = ExpenseBudget::findOrFail($this->editId);
            $budget->update($data);
            session()->flash('success', 'Budget limit updated successfully.');
        } else {
            // Auto calculate any existing consumed amount on create
            $expQuery = \App\Models\Expense::where('marquee_id', $marqueeId)
                ->where('expense_category_id', $this->category_id)
                ->whereYear('expense_date', $this->year)
                ->whereNotIn('status', [\App\Models\Expense::STATUS_DRAFT, \App\Models\Expense::STATUS_REJECTED]);

            if ($this->month) {
                $expQuery->whereMonth('expense_date', $this->month);
            }
            if ($this->branch_id) {
                $expQuery->where('branch_id', $this->branch_id);
            }

            $data['consumed_amount'] = (float)$expQuery->sum('total_amount_base');
            ExpenseBudget::create($data);
            session()->flash('success', 'Budget limit created and synchronized successfully.');
        }

        $this->isFormOpen = false;
        $this->resetInputFields();
    }

    public function syncActuals()
    {
        $user = auth()->user();
        $marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;

        $budgets = ExpenseBudget::where('marquee_id', $marqueeId)->get();
        $synced = 0;

        foreach ($budgets as $bg) {
            $expQuery = \App\Models\Expense::where('marquee_id', $marqueeId)
                ->where('expense_category_id', $bg->category_id)
                ->whereYear('expense_date', $bg->year)
                ->whereNotIn('status', [\App\Models\Expense::STATUS_DRAFT, \App\Models\Expense::STATUS_REJECTED]);

            if ($bg->month) {
                $expQuery->whereMonth('expense_date', $bg->month);
            }

            if ($bg->branch_id) {
                $expQuery->where('branch_id', $bg->branch_id);
            }

            $consumed = (float)$expQuery->sum('total_amount_base');
            $bg->update(['consumed_amount' => $consumed]);
            $synced++;
        }

        session()->flash('success', "Synchronized actual expenditures for {$synced} budget limits against the General Ledger.");
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
        $user = auth()->user();
        $marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;

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

        // Summary KPI statistics
        $kpiQuery = ExpenseBudget::where('marquee_id', $marqueeId);
        if ($this->filterYear) {
            $kpiQuery->where('year', $this->filterYear);
        }
        $totalAllocated = (float)(clone $kpiQuery)->sum('allocated_amount');
        $totalConsumed = (float)(clone $kpiQuery)->sum('consumed_amount');
        $totalRemaining = $totalAllocated - $totalConsumed;
        $exceededCount = (clone $kpiQuery)->whereRaw('consumed_amount >= allocated_amount AND allocated_amount > 0')->count();

        $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->get();
        $categories = ExpenseCategory::where('marquee_id', $marqueeId)->where('is_active', true)->orderBy('name')->get();

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
            'totalAllocated' => $totalAllocated,
            'totalConsumed' => $totalConsumed,
            'totalRemaining' => $totalRemaining,
            'exceededCount' => $exceededCount,
            'branches' => $branches,
            'categories' => $categories,
            'departments' => $departments,
        ]);
    }
}
