<div>
    <!-- Session Messages -->
    @if(session('success'))
        <div class="alert alert-success border-2 d-flex align-items-center m-3" role="alert">
            <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
            <p class="mb-0 flex-grow-1 text-success-800">{{ session('success') }}</p>
            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Soft Deleted Alert -->
    @if($booking->trashed())
        <div class="alert alert-danger border-3 border-danger d-flex align-items-center m-3 shadow-sm" role="alert">
            <div class="bg-danger text-white me-3 icon-item rounded-circle p-2"><span class="fas fa-exclamation-triangle fs-7"></span></div>
            <div class="flex-grow-1">
                <h6 class="alert-heading mb-1 fw-bold text-danger">Booking Deleted / بکنگ حذف ہو چکی ہے</h6>
                <p class="mb-0 fs-12 text-800">
                    This booking has been cancelled and soft-deleted. It is kept in the system for historical logs and audit trail but cannot be edited or modified.
                </p>
            </div>
        </div>
    @endif

    <!-- Menu Modification Alert for Kitchen Slip -->
    @if($booking->is_kitchen_menu_modified)
        <div class="alert alert-warning border-3 border-warning d-flex align-items-center m-3 shadow-sm" role="alert">
            <div class="bg-warning text-dark me-3 icon-item rounded-circle p-2"><span class="fas fa-exclamation-triangle fs-7"></span></div>
            <div class="flex-grow-1">
                <h6 class="alert-heading mb-1 fw-bold text-dark">Kitchen Menu Modified! / کچن مینو تبدیل ہو چکا ہے</h6>
                <p class="mb-0 fs-12 text-800">
                    The menu items or guest headcount for this booking have been modified since Kitchen Slip <strong>V{{ $booking->kitchen_print_version }}</strong> was printed on <strong>{{ $booking->kitchen_printed_at?->format('d-M-Y h:i A') }}</strong>. Please print an updated Kitchen Slip.
                </p>
            </div>
            <button wire:click="openKitchenSlipModal" class="btn btn-dark btn-sm ms-3 fw-bold text-nowrap">
                <i class="fas fa-print me-1"></i> Print Updated Slip (V{{ ($booking->kitchen_print_version ?? 0) + 1 }})
            </button>
        </div>
    @endif

    <!-- Top Status Transition Banner -->
    <div class="card mb-3 border border-300">
        <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2 bg-light">
            <div class="d-flex align-items-center gap-2">
                <span class="fs-12 fw-bold text-600">Current Booking Status:</span>
                @if($booking->booking_status === 'Confirmed')
                    <span class="badge badge-subtle-success fs-11 rounded-pill">Confirmed</span>
                @elseif($booking->booking_status === 'Reserved')
                    <span class="badge badge-subtle-warning fs-11 rounded-pill">Reserved</span>
                @elseif($booking->booking_status === 'Completed')
                    <span class="badge badge-subtle-info fs-11 rounded-pill">Completed</span>
                @elseif($booking->booking_status === 'Cancelled')
                    <span class="badge badge-subtle-danger fs-11 rounded-pill">Cancelled</span>
                @else
                    <span class="badge badge-subtle-secondary fs-11 rounded-pill">{{ $booking->booking_status }}</span>
                @endif
            </div>

            @if(!$booking->trashed() && ($booking->booking_status !== 'Completed' || (auth()->user()->role && in_array(auth()->user()->role->name, ['owner', 'super_admin']))))
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    <span class="fs-12 text-600 fw-bold me-2">Transition Status:</span>
                    @if($booking->booking_status !== 'Confirmed')
                        <button wire:click="updateStatus('Confirmed')" class="btn btn-success btn-xs" type="button"><span class="fas fa-check-circle me-1"></span>Confirm</button>
                    @endif
                    @if($booking->booking_status !== 'Reserved')
                        <button wire:click="updateStatus('Reserved')" class="btn btn-warning btn-xs text-white" type="button"><span class="fas fa-pause-circle me-1"></span>Reserve</button>
                    @endif
                    @if($booking->booking_status !== 'Completed' && $booking->booking_status !== 'Cancelled')
                        <button wire:click="updateStatus('Completed')" class="btn btn-info btn-xs" type="button"><span class="fas fa-calendar-check me-1"></span>Complete</button>
                    @endif
                    @if($booking->booking_status !== 'Cancelled')
                        <button wire:click="updateStatus('Cancelled')" class="btn btn-danger btn-xs" type="button"><span class="fas fa-times-circle me-1"></span>Cancel</button>
                    @endif
                    @if($booking->booking_status !== 'Rejected' && $booking->booking_status !== 'Confirmed')
                        <button wire:click="updateStatus('Rejected')" class="btn btn-dark btn-xs" type="button"><span class="fas fa-ban me-1"></span>Reject</button>
                    @endif
                </div>
            @endif
        </div>
    </div>

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
                        @if((auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_bookings')) && ($booking->booking_status !== 'Completed' || (auth()->user()->role && in_array(auth()->user()->role->name, ['owner', 'super_admin']))))
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
                                    <td class="text-500 fw-bold px-0 py-1">Venue Hall(s):</td>
                                    <td class="px-0 py-1 fw-bold text-800">
                                        @if($booking->halls->isNotEmpty())
                                            {{ $booking->halls->pluck('hall_name')->implode(', ') }}
                                        @else
                                            {{ $booking->hall->hall_name ?? '—' }}
                                        @endif
                                    </td>
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

            <!-- Guest Information Card -->
            <div class="card mb-3 border border-200 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><span class="fas fa-users me-2 text-primary"></span>Guest Headcount & Confirmation Status</h6>
                    <button wire:click="openGuestModal" class="btn btn-falcon-primary btn-sm">
                        <span class="fas fa-user-edit me-1"></span> Update Headcount
                    </button>
                </div>
                <div class="card-body fs-11">
                    <div class="row g-3 text-center">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded border">
                                <div class="text-500 fw-bold text-uppercase fs-11">Tentative Guests</div>
                                <div class="fs-3 font-monospace fw-bold text-primary">{{ number_format($booking->tentative_guests ?? $booking->guest_count) }}</div>
                                <div class="text-muted fs-12">Initial Headcount Estimate</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded border">
                                <div class="text-500 fw-bold text-uppercase fs-11">Confirmed Guests</div>
                                @if($booking->confirmed_guests)
                                    <div class="fs-3 font-monospace fw-bold text-success">{{ number_format($booking->confirmed_guests) }}</div>
                                    <div class="text-muted fs-12">Confirmed Customer Headcount</div>
                                @else
                                    <div class="fs-3 font-monospace fw-bold text-muted">—</div>
                                    <div class="text-muted fs-12">Pending Confirmation</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded border">
                                <div class="text-500 fw-bold text-uppercase fs-11">Confirmation Status</div>
                                <div class="mt-2">
                                    @if($booking->is_guest_confirmed)
                                        <span class="badge bg-success-subtle text-success fs-10 px-3 py-2"><span class="fas fa-check-circle me-1"></span>Confirmed</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning fs-10 px-3 py-2"><span class="fas fa-clock me-1"></span>Tentative</span>
                                    @endif
                                </div>
                                <div class="text-muted fs-12 mt-2">Effective: <strong>{{ number_format($booking->effective_guest_count) }} Guests</strong></div>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-3">
                        <h6 class="text-primary font-sans-serif fw-bold mb-2">Guest Privacy & Partition Arrangement</h6>
                        @if($booking->privacy_required)
                            <div class="row g-2">
                                <div class="col-sm-4">
                                    <span class="text-500 fw-bold text-uppercase fs-11">Privacy Required:</span>
                                    <span class="badge bg-danger-subtle text-danger px-2 py-1 ms-2 fs-12"><i class="fas fa-lock me-1"></i>Yes</span>
                                </div>
                                <div class="col-sm-4">
                                    <span class="text-500 fw-bold text-uppercase fs-11">Ladies Ratio:</span>
                                    <strong class="text-800 font-monospace fs-12">{{ $booking->privacy_ladies_percentage }}%</strong>
                                </div>
                                <div class="col-sm-4">
                                    <span class="text-500 fw-bold text-uppercase fs-11">Gents Ratio:</span>
                                    <strong class="text-800 font-monospace fs-12">{{ $booking->privacy_gents_percentage }}%</strong>
                                </div>
                            </div>
                        @else
                            <div class="row g-2">
                                <div class="col-12">
                                    <span class="text-500 fw-bold text-uppercase fs-11">Privacy Required:</span>
                                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1 ms-2 fs-12"><i class="fas fa-lock-open me-1"></i>No</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Customize Menu and Add-ons Details -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold"><span class="fas fa-utensils me-2 text-primary"></span>Selected Menu & Extra Services</h6>
                </div>
                <div class="card-body fs-11">
                    <div class="row g-3">
                        <!-- Custom Menu Items -->
                        <div class="col-md-6 border-end border-translucent">
                            <h6 class="text-primary font-sans-serif fw-bold mb-2">Customized Menu Items</h6>
                            @if($booking->menuItems->isNotEmpty())
                                <ul class="list-group list-group-flush border-translucent">
                                    @foreach($booking->menuItems as $item)
                                        <li class="list-group-item px-0 py-1 d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="fw-bold text-800">
                                                    {{ $item->item_name }}
                                                    @if($item->urdu_name)
                                                        <span class="text-muted fs-11 ms-1">({{ $item->urdu_name }})</span>
                                                    @endif
                                                </span>
                                                @if($item->pivot->custom_note)
                                                    <span class="d-block text-muted fs-12 italic">({{ $item->pivot->custom_note }})</span>
                                                @endif
                                            </div>
                                            @if(!empty($item->pivot->managed_by_host))
                                                <span class="badge badge-subtle-warning fs-11">Managed by Host</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-muted fs-11">No customized menu items recorded.</p>
                            @endif
                        </div>

                        <!-- Booked Extra Services (Add-ons) -->
                        <div class="col-md-6">
                            <h6 class="text-primary font-sans-serif fw-bold mb-2">Selected Add-ons / Services</h6>
                            @php
                                $displayAddons = $booking->finalBill ? $booking->finalBill->extraServices : $booking->extraServices;
                            @endphp
                            @if($displayAddons->isNotEmpty())
                                <table class="table table-sm table-borderless fs-11 mb-0">
                                    <thead>
                                        <tr class="border-bottom text-500">
                                            <th class="px-0 py-1">Service</th>
                                            <th class="px-0 py-1 text-center" style="width: 50px;">Qty</th>
                                            <th class="px-0 py-1 text-end" style="width: 100px;">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($displayAddons as $savedSrv)
                                            <tr>
                                                <td class="px-0 py-1 fw-semi-bold text-800">{{ $savedSrv->service_name }}</td>
                                                <td class="px-0 py-1 text-center text-800">{{ $savedSrv->quantity }}</td>
                                                <td class="px-0 py-1 text-end text-800 font-monospace">Rs. {{ number_format($savedSrv->total_price, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="text-muted fs-11">No additional services selected.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event Vendor Services & Commission Card -->
            <div class="card mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><span class="fas fa-handshake me-2 text-primary"></span>Event Vendor Services & Partnerships</h6>
                    <button wire:click="openVendorSaleModal" class="btn btn-falcon-success btn-xs"><i class="fas fa-plus me-1"></i> Add Vendor Service</button>
                </div>
                <div class="card-body fs-12">
                    @if($vendorSales->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 fs-12">
                                <thead class="bg-200">
                                    <tr>
                                        <th>Vendor</th>
                                        <th>Service</th>
                                        <th>Sale Amount</th>
                                        <th>Commission %</th>
                                        <th>Commission Income</th>
                                        <th>Net Vendor Payable</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($vendorSales as $vs)
                                        <tr>
                                            <td class="fw-bold text-dark">{{ $vs->vendor->name ?? '—' }} <span class="badge badge-subtle-secondary fs-10">{{ $vs->vendor->vendor_type ?? '' }}</span></td>
                                            <td><span class="badge badge-subtle-info">{{ $vs->service->service_name ?? 'Custom Service' }}</span></td>
                                            <td class="fw-bold">Rs. {{ number_format($vs->sale_amount) }}</td>
                                            <td><span class="badge badge-subtle-success">{{ $vs->commission_rate }}%</span></td>
                                            <td class="fw-bold text-success">Rs. {{ number_format($vs->commission_amount) }}</td>
                                            <td class="fw-bold text-primary">Rs. {{ number_format($vs->vendor_net_amount) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light fw-bold">
                                    <tr>
                                        <td colspan="2" class="text-end">Vendor Totals:</td>
                                        <td class="text-dark">Rs. {{ number_format($vendorSales->sum('sale_amount')) }}</td>
                                        <td>—</td>
                                        <td class="text-success">Rs. {{ number_format($vendorSales->sum('commission_amount')) }}</td>
                                        <td class="text-primary">Rs. {{ number_format($vendorSales->sum('vendor_net_amount')) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <p class="text-muted fs-11 mb-0">No external vendor services attached to this booking. Click "Add Vendor Service" to assign a florist, sound system, photographer, or decorator.</p>
                    @endif
                </div>
            </div>

            <!-- Payment Transactions Ledger -->
            <div class="card mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><span class="fas fa-wallet me-2 text-primary"></span>Payment History Ledger</h6>
                    <span class="badge badge-subtle-success fs-12 font-monospace">
                        Total Paid: Rs. {{ number_format($booking->payments->sum('amount'), 2) }}
                    </span>
                </div>
                <div class="card-body">
                    @if($booking->payments->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm table-striped border fs-11 mb-0">
                                <thead>
                                    <tr class="bg-light text-700">
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th>Recorded By</th>
                                        <th>Notes</th>
                                        <th class="text-end" style="width: 120px;">Amount</th>
                                        <th class="text-center" style="width: 80px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($booking->payments as $payment)
                                        <tr>
                                            <td class="align-middle fw-bold">{{ $payment->payment_date->format('M d, Y') }}</td>
                                            <td class="align-middle"><span class="badge badge-subtle-primary">{{ $payment->payment_method }}</span></td>
                                            <td class="align-middle font-monospace">{{ $payment->transaction_reference ?? '—' }}</td>
                                            <td class="align-middle fw-semi-bold text-700">{{ $payment->recorder->name ?? 'System' }}</td>
                                            <td class="align-middle text-muted">{{ $payment->notes ?? '—' }}</td>
                                            <td class="align-middle text-end font-monospace fw-bold text-800">Rs. {{ number_format($payment->amount, 2) }}</td>
                                            <td class="align-middle text-center">
                                                <a href="{{ route('bookings.payment-receipt', $payment->id) }}" target="_blank" class="btn btn-falcon-default btn-xs" title="Print Receipt">
                                                    <span class="fas fa-print"></span>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @php
                                        $billingAmount = $booking->finalBill ? $booking->finalBill->grand_total : $booking->grand_total;
                                        $balanceDue = max(0, $billingAmount - $booking->payments->sum('amount'));
                                    @endphp
                                    <tr class="table-info fw-bold fs-11">
                                        <td colspan="6" class="text-end text-800">Total Payments Recorded:</td>
                                        <td class="text-end text-800 font-monospace">Rs. {{ number_format($booking->payments->sum('amount'), 2) }}</td>
                                    </tr>
                                    <tr class="{{ $balanceDue <= 0 ? 'table-success' : 'table-warning' }} fw-bold fs-11">
                                        <td colspan="6" class="text-end text-800">Outstanding Balance:</td>
                                        <td class="text-end text-800 font-monospace">
                                            Rs. {{ number_format($balanceDue, 2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted fs-11">
                            <span class="fas fa-info-circle me-1"></span>No payment transactions recorded in the ledger yet.
                        </div>
                    @endif
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
            <!-- If Final Bill exists, show Final Bill card first, then Original Bill below it! -->
            @if($booking->finalBill)
            <div class="card border border-success mb-3 shadow-sm">
                <div class="card-header bg-success text-white py-2">
                    <h6 class="mb-0 text-white"><span class="fas fa-file-invoice-dollar me-2"></span>Event-Day Final Bill (Actual)</h6>
                </div>
                <div class="card-body fs-11">
                    <table class="table table-sm table-borderless mb-0">
                        @if(!$booking->no_food)
                            <tr>
                                <td class="text-500 px-0">Actual Guests:</td>
                                <td class="px-0 text-end fw-semi-bold">{{ $booking->finalBill->guest_count }}</td>
                            </tr>
                            <tr>
                                <td class="text-500 px-0">Per Plate Rate:</td>
                                <td class="px-0 text-end fw-semi-bold">Rs. {{ number_format($booking->finalBill->per_plate_price, 2) }}</td>
                            </tr>
                            <tr class="border-bottom">
                                <td class="text-500 px-0 pb-2">Package Sum:</td>
                                <td class="px-0 text-end fw-bold pb-2">Rs. {{ number_format($booking->finalBill->package_amount, 2) }}</td>
                            </tr>
                        @else
                            <tr class="border-bottom text-secondary">
                                <td class="px-0 pb-2">Catering Plan:</td>
                                <td class="px-0 text-end fw-bold pb-2">Sitting Plan Only (No Food)</td>
                            </tr>
                        @endif

                        <tr>
                            <td class="text-500 px-0 pt-2">Hall Rent:</td>
                            <td class="px-0 text-end fw-semi-bold pt-2">Rs. {{ number_format($booking->finalBill->hall_charges, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-500 px-0">Extra Addons (Actual):</td>
                            <td class="px-0 text-end fw-semi-bold">Rs. {{ number_format($booking->finalBill->extra_charges, 2) }}</td>
                        </tr>
                        <tr class="border-bottom text-danger">
                            <td class="px-0 pb-2">Discount:</td>
                            <td class="px-0 text-end fw-bold pb-2">- Rs. {{ number_format($booking->finalBill->discount_amount, 2) }}</td>
                        </tr>

                        <tr class="fw-bold">
                            <td class="px-0 py-2">Subtotal:</td>
                            <td class="px-0 text-end py-2">Rs. {{ number_format($booking->finalBill->subtotal, 2) }}</td>
                        </tr>
                        <tr class="border-bottom text-muted">
                            <td class="px-0 pb-2">Tax:</td>
                            <td class="px-0 text-end pb-2">Rs. {{ number_format($booking->finalBill->tax_amount, 2) }}</td>
                        </tr>

                        <tr class="border-bottom text-info">
                            <td class="px-0 py-2">Refundable Deposit:</td>
                            <td class="px-0 text-end fw-bold py-2">Rs. {{ number_format($booking->security_deposit, 2) }}</td>
                        </tr>

                        <tr class="fs-9 fw-black text-success">
                            <td class="px-0 pt-3">Final Grand Total:</td>
                            <td class="px-0 text-end pt-3">Rs. {{ number_format($booking->finalBill->grand_total, 2) }}</td>
                        </tr>
                    </table>
                    @if($booking->finalBill->notes)
                        <div class="border-top pt-2 mt-2 text-muted italic fs-12">
                            <strong>Remarks:</strong> {{ $booking->finalBill->notes }}
                        </div>
                    @endif
                </div>
            </div>
            @endif

            <div class="card border border-primary mb-3">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0 text-white"><span class="fas fa-receipt me-2"></span>Billing & Invoice Math (Original)</h6>
                </div>
                <div class="card-body fs-11">
                    <table class="table table-sm table-borderless mb-0">
                        @if(!$booking->no_food)
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
                        @else
                            <tr class="border-bottom text-secondary">
                                <td class="px-0 pb-2">Catering Plan:</td>
                                <td class="px-0 text-end fw-bold pb-2">Sitting Plan Only (No Food)</td>
                            </tr>
                        @endif

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
                            <span class="fas fa-print me-1"></span> Print Booking Slip (V1)
                        </a>
                        <a class="btn btn-falcon-primary btn-sm w-100 mt-2" href="{{ route('bookings.slip-v2', $booking->id) }}" target="_blank">
                            <span class="fas fa-print me-1"></span> Print Booking Slip (V2)
                        </a>
                        <a class="btn btn-falcon-info btn-sm w-100 mt-2" href="{{ route('bookings.slip-v3', $booking->id) }}" target="_blank">
                            <span class="fas fa-print me-1"></span> Print Booking Slip (V3)
                        </a>
                        <a class="btn btn-falcon-danger btn-sm w-100 mt-2" href="{{ route('bookings.pdf', $booking->id) }}" target="_blank">
                            <span class="fas fa-file-pdf me-1"></span> Download Invoice PDF
                        </a>
                        <button wire:click="openKitchenSlipModal" class="btn btn-warning btn-sm w-100 mt-2 text-dark fw-bold shadow-xs" type="button">
                            <span class="fas fa-utensils me-1"></span> Print Kitchen Menu
                            @if(!empty($booking->kitchen_print_version))
                                <span class="badge bg-dark text-white ms-1 fs-11">V{{ $booking->kitchen_print_version }}</span>
                            @endif
                        </button>
                        <hr class="my-2" />

                        <!-- Prepare Final Bill Button -->
                        @if($booking->booking_status === 'Confirmed' || $booking->booking_status === 'Completed')
                            <button wire:click="openFinalBillModal" class="btn btn-falcon-warning btn-sm w-100 mt-2" type="button">
                                <span class="fas fa-file-invoice-dollar me-1"></span> 
                                {{ $booking->finalBill ? 'Adjust Final Bill' : 'Prepare Final Bill' }}
                            </button>
                        @endif


                        <!-- Refundable Security Deposit Status & Process -->
                        <hr class="my-2" />
                        <span class="text-muted fs-12 fw-bold mb-1">Security Deposit Status:</span>
                        <div class="bg-light border rounded p-2 mb-2 fs-11">
                            <div><strong>Current Status:</strong> 
                                @if($booking->deposit_status === 'Refunded')
                                    <span class="badge badge-subtle-success">Refunded</span>
                                @elseif($booking->deposit_status === 'Deducted')
                                    <span class="badge badge-subtle-danger">Deducted (Damages)</span>
                                @else
                                    <span class="badge badge-subtle-info">Held (Active)</span>
                                @endif
                            </div>
                            @if($booking->deposit_status !== 'Held')
                                <div class="mt-1"><strong>Refunded:</strong> Rs. {{ number_format($booking->deposit_refunded_amount, 2) }}</div>
                                <div><strong>Deducted:</strong> Rs. {{ number_format($booking->deposit_deducted_amount, 2) }}</div>
                                @if($booking->deposit_notes)
                                    <div class="mt-1 text-muted fs-12 text-wrap" style="word-break: break-all;"><strong>Notes:</strong> {{ $booking->deposit_notes }}</div>
                                @endif
                            @endif
                        </div>

                        @if($booking->deposit_status === 'Held')
                            @if($showDepositModal)
                                <div class="border border-info rounded p-2 bg-info-subtle">
                                    <h6 class="fs-12 text-info-900 fw-bold mb-2">Process Security Deposit</h6>
                                    
                                    <div class="mb-2">
                                        <label class="form-label fs-11 mb-1">Action Type</label>
                                        <select wire:model.live="depositAction" class="form-select form-select-sm fs-12">
                                            <option value="refund_full">Refund Full Amount (Rs. {{ number_format($booking->security_deposit, 2) }})</option>
                                            <option value="partial_refund">Partial Refund / Deduct Damages</option>
                                        </select>
                                    </div>

                                    @if($depositAction === 'partial_refund')
                                        <div class="mb-2">
                                            <label class="form-label fs-11 mb-1">Refunded Amount (Rs.)</label>
                                            <input wire:model="depositRefundedAmount" type="number" class="form-control form-control-sm fs-12" />
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label fs-11 mb-1">Deducted Amount (Rs.)</label>
                                            <input wire:model="depositDeductedAmount" type="number" class="form-control form-control-sm fs-12" />
                                        </div>
                                        @error('depositSum') <div class="text-danger fs-12 mb-2">{{ $message }}</div> @enderror
                                    @endif

                                    <div class="mb-2">
                                        <label class="form-label fs-11 mb-1">Deduction / Refund Notes</label>
                                        <textarea wire:model="depositNotes" class="form-control form-control-sm fs-12" rows="2" placeholder="Describe damages, deductions, or refund reference..."></textarea>
                                        @error('depositNotes') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="d-flex justify-content-end gap-1">
                                        <button wire:click="$set('showDepositModal', false)" class="btn btn-falcon-default btn-xs" type="button">Cancel</button>
                                        <button wire:click="processDeposit" class="btn btn-info btn-xs" type="button">Process Release</button>
                                    </div>
                                </div>
                            @else
                                <button wire:click="$set('showDepositModal', true)" class="btn btn-falcon-info btn-sm w-100" type="button">
                                    <span class="fas fa-hand-holding-usd me-1"></span> Process Deposit Release
                                </button>
                            @endif
                        @endif

                        <hr class="my-2" />

                        <!-- Recording payments panel -->
                        @if($showPaymentModal)
                            <div class="border border-warning rounded p-2 bg-warning-subtle">
                                <h6 class="fs-12 text-warning-800 fw-bold mb-2">Record Ledger Payment</h6>
                                <div class="mb-2">
                                    <label class="form-label fs-11 mb-1">Amount Paid (Rs.) *</label>
                                    <input wire:model="amountPaid" type="number" class="form-control form-control-sm fs-12" placeholder="e.g. 50000" />
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fs-11 mb-1">Payment Date *</label>
                                    <input wire:model="paymentDate" type="date" class="form-control form-control-sm fs-12" />
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fs-11 mb-1">Payment Method *</label>
                                    <select wire:model="paymentMethod" class="form-select form-select-sm fs-12">
                                        <option value="Cash">Cash</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Cheque">Cheque</option>
                                        <option value="Card">Card</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fs-11 mb-1">Transaction Ref / Cheque #</label>
                                    <input wire:model="transactionReference" type="text" class="form-control form-control-sm fs-12" placeholder="e.g. TXN-123456" />
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fs-11 mb-1">Notes / Remarks</label>
                                    <input wire:model="paymentNote" type="text" class="form-control form-control-sm fs-12" placeholder="e.g. Advance cash payment" />
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
    <!-- Final Bill Modal -->
    @if($showFinalBillModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index:1050;">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-translucent shadow-lg">
                    <div class="modal-header bg-light py-3">
                        <h6 class="modal-title mb-0 fw-bold text-primary">
                            <span class="fas fa-file-invoice-dollar me-2"></span>Event-Day Final Bill Adjustments
                        </h6>
                        <button wire:click="$set('showFinalBillModal', false)" type="button" class="btn-close fs-12" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3 fs-12">
                        <div class="bg-light border rounded p-3 mb-3">
                            <div class="row">
                                <div class="col-sm-6">
                                    <strong>Original Booking Details:</strong>
                                    <div class="mt-1">Guests: {{ $booking->guest_count }}</div>
                                    <div>Per Plate: Rs. {{ number_format($booking->per_plate_price, 2) }}</div>
                                    <div>Original Grand Total: Rs. {{ number_format($booking->grand_total, 2) }}</div>
                                </div>
                                <div class="col-sm-6 text-end">
                                    <span class="badge badge-subtle-info rounded-pill">Event Day Adjustment Mode</span>
                                </div>
                            </div>
                        </div>

                        <!-- Form Grid -->
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-bold mb-1">Actual Guest Count *</label>
                                <input wire:model.live="fbGuestCount" type="number" class="form-control form-control-sm" />
                                @error('fbGuestCount') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-bold mb-1">Actual Per Plate Price (Rs.) *</label>
                                <input wire:model.live="fbPerPlatePrice" type="number" step="0.01" class="form-control form-control-sm" />
                                @error('fbPerPlatePrice') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-bold mb-1">Actual Hall Charges (Rs.) *</label>
                                <input wire:model.live="fbHallCharges" type="number" step="0.01" class="form-control form-control-sm" />
                                @error('fbHallCharges') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-bold mb-1">Discount Amount (Rs.) *</label>
                                <input wire:model.live="fbDiscountAmount" type="number" step="0.01" class="form-control form-control-sm" />
                                @error('fbDiscountAmount') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <!-- Addons / Extra Services Adjustment -->
                        <div class="card mb-3 bg-light border">
                            <div class="card-header bg-200 py-2 d-flex justify-content-between align-items-center">
                                <h7 class="mb-0 fw-bold fs-11 text-800">Actual Add-ons / Services consumed</h7>
                            </div>
                            <div class="card-body p-2 fs-11">
                                <table class="table table-sm table-striped border-0 mb-2">
                                    <thead>
                                        <tr>
                                            <th>Service Name</th>
                                            <th class="text-center" style="width: 80px;">Rate</th>
                                            <th class="text-center" style="width: 80px;">Qty</th>
                                            <th class="text-end" style="width: 100px;">Total</th>
                                            <th style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($fbAddonsList as $idx => $addon)
                                            <tr>
                                                <td class="align-middle fw-semi-bold">{{ $addon['service_name'] }}</td>
                                                <td class="align-middle text-center font-monospace">Rs. {{ number_format($addon['unit_price'], 2) }}</td>
                                                <td class="align-middle text-center">{{ $addon['quantity'] }}</td>
                                                <td class="align-middle text-end font-monospace">Rs. {{ number_format($addon['total_price'], 2) }}</td>
                                                <td class="text-center">
                                                    <button wire:click="removeFbAddon({{ $idx }})" class="btn btn-link text-danger p-0" type="button"><span class="fas fa-trash-alt"></span></button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">No addons added to final bill yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                <!-- Quick Add Addon Row -->
                                <div class="row g-2 border-top pt-2">
                                    <div class="col-sm-5">
                                        <input wire:model="newAddonName" type="text" class="form-control form-control-xs" placeholder="Add-on Service Name" />
                                    </div>
                                    <div class="col-sm-3">
                                        <input wire:model="newAddonPrice" type="number" class="form-control form-control-xs" placeholder="Rate (Rs.)" />
                                    </div>
                                    <div class="col-sm-2">
                                        <input wire:model="newAddonQty" type="number" class="form-control form-control-xs" placeholder="Qty" />
                                    </div>
                                    <div class="col-sm-2 d-grid">
                                        <button wire:click="addFbAddon" class="btn btn-falcon-success btn-xs" type="button"><span class="fas fa-plus"></span> Add</button>
                                    </div>
                                </div>
                                @error('newAddonName') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                                @error('newAddonPrice') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                                @error('newAddonQty') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <!-- Dynamic Live Calculation Result -->
                        <div class="bg-light border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between">
                                <span>Adjusted Subtotal:</span>
                                <span class="fw-bold">Rs. {{ number_format($fbGuestCount * $fbPerPlatePrice + $fbHallCharges + $fbExtraCharges - $fbDiscountAmount, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <span>Estimated Tax:</span>
                                <span class="fw-bold">Rs. {{ number_format($fbTaxAmount, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mt-1 border-top pt-2 text-primary fw-bold">
                                <span>Final Bill Grand Total:</span>
                                <span>Rs. {{ number_format($fbGuestCount * $fbPerPlatePrice + $fbHallCharges + $fbExtraCharges - $fbDiscountAmount + $fbTaxAmount + $booking->security_deposit, 2) }}</span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-bold mb-1">Deduction / Final Bill Audit Remarks</label>
                            <textarea wire:model="fbNotes" class="form-control form-control-sm" rows="2" placeholder="e.g. Guests count verified on event day. Added extra stage mic."></textarea>
                            @error('fbNotes') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button wire:click="$set('showFinalBillModal', false)" type="button" class="btn btn-falcon-default btn-xs px-3">Cancel</button>
                        <button wire:click="saveFinalBill" type="button" class="btn btn-warning btn-xs px-4">Lock Final Bill</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Guest Headcount Modal -->
    @if($showGuestModal)
        <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title"><span class="fas fa-user-edit me-2 text-primary"></span>Update Guest Headcount</h5>
                        <button type="button" class="btn-close" wire:click="$set('showGuestModal', false)"></button>
                    </div>
                    <form wire:submit.prevent="confirmGuestCount">
                        <div class="modal-body p-3">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tentative Guests (Estimated)</label>
                                <input type="number" wire:model="modalTentativeGuests" class="form-control @error('modalTentativeGuests') is-invalid @enderror" min="1">
                                @error('modalTentativeGuests') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Confirmed Guests (Customer Count)</label>
                                <input type="number" wire:model="modalConfirmedGuests" class="form-control @error('modalConfirmedGuests') is-invalid @enderror" placeholder="Leave empty if still tentative" min="0">
                                <small class="text-muted d-block mt-1">Entering a confirmed guest count automatically updates the headcount status to <strong>Confirmed</strong>.</small>
                                @error('modalConfirmedGuests') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showGuestModal', false)">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm"><span class="fas fa-save me-1"></span>Save & Recalculate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Kitchen Slip Selection & Instructions Modal -->
    @if($showKitchenSlipModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header bg-warning text-dark py-2">
                        <h5 class="modal-title fw-bold fs-14">
                            <i class="fas fa-utensils me-2"></i>Print Kitchen Menu Slip (کچن مینو آرڈر سلپ)
                        </h5>
                        <button wire:click="$set('showKitchenSlipModal', false)" type="button" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-700 fs-12">Select Print Language Mode / زبان کا انتخاب:</label>
                            <div class="d-flex flex-column gap-2 fs-12">
                                <div class="form-check p-2 ps-4 border rounded bg-light">
                                    <input wire:model="kitchenLang" class="form-check-input" type="radio" name="kitchenLang" id="langBilingual" value="bilingual">
                                    <label class="form-check-label fw-bold text-primary" for="langBilingual">
                                        <i class="fas fa-globe me-1"></i> Bilingual — English + Urdu (Recommended / تجویز کردہ)
                                        <div class="text-muted fs-11 fw-normal">Displays dual English and Urdu labels for items, headings, and headcount.</div>
                                    </label>
                                </div>
                                <div class="form-check p-2 ps-4 border rounded">
                                    <input wire:model="kitchenLang" class="form-check-input" type="radio" name="kitchenLang" id="langEnglish" value="english">
                                    <label class="form-check-label fw-bold text-dark" for="langEnglish">
                                        English Only
                                    </label>
                                </div>
                                <div class="form-check p-2 ps-4 border rounded">
                                    <input wire:model="kitchenLang" class="form-check-input" type="radio" name="kitchenLang" id="langUrdu" value="urdu">
                                    <label class="form-check-label fw-bold text-dark" for="langUrdu">
                                        اردو (Urdu RTL Format)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-bold text-700 fs-12">Special Kitchen Preparation Notes / خصوصیات:</label>
                            <textarea wire:model="kitchenInstructions" class="form-control fs-12" rows="3" placeholder="e.g. Less spicy, VIP table setup, Separate naan preparation..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button wire:click="$set('showKitchenSlipModal', false)" type="button" class="btn btn-secondary btn-sm px-3">Cancel</button>
                        <button wire:click="saveKitchenInstructionsAndPrint" type="button" class="btn btn-warning btn-sm px-4 fw-bold text-dark">
                            <i class="fas fa-print me-1"></i> Generate & Print Slip (V{{ ($booking->kitchen_print_version ?? 0) + 1 }})
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Add Vendor Service Modal -->
    @if($showVendorSaleModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-success text-white py-2">
                        <h6 class="modal-title fw-bold fs-13"><i class="fas fa-handshake me-2"></i>Add Vendor Service to Booking #{{ $booking->booking_number }}</h6>
                        <button wire:click="$set('showVendorSaleModal', false)" type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="saveBookingVendorSale">
                        <div class="modal-body p-3 fs-12">
                            <div class="mb-2">
                                <label class="form-label fw-bold">Select Vendor Partner <span class="text-danger">*</span></label>
                                <select wire:model.live="vsVendorId" class="form-select form-select-sm">
                                    <option value="">-- Choose Vendor Partner --</option>
                                    @foreach($allVendors as $v)
                                        <option value="{{ $v->id }}">{{ $v->name }} ({{ $v->vendor_type }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold">Vendor Service (Optional)</label>
                                <select wire:model="vsServiceId" class="form-select form-select-sm">
                                    <option value="">-- Custom / Direct Service --</option>
                                    @foreach($vsVendorServices as $vs)
                                        <option value="{{ $vs->id }}">{{ $vs->service_name }} (Default Price: Rs. {{ number_format($vs->default_sale_price) }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Sale Amount (Rs.) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" wire:model="vsSaleAmount" class="form-control form-control-sm @error('vsSaleAmount') is-invalid @enderror" placeholder="80000">
                                    @error('vsSaleAmount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Custom Commission % (Override)</label>
                                    <input type="number" step="0.01" wire:model="vsCommissionRate" class="form-control form-control-sm" placeholder="Auto-resolved if blank">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold">Notes / Requirements</label>
                                <textarea wire:model="vsNotes" class="form-control form-control-sm" rows="2" placeholder="Specific arrangement details, setup instructions..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button wire:click="$set('showVendorSaleModal', false)" type="button" class="btn btn-secondary btn-sm px-3">Cancel</button>
                            <button type="submit" class="btn btn-success btn-sm px-4"><i class="fas fa-check-circle me-1"></i> Attach Vendor Service</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('open-print-window', (data) => {
                window.open(data.url, '_blank');
            });
        });
    </script>
</div>
