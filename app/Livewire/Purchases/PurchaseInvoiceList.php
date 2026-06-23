<?php

namespace App\Livewire\Purchases;

use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseInvoiceList extends Component
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
            $invoice = PurchaseInvoice::findOrFail($this->confirmingDeletionId);

            if ($invoice->status !== 'Draft') {
                session()->flash('error', 'Only draft purchase invoices can be deleted.');
                $this->confirmingDeletionId = null;
                return;
            }

            $invoice->details()->delete();
            $invoice->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Purchase invoice deleted successfully.');
        }
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $query = PurchaseInvoice::where('marquee_id', $marqueeId)->with(['supplier', 'branch', 'journalVoucher']);

        if (!empty($this->search)) {
            $query->where('invoice_number', 'like', '%' . $this->search . '%');
        }

        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        if (!empty($this->filterSupplier)) {
            $query->where('supplier_id', $this->filterSupplier);
        }

        $purchaseInvoices = $query->latest()->paginate(10);
        $suppliers = Supplier::where('marquee_id', $marqueeId)->orderBy('name')->get();

        return view('livewire.purchases.purchase-invoice-list', compact('purchaseInvoices', 'suppliers'))
            ->layout('layouts.admin');
    }
}
