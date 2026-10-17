<?php

namespace App\Livewire\Purchases;

use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseInvoiceList extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = '';
    public $filterSupplier = '';
    public $perPage = 15;
    public $confirmingDeletionId = null;
    public $confirmingPostId = null;
    public $confirmingInvoice = null;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterSupplier' => ['except' => ''],
        'perPage' => ['except' => 15],
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

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function confirmPost(int $id)
    {
        $this->confirmingPostId = $id;
        $marqueeId = auth()->user()->marquee_id;
        $this->confirmingInvoice = PurchaseInvoice::where('marquee_id', $marqueeId)->with('supplier')->find($id);
    }

    public function cancelPostConfirmation()
    {
        $this->confirmingPostId = null;
        $this->confirmingInvoice = null;
    }

    public function postToLedger(?int $id = null, ?PurchaseService $purchaseService = null)
    {
        $purchaseService = $purchaseService ?: app(PurchaseService::class);
        $targetId = $id ?: $this->confirmingPostId;

        if (!$targetId) {
            return;
        }

        try {
            $user = auth()->user();
            if (!$user->isSuperAdmin() && !$user->hasPermission('manage_accounting') && !$user->hasPermission('manage_inventory')) {
                abort(403, 'Unauthorized action.');
            }

            $marqueeId = $user->marquee_id;
            $invoice = PurchaseInvoice::where('marquee_id', $marqueeId)->findOrFail($targetId);

            if ($invoice->status === 'Posted') {
                session()->flash('error', "Invoice #{$invoice->invoice_number} is already posted to the ledger.");
                $this->cancelPostConfirmation();
                return;
            }

            if ($invoice->status === 'Cancelled') {
                session()->flash('error', "Cancelled invoices cannot be posted to the ledger.");
                $this->cancelPostConfirmation();
                return;
            }

            if ($invoice->details()->count() === 0) {
                session()->flash('error', "Cannot post Invoice #{$invoice->invoice_number} because it has no line items.");
                $this->cancelPostConfirmation();
                return;
            }

            $purchaseService->postPurchaseInvoice($invoice->id);
            session()->flash('success', "Invoice #{$invoice->invoice_number} has been posted to ledger! Journal voucher generated and supplier balance updated.");
            $this->cancelPostConfirmation();
        } catch (\Exception $e) {
            session()->flash('error', 'Error posting invoice: ' . $e->getMessage());
        }
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
            $query->where(function ($q) {
                $q->where('invoice_number', 'like', '%' . $this->search . '%')
                  ->orWhere('reference_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('supplier', function ($sq) {
                      $sq->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        if (!empty($this->filterSupplier)) {
            $query->where('supplier_id', $this->filterSupplier);
        }

        $purchaseInvoices = $query->latest()->paginate($this->perPage);
        $suppliers = Supplier::where('marquee_id', $marqueeId)->orderBy('name')->get();

        return view('livewire.purchases.purchase-invoice-list', compact('purchaseInvoices', 'suppliers'))
            ->layout('layouts.admin');
    }
}
