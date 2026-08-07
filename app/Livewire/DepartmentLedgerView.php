<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\DepartmentStockLedger;
use App\Models\InventoryItem;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentLedgerView extends Component
{
    use WithPagination;

    public $filterDepartment = '';
    public $filterItem = '';
    
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;
        
        $firstDept = Department::where('marquee_id', $marqueeId);
        if ($branchId) {
            $firstDept->where('branch_id', $branchId);
        }
        $firstDept = $firstDept->first();
        if ($firstDept) {
            $this->filterDepartment = $firstDept->id;
        }
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;

        $query = DepartmentStockLedger::where('marquee_id', $marqueeId)->with(['department', 'item']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($this->filterDepartment) {
            $query->where('department_id', $this->filterDepartment);
        }

        if ($this->filterItem) {
            $query->where('item_id', $this->filterItem);
        }

        $ledgerEntries = $query->orderBy('id', 'desc')->paginate(15);

        $departments = Department::where('marquee_id', $marqueeId);
        if ($branchId) {
            $departments->where('branch_id', $branchId);
        }
        $departments = $departments->orderBy('display_order', 'asc')->get();

        $inventoryItems = InventoryItem::where('marquee_id', $marqueeId)->where('status', 'Active')->get();

        return view('livewire.department-ledger-view', [
            'ledgerEntries' => $ledgerEntries,
            'departments' => $departments,
            'inventoryItems' => $inventoryItems,
        ])->layout('layouts.admin');
    }
}
