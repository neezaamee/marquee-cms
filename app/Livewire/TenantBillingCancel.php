<?php

namespace App\Livewire;

use App\Models\SaasInvoice;
use Livewire\Component;

class TenantBillingCancel extends Component
{
    public $invoice = null;

    public function mount()
    {
        $invoiceId = request()->query('invoice_id');
        if ($invoiceId) {
            $this->invoice = SaasInvoice::with('subscriptionPlan')->find($invoiceId);
        }
    }

    public function render()
    {
        return view('livewire.tenant-billing-cancel');
    }
}
