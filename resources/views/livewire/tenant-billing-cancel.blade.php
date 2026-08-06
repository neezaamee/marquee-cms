<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="card shadow-sm border-0 p-4 p-md-5 bg-white">
                <div class="card-body">
                    <div class="mb-4">
                        <span class="fas fa-exclamation-circle fa-5x text-warning"></span>
                    </div>
                    <h2 class="fw-bold text-dark mb-2">Payment Cancelled</h2>
                    <p class="text-secondary fs-12 mb-4">
                        The Stripe checkout session was cancelled. No charges were made to your account.
                    </p>

                    @if($invoice)
                        <div class="card bg-light border-0 text-start p-3 mb-4 fs-12" style="max-width: 400px; margin: 0 auto;">
                            <div><strong>Invoice Ref:</strong> {{ $invoice->invoice_number }}</div>
                            <div><strong>Plan Tier:</strong> {{ $invoice->subscriptionPlan->name }}</div>
                            <div><strong>Total Due:</strong> {{ number_format($invoice->total_amount, 2) }} {{ $invoice->subscriptionPlan->currency ?? 'PKR' }}</div>
                        </div>
                    @endif

                    <div class="d-flex justify-content-center gap-3">
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary px-4 fs-12">
                            <span class="fas fa-home me-1"></span> Dashboard
                        </a>
                        <a href="{{ route('billing.index') }}" class="btn btn-primary px-4 fs-12">
                            <span class="fas fa-file-invoice-dollar me-1"></span> Return to Billing
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
