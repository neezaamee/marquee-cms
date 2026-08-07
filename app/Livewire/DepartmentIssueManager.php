<?php

namespace App\Livewire;

use App\Models\DepartmentStockRequest;
use App\Models\DepartmentStockIssue;
use App\Models\Employee;
use App\Services\DepartmentStockService;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentIssueManager extends Component
{
    use WithPagination;

    public $filterRequest = '';
    
    // Issue form states
    public $selectedRequestId;
    public $selectedRequest;
    
    // Issue quantities: [item_id => quantity]
    public $issueQuantities = [];
    public $receiverEmployeeId;

    public $isFormOpen = false;

    protected $paginationTheme = 'bootstrap';

    public function selectRequestForIssue($id)
    {
        $this->selectedRequestId = $id;
        $this->selectedRequest = DepartmentStockRequest::with(['department', 'items.item'])->findOrFail($id);

        $this->issueQuantities = [];
        $stockService = app(DepartmentStockService::class);
        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;

        foreach ($this->selectedRequest->items as $reqItem) {
            // Find current Central Warehouse Stock
            $currentCentralStock = $stockService->getCentralWarehouseStock($marqueeId, $branchId, $reqItem->item_id);
            $pendingToIssue = $reqItem->approved_qty - $reqItem->issued_qty;
            
            // Suggest either what is pending, or what is available in Central warehouse (whichever is lower)
            $suggestedIssueQty = min($pendingToIssue, $currentCentralStock);
            $suggestedIssueQty = max($suggestedIssueQty, 0.0);

            $this->issueQuantities[$reqItem->item_id] = [
                'item_name' => $reqItem->item->name,
                'requested_qty' => $reqItem->requested_qty,
                'approved_qty' => $reqItem->approved_qty,
                'already_issued' => $reqItem->issued_qty,
                'central_stock' => $currentCentralStock,
                'quantity' => $suggestedIssueQty,
            ];
        }

        $this->isFormOpen = true;
    }

    public function issueStock()
    {
        $this->validate([
            'issueQuantities' => 'required|array|min:1',
            'issueQuantities.*.quantity' => 'required|numeric|min:0',
            'receiverEmployeeId' => 'required|exists:employees,id',
        ], [
            'receiverEmployeeId.required' => 'Please select the department employee receiving the stock.',
        ]);

        $stockService = app(DepartmentStockService::class);
        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;

        // Perform stock check before issue
        foreach ($this->issueQuantities as $itemId => $data) {
            $qtyToIssue = (float) $data['quantity'];
            if ($qtyToIssue <= 0) {
                continue;
            }

            $currentCentralStock = $stockService->getCentralWarehouseStock($marqueeId, $branchId, $itemId);
            if ($qtyToIssue > $currentCentralStock) {
                $this->addError("issueQuantities.{$itemId}.quantity", "Cannot issue more than current Central Warehouse Stock (" . number_format($currentCentralStock, 2) . ").");
                return;
            }
        }

        // Run Issue transaction via service
        $quantities = collect($this->issueQuantities)->map(fn($d) => $d['quantity'])->toArray();
        
        $stockService->issueStock(
            $this->selectedRequest,
            $quantities,
            auth()->id(),
            $this->receiverEmployeeId
        );

        session()->flash('message', 'Inventory issued to department successfully.');
        $this->isFormOpen = false;
        $this->selectedRequestId = null;
        $this->selectedRequest = null;
        $this->issueQuantities = [];
        $this->receiverEmployeeId = null;
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;

        // Fetch requests waiting to be issued (Submitted, Partially Issued)
        $query = DepartmentStockRequest::where('marquee_id', $marqueeId)
            ->whereIn('status', ['Submitted', 'Partially Issued'])
            ->with(['department', 'requester']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $requests = $query->orderBy('id', 'asc')->paginate(10);

        // Fetch potential receivers in department (or fallback to branch staff)
        $employees = Employee::where('marquee_id', $marqueeId);
        if ($this->selectedRequest) {
            $deptEmployeesCount = Employee::where('marquee_id', $marqueeId)->where('department_id', $this->selectedRequest->department_id)->count();
            if ($deptEmployeesCount > 0) {
                $employees->where('department_id', $this->selectedRequest->department_id);
            }
        }
        if ($branchId) {
            $employees->where('branch_id', $branchId);
        }
        $employees = $employees->orderBy('name', 'asc')->get();

        return view('livewire.department-issue-manager', [
            'requests' => $requests,
            'employees' => $employees,
        ])->layout('layouts.admin');
    }
}
