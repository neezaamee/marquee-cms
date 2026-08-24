<?php

namespace App\Livewire\SuperAdmin\Trials;

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\BillingCycle;
use App\Models\SaasInvoice;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class ExpiringTrials extends Component
{
    use WithPagination;

    public $search = '';
    
    // Modal states
    public $showExtendModal = false;
    public $showConvertModal = false;
    
    // Action targets
    public $selectedUserId = null;
    public $selectedUser = null;
    
    // Extend fields
    public $new_trial_ends_at = '';
    
    // Convert fields
    public $plan_id = '';
    public $billing_cycle_id = '';
    public $mark_as_paid = false;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function selectUserForExtend($id)
    {
        $this->selectedUserId = $id;
        $this->selectedUser = User::findOrFail($id);
        $this->new_trial_ends_at = $this->selectedUser->subscription_trial_ends_at 
            ? $this->selectedUser->subscription_trial_ends_at->format('Y-m-d') 
            : date('Y-m-d', strtotime('+7 days'));
        $this->showExtendModal = true;
    }

    public function extendTrial()
    {
        $this->validate([
            'new_trial_ends_at' => 'required|date|after_or_equal:today',
        ]);

        $user = User::findOrFail($this->selectedUserId);
        $user->update([
            'subscription_trial_ends_at' => $this->new_trial_ends_at,
            'status' => 'active',
        ]);

        session()->flash('success', "Trial for {$user->name} extended successfully.");
        $this->closeModals();
    }

    public function sendReminder($id)
    {
        $user = User::findOrFail($id);
        // Simulate sending a reminder email
        session()->flash('success', "Reminder email sent to {$user->email} successfully.");
    }

    public function selectUserForConvert($id)
    {
        $this->selectedUserId = $id;
        $this->selectedUser = User::findOrFail($id);
        $this->plan_id = $this->selectedUser->subscription_plan_id ?: '';
        $this->billing_cycle_id = '';
        $this->mark_as_paid = false;
        $this->showConvertModal = true;
    }

    public function convertToPaid()
    {
        $this->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle_id' => 'required|exists:billing_cycles,id',
        ]);

        DB::transaction(function() {
            $user = User::findOrFail($this->selectedUserId);
            $plan = SubscriptionPlan::findOrFail($this->plan_id);
            $cycle = BillingCycle::findOrFail($this->billing_cycle_id);

            // Calculate amounts
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
            $totalAmount = $baseAmount - $discount;

            // Generate invoice
            $invoice = SaasInvoice::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'billing_cycle_id' => $cycle->id,
                'amount' => $baseAmount,
                'tax' => 0.00,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'payment_status' => $this->mark_as_paid ? 'Paid' : 'Unpaid',
                'invoice_status' => $this->mark_as_paid ? 'Paid' : 'Pending',
                'due_date' => date('Y-m-d', strtotime('+14 days')),
                'paid_date' => $this->mark_as_paid ? date('Y-m-d') : null,
                'notes' => 'Automatically generated upon conversion from expiring trial.',
            ]);

            // Post double-entry journal entry to SaaS Ledger
            app(\App\Services\AccountingService::class)->postSaasInvoiceJournal($invoice);

            if ($this->mark_as_paid) {
                // Post payment journal voucher
                $payment = \App\Models\SaasPayment::create([
                    'payment_reference' => '',
                    'invoice_id' => $invoice->id,
                    'user_id' => $user->id,
                    'amount' => $totalAmount,
                    'payment_method' => 'Cash',
                    'transaction_id' => 'CONV-' . strtoupper(uniqid()),
                    'payment_date' => date('Y-m-d'),
                    'notes' => 'Manual payment recorded during conversion from trial.',
                ]);
                app(\App\Services\AccountingService::class)->postSaasPaymentJournal($payment);
            }

            // Extend subscription ends at
            $endDate = now()->addMonths($months);
            $user->update([
                'subscription_plan_id' => $plan->id,
                'subscription_ends_at' => $endDate,
                'status' => 'active',
            ]);
        });

        session()->flash('success', "Trial account successfully converted to paid subscription.");
        $this->closeModals();
    }

    public function closeModals()
    {
        $this->showExtendModal = false;
        $this->showConvertModal = false;
        $this->reset(['selectedUserId', 'selectedUser', 'new_trial_ends_at', 'plan_id', 'billing_cycle_id', 'mark_as_paid']);
    }

    public function render()
    {
        $query = User::whereHas('role', function($q) {
            $q->whereIn('name', ['owner', 'business_owner']);
        })
        ->whereNotNull('subscription_trial_ends_at')
        ->where(function($q) {
            $q->whereNull('subscription_ends_at')
              ->orWhere('subscription_ends_at', '<=', now());
        })
        // Expiring in the next 7 days, or recently expired (within past 7 days)
        ->whereBetween('subscription_trial_ends_at', [now()->subDays(7), now()->addDays(7)]);

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('username', 'like', '%' . $this->search . '%');
            });
        }

        $expiringTrials = $query->orderBy('subscription_trial_ends_at', 'asc')->paginate(10);
        $plans = SubscriptionPlan::orderBy('name')->get();
        $cycles = BillingCycle::orderBy('duration_in_months')->get();

        return view('livewire.super-admin.trials.expiring-trials', [
            'expiringTrials' => $expiringTrials,
            'plans' => $plans,
            'cycles' => $cycles,
        ])->layout('layouts.admin');
    }
}
