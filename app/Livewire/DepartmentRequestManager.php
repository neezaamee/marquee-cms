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
        'department_id.required' => 'Please select a department.',
        'department_id.exists' => 'The selected department is invalid.',
        'request_date.required' => 'Please select a request date.',
        'formItems.required' => 'You must add at least one item to request.',
        'formItems.min' => 'You must add at least one item to request.',
        'formItems.*.item_id.required' => 'Please select an item for each row.',
        'formItems.*.item_id.exists' => 'The selected item is invalid.',
        'formItems.*.requested_qty.required' => 'Specify quantity for each item.',
        'formItems.*.requested_qty.min' => 'Quantity must be at least 0.01.',
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

    public function closeCreateForm()
    {
        $this->isFormOpen = false;
        $this->resetForm();
    }

    public function generateNextRequestNumber()
    {
        $marqueeId = auth()->user()->marquee_id;
        $maxId = DepartmentStockRequest::withTrashed()
            ->where('marquee_id', $marqueeId)
            ->max('id') ?? 0;
        return 'REQ-' . str_pad($maxId + 1, 5, '0', STR_PAD_LEFT);
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
            $dept = Department::find($this->department_id);
            if ($dept && $dept->branch_id) {
                $branchId = $dept->branch_id;
            } else {
                $branchId = \App\Models\Branch::where('marquee_id', $marqueeId)->value('id');
            }
        }

        if (!$branchId) {
            $this->addError('branch_id', 'Please make sure a branch is configured for this department or marquee.');
            return;
        }

        $employee = auth()->user()->employee;
        $requestedBy = $employee ? $employee->id : (\App\Models\Employee::where('marquee_id', $marqueeId)->value('id') ?? auth()->id());

        // Ensure request_number does not collide on create
        if (!$this->selectedRequestId) {
            $exists = DepartmentStockRequest::where('marquee_id', $marqueeId)
                ->where('request_number', $this->request_number)
                ->exists();
            if ($exists) {
                $this->request_number = $this->generateNextRequestNumber();
            }
        }

        \DB::transaction(function () use ($marqueeId, $branchId, $requestedBy) {
            if ($this->selectedRequestId) {
                $request = DepartmentStockRequest::findOrFail($this->selectedRequestId);
                $request->update([
                    'department_id' => $this->department_id,
                    'request_date' => $this->request_date,
                    'remarks' => $this->remarks,
                    'updated_by' => auth()->id(),
                ]);
                $request->items()->delete();
            } else {
                $request = DepartmentStockRequest::create([
                    'marquee_id' => $marqueeId,
                    'branch_id' => $branchId,
                    'department_id' => $this->department_id,
                    'request_number' => $this->request_number,
                    'request_date' => $this->request_date,
                    'requested_by' => $requestedBy,
                    'status' => 'Submitted',
                    'remarks' => $this->remarks,
                    'created_by' => auth()->id(),
                ]);
            }

            foreach ($this->formItems as $formItem) {
                if (!empty($formItem['item_id'])) {
                    DepartmentStockRequestItem::create([
                        'department_stock_request_id' => $request->id,
                        'item_id' => $formItem['item_id'],
                        'requested_qty' => $formItem['requested_qty'] ?? 1,
                        'approved_qty' => $formItem['requested_qty'] ?? 1,
                    ]);
                }
            }
        });

        session()->flash('message', 'Stock request ' . $this->request_number . ' submitted successfully.');
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

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterDepartment()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;

        $query = DepartmentStockRequest::where('marquee_id', $marqueeId)
            ->with(['department', 'requester', 'items.inventoryItem']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($this->search) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('request_number', 'like', "%{$term}%")
                  ->orWhere('remarks', 'like', "%{$term}%")
                  ->orWhereHas('department', function ($dq) use ($term) {
                      $dq->where('name', 'like', "%{$term}%")
                         ->orWhere('department_code', 'like', "%{$term}%");
                  })
                  ->orWhereHas('requester', function ($rq) use ($term) {
                      $rq->where('name', 'like', "%{$term}%");
                  })
                  ->orWhereHas('items.inventoryItem', function ($iq) use ($term) {
                      $iq->where('name', 'like', "%{$term}%")
                         ->orWhere('item_code', 'like', "%{$term}%");
                  });
            });
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

        $inventoryItems = InventoryItem::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->with('unit')
            ->orderBy('name', 'asc')
            ->get();

        return view('livewire.department-request-manager', [
            'requests' => $requests,
            'departments' => $departments,
            'inventoryItems' => $inventoryItems,
        ])->layout('layouts.admin');
    }
}
