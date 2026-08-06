<?php

namespace App\Livewire;

use App\Models\SaasInvoice;
use App\Services\StripeBillingService;
use Livewire\Component;

class TenantBilling extends Component
{
    public $marquee = null;
    public $invoices = [];

    public function mount()
    {
        // Authenticated owner user check
        abort_unless(auth()->user()->hasRole('owner') || auth()->user()->isSuperAdmin(), 403);

        $this->marquee = auth()->user()->marquee;

        if ($this->marquee) {
            $this->invoices = SaasInvoice::with(['subscriptionPlan', 'billingCycle', 'payments'])
                ->where('marquee_id', $this->marquee->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }
    }

    /**
     * Initiate Stripe Checkout payment flow for selected invoice.
     */
    public function checkout($invoiceId, StripeBillingService $stripeService)
    {
        $invoice = SaasInvoice::with(['subscriptionPlan', 'billingCycle'])->find($invoiceId);

        if (!$invoice || $invoice->marquee_id !== $this->marquee->id) {
            session()->flash('error', 'Invoice details not found or access denied.');
            return;
        }

        if ($invoice->payment_status === 'Paid') {
            session()->flash('error', 'This invoice has already been fully paid.');
            return;
        }

        $result = $stripeService->createCheckoutSession($invoice);

        if ($result['success'] && isset($result['url'])) {
            return redirect()->away($result['url']);
        }

        session()->flash('error', $result['message'] ?? 'Unable to process checkout. Please try again.');
    }

    public function render()
    {
        return view('livewire.tenant-billing');
    }
}
