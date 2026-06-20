<div>
    <!-- Back & Actions Bar -->
    <div class="card mb-3 no-print">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="{{ route('saas-invoices.index') }}" class="btn btn-falcon-default btn-sm">
                <span class="fas fa-arrow-left me-1"></span> Back to Invoices
            </a>
            <div class="d-flex gap-2">
                <button onclick="window.print();" class="btn btn-falcon-default btn-sm">
                    <span class="fas fa-print me-1"></span> Print Invoice
                </button>
                @if($invoice->payment_status !== 'Paid')
                    <a href="{{ route('saas-payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-falcon-primary btn-sm">
                        <span class="fas fa-money-bill-wave me-1"></span> Record Payment
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Invoice Sheet -->
    <div class="card mb-3 invoice-sheet">
        <div class="card-body">
            <div class="row align-items-center text-center text-sm-start">
                <div class="col-sm-6 text-sm-start">
                    <img src="{{ asset('assets/img/icons/spot-illustrations/falcon.png') }}" alt="Logo" width="60" />
                    <h4 class="mt-2 text-primary font-sans-serif">M<span class="text-secondary fw-semibold">CMS</span> Platform</h4>
                    <p class="fs-11 mb-0 text-600">
                        12-C, Lane 5, Bukhari Commercial Area Phase 6 DHA<br />
                        Karachi, Pakistan<br />
                        support@marqueecms.com
                    </p>
                </div>
                <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                    <h2 class="text-300">INVOICE</h2>
                    <h5 class="text-dark">{{ $invoice->invoice_number }}</h5>
                    <div class="d-flex justify-content-sm-end gap-2 mt-2">
                        <span class="badge badge-subtle-{{ $invoice->payment_status === 'Paid' ? 'success' : 'danger' }} rounded-pill">
                            {{ $invoice->payment_status }}
                        </span>
                        <span class="badge badge-subtle-{{ $invoice->invoice_status === 'Paid' ? 'success' : 'primary' }} rounded-pill">
                            {{ $invoice->invoice_status }}
                        </span>
                    </div>
                </div>
            </div>

            <hr class="my-4 text-300" />

            <div class="row g-3 fs-11">
                <div class="col-sm-6">
                    <h6 class="text-500 mb-1">Invoice To:</h6>
                    <h5 class="text-dark mb-0">{{ $invoice->marquee->name }}</h5>
                    <p class="mb-0 text-600">
                        {{ $invoice->marquee->address }}, {{ $invoice->marquee->city }}<br />
                        {{ $invoice->marquee->province }}, Pakistan<br />
                        Email: {{ $invoice->marquee->email }}<br />
                        Phone: {{ $invoice->marquee->phone }}
                    </p>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <h6 class="text-500 mb-1">Billing Info:</h6>
                    <table class="table table-borderless table-sm fs-11 ms-sm-auto mb-0" style="max-width: 250px;">
                        <tr>
                            <td class="px-0 fw-semi-bold">Invoice Date:</td>
                            <td class="text-end px-0">{{ $invoice->created_at->format('M d, Y') }}</td>
                        </tr>
                        <tr>
                            <td class="px-0 fw-semi-bold">Due Date:</td>
                            <td class="text-end px-0 text-danger">{{ $invoice->due_date->format('M d, Y') }}</td>
                        </tr>
                        @if($invoice->paid_date)
                            <tr>
                                <td class="px-0 fw-semi-bold text-success">Paid Date:</td>
                                <td class="text-end px-0 text-success fw-bold">{{ $invoice->paid_date->format('M d, Y') }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            <div class="table-responsive my-4 scrollbar">
                <table class="table table-sm table-striped border-bottom fs-11">
                    <thead>
                        <tr class="bg-200 text-900">
                            <th class="px-3">Subscription Item / Plan</th>
                            <th class="text-center">Billing Cycle</th>
                            <th class="text-end">Base Amount</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end px-3">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-3 align-middle fw-semi-bold text-dark">
                                {{ $invoice->subscriptionPlan->name }} Subscription Plan
                                <small class="text-muted d-block">{{ $invoice->subscriptionPlan->description }}</small>
                            </td>
                            <td class="align-middle text-center">{{ $invoice->billingCycle->cycle_name }}</td>
                            <td class="align-middle text-end">{{ number_format($invoice->amount, 2) }} {{ $invoice->subscriptionPlan->currency }}</td>
                            <td class="align-middle text-end text-success">-{{ number_format($invoice->discount, 2) }} {{ $invoice->subscriptionPlan->currency }}</td>
                            <td class="align-middle text-end px-3 fw-bold text-dark">{{ number_format($invoice->total_amount, 2) }} {{ $invoice->subscriptionPlan->currency }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="row justify-content-end fs-11">
                <div class="col-sm-5 col-md-4">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="fw-semi-bold">Subtotal:</td>
                            <td class="text-end">{{ number_format($invoice->amount, 2) }} {{ $invoice->subscriptionPlan->currency }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semi-bold text-success">Discount Applied:</td>
                            <td class="text-end text-success">-{{ number_format($invoice->discount, 2) }} {{ $invoice->subscriptionPlan->currency }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semi-bold">Tax (0%):</td>
                            <td class="text-end">{{ number_format($invoice->tax, 2) }} {{ $invoice->subscriptionPlan->currency }}</td>
                        </tr>
                        <tr class="border-top fw-bold fs-10 text-dark">
                            <td>Total Amount:</td>
                            <td class="text-end text-primary">{{ number_format($invoice->total_amount, 2) }} {{ $invoice->subscriptionPlan->currency }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($invoice->notes)
                <div class="mt-4 p-3 bg-light rounded text-600 fs-11">
                    <h6 class="text-primary mb-1">Invoice Notes:</h6>
                    {{ $invoice->notes }}
                </div>
            @endif

            <!-- Associated Payments Log -->
            <div class="mt-4">
                <h6 class="text-primary border-bottom pb-2 mb-2">Payment History & Transactions</h6>
                @if($invoice->payments->count() > 0)
                    <div class="table-responsive scrollbar fs-11">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr class="text-800">
                                    <th>Payment Ref</th>
                                    <th>Payment Date</th>
                                    <th>Payment Method</th>
                                    <th>Transaction ID</th>
                                    <th class="text-end">Amount Paid</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->payments as $payment)
                                    <tr>
                                        <td><strong>{{ $payment->payment_reference }}</strong></td>
                                        <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                                        <td>{{ $payment->payment_method }}</td>
                                        <td><code>{{ $payment->transaction_id ?: 'N/A' }}</code></td>
                                        <td class="text-end fw-bold text-success">{{ number_format($payment->amount, 2) }} {{ $invoice->subscriptionPlan->currency }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted fs-11">No transactions recorded for this invoice yet.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Print Custom Styles -->
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .main {
                padding: 0 !important;
            }
            .content {
                padding: 0 !important;
            }
            .invoice-sheet {
                border: none !important;
                box-shadow: none !important;
            }
            body {
                background: white !important;
            }
        }
    </style>
</div>
