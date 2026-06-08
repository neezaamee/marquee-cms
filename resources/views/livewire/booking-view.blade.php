<div>
    <!-- Session Messages -->
    @if(session('success'))
        <div class="alert alert-success border-2 d-flex align-items-center m-3" role="alert">
            <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
            <p class="mb-0 flex-grow-1 text-success-800">{{ session('success') }}</p>
            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3">
        <!-- Main details card -->
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><span class="fas fa-info-circle me-2 text-primary"></span>Booking #{{ $booking->booking_number }}</h5>
                    <div class="d-flex gap-2">
                        <a class="btn btn-falcon-default btn-sm" href="{{ route('bookings.index') }}">
                            <span class="fas fa-chevron-left me-1"></span> Back to List
                        </a>
                        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_bookings'))
                            <a class="btn btn-falcon-primary btn-sm" href="{{ route('bookings.edit', $booking->id) }}">
                                <span class="fas fa-edit me-1"></span> Edit
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <!-- Left Side Event Info -->
                        <div class="col-md-6 border-end border-translucent">
                            <h6 class="text-primary font-sans-serif fw-bold mb-2">Event Details</h6>
                            <table class="table table-sm table-borderless fs-11">
                                <tr>
                                    <td class="text-500 fw-bold px-0 py-1" style="width: 120px;">Event Type:</td>
                                    <td class="px-0 py-1 fw-bold text-800">{{ $booking->eventType->event_type_name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-500 fw-bold px-0 py-1">Venue Hall:</td>
                                    <td class="px-0 py-1 fw-bold text-800">{{ $booking->hall->hall_name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-500 fw-bold px-0 py-1">Booking Date:</td>
                                    <td class="px-0 py-1 fw-bold text-800">{{ $booking->booking_date->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-500 fw-bold px-0 py-1">Shift / Slot:</td>
                                    <td class="px-0 py-1 fw-bold text-800">{{ $booking->slot->slot_name ?? 'Custom Time Range' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-500 fw-bold px-0 py-1">Event Timings:</td>
                                    <td class="px-0 py-1 fw-bold font-monospace text-danger-800">
                                        {{ $booking->start_time->format('h:i A') }} → {{ $booking->end_time->format('h:i A') }}
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Right Side Customer Details -->
                        <div class="col-md-6">
                            <h6 class="text-primary font-sans-serif fw-bold mb-2">Customer Profile</h6>
                            @if($booking->customer)
                                <table class="table table-sm table-borderless fs-11">
                                    <tr>
                                        <td class="text-500 fw-bold px-0 py-1" style="width: 120px;">Name:</td>
                                        <td class="px-0 py-1 fw-bold text-800">
                                            <a href="{{ route('customers.show', $booking->customer->id) }}">{{ $booking->customer->full_name }}</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-500 fw-bold px-0 py-1">Type:</td>
                                        <td class="px-0 py-1"><span class="badge badge-subtle-info">{{ $booking->customer->customer_type }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-500 fw-bold px-0 py-1">Phone:</td>
                                        <td class="px-0 py-1 text-800 fw-bold">{{ $booking->customer->phone_number }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-500 fw-bold px-0 py-1">CNIC / ID:</td>
                                        <td class="px-0 py-1 text-800">{{ $booking->customer->cnic_national_id ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-500 fw-bold px-0 py-1">Email:</td>
                                        <td class="px-0 py-1 text-800">{{ $booking->customer->email ?? '—' }}</td>
                                    </tr>
                                </table>
                            @else
                                <p class="text-muted fs-11">No customer linked to this booking.</p>
                            @endif
                        </div>
                    </div>

                    <!-- Special Instructions Panel -->
                    <div class="border-top pt-3">
                        <h6 class="text-primary font-sans-serif fw-bold mb-2">Special Instructions</h6>
                        <div class="bg-light border rounded p-3 fs-11 text-800" style="white-space: pre-wrap;">
                            {{ $booking->special_instructions ?? 'No special setup instructions or catering modifications recorded.' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking histories timeline audit log -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold"><span class="fas fa-history me-2 text-primary"></span>Audit Log & History Timeline</h6>
                </div>
                <div class="card-body">
                    <div class="timeline-simple">
                        @forelse($histories as $hist)
                            <div class="d-flex mb-3">
                                <div class="timeline-item-icon me-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                        <span class="fas fa-check-double fs-12"></span>
                                    </div>
                                </div>
                                <div class="flex-grow-1 border-bottom pb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-800 fs-11">
                                            Status: 
                                            @if($hist->status_from)
                                                <span class="text-decoration-line-through text-muted">{{ $hist->status_from }}</span> → 
                                            @endif
                                            <strong>{{ $hist->status_to }}</strong>
                                        </span>
                                        <span class="text-muted fs-12">{{ $hist->created_at->format('M d, Y h:i A') }}</span>
                                    </div>
                                    @if($hist->payment_status_to)
                                        <div class="fs-12 text-600">
                                            Payment: 
                                            @if($hist->payment_status_from)
                                                <span class="text-decoration-line-through">{{ $hist->payment_status_from }}</span> → 
                                            @endif
                                            <strong>{{ $hist->payment_status_to }}</strong>
                                        </div>
                                    @endif
                                    <div class="text-800 fs-11 mt-1">{{ $hist->notes }}</div>
                                    <div class="text-500 fs-12 mt-1">Recorded by: <strong>{{ $hist->user->name ?? 'System' }}</strong></div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-3 text-muted fs-11">No status logs recorded yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Right billing breakdown column -->
        <div class="col-lg-4">
            <div class="card border border-primary mb-3">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0 text-white"><span class="fas fa-receipt me-2"></span>Billing & Invoice Math</h6>
                </div>
                <div class="card-body fs-11">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-500 px-0">Package:</td>
                            <td class="px-0 text-end fw-semi-bold">{{ $booking->package->package_name ?? 'Custom' }}</td>
                        </tr>
                        <tr>
                            <td class="text-500 px-0">Guests:</td>
                            <td class="px-0 text-end fw-semi-bold">{{ $booking->guest_count }}</td>
                        </tr>
                        <tr>
                            <td class="text-500 px-0">Per Plate Rate:</td>
                            <td class="px-0 text-end fw-semi-bold">Rs. {{ number_format($booking->per_plate_price, 2) }}</td>
                        </tr>
                        <tr class="border-bottom">
                            <td class="text-500 px-0 pb-2">Package Sum:</td>
                            <td class="px-0 text-end fw-bold pb-2">Rs. {{ number_format($booking->package_amount, 2) }}</td>
                        </tr>

                        <tr>
                            <td class="text-500 px-0 pt-2">Hall Rent:</td>
                            <td class="px-0 text-end fw-semi-bold pt-2">Rs. {{ number_format($booking->hall_charges, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-500 px-0">Extra Addons:</td>
                            <td class="px-0 text-end fw-semi-bold">Rs. {{ number_format($booking->extra_charges, 2) }}</td>
                        </tr>
                        <tr class="border-bottom text-danger">
                            <td class="px-0 pb-2">Discount:</td>
                            <td class="px-0 text-end fw-bold pb-2">- Rs. {{ number_format($booking->discount_amount, 2) }}</td>
                        </tr>

                        <tr class="fw-bold">
                            <td class="px-0 py-2">Subtotal:</td>
                            <td class="px-0 text-end py-2">Rs. {{ number_format($booking->subtotal, 2) }}</td>
                        </tr>
                        <tr class="border-bottom text-muted">
                            <td class="px-0 pb-2">Tax:</td>
                            <td class="px-0 text-end pb-2">Rs. {{ number_format($booking->tax_amount, 2) }}</td>
                        </tr>

                        <tr class="border-bottom text-info">
                            <td class="px-0 py-2">Refundable Deposit:</td>
                            <td class="px-0 text-end fw-bold py-2">Rs. {{ number_format($booking->security_deposit, 2) }}</td>
                        </tr>

                        <tr class="fs-9 fw-black text-primary">
                            <td class="px-0 pt-3">Grand Total:</td>
                            <td class="px-0 text-end pt-3">Rs. {{ number_format($booking->grand_total, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Booking Statuses Summary panel -->
            <div class="card mb-3">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0 fw-bold"><span class="fas fa-sliders-h me-2 text-primary"></span>Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-2">
                        <!-- Invoice Printing -->
                        <a class="btn btn-falcon-success btn-sm w-100" href="{{ route('bookings.slip', $booking->id) }}" target="_blank">
                            <span class="fas fa-print me-1"></span> Print Booking Slip
                        </a>

                        <hr class="my-2" />

                        <!-- Booking Status Updates -->
                        <span class="text-muted fs-12 fw-bold mb-1">Update Reservation Status:</span>
                        <div class="d-flex gap-1 flex-wrap">
                            @if($booking->booking_status !== 'Confirmed')
                                <button wire:click="updateStatus('Confirmed')" class="btn btn-success btn-xs" type="button">Confirm</button>
                            @endif
                            @if($booking->booking_status !== 'Reserved')
                                <button wire:click="updateStatus('Reserved')" class="btn btn-warning btn-xs" type="button">Reserve</button>
                            @endif
                            @if($booking->booking_status !== 'Completed' && $booking->booking_status !== 'Cancelled')
                                <button wire:click="updateStatus('Completed')" class="btn btn-info btn-xs" type="button">Complete</button>
                            @endif
                            @if($booking->booking_status !== 'Cancelled')
                                <button wire:click="updateStatus('Cancelled')" class="btn btn-danger btn-xs" type="button">Cancel</button>
                            @endif
                            @if($booking->booking_status !== 'Rejected' && $booking->booking_status !== 'Confirmed')
                                <button wire:click="updateStatus('Rejected')" class="btn btn-dark btn-xs" type="button">Reject</button>
                            @endif
                        </div>

                        <hr class="my-2" />

                        <!-- Recording payments panel -->
                        @if($showPaymentModal)
                            <div class="border border-warning rounded p-2 bg-warning-subtle">
                                <h6 class="fs-12 text-warning-800 fw-bold mb-2">Record Cash Payment</h6>
                                <div class="mb-2">
                                    <label class="form-label fs-11 mb-1">Amount Paid (Rs.)</label>
                                    <input wire:model="amountPaid" type="number" class="form-control form-control-sm" placeholder="e.g. 50000" />
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fs-11 mb-1">Notes</label>
                                    <input wire:model="paymentNote" type="text" class="form-control form-control-sm" placeholder="e.g. Adv cash payment" />
                                </div>
                                <div class="d-flex justify-content-end gap-1">
                                    <button wire:click="$set('showPaymentModal', false)" class="btn btn-falcon-default btn-xs" type="button">Cancel</button>
                                    <button wire:click="recordPayment" class="btn btn-warning btn-xs" type="button">Save Payment</button>
                                </div>
                            </div>
                        @else
                            @if($booking->payment_status !== 'Paid')
                                <button wire:click="$set('showPaymentModal', true)" class="btn btn-falcon-warning btn-sm w-100" type="button">
                                    <span class="fas fa-wallet me-1"></span> Record Payment
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
