<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\Employee;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentManager extends Component
{
    use WithPagination;

    public $search = '';
    public $filterType = '';
    public $filterStatus = '';

    // Form fields
    public $departmentId;
    public $department_code;
    public $name;
    public $department_type = 'Operations';
    public $manager_id;
    public $description;
    public $status = 'Active';
    public $display_order = 0;

    public $isFormOpen = false;

    protected $paginationTheme = 'bootstrap';

    protected $rules = [
        'name' => 'required|string|max:255',
        'department_type' => 'required|string',
        'manager_id' => 'nullable|exists:employees,id',
        'description' => 'nullable|string',
        'status' => 'required|string',
        'display_order' => 'required|integer|min:0',
    ];

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;

        $query = Department::where('marquee_id', $marqueeId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('department_code', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterType) {
            $query->where('department_type', $this->filterType);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $departments = $query->orderBy('display_order', 'asc')->paginate(10);
        $employees = Employee::where('marquee_id', $marqueeId);
        if ($branchId) {
            $employees->where('branch_id', $branchId);
        }
        $employeesList = (clone $employees)->orderBy('name', 'asc')->get();

        $totalDepts = Department::where('marquee_id', $marqueeId)->when($branchId, fn($q) => $q->where('branch_id', $branchId))->count();
        $activeDepts = Department::where('marquee_id', $marqueeId)->when($branchId, fn($q) => $q->where('branch_id', $branchId))->where('status', 'Active')->count();
        $kitchenDepts = Department::where('marquee_id', $marqueeId)->when($branchId, fn($q) => $q->where('branch_id', $branchId))->where('department_type', 'Kitchen Production')->count();
        $totalAssignedStaff = Employee::where('marquee_id', $marqueeId)->when($branchId, fn($q) => $q->where('branch_id', $branchId))->whereNotNull('department_id')->count();

        return view('livewire.department-manager', [
            'departments' => $departments,
            'employees' => $employeesList,
            'totalDepts' => $totalDepts,
            'activeDepts' => $activeDepts,
            'kitchenDepts' => $kitchenDepts,
            'totalAssignedStaff' => $totalAssignedStaff,
        ])->layout('layouts.admin');
    }

    public function generateNextCode()
    {
        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id ?: 1;
        $count = Department::where('marquee_id', $marqueeId)->where('branch_id', $branchId)->count();
        return 'DEPT-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }

    public function openCreateForm()
    {
        $this->resetForm();
        $this->department_code = $this->generateNextCode();
        $this->isFormOpen = true;
    }

    public function resetForm()
    {
        $this->departmentId = null;
        $this->name = '';
        $this->department_type = 'Operations';
        $this->manager_id = null;
        $this->description = '';
        $this->status = 'Active';
        $this->display_order = 0;
        $this->resetErrorBag();
    }

    public function save()
    {
        $this->validate();

        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;

        if (!$branchId) {
            $this->addError('branch_id', 'Please make sure you are logged in to a branch.');
            return;
        }

        if ($this->departmentId) {
            $department = Department::findOrFail($this->departmentId);
            $department->update([
                'name' => $this->name,
                'department_type' => $this->department_type,
                'manager_id' => $this->manager_id,
                'description' => $this->description,
                'status' => $this->status,
                'display_order' => $this->display_order,
                'updated_by' => auth()->id(),
            ]);
            session()->flash('message', 'Department updated successfully.');
        } else {
            Department::create([
                'marquee_id' => $marqueeId,
                'branch_id' => $branchId,
                'department_code' => $this->department_code,
                'name' => $this->name,
                'department_type' => $this->department_type,
                'manager_id' => $this->manager_id,
                'description' => $this->description,
                'status' => $this->status,
                'display_order' => $this->display_order,
                'created_by' => auth()->id(),
            ]);
            session()->flash('message', 'Department created successfully.');
        }

        $this->isFormOpen = false;
        $this->resetForm();
    }

    public function edit($id)
    {
        $department = Department::findOrFail($id);
        $this->departmentId = $department->id;
        $this->department_code = $department->department_code;
        $this->name = $department->name;
        $this->department_type = $department->department_type;
        $this->manager_id = $department->manager_id;
        $this->description = $department->description;
        $this->status = $department->status;
        $this->display_order = $department->display_order;

        $this->isFormOpen = true;
    }

    public function delete($id)
    {
        $department = Department::findOrFail($id);
        $department->delete();
        session()->flash('message', 'Department deleted successfully.');
    }
}
