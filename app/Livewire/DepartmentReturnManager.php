<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\DepartmentStockReturn;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Services\DepartmentStockService;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentReturnManager extends Component
{
    use WithPagination;

    public $filterDepartment = '';

    // Form fields
    public $department_id;
    public $returned_by;
    public $remarks;
    
    // Return items: [['item_id' => x, 'quantity' => y, 'status' => 'Good'/'Damaged'/'Wastage']]
    public $formItems = [];

    public $isFormOpen = false;

    protected $paginationTheme = 'bootstrap';

    protected $rules = [
        'department_id' => 'required|exists:departments,id',
        'returned_by' => 'required|exists:employees,id',
        'remarks' => 'nullable|string',
        'formItems' => 'required|array|min:1',
        'formItems.*.item_id' => 'required|exists:inventory_items,id',
        'formItems.*.quantity' => 'required|numeric|min:0.01',
        'formItems.*.status' => 'required|string',
    ];

    public function openCreateForm()
    {
        $this->resetForm();
        $this->addItemRow();
        $this->isFormOpen = true;
    }

    public function resetForm()
    {
        $this->remarks = '';
        $this->formItems = [];
        $this->resetErrorBag();
    }

    public function addItemRow()
    {
        $this->formItems[] = [
            'item_id' => '',
            'quantity' => 1.0,
            'status' => 'Good',
        ];
    }

    public function removeItemRow($index)
    {
        unset($this->formItems[$index]);
        $this->formItems = array_values($this->formItems);
    }

    public function save()
    {
        $this->validate();

        $stockService = app(DepartmentStockService::class);
        $department = Department::findOrFail($this->department_id);

        // Perform department stock checks before return
        foreach ($this->formItems as $idx => $formItem) {
            $itemId = $formItem['item_id'];
            $qtyToReturn = (float) $formItem['quantity'];

            $currentDeptStock = $stockService->getDepartmentStockBalance($department->id, $itemId);
            if ($qtyToReturn > $currentDeptStock) {
                $this->addError("formItems.{$idx}.quantity", "Cannot return more than current Department Stock balance (" . number_format($currentDeptStock, 2) . ").");
                return;
            }
        }

        // Run Return transaction via service
        $returnItems = [];
        foreach ($this->formItems as $formItem) {
            $returnItems[$formItem['item_id']] = [
                'quantity' => $formItem['quantity'],
                'status' => $formItem['status'],
            ];
        }

        $stockService->returnStock(
            $department,
            $returnItems,
            auth()->id(),
            $this->returned_by
        );

        session()->flash('message', 'Stock returned to warehouse successfully.');
        $this->isFormOpen = false;
        $this->resetForm();
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;

        $query = DepartmentStockReturn::where('marquee_id', $marqueeId)->with(['department', 'returner']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($this->filterDepartment) {
            $query->where('department_id', $this->filterDepartment);
        }

        $returns = $query->orderBy('id', 'desc')->paginate(10);

        $departments = Department::where('marquee_id', $marqueeId);
        if ($branchId) {
            $departments->where('branch_id', $branchId);
        }
        $departments = $departments->orderBy('display_order', 'asc')->get();

        $employees = Employee::where('marquee_id', $marqueeId);
        if ($this->department_id) {
            $deptStaffCount = Employee::where('marquee_id', $marqueeId)->where('department_id', $this->department_id)->count();
            if ($deptStaffCount > 0) {
                $employees->where('department_id', $this->department_id);
            }
        }
        if ($branchId) {
            $employees->where('branch_id', $branchId);
        }
        $employees = $employees->orderBy('name', 'asc')->get();

        $inventoryItems = InventoryItem::where('marquee_id', $marqueeId)->where('status', 'Active')->get();

        return view('livewire.department-return-manager', [
            'returns' => $returns,
            'departments' => $departments,
            'employees' => $employees,
            'inventoryItems' => $inventoryItems,
        ])->layout('layouts.admin');
    }
}
