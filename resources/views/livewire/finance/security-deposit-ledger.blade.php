<div>
    <!-- Title Card -->
    <div class="card mb-3">
        <div class="card-body py-3">
            <h5 class="mb-1 text-primary fw-bold"><span class="fas fa-hand-holding-usd me-2"></span>Security Deposit Ledger</h5>
            <p class="fs-12 text-600 mb-0">Manage customer security deposits separately from core event revenues. Release full refunds or log damage deductions.</p>
        </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="row g-3 mb-3 text-center">
        <!-- Held -->
        <div class="col-sm-4">
            <div class="card h-100 border border-info bg-light-subtle">
                <div class="card-body py-3">
                    <span class="fs-11 fw-bold text-uppercase text-600 d-block">Active Held Deposits</span>
                    <span class="fs-6 fw-black text-info font-monospace d-block my-1">Rs. {{ number_format($heldTotal, 2) }}</span>
                    <span class="badge badge-subtle-info rounded-pill">Total Liability</span>
                </div>
            </div>
        </div>

        <!-- Refunded -->
        <div class="col-sm-4">
            <div class="card h-100 border border-success bg-light-subtle">
                <div class="card-body py-3">
                    <span class="fs-11 fw-bold text-uppercase text-600 d-block">Total Refunded</span>
                    <span class="fs-6 fw-black text-success font-monospace d-block my-1">Rs. {{ number_format($refundedTotal, 2) }}</span>
                    <span class="badge badge-subtle-success rounded-pill">Returned to Customer</span>
                </div>
            </div>
        </div>

        <!-- Deducted -->
        <div class="col-sm-4">
            <div class="card h-100 border border-danger bg-light-subtle">
                <div class="card-body py-3">
                    <span class="fs-11 fw-bold text-uppercase text-600 d-block">Total Deductions (Damages)</span>
                    <span class="fs-6 fw-black text-danger font-monospace d-block my-1">Rs. {{ number_format($deductedTotal, 2) }}</span>
                    <span class="badge badge-subtle-danger rounded-pill">Retained as Revenue Offset</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <!-- Search -->
                <div class="col-md-6">
                    <label class="form-label fs-11 fw-bold text-600">Search Deposits</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><span class="fas fa-search text-400"></span></span>
                        <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Search by Booking #, Customer name, Phone..." />
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-md-6">
                    <label class="form-label fs-11 fw-bold text-600">Deposit Status</label>
                    <select wire:model.live="statusFilter" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="Held">Held (Active)</option>
                        <option value="Refunded">Refunded (Fully Released)</option>
                        <option value="Deducted">Deducted (Damages Claimed)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Session Success Alert -->
    @if(session('success'))
        <div class="alert alert-success border-2 d-flex align-items-center mb-3" role="alert">
            <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
            <p class="mb-0 flex-grow-1 text-success-800">{{ session('success') }}</p>
            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Datatable -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped border-0 fs-11 mb-0 align-middle">
                    <thead class="bg-light text-800">
                        <tr>
                            <th class="ps-3">Booking Number</th>
                            <th>Customer Name</th>
                            <th>Event Date</th>
                            <th class="text-center">Deposit Status</th>
                            <th class="text-end">Initial Deposit</th>
                            <th class="text-end">Refunded Amount</th>
                            <th class="text-end">Deducted Amount</th>
                            <th>Audit Notes</th>
                            <th class="text-center" style="width: 150px;">Release Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            <tr>
                                <td class="ps-3">
                                    <a href="{{ route('bookings.show', $booking->id) }}" class="fw-bold font-monospace">
                                        #{{ $booking->booking_number }}
                                    </a>
                                </td>
                                <td>{{ $booking->customer->full_name ?? '—' }}</td>
                                <td>{{ $booking->booking_date->format('M d, Y') }}</td>
                                <td class="text-center">
                                    @if($booking->deposit_status === 'Refunded')
                                        <span class="badge badge-subtle-success">Refunded</span>
                                    @elseif($booking->deposit_status === 'Deducted')
                                        <span class="badge badge-subtle-danger">Deducted</span>
                                    @else
                                        <span class="badge badge-subtle-info">Held</span>
                                    @endif
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark">Rs. {{ number_format($booking->security_deposit, 2) }}</td>
                                <td class="text-end font-monospace text-success">
                                    {{ $booking->deposit_refunded_amount > 0 ? 'Rs. '.number_format($booking->deposit_refunded_amount, 2) : '—' }}
                                </td>
                                <td class="text-end font-monospace text-danger">
                                    {{ $booking->deposit_deducted_amount > 0 ? 'Rs. '.number_format($booking->deposit_deducted_amount, 2) : '—' }}
                                </td>
                                <td class="text-muted text-wrap" style="max-width: 250px;">{{ $booking->deposit_notes ?? '—' }}</td>
                                <td class="text-center">
                                    @if($booking->deposit_status === 'Held')
                                        <button wire:click="openDepositModal({{ $booking->id }})" class="btn btn-falcon-info btn-xs py-1" type="button">
                                            <span class="fas fa-hand-holding-usd me-1"></span>Process Release
                                        </button>
                                    @else
                                        <span class="text-muted fs-12 italic"><span class="fas fa-check-circle text-success me-1"></span>Processed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5 fs-12">
                                    <span class="fas fa-info-circle me-1"></span>No security deposit records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($bookings->hasPages())
            <div class="card-footer d-flex justify-content-end bg-light py-2">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>

    <!-- Processing Modal -->
    @if($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0, 0, 0, 0.5); z-index: 1050;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-translucent shadow-lg">
                    <div class="modal-header bg-light py-3">
                        <h6 class="modal-title mb-0 fw-bold text-primary">
                            <span class="fas fa-hand-holding-usd me-2"></span>Process Deposit Release
                        </h6>
                        <button wire:click="closeModal" type="button" class="btn-close fs-12" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3 fs-12">
                        @if($selectedBookingObj)
                            <div class="bg-light border rounded p-3 mb-3">
                                <div><strong>Booking Number:</strong> #{{ $selectedBookingObj->booking_number }}</div>
                                <div><strong>Customer:</strong> {{ $selectedBookingObj->customer->full_name ?? '—' }}</div>
                                <div><strong>Security Deposit Held:</strong> <strong class="text-primary font-monospace">Rs. {{ number_format($selectedBookingObj->security_deposit, 2) }}</strong></div>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-bold text-600 mb-1">Release Action Type</label>
                            <select wire:model.live="depositAction" class="form-select form-select-sm">
                                <option value="refund_full">Refund Full Amount (Rs. {{ $selectedBookingObj ? number_format($selectedBookingObj->security_deposit, 2) : '0.00' }})</option>
                                <option value="partial_refund">Partial Refund / Deduct Damages</option>
                            </select>
                        </div>

                        @if($depositAction === 'partial_refund')
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-bold text-600 mb-1">Amount to Refund (Rs.)</label>
                                    <input wire:model="depositRefundedAmount" type="number" class="form-control form-control-sm" />
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold text-600 mb-1">Amount to Deduct (Rs.)</label>
                                    <input wire:model="depositDeductedAmount" type="number" class="form-control form-control-sm" />
                                </div>
                            </div>
                            @error('depositSum') <div class="text-danger fs-12 mb-3">{{ $message }}</div> @enderror
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-bold text-600 mb-1">Deduction Remarks / Refund Details</label>
                            <textarea wire:model="depositNotes" class="form-control form-control-sm" rows="3" placeholder="Describe damage details, replacement values, or bank wire reference numbers..."></textarea>
                            @error('depositNotes') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button wire:click="closeModal" type="button" class="btn btn-falcon-default btn-xs px-3">Cancel</button>
                        <button wire:click="processDeposit" type="button" class="btn btn-info btn-xs px-4">Release Deposit</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
