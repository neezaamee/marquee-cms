<?php

namespace App\Livewire;

use App\Models\SaasInvoice;
use App\Models\Marquee;
use App\Models\SubscriptionPlan;
use App\Models\BillingCycle;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class SaasInvoicesList extends Component
{
    use WithPagination;

    public $search = '';
    public $filterPaymentStatus = '';
    public $filterInvoiceStatus = '';

    // Create Manual Invoice Fields
    public $showCreateModal = false;
    public $new_marquee_id = '';
    public $new_plan_id = '';
    public $new_billing_cycle_id = '';
    public $new_due_date = '';
    public $new_notes = '';
    
    // Dynamically calculated preview fields
    public $calc_base_amount = 0.00;
    public $calc_discount = 0.00;
    public $calc_tax = 0.00;
    public $calc_total = 0.00;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterPaymentStatus' => ['except' => ''],
        'filterInvoiceStatus' => ['except' => ''],
    ];

    public function mount()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $this->new_due_date = date('Y-m-d', strtotime('+14 days'));
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterPaymentStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterInvoiceStatus()
    {
        $this->resetPage();
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['new_plan_id', 'new_billing_cycle_id'])) {
            $this->calculateTotals();
        }
    }

    public function calculateTotals()
    {
        if (empty($this->new_plan_id) || empty($this->new_billing_cycle_id)) {
            $this->calc_base_amount = 0.00;
            $this->calc_discount = 0.00;
            $this->calc_tax = 0.00;
            $this->calc_total = 0.00;
            return;
        }

        $plan = SubscriptionPlan::find($this->new_plan_id);
        $cycle = BillingCycle::find($this->new_billing_cycle_id);

        if (!$plan || !$cycle) return;

        // Choose base price based on duration of cycle
        $months = $cycle->duration_in_months;
        if ($months <= 1) {
            $this->calc_base_amount = $plan->monthly_price ?: $plan->price;
        } elseif ($months <= 3) {
            $this->calc_base_amount = $plan->quarterly_price ?: ($plan->monthly_price * 3);
        } elseif ($months <= 6) {
            $this->calc_base_amount = $plan->semi_annual_price ?: ($plan->monthly_price * 6);
        } else {
            $this->calc_base_amount = $plan->annual_price ?: ($plan->monthly_price * 12);
        }

        // Apply discount percentage from cycle
        $discountPercent = $cycle->discount_percentage ?? 0;
        $this->calc_discount = ($this->calc_base_amount * $discountPercent) / 100;

        // Calculate tax (let's assume standard 0% tax for simplicity, but easily scalable)
        $this->calc_tax = 0.00;

        $this->calc_total = ($this->calc_base_amount - $this->calc_discount) + $this->calc_tax;
    }

    public function createInvoice()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $this->validate([
            'new_marquee_id' => 'required|exists:marquees,id',
            'new_plan_id' => 'required|exists:subscription_plans,id',
            'new_billing_cycle_id' => 'required|exists:billing_cycles,id',
            'new_due_date' => 'required|date|after_or_equal:today',
            'new_notes' => 'nullable|string',
        ]);

        $this->calculateTotals();

        DB::transaction(function() {
            SaasInvoice::create([
                'marquee_id' => $this->new_marquee_id,
                'subscription_plan_id' => $this->new_plan_id,
                'billing_cycle_id' => $this->new_billing_cycle_id,
                'amount' => $this->calc_base_amount,
                'tax' => $this->calc_tax,
                'discount' => $this->calc_discount,
                'total_amount' => $this->calc_total,
                'payment_status' => 'Unpaid',
                'invoice_status' => 'Pending',
                'due_date' => $this->new_due_date,
                'notes' => $this->new_notes,
            ]);
        });

        $this->reset(['new_marquee_id', 'new_plan_id', 'new_billing_cycle_id', 'new_notes', 'calc_base_amount', 'calc_discount', 'calc_tax', 'calc_total']);
        $this->new_due_date = date('Y-m-d', strtotime('+14 days'));
        
        session()->flash('success', 'Manual SaaS invoice generated successfully.');
        $this->dispatch('close-modal');
    }

    public function updateStatus(int $invoiceId, string $status)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $invoice = SaasInvoice::findOrFail($invoiceId);
        $invoice->update(['invoice_status' => $status]);

        session()->flash('success', "Invoice status updated to {$status}.");
    }

    public function render()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $query = SaasInvoice::with(['marquee', 'subscriptionPlan', 'billingCycle']);

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('invoice_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('marquee', function($mq) {
                      $mq->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if (!empty($this->filterPaymentStatus)) {
            $query->where('payment_status', $this->filterPaymentStatus);
        }

        if (!empty($this->filterInvoiceStatus)) {
            $query->where('invoice_status', $this->filterInvoiceStatus);
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(10);
        $marquees = Marquee::where('status', 'active')->orderBy('name')->get();
        $plans = SubscriptionPlan::where('status', 'active')->orderBy('name')->get();
        $billingCycles = BillingCycle::where('status', 'Active')->orderBy('cycle_name')->get();

        return view('livewire.saas-invoices-list', compact('invoices', 'marquees', 'plans', 'billingCycles'));
    }
}
