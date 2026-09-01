<?php

namespace App\Livewire;

use App\Models\Branch;
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
    public $filterBranch = '';

    // Form fields
    public $departmentId;
    public $branch_id;
    public $department_code;
    public $name;
    public $department_type = 'Operations';
    public $manager_id;
    public $description;
    public $status = 'Active';
    public $display_order = 0;

    public $isFormOpen = false;

    protected $paginationTheme = 'bootstrap';

    protected function rules()
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'department_type' => 'required|string',
            'manager_id' => 'nullable|exists:employees,id',
            'description' => 'nullable|string',
            'status' => 'required|string|in:Active,Inactive',
            'display_order' => 'required|integer|min:0',
        ];
    }

    public function updatedBranchId()
    {
        if (!$this->departmentId) {
            $this->department_code = $this->generateNextCode();
        }
    }

    public function render()
    {
        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();

        // Load branches for this tenant
        $branches = Branch::where('marquee_id', $marqueeId)->orderBy('is_head_office', 'desc')->orderBy('name')->get();

        $query = Department::where('departments.marquee_id', $marqueeId)->with(['branch', 'manager']);

        // Branch filtering
        if ($user->branch_id) {
            $query->where('departments.branch_id', $user->branch_id);
            $this->filterBranch = $user->branch_id;
        } elseif ($this->filterBranch) {
            $query->where('departments.branch_id', $this->filterBranch);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('departments.name', 'like', '%' . $this->search . '%')
                  ->orWhere('departments.department_code', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterType) {
            $query->where('departments.department_type', $this->filterType);
        }

        if ($this->filterStatus) {
            $query->where('departments.status', $this->filterStatus);
        }

        $departments = $query->orderBy('departments.display_order', 'asc')->paginate(10);

        // Load employees for manager selection
        $employeesQuery = Employee::where('marquee_id', $marqueeId);
        $targetBranch = $this->branch_id ?: ($user->branch_id ?: $this->filterBranch);
        if ($targetBranch) {
            $employeesQuery->where(function($q) use ($targetBranch) {
                $q->where('branch_id', $targetBranch)->orWhereNull('branch_id');
            });
        }
        $employeesList = $employeesQuery->orderBy('name', 'asc')->get();

        $effectiveBranch = $user->branch_id ?: $this->filterBranch;
        $totalDepts = Department::where('marquee_id', $marqueeId)->when($effectiveBranch, fn($q) => $q->where('branch_id', $effectiveBranch))->count();
        $activeDepts = Department::where('marquee_id', $marqueeId)->when($effectiveBranch, fn($q) => $q->where('branch_id', $effectiveBranch))->where('status', 'Active')->count();
        $kitchenDepts = Department::where('marquee_id', $marqueeId)->when($effectiveBranch, fn($q) => $q->where('branch_id', $effectiveBranch))->where('department_type', 'Kitchen Production')->count();
        $totalAssignedStaff = Employee::where('marquee_id', $marqueeId)->when($effectiveBranch, fn($q) => $q->where('branch_id', $effectiveBranch))->whereNotNull('department_id')->count();

        return view('livewire.department-manager', [
            'departments' => $departments,
            'branches' => $branches,
            'employees' => $employeesList,
            'totalDepts' => $totalDepts,
            'activeDepts' => $activeDepts,
            'kitchenDepts' => $kitchenDepts,
            'totalAssignedStaff' => $totalAssignedStaff,
        ])->layout('layouts.admin');
    }

    public function generateNextCode()
    {
        $marqueeId = auth()->user()->getActiveMarqueeId();
        $branchId = $this->branch_id ?: (auth()->user()->branch_id ?: Branch::where('marquee_id', $marqueeId)->value('id'));
        $count = Department::where('marquee_id', $marqueeId)->where('branch_id', $branchId)->count();
        return 'DEPT-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }

    public function openCreateForm()
    {
        $this->resetForm();
        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();
        
        $this->branch_id = $user->branch_id 
            ?: ($this->filterBranch 
            ?: (Branch::where('marquee_id', $marqueeId)->where('is_head_office', true)->value('id') 
            ?: Branch::where('marquee_id', $marqueeId)->value('id')));

        $this->department_code = $this->generateNextCode();
        $this->isFormOpen = true;
    }

    public function resetForm()
    {
        $this->departmentId = null;
        $this->branch_id = null;
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

        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();

        // Verify that the branch belongs to the tenant
        $branch = Branch::where('marquee_id', $marqueeId)->findOrFail($this->branch_id);

        if ($this->departmentId) {
            $department = Department::where('marquee_id', $marqueeId)->findOrFail($this->departmentId);
            $department->update([
                'branch_id' => $branch->id,
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
                'branch_id' => $branch->id,
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
        $marqueeId = auth()->user()->getActiveMarqueeId();
        $department = Department::where('marquee_id', $marqueeId)->findOrFail($id);
        
        $this->departmentId = $department->id;
        $this->branch_id = $department->branch_id;
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
        $marqueeId = auth()->user()->getActiveMarqueeId();
        $department = Department::where('marquee_id', $marqueeId)->findOrFail($id);

        // Referential safety checks:
        if ($department->employees()->exists()) {
            session()->flash('error', 'Cannot delete department because active staff/employees are assigned to it.');
            return;
        }

        if ($department->stockLedgers()->exists() || $department->stockRequests()->exists() || $department->stockIssues()->exists()) {
            session()->flash('error', 'Cannot delete department because historical inventory transactions or stock ledgers exist for it.');
            return;
        }

        if ($department->productions()->exists()) {
            session()->flash('error', 'Cannot delete department because historical kitchen production batches exist for it.');
            return;
        }

        $department->delete();
        session()->flash('message', 'Department deleted successfully.');
    }
}
