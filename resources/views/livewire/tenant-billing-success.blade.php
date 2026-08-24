<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            @if($error)
                <!-- Error View -->
                <div class="card shadow-sm border-0 p-4">
                    <div class="card-body">
                        <span class="fas fa-times-circle fa-4x text-danger mb-3"></span>
                        <h3 class="fw-bold text-dark">Checkout Validation Failed</h3>
                        <p class="text-secondary fs-12 mb-4">{{ $error }}</p>
                        <a href="{{ route('billing.index') }}" class="btn btn-primary px-4">
                            <span class="fas fa-arrow-left me-1"></span> Return to Billing
                        </a>
                    </div>
                </div>
            @else
                <!-- Success Receipt View -->
                <div class="card shadow-sm border-0 p-4 p-md-5 bg-white">
                    <div class="card-body">
                        <div class="mb-4">
                            <span class="fas fa-check-circle fa-5x text-success animate__animated animate__bounceIn"></span>
                        </div>
                        <h2 class="fw-bold text-dark mb-2">Subscription Activated!</h2>
                        <p class="text-secondary fs-12 mb-4">
                            Thank you for your payment. Your subscription limits have been successfully updated.
                        </p>

                        <div class="card bg-light border-0 text-start p-4 mb-4">
                            <h6 class="fw-bold text-uppercase fs-11 text-secondary mb-3 border-bottom pb-2">
                                Payment Details & Receipt
                            </h6>
                            <div class="row g-3 fs-12">
                                <div class="col-6">
                                    <span class="text-muted d-block">Subscriber:</span>
                                    <strong class="text-dark">{{ $invoice->user->name }}</strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Plan Tier:</span>
                                    <strong class="text-dark">{{ $invoice->subscriptionPlan->name }}</strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Invoice Number:</span>
                                    <strong class="text-dark">{{ $invoice->invoice_number }}</strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Transaction ID:</span>
                                    <strong class="text-dark font-monospace">{{ $payment->transaction_id ?? 'N/A' }}</strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Amount Charged:</span>
                                    <strong class="text-success font-monospace">
                                        {{ number_format($invoice->total_amount, 2) }} {{ $invoice->subscriptionPlan->currency ?? 'PKR' }}
                                    </strong>
                                </div>
                                <div class="col-6">
                                     <span class="text-muted d-block">Subscription Ends:</span>
                                     <strong class="text-dark">
                                         {{ $invoice->user && $invoice->user->subscription_ends_at ? $invoice->user->subscription_ends_at->format('M d, Y') : 'N/A' }}
                                     </strong>
                                 </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center gap-3">
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary px-4 fs-12">
                                <span class="fas fa-home me-1"></span> Dashboard
                            </a>
                            <a href="{{ route('billing.index') }}" class="btn btn-primary px-4 fs-12">
                                <span class="fas fa-file-invoice-dollar me-1"></span> Billing Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
