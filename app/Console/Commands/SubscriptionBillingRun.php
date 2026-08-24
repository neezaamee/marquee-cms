<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\BillingCycle;
use App\Models\SaasInvoice;
use App\Services\AccountingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SubscriptionBillingRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:billing-run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automate SaaS subscription invoice renewals and handle grace periods / suspensions.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting SaaS subscription billing and renewal checks...');

        // 1. Process upcoming renewals (subscriptions ends within 7 days)
        $upcomingRenewals = User::whereHas('role', function($q) {
                $q->whereIn('name', ['owner', 'business_owner']);
            })
            ->where('status', 'active')
            ->whereNotNull('subscription_ends_at')
            ->whereNotNull('subscription_plan_id')
            ->whereBetween('subscription_ends_at', [now(), now()->addDays(7)])
            ->get();

        foreach ($upcomingRenewals as $user) {
            $this->processRenewalInvoice($user);
        }

        // 2. Process expired trial & subscription accounts past grace period (3 days)
        $expiredAccounts = User::whereHas('role', function($q) {
                $q->whereIn('name', ['owner', 'business_owner']);
            })
            ->where('status', 'active')
            ->where(function($query) {
                // If trial expired 3+ days ago and no paid subscription ends date
                $query->where(function($q) {
                    $q->whereNotNull('subscription_trial_ends_at')
                      ->where('subscription_trial_ends_at', '<', now()->subDays(3))
                      ->where(function($sub) {
                          $sub->whereNull('subscription_ends_at')
                              ->orWhere('subscription_ends_at', '<', now());
                      });
                })
                // Or if paid subscription expired 3+ days ago
                ->orWhere(function($q) {
                    $q->whereNotNull('subscription_ends_at')
                      ->where('subscription_ends_at', '<', now()->subDays(3));
                });
            })
            ->get();

        foreach ($expiredAccounts as $user) {
            $user->update(['status' => 'inactive']);
            $this->info("Suspended expired account for User: {$user->name} ({$user->email})");
        }

        $this->info('SaaS subscription billing check completed.');
    }

    private function processRenewalInvoice(User $user)
    {
        // Check if renewal invoice has already been generated for the next period
        $alreadyBilled = SaasInvoice::where('user_id', $user->id)
            ->where('due_date', '>', $user->subscription_ends_at)
            ->where('invoice_status', '!=', 'Cancelled')
            ->exists();

        if ($alreadyBilled) {
            return;
        }

        // Determine billing cycle from previous invoice, fallback to Monthly
        $lastInvoice = SaasInvoice::where('user_id', $user->id)
            ->where('invoice_status', '!=', 'Cancelled')
            ->orderBy('created_at', 'desc')
            ->first();

        $plan = $user->subscriptionPlan;
        $cycle = $lastInvoice ? $lastInvoice->billingCycle : BillingCycle::where('duration_in_months', 1)->first();

        if (!$plan || !$cycle) {
            $this->warn("Missing plan or cycle configuration for User ID: {$user->id}");
            return;
        }

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
            $subtotal = $baseAmount - $discount;

            // Apply user credit if any
            $creditApplied = 0.00;
            if ($user->credit_balance > 0) {
                $creditApplied = min($user->credit_balance, $subtotal);
                $user->credit_balance -= $creditApplied;
                $user->save();
            }

            $totalAmount = $subtotal - $creditApplied;
            $isFullyPaid = $totalAmount <= 0;

            $notes = 'Auto-renewal invoice for plan ' . $plan->name . '.';
            if ($creditApplied > 0) {
                $notes .= " Proration credit of PKR " . number_format($creditApplied, 2) . " applied.";
            }

            // Create Invoice
            $invoice = SaasInvoice::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'billing_cycle_id' => $cycle->id,
                'amount' => $baseAmount,
                'tax' => 0.00,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'payment_status' => $isFullyPaid ? 'Paid' : 'Unpaid',
                'invoice_status' => $isFullyPaid ? 'Paid' : 'Pending',
                'due_date' => $user->subscription_ends_at->format('Y-m-d'),
                'paid_date' => $isFullyPaid ? date('Y-m-d') : null,
                'notes' => $notes,
            ]);

            // Post double-entry journal entry to SaaS Ledger
            app(AccountingService::class)->postSaasInvoiceJournal($invoice);

            if ($isFullyPaid) {
                // Post payment journal voucher reflecting 0 balance
                $payment = \App\Models\SaasPayment::create([
                    'payment_reference' => '',
                    'invoice_id' => $invoice->id,
                    'user_id' => $user->id,
                    'amount' => 0.00,
                    'payment_method' => 'Cash',
                    'transaction_id' => 'CREDIT-' . strtoupper(uniqid()),
                    'payment_date' => date('Y-m-d'),
                    'notes' => 'Invoice fully settled using account credits.',
                ]);
                app(AccountingService::class)->postSaasPaymentJournal($payment);

                // Auto extend subscriptionends date
                $currentEnd = $user->subscription_ends_at;
                $user->update([
                    'subscription_ends_at' => $currentEnd->copy()->addMonths($months),
                    'status' => 'active',
                ]);
                $this->info("Auto-renewed subscription for {$user->name} via credits.");
            } else {
                $this->info("Generated renewal invoice {$invoice->invoice_number} for {$user->name}.");
            }
        });
    }
}
