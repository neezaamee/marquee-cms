<?php

namespace App\Livewire\Purchases;

use App\Models\PurchaseReturn;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseReturnList extends Component
{
    use WithPagination;

    public $search = '';
    public $filterSupplier = '';
    public $confirmingDeletionId = null;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterSupplier' => ['except' => ''],
    ];

    public function updatingSearch()
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
            $return = PurchaseReturn::findOrFail($this->confirmingDeletionId);

            if ($return->status !== 'Draft') {
                session()->flash('error', 'Only draft purchase returns can be deleted.');
                $this->confirmingDeletionId = null;
                return;
            }

            $return->details()->delete();
            $return->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Purchase return deleted successfully.');
        }
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $query = PurchaseReturn::where('marquee_id', $marqueeId)->with(['supplier', 'branch', 'journalVoucher']);

        if (!empty($this->search)) {
            $query->where('return_number', 'like', '%' . $this->search . '%');
        }

        if (!empty($this->filterSupplier)) {
            $query->where('supplier_id', $this->filterSupplier);
        }

        $purchaseReturns = $query->latest()->paginate(10);
        $suppliers = Supplier::where('marquee_id', $marqueeId)->orderBy('name')->get();

        return view('livewire.purchases.purchase-return-list', compact('purchaseReturns', 'suppliers'))
            ->layout('layouts.admin');
    }
}
