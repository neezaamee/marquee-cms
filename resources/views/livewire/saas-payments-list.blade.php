<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Payment History</h5>
            <div class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm w-auto">
                    <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search payments..." />
                    <span class="input-group-text"><span class="fas fa-search"></span></span>
                </div>
                
                <select wire:model.live="filterMethod" class="form-select form-select-sm w-auto">
                    <option value="">All Methods</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Easypaisa">Easypaisa</option>
                    <option value="JazzCash">JazzCash</option>
                    <option value="Credit Card">Credit Card</option>
                </select>

                <a class="btn btn-falcon-primary btn-sm" href="{{ route('saas-payments.create') }}">
                    <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Record Payment
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            @if(session('success'))
                <div class="alert alert-success border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-1">{{ session('success') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="align-middle px-3">Payment Reference</th>
                            <th class="align-middle">Invoice Number</th>
                            <th class="align-middle">Business Owner</th>
                            <th class="align-middle">Amount</th>
                            <th class="align-middle">Method</th>
                            <th class="align-middle">Transaction ID</th>
                            <th class="align-middle">Payment Date</th>
                            <th class="align-middle text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td class="align-middle px-3 fw-semi-bold">{{ $payment->payment_reference }}</td>
                                <td class="align-middle fw-semi-bold">
                                    @if($payment->invoice)
                                        <a href="{{ route('saas-invoices.show', $payment->invoice->id) }}">{{ $payment->invoice->invoice_number }}</a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="align-middle">{{ $payment->user?->name ?? 'Deleted Owner' }}</td>
                                <td class="align-middle fw-bold text-success">{{ number_format($payment->amount, 2) }} {{ $payment->invoice?->subscriptionPlan?->currency ?? 'USD' }}</td>
                                <td class="align-middle">{{ $payment->payment_method }}</td>
                                <td class="align-middle font-sans-serif"><code>{{ $payment->transaction_id ?: 'N/A' }}</code></td>
                                <td class="align-middle">{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : 'N/A' }}</td>
                                <td class="align-middle text-end px-3">
                                    @if($payment->invoice)
                                        <a class="btn btn-link p-0" href="{{ route('saas-invoices.show', $payment->invoice->id) }}" data-bs-toggle="tooltip" title="View Related Invoice">
                                            <span class="text-info fas fa-file-invoice"></span>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No payments recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($payments->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
</div>
