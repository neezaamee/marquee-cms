<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-ticket-alt me-2 text-primary"></span>Booking Management</h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('create_bookings'))
                    <a class="btn btn-falcon-primary btn-sm text-nowrap" href="{{ route('bookings.create') }}">
                        <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add Booking
                    </a>
                @endif
            </div>
        </div>

        <div class="card-body bg-light border-top border-bottom py-2">
            <div class="row g-2">
                <!-- Search -->
                <div class="col-lg-2 col-md-4 col-12">
                    <div class="input-group input-group-sm">
                        <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search number, customer..." />
                        <span class="input-group-text"><span class="fas fa-search"></span></span>
                    </div>
                </div>

                <!-- Hall Filter -->
                <div class="col-lg-2 col-md-4 col-6">
                    <select wire:model.live="filterHall" class="form-select form-select-sm">
                        <option value="">All Halls</option>
                        @foreach($halls as $hall)
                            <option value="{{ $hall->id }}">{{ $hall->hall_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="col-lg-2 col-md-4 col-6">
                    <select wire:model.live="filterStatus" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="Draft">Draft</option>
                        <option value="Reserved">Reserved</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>

                <!-- Payment Status Filter -->
                <div class="col-lg-2 col-md-4 col-6">
                    <select wire:model.live="filterPaymentStatus" class="form-select form-select-sm">
                        <option value="">All Payments</option>
                        <option value="Unpaid">Unpaid</option>
                        <option value="Partially Paid">Partially Paid</option>
                        <option value="Paid">Paid</option>
                        <option value="Refunded">Refunded</option>
                    </select>
                </div>

                <!-- Start Date Filter -->
                <div class="col-lg-2 col-md-4 col-6">
                    <input wire:model.live="filterDateStart" type="date" class="form-control form-control-sm font-monospace" placeholder="From Date" title="From Booking Date" />
                </div>

                <!-- End Date Filter -->
                <div class="col-lg-2 col-md-4 col-6">
                    <input wire:model.live="filterDateEnd" type="date" class="form-control form-control-sm font-monospace" placeholder="To Date" title="To Booking Date" />
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            @if(session('success'))
                <div class="alert alert-success border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-grow-1 text-success-800">{{ session('success') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-grow-1 text-danger-800">{{ session('error') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3">Booking #</th>
                            <th>Customer</th>
                            <th>Hall & Slot</th>
                            <th>Event Date</th>
                            <th>Guest Count</th>
                            <th>Grand Total</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Payment</th>
                            <th class="text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            <tr>
                                <td class="px-3">
                                    <span class="badge badge-subtle-secondary fs-11 font-monospace">{{ $booking->booking_number }}</span>
                                </td>
                                <td class="fw-semi-bold">
                                    @if($booking->customer)
                                        <a href="{{ route('customers.show', $booking->customer->id) }}">{{ $booking->customer->full_name }}</a>
                                        <div class="text-muted fs-11">{{ $booking->customer->phone_number }}</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semi-bold">{{ $booking->hall->hall_name ?? '—' }}</div>
                                    <div class="text-muted fs-11">{{ $booking->slot->slot_name ?? 'Custom Time' }}</div>
                                </td>
                                <td>
                                    <div>{{ $booking->booking_date->format('M d, Y') }}</div>
                                    <div class="text-muted fs-11 font-monospace">
                                        {{ $booking->start_time->format('h:i A') }} - {{ $booking->end_time->format('h:i A') }}
                                    </div>
                                </td>
                                <td>
                                    {{ number_format($booking->guest_count) }}
                                </td>
                                <td class="fw-semi-bold">
                                    Rs. {{ number_format($booking->grand_total, 2) }}
                                </td>
                                <td class="text-center">
                                    @php
                                        $statusColors = [
                                            'Draft' => 'secondary',
                                            'Reserved' => 'warning',
                                            'Confirmed' => 'success',
                                            'Completed' => 'info',
                                            'Cancelled' => 'danger',
                                            'Rejected' => 'dark'
                                        ];
                                        $sc = $statusColors[$booking->booking_status] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-subtle-{{ $sc }} rounded-pill">{{ $booking->booking_status }}</span>
                                </td>
                                <td class="text-center">
                                    @php
                                        $paymentColors = [
                                            'Unpaid' => 'danger',
                                            'Partially Paid' => 'warning',
                                            'Paid' => 'success',
                                            'Refunded' => 'secondary'
                                        ];
                                        $pc = $paymentColors[$booking->payment_status] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-subtle-{{ $pc }} rounded-pill">{{ $booking->payment_status }}</span>
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-link p-0" href="{{ route('bookings.show', $booking->id) }}" data-bs-toggle="tooltip" title="View Details">
                                            <span class="text-info fas fa-eye"></span>
                                        </a>
                                        <a class="btn btn-link p-0" href="{{ route('bookings.slip', $booking->id) }}" data-bs-toggle="tooltip" title="Print Slip" target="_blank">
                                            <span class="text-success fas fa-print"></span>
                                        </a>
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_bookings'))
                                            <a class="btn btn-link p-0" href="{{ route('bookings.edit', $booking->id) }}" data-bs-toggle="tooltip" title="Edit Booking">
                                                <span class="text-primary fas fa-edit"></span>
                                            </a>
                                        @endif
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('cancel_bookings'))
                                            <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $booking->id }})" title="Cancel Booking">
                                                <span class="text-danger fas fa-ban"></span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <span class="fas fa-ticket-alt fa-2x mb-2 d-block"></span>
                                    No bookings found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($bookings->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>

    <!-- Cancel Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white" id="deleteConfirmModalLabel">
                        <span class="fas fa-exclamation-triangle me-2"></span>Confirm Booking Cancellation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <p class="mb-0 text-900">Are you sure you want to cancel and delete this booking? This will change its status to <strong>Cancelled</strong>, log the event in history, and soft-delete the record.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Go Back</button>
                    <button wire:click="deleteRecord" type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        <span class="fas fa-trash-alt me-1"></span>Cancel & Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
