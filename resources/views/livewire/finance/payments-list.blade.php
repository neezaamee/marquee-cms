<div>
    <!-- Title Card -->
    <div class="card mb-3">
        <div class="card-body py-3">
            <h5 class="mb-1 text-primary fw-bold"><span class="fas fa-wallet me-2"></span>Payments Ledger Tracker</h5>
            <p class="fs-12 text-600 mb-0">Centralized log of all event bookings payments, installment receipts, and collection transactions.</p>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <!-- Search -->
                <div class="col-md-4">
                    <label class="form-label fs-11 fw-bold text-600">Search Payments</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><span class="fas fa-search text-400"></span></span>
                        <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Search by Booking #, Customer, Ref..." />
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="col-md-3">
                    <label class="form-label fs-11 fw-bold text-600">Payment Method</label>
                    <select wire:model.live="paymentMethod" class="form-select form-select-sm">
                        <option value="">All Methods</option>
                        <option value="Cash">Cash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Cheque">Cheque</option>
                        <option value="Card">Card</option>
                    </select>
                </div>

                <!-- Start Date -->
                <div class="col-md-2.5 col-sm-6">
                    <label class="form-label fs-11 fw-bold text-600">Start Date</label>
                    <input wire:model.live="startDate" type="date" class="form-control form-control-sm" />
                </div>

                <!-- End Date -->
                <div class="col-md-2.5 col-sm-6">
                    <label class="form-label fs-11 fw-bold text-600">End Date</label>
                    <input wire:model.live="endDate" type="date" class="form-control form-control-sm" />
                </div>
            </div>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped border-0 fs-11 mb-0 align-middle">
                    <thead class="bg-light text-800">
                        <tr>
                            <th class="ps-3">Payment Date</th>
                            <th>Booking Number</th>
                            <th>Customer Name</th>
                            <th>Method</th>
                            <th>Transaction Ref</th>
                            <th>Notes</th>
                            <th>Recorded By</th>
                            <th class="text-end">Amount Paid</th>
                            <th class="text-center" style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td class="ps-3 fw-bold">{{ $payment->payment_date->format('M d, Y') }}</td>
                                <td>
                                    <a href="{{ route('bookings.show', $payment->booking_id) }}" class="fw-bold font-monospace text-primary">
                                        #{{ $payment->booking->booking_number }}
                                    </a>
                                </td>
                                <td>{{ $payment->booking->customer->full_name ?? '—' }}</td>
                                <td><span class="badge badge-subtle-primary">{{ $payment->payment_method }}</span></td>
                                <td class="font-monospace text-700">{{ $payment->transaction_reference ?? '—' }}</td>
                                <td class="text-muted text-wrap" style="max-width: 200px;">{{ $payment->notes ?? '—' }}</td>
                                <td class="fw-semi-bold text-700">{{ $payment->recorder->name ?? 'System' }}</td>
                                <td class="text-end font-monospace fw-black text-success fs-10">Rs. {{ number_format($payment->amount, 2) }}</td>
                                <td class="text-center">
                                    <a href="{{ route('bookings.payment-receipt', $payment->id) }}" target="_blank" class="btn btn-falcon-default btn-xs" title="Print Receipt">
                                        <span class="fas fa-print me-1 text-secondary"></span>Receipt
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5 fs-12">
                                    <span class="fas fa-info-circle me-1"></span>No payment transactions match your search filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($payments->hasPages())
            <div class="card-footer d-flex justify-content-end bg-light py-2">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
</div>
