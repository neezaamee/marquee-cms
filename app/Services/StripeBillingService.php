<?php

namespace App\Services;

use App\Models\SaasInvoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeBillingService
{
    protected $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.stripe.secret') ?: env('STRIPE_SECRET', 'sk_test_mock_secret_key_123');
    }

    /**
     * Create a Stripe Checkout Session for a SaasInvoice.
     */
    public function createCheckoutSession(SaasInvoice $invoice): array
    {
        $plan = $invoice->subscriptionPlan;
        $currency = strtolower($plan->currency ?? 'usd');
        
        // Stripe unit amount is in cents/smallest currency unit
        $unitAmount = (int)round($invoice->total_amount * 100);

        $endpoint = 'https://api.stripe.com/v1/checkout/sessions';

        $payload = [
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'success_url' => route('billing.success') . '?session_id={CHECKOUT_SESSION_ID}&invoice_id=' . $invoice->id,
            'cancel_url' => route('billing.cancel') . '?invoice_id=' . $invoice->id,
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => "MarqueeCMS Plan: " . $plan->name,
                            'description' => "Invoice #" . $invoice->invoice_number . " for " . ($invoice->billingCycle->cycle_name ?? 'Subscription'),
                        ],
                        'unit_amount' => $unitAmount,
                    ],
                    'quantity' => 1,
                ]
            ],
            'client_reference_id' => (string)$invoice->id,
        ];

        try {
            // Stripe API expects nested query-string parameters for complex payloads (e.g. line_items)
            // Http::asForm() handles nested arrays by formatting them as standard nested form fields.
            $response = Http::withToken($this->secretKey)
                ->asForm()
                ->post($endpoint, $payload);

            $data = $response->json();

            if ($response->successful() && isset($data['url'])) {
                return [
                    'success' => true,
                    'session_id' => $data['id'],
                    'url' => $data['url'],
                ];
            }

            $errorMsg = $data['error']['message'] ?? 'Stripe API Session generation failed.';
            Log::error("Stripe Checkout Session Creation Error: " . json_encode($data));

            return [
                'success' => false,
                'message' => $errorMsg,
            ];
        } catch (\Throwable $e) {
            Log::error("Stripe Connection Exception: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to connect to payment gateway.',
            ];
        }
    }

    /**
     * Retrieve and verify a Stripe Checkout Session status.
     */
    public function retrieveCheckoutSession(string $sessionId): array
    {
        $endpoint = "https://api.stripe.com/v1/checkout/sessions/{$sessionId}";

        try {
            $response = Http::withToken($this->secretKey)->get($endpoint);
            $data = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'payment_status' => $data['payment_status'] ?? 'unpaid',
                    'amount_total' => ($data['amount_total'] ?? 0) / 100,
                    'currency' => strtoupper($data['currency'] ?? 'usd'),
                    'transaction_id' => $data['payment_intent'] ?? $sessionId,
                ];
            }

            $errorMsg = $data['error']['message'] ?? 'Failed to retrieve Stripe session.';
            Log::error("Stripe Retrieve Session Error: " . json_encode($data));

            return [
                'success' => false,
                'message' => $errorMsg,
            ];
        } catch (\Throwable $e) {
            Log::error("Stripe Retrieve Connection Exception: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Gateway connectivity issue.',
            ];
        }
    }
}
