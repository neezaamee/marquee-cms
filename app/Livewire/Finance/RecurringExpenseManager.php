<?php

namespace App\Livewire\Finance;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\ExpenseCategory;
use App\Models\ExpenseType;
use App\Models\RecurringExpense;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;

class RecurringExpenseManager extends Component
{
    use WithPagination;

    // Form fields
    public $branch_id;
    public $expense_category_id;
    public $expense_type_id;
    public $supplier_id;
    public $employee_id;
    public $department;
    public $cost_center;
    public $description;
    public $amount = 0.00;
    public $tax_amount = 0.00;
    public $discount_amount = 0.00;
    public $frequency = 'Monthly';
    public $start_date;
    public $end_date;
    public $is_active = true;

    public $editId = null;
    public $isFormOpen = false;

    protected $rules = [
        'branch_id' => 'nullable|exists:branches,id',
        'expense_category_id' => 'required|exists:expense_categories,id',
        'expense_type_id' => 'required|exists:expense_types,id',
        'supplier_id' => 'nullable|exists:suppliers,id',
        'employee_id' => 'nullable|exists:employees,id',
        'department' => 'nullable|string|max:100',
        'cost_center' => 'nullable|string|max:100',
        'description' => 'required|string',
        'amount' => 'required|numeric|min:0.01',
        'tax_amount' => 'required|numeric|min:0',
        'discount_amount' => 'required|numeric|min:0',
        'frequency' => 'required|in:Daily,Weekly,Monthly,Quarterly,Yearly',
        'start_date' => 'required|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'is_active' => 'boolean',
    ];

    public function openCreateForm()
    {
        $this->resetInputFields();
        $this->start_date = date('Y-m-d');
        $this->isFormOpen = true;
    }

    public function resetInputFields()
    {
        $this->branch_id = '';
        $this->expense_category_id = '';
        $this->expense_type_id = '';
        $this->supplier_id = '';
        $this->employee_id = '';
        $this->department = '';
        $this->cost_center = '';
        $this->description = '';
        $this->amount = 0.00;
        $this->tax_amount = 0.00;
        $this->discount_amount = 0.00;
        $this->frequency = 'Monthly';
        $this->start_date = '';
        $this->end_date = '';
        $this->is_active = true;
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
        $total = $this->amount + $this->tax_amount - $this->discount_amount;

        // Calculate next generation date
        $next = now();
        if ($this->start_date > date('Y-m-d')) {
            $next = \Carbon\Carbon::parse($this->start_date);
        }

        $data = [
            'marquee_id' => $marqueeId,
            'branch_id' => $this->branch_id ?: null,
            'expense_category_id' => $this->expense_category_id,
            'expense_type_id' => $this->expense_type_id,
            'supplier_id' => $this->supplier_id ?: null,
            'employee_id' => $this->employee_id ?: null,
            'department' => $this->department ?: null,
            'cost_center' => $this->cost_center ?: null,
            'description' => $this->description,
            'amount' => $this->amount,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $total,
            'frequency' => $this->frequency,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->editId) {
            $template = RecurringExpense::findOrFail($this->editId);
            $template->update($data);
            session()->flash('success', 'Recurring expense template updated successfully.');
        } else {
            $data['next_generation_date'] = $next->format('Y-m-d');
            RecurringExpense::create($data);
            session()->flash('success', 'Recurring expense template created.');
        }

        $this->isFormOpen = false;
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $template = RecurringExpense::findOrFail($id);
        $this->editId = $template->id;
        $this->branch_id = $template->branch_id ?? '';
        $this->expense_category_id = $template->expense_category_id;
        $this->expense_type_id = $template->expense_type_id;
        $this->supplier_id = $template->supplier_id ?? '';
        $this->employee_id = $template->employee_id ?? '';
        $this->department = $template->department ?? '';
        $this->cost_center = $template->cost_center ?? '';
        $this->description = $template->description;
        $this->amount = (float)$template->amount;
        $this->tax_amount = (float)$template->tax_amount;
        $this->discount_amount = (float)$template->discount_amount;
        $this->frequency = $template->frequency;
        $this->start_date = $template->start_date->format('Y-m-d');
        $this->end_date = $template->end_date ? $template->end_date->format('Y-m-d') : '';
        $this->is_active = $template->is_active;
        $this->isFormOpen = true;
    }

    public function toggleActive($id)
    {
        $template = RecurringExpense::findOrFail($id);
        $template->update(['is_active' => !$template->is_active]);
        session()->flash('success', 'Template state updated.');
    }

    public function skipCycle($id)
    {
        $template = RecurringExpense::findOrFail($id);
        $next = \Carbon\Carbon::parse($template->next_generation_date);
        
        switch ($template->frequency) {
            case 'Daily':
                $next->addDay();
                break;
            case 'Weekly':
                $next->addWeek();
                break;
            case 'Monthly':
                $next->addMonth();
                break;
            case 'Quarterly':
                $next->addMonths(3);
                break;
            case 'Yearly':
                $next->addYear();
                break;
        }

        $template->update(['next_generation_date' => $next->format('Y-m-d')]);
        session()->flash('success', 'Cycle skipped. Next generation scheduled for ' . $next->format('Y-m-d'));
    }

    public function delete($id)
    {
        $template = RecurringExpense::findOrFail($id);
        $template->delete();
        session()->flash('success', 'Template deleted successfully.');
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;

        $templates = RecurringExpense::where('marquee_id', $marqueeId)
            ->with(['branch', 'category', 'type'])
            ->paginate(10);

        $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->get();
        $categories = ExpenseCategory::where('marquee_id', $marqueeId)->where('is_active', true)->get();
        $expenseTypes = ExpenseType::where('marquee_id', $marqueeId)->where('is_active', true)->get();
        $suppliers = Supplier::where('marquee_id', $marqueeId)->get();
        $employees = Employee::where('marquee_id', $marqueeId)->where('status', 'active')->get();

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

        return view('livewire.finance.recurring-expense-manager', [
            'templates' => $templates,
            'branches' => $branches,
            'categories' => $categories,
            'expenseTypes' => $expenseTypes,
            'suppliers' => $suppliers,
            'employees' => $employees,
            'departments' => $departments,
        ]);
    }
}
