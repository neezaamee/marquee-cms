<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\Employee;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentEmployeeManager extends Component
{
    use WithPagination;

    public $search = '';
    public $filterDepartment = '';
    public $filterDesignation = '';

    // Assign form fields
    public $employeeId;
    public $department_id;
    public $reporting_manager_id;
    public $isAssignModalOpen = false;

    // Bulk transfer
    public $selectedEmployees = [];
    public $bulk_department_id;
    public $isBulkModalOpen = false;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'filterDepartment' => ['except' => ''],
    ];

    public function openAssignModal($id)
    {
        $employee = Employee::findOrFail($id);
        $this->employeeId = $employee->id;
        $this->department_id = $employee->department_id;
        $this->reporting_manager_id = $employee->reporting_manager_id;
        $this->isAssignModalOpen = true;
    }

    public function saveAssignment()
    {
        $this->validate([
            'department_id' => 'nullable|exists:departments,id',
            'reporting_manager_id' => 'nullable|exists:employees,id',
        ]);

        $employee = Employee::findOrFail($this->employeeId);
        $employee->update([
            'department_id' => $this->department_id ?: null,
            'reporting_manager_id' => $this->reporting_manager_id ?: null,
        ]);

        session()->flash('message', 'Employee department assignment updated successfully.');
        $this->isAssignModalOpen = false;
    }

    public function openBulkModal()
    {
        if (empty($this->selectedEmployees)) {
            session()->flash('error', 'Please select at least one employee from the list.');
            return;
        }
        $this->isBulkModalOpen = true;
    }

    public function processBulkTransfer()
    {
        $this->validate([
            'bulk_department_id' => 'required|exists:departments,id',
        ]);

        Employee::whereIn('id', $this->selectedEmployees)->update([
            'department_id' => $this->bulk_department_id,
        ]);

        session()->flash('message', count($this->selectedEmployees) . ' employees transferred to department successfully.');
        $this->selectedEmployees = [];
        $this->isBulkModalOpen = false;
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;

        $query = Employee::where('marquee_id', $marqueeId)
            ->with(['department', 'reportingManager', 'branch']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('employee_id', 'like', '%' . $this->search . '%')
                  ->orWhere('mobile_number', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterDepartment) {
            if ($this->filterDepartment === 'unassigned') {
                $query->whereNull('department_id');
            } else {
                $query->where('department_id', $this->filterDepartment);
            }
        }

        if ($this->filterDesignation) {
            $query->where('designation', $this->filterDesignation);
        }

        $employees = $query->orderBy('name', 'asc')->paginate(12);

        $departments = Department::where('marquee_id', $marqueeId);
        if ($branchId) {
            $departments->where('branch_id', $branchId);
        }
        $departments = $departments->orderBy('name', 'asc')->get();

        $allManagers = Employee::where('marquee_id', $marqueeId);
        if ($branchId) {
            $allManagers->where('branch_id', $branchId);
        }
        $allManagers = $allManagers->orderBy('name', 'asc')->get();

        // Department staffing stats
        $totalStaff = Employee::where('marquee_id', $marqueeId)->when($branchId, fn($q) => $q->where('branch_id', $branchId))->count();
        $assignedStaff = Employee::where('marquee_id', $marqueeId)->when($branchId, fn($q) => $q->where('branch_id', $branchId))->whereNotNull('department_id')->count();
        $unassignedStaff = $totalStaff - $assignedStaff;

        return view('livewire.department-employee-manager', [
            'employees' => $employees,
            'departments' => $departments,
            'allManagers' => $allManagers,
            'totalStaff' => $totalStaff,
            'assignedStaff' => $assignedStaff,
            'unassignedStaff' => $unassignedStaff,
            'designations' => Employee::DESIGNATIONS,
        ])->layout('layouts.admin');
    }
}
