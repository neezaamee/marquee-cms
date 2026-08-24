<?php

namespace App\Livewire;

use App\Models\SaasInvoice;
use App\Models\SubscriptionPlan;
use App\Models\BillingCycle;
use App\Services\StripeBillingService;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class TenantBilling extends Component
{
    public $marquee = null;
    public $invoices = [];

    // Change Plan fields
    public $showChangePlanModal = false;
    public $selectedPlanId = '';
    public $selectedCycleId = '';
    public $unusedCredit = 0.00;
    public $newPlanCharge = 0.00;
    public $netPayable = 0.00;
    public $newCreditBalance = 0.00;
    public $plans = [];
    public $cycles = [];

    public function mount()
    {
        // Authenticated owner user check
        abort_unless(auth()->user()->hasRole('owner') || auth()->user()->isBusinessOwner() || auth()->user()->isSuperAdmin(), 403);

        $user = auth()->user();
        $this->marquee = $user->marquee ?? $user->ownedMarquees()->first();
        $this->invoices = SaasInvoice::with(['subscriptionPlan', 'billingCycle', 'payments'])
            ->where('user_id', $user->id)
            ->where('invoice_status', '!=', 'Cancelled')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['selectedPlanId', 'selectedCycleId'])) {
            $this->calculateProration();
        }
    }

    public function calculateProration()
    {
        if (empty($this->selectedPlanId) || empty($this->selectedCycleId)) {
            $this->unusedCredit = 0.00;
            $this->newPlanCharge = 0.00;
            $this->netPayable = 0.00;
            $this->newCreditBalance = 0.00;
            return;
        }

        $user = auth()->user();
        $plan = SubscriptionPlan::find($this->selectedPlanId);
        $cycle = BillingCycle::find($this->selectedCycleId);

        if (!$plan || !$cycle) return;

        // 1. Calculate Unused Credit of current subscription
        $this->unusedCredit = 0.00;
        if ($user->subscriptionPlan && $user->subscription_ends_at && $user->subscription_ends_at->isFuture()) {
            // Find last paid invoice
            $lastInvoice = SaasInvoice::where('user_id', $user->id)
                ->where('payment_status', 'Paid')
                ->where('invoice_status', '!=', 'Cancelled')
                ->orderBy('created_at', 'desc')
                ->first();

            $paidAmount = $lastInvoice ? (float)$lastInvoice->total_amount : (float)$user->subscriptionPlan->price;
            $cycleMonths = $lastInvoice && $lastInvoice->billingCycle ? $lastInvoice->billingCycle->duration_in_months : 1;
            
            $totalDays = $cycleMonths * 30;
            $remainingDays = now()->diffInDays($user->subscription_ends_at, false);
            
            if ($totalDays > 0 && $remainingDays > 0) {
                $this->unusedCredit = round(min($paidAmount, ($remainingDays / $totalDays) * $paidAmount), 2);
            }
        }

        // 2. Calculate New Plan Charge
        $months = $cycle->duration_in_months;
        $baseAmount = $plan->monthly_price ?: $plan->price;
        if ($months == 3) {
            $baseAmount = $plan->quarterly_price ?: ($plan->monthly_price * 3);
        } elseif ($months == 6) {
            $baseAmount = $plan->semi_annual_price ?: ($plan->monthly_price * 6);
        } elseif ($months == 12) {
            $baseAmount = $plan->annual_price ?: ($plan->monthly_price * 12);
        }

        $discount = ($baseAmount * ($cycle->discount_percentage ?? 0)) / 100;
        $this->newPlanCharge = $baseAmount - $discount;

        // 3. Calculate Net Payable & New Credit Balance
        $totalCredits = $this->unusedCredit + (float)$user->credit_balance;
        if ($totalCredits >= $this->newPlanCharge) {
            $this->netPayable = 0.00;
            $this->newCreditBalance = round($totalCredits - $this->newPlanCharge, 2);
        } else {
            $this->netPayable = round($this->newPlanCharge - $totalCredits, 2);
            $this->newCreditBalance = 0.00;
        }
    }

    public function openChangePlanModal()
    {
        $this->plans = SubscriptionPlan::orderBy('name')->get();
        $this->cycles = BillingCycle::orderBy('duration_in_months')->get();
        $this->selectedPlanId = auth()->user()->subscription_plan_id ?: '';
        $this->selectedCycleId = '';
        $this->calculateProration();
        $this->showChangePlanModal = true;
    }

    public function changePlan()
    {
        $this->validate([
            'selectedPlanId' => 'required|exists:subscription_plans,id',
            'selectedCycleId' => 'required|exists:billing_cycles,id',
        ]);

        $user = auth()->user();
        $plan = SubscriptionPlan::findOrFail($this->selectedPlanId);
        $cycle = BillingCycle::findOrFail($this->selectedCycleId);

        $this->calculateProration();

        DB::transaction(function() use ($user, $plan, $cycle) {
            $months = $cycle->duration_in_months;
            $baseAmount = $plan->monthly_price ?: $plan->price;
            if ($months == 3) {
                $baseAmount = $plan->quarterly_price ?: ($plan->monthly_price * 3);
            } elseif ($months == 6) {
                $baseAmount = $plan->semi_annual_price ?: ($plan->monthly_price * 6);
            } elseif ($months == 12) {
                $baseAmount = $plan->annual_price ?: ($plan->monthly_price * 12);
            }

            $discount = ($baseAmount * ($cycle->discount_percentage ?? 0)) / 100;

            // Generate Invoice
            $isPaid = $this->netPayable <= 0;
            $invoiceNotes = "Prorated change to Plan: {$plan->name}. Unused credit of PKR " . number_format($this->unusedCredit, 2) . " applied.";

            $invoice = SaasInvoice::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'billing_cycle_id' => $cycle->id,
                'amount' => $baseAmount,
                'tax' => 0.00,
                'discount' => $discount,
                'total_amount' => $this->netPayable,
                'payment_status' => $isPaid ? 'Paid' : 'Unpaid',
                'invoice_status' => $isPaid ? 'Paid' : 'Pending',
                'due_date' => date('Y-m-d'),
                'paid_date' => $isPaid ? date('Y-m-d') : null,
                'notes' => $invoiceNotes,
            ]);

            // Post double-entry journal entry to SaaS Ledger
            app(\App\Services\AccountingService::class)->postSaasInvoiceJournal($invoice);

            if ($isPaid) {
                // Post payment journal voucher reflecting 0 balance
                $payment = \App\Models\SaasPayment::create([
                    'payment_reference' => '',
                    'invoice_id' => $invoice->id,
                    'user_id' => $user->id,
                    'amount' => 0.00,
                    'payment_method' => 'Cash',
                    'transaction_id' => 'CONV-' . strtoupper(uniqid()),
                    'payment_date' => date('Y-m-d'),
                    'notes' => 'Plan upgraded/changed using proration credits.',
                ]);
                app(\App\Services\AccountingService::class)->postSaasPaymentJournal($payment);

                // Instantly update user's plan and extend subscription ends date
                $user->update([
                    'subscription_plan_id' => $plan->id,
                    'subscription_ends_at' => now()->addMonths($months),
                    'credit_balance' => $this->newCreditBalance,
                    'status' => 'active',
                ]);

                session()->flash('success', "Plan successfully updated. Your new plan is active immediately.");
            } else {
                // Deduct applied credits from user's credit balance right away
                $user->update([
                    'credit_balance' => 0.00, // Credits used towards this invoice
                ]);
                session()->flash('success', "Prorated invoice generated successfully. Please pay the remaining balance to activate your new plan.");
            }
        });

        $this->showChangePlanModal = false;
        
        // Refresh invoice list
        $this->invoices = SaasInvoice::with(['subscriptionPlan', 'billingCycle', 'payments'])
            ->where('user_id', $user->id)
            ->where('invoice_status', '!=', 'Cancelled')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Initiate Stripe Checkout payment flow for selected invoice.
     */
    public function checkout($invoiceId, StripeBillingService $stripeService)
    {
        $invoice = SaasInvoice::with(['subscriptionPlan', 'billingCycle'])->find($invoiceId);

        if (!$invoice || $invoice->user_id !== auth()->id()) {
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
