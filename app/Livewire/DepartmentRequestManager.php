<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\DepartmentStockRequest;
use App\Models\DepartmentStockRequestItem;
use App\Models\InventoryItem;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentRequestManager extends Component
{
    use WithPagination;

    public $search = '';
    public $filterDepartment = '';
    public $filterStatus = '';

    // Form fields
    public $selectedRequestId;
    public $department_id;
    public $request_number;
    public $request_date;
    public $remarks;

    // Items list for request form: [['item_id' => x, 'requested_qty' => y]]
    public $formItems = [];

    public $isFormOpen = false;
    public $isViewModalOpen = false;
    public $viewRequest = null;

    protected $paginationTheme = 'bootstrap';

    protected $rules = [
        'department_id' => 'required|exists:departments,id',
        'request_date' => 'required|date',
        'remarks' => 'nullable|string',
        'formItems' => 'required|array|min:1',
        'formItems.*.item_id' => 'required|exists:inventory_items,id',
        'formItems.*.requested_qty' => 'required|numeric|min:0.01',
    ];

    protected $messages = [
        'formItems.required' => 'You must add at least one item to request.',
        'formItems.*.item_id.required' => 'Select an item.',
        'formItems.*.requested_qty.required' => 'Specify quantity.',
    ];

    public function mount()
    {
        $this->request_date = now()->format('Y-m-d');
        
        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;
        $firstDept = Department::where('marquee_id', $marqueeId);
        if ($branchId) {
            $firstDept->where('branch_id', $branchId);
        }
        $firstDept = $firstDept->first();
        if ($firstDept) {
            $this->department_id = $firstDept->id;
        }
    }

    public function openCreateForm()
    {
        $this->resetForm();
        $this->request_number = $this->generateNextRequestNumber();
        $this->addItemRow();
        $this->isFormOpen = true;
    }

    public function generateNextRequestNumber()
    {
        $marqueeId = auth()->user()->marquee_id;
        $count = DepartmentStockRequest::where('marquee_id', $marqueeId)->count();
        return 'REQ-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
    }

    public function resetForm()
    {
        $this->selectedRequestId = null;
        $this->request_date = now()->format('Y-m-d');
        $this->remarks = '';
        $this->formItems = [];
        $this->resetErrorBag();
    }

    public function addItemRow()
    {
        $this->formItems[] = [
            'item_id' => '',
            'requested_qty' => 1.0,
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

        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;

        if (!$branchId) {
            $this->addError('branch_id', 'Please make sure you are logged in to a branch.');
            return;
        }

        $employee = auth()->user()->employee;
        $requestedBy = $employee ? $employee->id : 1; // Fallback to ID 1 if not linked

        DepartmentStockRequest::updateOrCreate(
            ['id' => $this->selectedRequestId],
            [
                'marquee_id' => $marqueeId,
                'branch_id' => $branchId,
                'department_id' => $this->department_id,
                'request_number' => $this->request_number,
                'request_date' => $this->request_date,
                'requested_by' => $requestedBy,
                'status' => 'Submitted',
                'remarks' => $this->remarks,
                'created_by' => auth()->id(),
            ]
        )->items()->delete();

        $request = DepartmentStockRequest::where('request_number', $this->request_number)
            ->where('marquee_id', $marqueeId)
            ->first();

        foreach ($this->formItems as $formItem) {
            DepartmentStockRequestItem::create([
                'department_stock_request_id' => $request->id,
                'item_id' => $formItem['item_id'],
                'requested_qty' => $formItem['requested_qty'],
                'approved_qty' => $formItem['requested_qty'], // By default auto-approve for simplicity, store manager can alter
            ]);
        }

        session()->flash('message', 'Stock request submitted successfully.');
        $this->isFormOpen = false;
        $this->resetForm();
    }

    public function viewDetails($id)
    {
        $this->viewRequest = DepartmentStockRequest::with(['department', 'requester', 'items.inventoryItem.unit'])->findOrFail($id);
        $this->isViewModalOpen = true;
    }

    public function cancelRequest($id)
    {
        $request = DepartmentStockRequest::findOrFail($id);
        $request->update(['status' => 'Cancelled']);
        session()->flash('message', 'Request cancelled successfully.');
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;

        $query = DepartmentStockRequest::where('marquee_id', $marqueeId)->with(['department', 'requester']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($this->filterDepartment) {
            $query->where('department_id', $this->filterDepartment);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $requests = $query->orderBy('id', 'desc')->paginate(10);

        $departments = Department::where('marquee_id', $marqueeId);
        if ($branchId) {
            $departments->where('branch_id', $branchId);
        }
        $departments = $departments->orderBy('display_order', 'asc')->get();

        $inventoryItems = InventoryItem::where('marquee_id', $marqueeId)->where('status', 'Active')->get();

        return view('livewire.department-request-manager', [
            'requests' => $requests,
            'departments' => $departments,
            'inventoryItems' => $inventoryItems,
        ])->layout('layouts.admin');
    }
}
