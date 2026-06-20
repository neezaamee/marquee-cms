<?php

namespace App\Livewire;

use App\Models\SaasInvoice;
use Livewire\Component;

class SaasInvoiceDetail extends Component
{
    public $invoice;

    public function mount($invoice)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $this->invoice = $invoice;
    }

    public function render()
    {
        return view('livewire.saas-invoice-detail');
    }
}
