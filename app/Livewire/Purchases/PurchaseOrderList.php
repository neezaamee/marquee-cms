<?php

namespace App\Livewire\Purchases;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseOrderList extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = '';
    public $filterSupplier = '';
    public $confirmingDeletionId = null;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterSupplier' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterSupplier()
    {
        $this->resetPage();
    }

    public function confirmDeletion(int $id)
    {
        $this->confirmingDeletionId = $id;
    }

    public function deleteRecord()
    {
        if ($this->confirmingDeletionId) {
            $po = PurchaseOrder::findOrFail($this->confirmingDeletionId);

            if ($po->status !== 'Draft') {
                session()->flash('error', 'Only draft purchase orders can be deleted.');
                $this->confirmingDeletionId = null;
                return;
            }

            $po->details()->delete();
            $po->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Purchase order deleted successfully.');
        }
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $query = PurchaseOrder::where('marquee_id', $marqueeId)->with(['supplier', 'branch']);

        if (!empty($this->search)) {
            $query->where('po_number', 'like', '%' . $this->search . '%');
        }

        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        if (!empty($this->filterSupplier)) {
            $query->where('supplier_id', $this->filterSupplier);
        }

        $purchaseOrders = $query->latest()->paginate(10);
        $suppliers = Supplier::where('marquee_id', $marqueeId)->orderBy('name')->get();

        return view('livewire.purchases.purchase-order-list', compact('purchaseOrders', 'suppliers'))
            ->layout('layouts.admin');
    }
}
