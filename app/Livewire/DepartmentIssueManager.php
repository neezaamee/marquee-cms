<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\DepartmentStockRequest;
use App\Models\DepartmentStockIssue;
use App\Models\Employee;
use App\Services\DepartmentStockService;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentIssueManager extends Component
{
    use WithPagination;

    public $search = '';
    public $filterDepartment = '';
    
    // Issue form states
    public $selectedRequestId;
    public $selectedRequest;
    
    // Issue quantities: [item_id => ['item_name' => ..., 'quantity' => ...]]
    public $issueQuantities = [];
    public $receiverEmployeeId;
    public $remarks = '';

    public $isFormOpen = false;

    protected $paginationTheme = 'bootstrap';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterDepartment()
    {
        $this->resetPage();
    }

    public function selectRequestForIssue($id)
    {
        $this->resetErrorBag();
        $this->selectedRequestId = $id;
        $this->selectedRequest = DepartmentStockRequest::with(['department', 'items.item.unit'])->findOrFail($id);

        $this->issueQuantities = [];
        $stockService = app(DepartmentStockService::class);
        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id ?: ($this->selectedRequest->branch_id ?? null);

        foreach ($this->selectedRequest->items as $reqItem) {
            $itemId = $reqItem->item_id;
            $currentCentralStock = $stockService->getCentralWarehouseStock($marqueeId, $branchId, $itemId);
            $pendingToIssue = (float) ($reqItem->approved_qty - $reqItem->issued_qty);
            
            // Suggest min between pending and available central stock
            $suggestedIssueQty = min($pendingToIssue, $currentCentralStock);
            $suggestedIssueQty = max($suggestedIssueQty, 0.0);

            $invItem = $reqItem->item;
            $unitPrice = $stockService->getLastPurchaseRate($marqueeId, $branchId, $itemId);
            if ($unitPrice <= 0) {
                $unitPrice = (float) ($invItem->default_purchase_rate ?: 1.0);
            }
            $unitName = $invItem->unit->name ?? ($invItem->unit->abbreviation ?? 'Units');

            $this->issueQuantities[$itemId] = [
                'item_name' => $invItem->name,
                'item_code' => $invItem->item_code ?? '',
                'unit' => $unitName,
                'unit_price' => $unitPrice,
                'requested_qty' => (float) $reqItem->requested_qty,
                'approved_qty' => (float) $reqItem->approved_qty,
                'already_issued' => (float) $reqItem->issued_qty,
                'pending_qty' => $pendingToIssue,
                'central_stock' => $currentCentralStock,
                'quantity' => $suggestedIssueQty,
            ];
        }

        $this->isFormOpen = true;
    }

    public function closeDispatchForm()
    {
        $this->isFormOpen = false;
        $this->selectedRequestId = null;
        $this->selectedRequest = null;
        $this->issueQuantities = [];
        $this->receiverEmployeeId = null;
        $this->remarks = '';
        $this->resetErrorBag();
    }

    public function issueStock()
    {
        $this->validate([
            'issueQuantities' => 'required|array|min:1',
            'issueQuantities.*.quantity' => 'required|numeric|min:0',
            'receiverEmployeeId' => 'required|exists:employees,id',
        ], [
            'receiverEmployeeId.required' => 'Please select the department employee receiving the stock.',
            'receiverEmployeeId.exists' => 'The selected receiving employee is invalid.',
            'issueQuantities.required' => 'No items available to dispatch.',
            'issueQuantities.*.quantity.required' => 'Please specify valid dispatch quantities.',
            'issueQuantities.*.quantity.min' => 'Quantities cannot be negative.',
        ]);

        if (!$this->selectedRequest) {
            $this->addError('general', 'Requisition data missing. Please re-open the dispatch form.');
            return;
        }

        // Validate that at least one item has a quantity greater than zero
        $totalDispatchQty = collect($this->issueQuantities)->sum(fn($d) => (float) ($d['quantity'] ?? 0));
        if ($totalDispatchQty <= 0) {
            $this->addError('issueQuantities', 'Please enter a quantity greater than zero for at least one item to dispatch.');
            return;
        }

        $stockService = app(DepartmentStockService::class);
        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id ?: ($this->selectedRequest->branch_id ?? null);

        // Perform stock check before dispatch
        foreach ($this->issueQuantities as $itemId => $data) {
            $qtyToIssue = (float) ($data['quantity'] ?? 0);
            if ($qtyToIssue <= 0) {
                continue;
            }

            $currentCentralStock = $stockService->getCentralWarehouseStock($marqueeId, $branchId, $itemId);
            if ($qtyToIssue > $currentCentralStock) {
                $this->addError("issueQuantities.{$itemId}.quantity", "Cannot dispatch {$qtyToIssue} units. Central Warehouse Stock available is " . number_format($currentCentralStock, 2) . ".");
                return;
            }

            // Also check that they do not issue more than pending quantity
            $pendingQty = (float) ($data['pending_qty'] ?? ($data['approved_qty'] - $data['already_issued']));
            if ($qtyToIssue > $pendingQty) {
                $this->addError("issueQuantities.{$itemId}.quantity", "Cannot dispatch more than pending quantity (" . number_format($pendingQty, 2) . ").");
                return;
            }
        }

        // Prepare quantity map and rates map for service
        $quantities = collect($this->issueQuantities)->map(fn($d) => (float) ($d['quantity'] ?? 0))->toArray();
        $rates = collect($this->issueQuantities)->map(fn($d) => (float) ($d['unit_price'] ?? 0))->toArray();
        
        $issue = $stockService->issueStock(
            $this->selectedRequest,
            $quantities,
            auth()->id(),
            $this->receiverEmployeeId,
            $rates
        );

        session()->flash('message', 'Stock dispatched successfully under ' . $issue->issue_number . '. Central warehouse inventory has been updated.');
        $this->closeDispatchForm();
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;

        // Calculate today's stock issued monetary summary
        $todayQuery = \App\Models\DepartmentStockLedger::where('marquee_id', $marqueeId)
            ->where('transaction_date', today()->toDateString())
            ->where('transaction_type', 'Issue');
        if ($branchId) {
            $todayQuery->where('branch_id', $branchId);
        }
        $todayIssuedValue = (float) $todayQuery->sum('total_cost');
        $todayIssuedQty = (float) $todayQuery->sum('qty_in');

        // Fetch requests waiting to be issued (Submitted, Partially Issued)
        $query = DepartmentStockRequest::where('marquee_id', $marqueeId)
            ->whereIn('status', ['Submitted', 'Partially Issued'])
            ->with(['department', 'requester', 'items.item']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($this->search) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('request_number', 'like', "%{$term}%")
                  ->orWhere('remarks', 'like', "%{$term}%")
                  ->orWhereHas('department', fn($dq) => $dq->where('name', 'like', "%{$term}%")->orWhere('department_code', 'like', "%{$term}%"))
                  ->orWhereHas('requester', fn($rq) => $rq->where('name', 'like', "%{$term}%"))
                  ->orWhereHas('items.item', fn($iq) => $iq->where('name', 'like', "%{$term}%")->orWhere('item_code', 'like', "%{$term}%"));
            });
        }

        if ($this->filterDepartment) {
            $query->where('department_id', $this->filterDepartment);
        }

        $requests = $query->orderBy('id', 'asc')->paginate(10);

        // Fetch potential receivers in department (or fallback to marquee employees)
        $empQuery = Employee::where('marquee_id', $marqueeId);
        if ($this->selectedRequest && $this->selectedRequest->department_id) {
            $deptCount = Employee::where('marquee_id', $marqueeId)
                ->where('department_id', $this->selectedRequest->department_id)
                ->count();
            if ($deptCount > 0) {
                $empQuery->where('department_id', $this->selectedRequest->department_id);
            }
        }
        if ($branchId) {
            $branchEmpCount = (clone $empQuery)->where('branch_id', $branchId)->count();
            if ($branchEmpCount > 0) {
                $empQuery->where('branch_id', $branchId);
            }
        }
        $employees = $empQuery->orderBy('name', 'asc')->get();

        $departments = Department::where('marquee_id', $marqueeId);
        if ($branchId) {
            $departments->where('branch_id', $branchId);
        }
        $departments = $departments->orderBy('name', 'asc')->get();

        return view('livewire.department-issue-manager', [
            'requests' => $requests,
            'employees' => $employees,
            'departments' => $departments,
            'todayIssuedValue' => $todayIssuedValue,
            'todayIssuedQty' => $todayIssuedQty,
        ])->layout('layouts.admin');
    }
}
