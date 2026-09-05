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
                    <span class="badge badge-subtle-info fs-11 rounded-pill"><i class="fas fa-check-double me-1"></i>Completed & Revenue Recognized</span>
                @elseif($booking->booking_status === 'Cancelled')
                    <span class="badge badge-subtle-danger fs-11 rounded-pill"><i class="fas fa-ban me-1"></i>Cancelled</span>
                @else
                    <span class="badge badge-subtle-secondary fs-11 rounded-pill">{{ $booking->booking_status }}</span>
                @endif

                <span class="ms-3 fs-12 fw-bold text-600">Accounting State:</span>
                @if($booking->is_revenue_recognized)
                    <span class="badge bg-success-subtle text-success fs-11"><i class="fas fa-file-invoice-dollar me-1"></i>Revenue Recognized</span>
                @else
                    <span class="badge bg-warning-subtle text-warning-emphasis fs-11"><i class="fas fa-clock me-1"></i>Advance Liability (Unearned)</span>
                @endif
            </div>

            @if(!$booking->trashed() && ($booking->booking_status !== 'Completed' || (auth()->user()->role && in_array(auth()->user()->role->name, ['owner', 'super_admin', 'business_owner']))))
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    <span class="fs-12 text-600 fw-bold me-2">Transition:</span>
                    @if($booking->booking_status !== 'Confirmed')
                        <button wire:click="updateStatus('Confirmed')" class="btn btn-success btn-xs" type="button"><span class="fas fa-check-circle me-1"></span>Confirm</button>
                    @endif
                    @if($booking->booking_status !== 'Reserved')
                        <button wire:click="updateStatus('Reserved')" class="btn btn-warning btn-xs text-white" type="button"><span class="fas fa-pause-circle me-1"></span>Reserve</button>
                    @endif
                    @if($booking->booking_status !== 'Completed' && $booking->booking_status !== 'Cancelled')
                        <button wire:click="updateStatus('Completed')" wire:confirm="Are you sure you want to mark this Event as Completed? This will automatically recognize earned revenue and release advance liabilities in the General Ledger." class="btn btn-info btn-xs" type="button">
                            <span class="fas fa-calendar-check me-1"></span>Complete Event & Recognize Revenue
                        </button>
                    @endif
                    @if($booking->booking_status !== 'Cancelled')
                        <button wire:click="openBookingCancelModal" class="btn btn-danger btn-xs" type="button"><span class="fas fa-times-circle me-1"></span>Cancel & Settle</button>
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
                            @php
                                $effectiveBranch = $booking->effective_branch ?? $booking->branch ?? ($booking->hall?->branch ?? null);
                            @endphp
                            <table class="table table-sm table-borderless fs-11">
                                @if($effectiveBranch)
                                    <tr>
                                        <td class="text-500 fw-bold px-0 py-1" style="width: 120px;">Branch:</td>
                                        <td class="px-0 py-1 fw-bold text-800">
                                            {{ $effectiveBranch->name }}
                                            @if($effectiveBranch->is_head_office)
                                                <span class="badge badge-subtle-primary ms-1 fs-12">Head Office</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-500 fw-bold px-0 py-1">Branch Location:</td>
                                        <td class="px-0 py-1 text-700">
                                            {{ $effectiveBranch->address ? $effectiveBranch->address . ', ' : '' }}{{ $effectiveBranch->city }}
                                            @if($effectiveBranch->phone)
                                                <span class="text-muted d-block fs-12"><i class="fas fa-phone me-1"></i>{{ $effectiveBranch->phone }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
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
                <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="mb-0 fw-bold"><span class="fas fa-handshake me-2 text-primary"></span>Service Providers / Partner Vendors</h6>
                        <div class="text-secondary fs-11">Manage external partner assignments, direct vs invoice billing, advance payouts, and ledger tracking.</div>
                    </div>
                    <button wire:click="openVendorSaleModal" class="btn btn-falcon-success btn-xs">
                        <i class="fas fa-plus me-1"></i> Assign Service Provider
                    </button>
                </div>
                <div class="card-body fs-12 p-0">
                    @if($vendorSales->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 fs-12">
                                <thead class="bg-200 text-800">
                                    <tr>
                                        <th class="px-3">Service Provider</th>
                                        <th>Service</th>
                                        <th class="text-end">Customer Charge</th>
                                        <th class="text-end">Cust. Advance</th>
                                        <th class="text-end text-danger fw-bold">Cust. Remaining</th>
                                        <th class="text-center">Billing Mode</th>
                                        <th class="text-end">Vendor Cost</th>
                                        <th class="text-end">Vendor Remaining</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end px-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($vendorSales as $vs)
                                        @php
                                            $custCharge = (float) $vs->sale_amount;
                                            $custPaid = (float) $vs->customer_paid;
                                            $custRemaining = (float) $vs->customer_remaining;
                                            $vendCost = (float) $vs->vendor_net_amount;
                                            $vendRemaining = (float) $vs->remaining_amount;
                                            $statusBadge = match($vs->payment_status) {
                                                'fully_paid' => 'success',
                                                'partially_paid' => 'warning',
                                                default => 'danger'
                                            };
                                            $isCancelled = $vs->status === 'cancelled';
                                        @endphp
                                        <tr class="{{ $isCancelled ? 'table-secondary opacity-75' : '' }}">
                                            <td class="px-3 fw-bold text-dark">
                                                {{ $vs->vendor->name ?? '—' }}
                                                <div class="text-muted fs-11">{{ $vs->vendor->vendor_type ?? 'Vendor' }} ({{ $vs->vendor->vendor_code ?? '' }})</div>
                                            </td>
                                            <td>
                                                <span class="badge badge-subtle-info">{{ $vs->service->service_name ?? 'Custom Service' }}</span>
                                            </td>
                                            <td class="text-end font-monospace fw-bold text-dark">Rs. {{ number_format($custCharge, 2) }}</td>
                                            <td class="text-end font-monospace text-success">
                                                Rs. {{ number_format($custPaid, 2) }}
                                            </td>
                                            <td class="text-end font-monospace fw-bold {{ $custRemaining > 0 ? 'text-danger' : 'text-success' }}">
                                                Rs. {{ number_format($custRemaining, 2) }}
                                            </td>
                                            <td class="text-center">
                                                @if($vs->include_in_invoice)
                                                    <span class="badge badge-subtle-success fs-10" title="Billed on Marquee Customer Invoice">
                                                        <i class="fas fa-file-invoice me-1"></i>In Invoice
                                                    </span>
                                                @else
                                                    <span class="badge badge-subtle-warning fs-10" title="Customer pays vendor directly. Excluded from invoice total.">
                                                        <i class="fas fa-hand-holding-usd me-1"></i>Direct Pay
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-end font-monospace text-primary fw-semi-bold">Rs. {{ number_format($vendCost, 2) }}</td>
                                            <td class="text-end font-monospace fw-bold {{ $vendRemaining > 0 ? 'text-danger' : 'text-success' }}">
                                                Rs. {{ number_format($vendRemaining, 2) }}
                                            </td>
                                            <td class="text-center">
                                                @if($isCancelled)
                                                    <span class="badge bg-danger text-white rounded-pill text-uppercase fs-10">Cancelled</span>
                                                @else
                                                    <span class="badge badge-subtle-{{ $statusBadge }} rounded-pill text-uppercase fs-11">
                                                        {{ str_replace('_', ' ', $vs->payment_status ?: 'unpaid') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-end px-3">
                                                <div class="d-flex justify-content-end gap-1">
                                                    <!-- View Details Button -->
                                                    <button wire:click="openVendorViewModal({{ $vs->id }})" class="btn btn-falcon-default btn-xs" title="View Full Details & History">
                                                        <i class="fas fa-eye text-info"></i>
                                                    </button>

                                                    @if(!$isCancelled)
                                                        <!-- Record Customer Advance Button -->
                                                        @if($custRemaining > 0.01)
                                                            <button wire:click="openCustomerPaymentModal({{ $vs->id }})" class="btn btn-falcon-success btn-xs" title="Record Customer Advance / Installment">
                                                                <i class="fas fa-plus-circle me-1"></i>Cust. Adv
                                                            </button>
                                                        @endif

                                                        <!-- Edit Button -->
                                                        <button wire:click="openVendorEditModal({{ $vs->id }})" class="btn btn-falcon-default btn-xs" title="Edit Service Provider Details">
                                                            <i class="fas fa-edit text-warning"></i>
                                                        </button>

                                                        <!-- Pay Vendor Installment Button -->
                                                        @if($vendRemaining > 0.01)
                                                            <button wire:click="openVendorPaymentModal({{ $vs->id }})" class="btn btn-falcon-primary btn-xs" title="Disburse Payout to Vendor">
                                                                <i class="fas fa-money-bill-wave me-1"></i> Pay Vendor
                                                            </button>
                                                        @endif

                                                        <!-- Cancel / Delete Button -->
                                                        <button wire:click="confirmCancelVendorSale({{ $vs->id }})" class="btn btn-falcon-danger btn-xs" title="Cancel or Remove Vendor Service">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    @endif

                                                    <!-- Vendor Ledger Button -->
                                                    <a href="{{ route('vendor-ledger.index', ['filterVendorId' => $vs->vendor_id]) }}" target="_blank" class="btn btn-falcon-default btn-xs" title="View Vendor Ledger">
                                                        <i class="fas fa-book text-secondary"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light fw-bold fs-11">
                                    <tr>
                                        <td colspan="2" class="text-end px-3 text-700">Vendor Totals:</td>
                                        <td class="text-end text-800 font-monospace">Rs. {{ number_format($vendorSales->where('status', '!=', 'cancelled')->sum('sale_amount'), 2) }}</td>
                                        <td class="text-center text-muted fs-10">
                                            In Invoice: Rs. {{ number_format($vendorSales->where('status', '!=', 'cancelled')->where('include_in_invoice', true)->sum('sale_amount'), 2) }}
                                        </td>
                                        <td class="text-end text-primary font-monospace">Rs. {{ number_format($vendorSales->where('status', '!=', 'cancelled')->sum('vendor_net_amount'), 2) }}</td>
                                        <td class="text-end text-muted font-monospace">Rs. {{ number_format($vendorSales->where('status', '!=', 'cancelled')->sum('advance_amount'), 2) }}</td>
                                        <td class="text-end text-success font-monospace">Rs. {{ number_format($vendorSales->where('status', '!=', 'cancelled')->sum('paid_amount'), 2) }}</td>
                                        <td class="text-end text-danger font-monospace">Rs. {{ number_format($vendorSales->where('status', '!=', 'cancelled')->sum('remaining_amount'), 2) }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="p-3 text-muted fs-11 mb-0">
                            No external service providers attached to this booking. Click "Assign Service Provider" to assign a florist, sound system, photographer, or decorator with advance payouts and ledger tracking.
                        </div>
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
                    <!-- Nav Tabs for Payments vs Sub-Ledger -->
                    <ul class="nav nav-tabs mb-3 fs-11" id="ledgerTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments-pane" type="button" role="tab"><i class="fas fa-receipt me-1"></i>Payment Receipts ({{ $booking->payments->count() }})</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="subledger-tab" data-bs-toggle="tab" data-bs-target="#subledger-pane" type="button" role="tab"><i class="fas fa-book me-1"></i>Customer Sub-Ledger ({{ $customerLedgers->count() }})</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="ledgerTabsContent">
                        <!-- Tab 1: Payment Receipts -->
                        <div class="tab-pane fade show active" id="payments-pane" role="tabpanel">
                            @if($booking->payments->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped border fs-11 mb-0 align-middle">
                                        <thead>
                                            <tr class="bg-light text-700">
                                                <th>Payment #</th>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Method</th>
                                                <th>Status</th>
                                                <th>Account / Voucher</th>
                                                <th>Recorded By</th>
                                                <th class="text-end" style="width: 120px;">Amount</th>
                                                <th class="text-center" style="width: 100px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($booking->payments as $payment)
                                                @php
                                                    $stBadge = match($payment->status) {
                                                        'pending_posting', 'received' => 'warning',
                                                        'posted' => 'success',
                                                        'rejected' => 'danger',
                                                        'reversed' => 'dark',
                                                        default => 'secondary'
                                                    };
                                                    $stLabel = match($payment->status) {
                                                        'pending_posting', 'received' => 'Pending Posting',
                                                        'posted' => 'Posted',
                                                        'rejected' => 'Rejected',
                                                        'reversed' => 'Reversed',
                                                        default => ucfirst($payment->status)
                                                    };
                                                @endphp
                                                <tr class="{{ $payment->status === 'pending_posting' ? 'table-warning-subtle' : '' }}">
                                                    <td class="align-middle font-monospace fw-bold text-primary">
                                                        {{ $payment->payment_number ?: ('PAY-'.$payment->id) }}
                                                    </td>
                                                    <td class="align-middle fw-bold">{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : '—' }}</td>
                                                    <td class="align-middle">
                                                        @if($payment->payment_type === 'refund')
                                                            <span class="badge badge-subtle-danger">Refund</span>
                                                        @elseif($payment->payment_type === 'receivable_payment')
                                                            <span class="badge badge-subtle-success">Receivable Settle</span>
                                                        @else
                                                            <span class="badge badge-subtle-info">Advance</span>
                                                        @endif
                                                    </td>
                                                    <td class="align-middle">
                                                        <span class="badge badge-subtle-primary">{{ $payment->payment_method }}</span>
                                                        @if($payment->transaction_reference || $payment->cheque_number)
                                                            <div class="font-monospace text-700 fs-10 mt-1">
                                                                {{ $payment->transaction_reference ?: ('Chq: '.$payment->cheque_number) }}
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="align-middle">
                                                        <span class="badge badge-subtle-{{ $stBadge }} fs-11">
                                                            @if($payment->status === 'pending_posting')
                                                                <span class="fas fa-clock me-1"></span>
                                                            @elseif($payment->status === 'posted')
                                                                <span class="fas fa-check-double me-1"></span>
                                                            @endif
                                                            {{ $stLabel }}
                                                        </span>
                                                    </td>
                                                    <td class="align-middle font-monospace fs-10">
                                                        @if($payment->status === 'posted')
                                                            <div class="text-success fw-bold">{{ $payment->account->name ?? 'Cash in Hand' }}</div>
                                                            <div class="text-muted">{{ $payment->journalVoucher?->voucher_no }}</div>
                                                        @else
                                                            <span class="text-muted">Awaiting Post</span>
                                                        @endif
                                                    </td>
                                                    <td class="align-middle fw-semi-bold text-700">{{ $payment->recorder->name ?? 'System' }}</td>
                                                    <td class="align-middle text-end font-monospace fw-bold {{ $payment->payment_type === 'refund' ? 'text-danger' : ($payment->status === 'posted' ? 'text-success' : 'text-800') }}">
                                                        {{ $payment->payment_type === 'refund' ? '- ' : '' }}Rs. {{ number_format($payment->amount, 2) }}
                                                    </td>
                                                    <td class="align-middle text-center">
                                                        <div class="d-inline-flex gap-1 align-items-center">
                                                            @if($payment->isPendingPosting() && (auth()->user()->isSuperAdmin() || auth()->user()->isBusinessOwner() || auth()->user()->hasPermission('post_payments')))
                                                                <button wire:click="openAccountantPostModal({{ $payment->id }})" class="btn btn-falcon-success btn-xs" type="button" title="Post to Cash/Bank Account">
                                                                    <span class="fas fa-check-double me-1"></span>Post
                                                                </button>
                                                            @endif
                                                            <a href="{{ route('bookings.payment-receipt', $payment->id) }}" target="_blank" class="btn btn-falcon-default btn-xs" title="Print Receipt">
                                                                <span class="fas fa-print"></span>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tr class="table-info fw-bold fs-11">
                                                <td colspan="7" class="text-end text-800">Net Posted Payments (Verified in Accounts):</td>
                                                <td class="text-end text-success font-monospace">Rs. {{ number_format($booking->total_paid, 2) }}</td>
                                                <td></td>
                                            </tr>
                                            @if($booking->advance_pending_posting > 0)
                                                <tr class="table-warning fw-bold fs-11">
                                                    <td colspan="7" class="text-end text-warning-emphasis">Pending Accountant Posting:</td>
                                                    <td class="text-end text-warning-emphasis font-monospace">Rs. {{ number_format($booking->advance_pending_posting, 2) }}</td>
                                                    <td></td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4 text-muted fs-11">
                                    <span class="fas fa-info-circle me-1"></span>No payment transactions recorded in the ledger yet.
                                </div>
                            @endif
                        </div>

                        <!-- Tab 2: Customer Sub-Ledger -->
                        <div class="tab-pane fade" id="subledger-pane" role="tabpanel">
                            @if($customerLedgers->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover border fs-11 mb-0">
                                        <thead class="bg-light text-700">
                                            <tr>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Reference / Voucher</th>
                                                <th>Description</th>
                                                <th class="text-end">Debit (Dr)</th>
                                                <th class="text-end">Credit (Cr)</th>
                                                <th class="text-end">Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($customerLedgers as $cl)
                                                <tr>
                                                    <td class="align-middle fw-bold">{{ $cl->transaction_date->format('M d, Y') }}</td>
                                                    <td class="align-middle"><span class="badge badge-subtle-secondary">{{ ucwords(str_replace('_', ' ', $cl->transaction_type)) }}</span></td>
                                                    <td class="align-middle font-monospace">{{ $cl->journalVoucher?->voucher_no ?? ($cl->reference_number ?? '—') }}</td>
                                                    <td class="align-middle text-700">{{ $cl->description }}</td>
                                                    <td class="align-middle text-end font-monospace text-danger">{{ $cl->debit > 0 ? 'Rs. ' . number_format($cl->debit, 2) : '—' }}</td>
                                                    <td class="align-middle text-end font-monospace text-success">{{ $cl->credit > 0 ? 'Rs. ' . number_format($cl->credit, 2) : '—' }}</td>
                                                    <td class="align-middle text-end font-monospace fw-bold text-dark">Rs. {{ number_format($cl->running_balance, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4 text-muted fs-11">
                                    <span class="fas fa-info-circle me-1"></span>No sub-ledger entries posted for this booking yet.
                                </div>
                            @endif
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
            <!-- Accounting & Financial Integration Card -->
            <div class="card border border-200 mb-3 shadow-sm">
                <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-white fs-12"><span class="fas fa-balance-scale me-2 text-warning"></span>Financial & Accounting Summary</h6>
                    <span class="badge {{ $booking->is_financially_settled ? 'bg-success' : 'bg-warning text-dark' }} fs-11">
                        {{ $booking->financial_status ?: 'Pending' }}
                    </span>
                </div>
                <div class="card-body fs-11 p-3">
                    <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                        <span class="text-600">Total Booking Value:</span>
                        <strong class="font-monospace text-800">Rs. {{ number_format($booking->effective_invoice_amount, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                        <span class="text-600">Customer Advance Liability (Held):</span>
                        <strong class="font-monospace {{ $booking->effective_advance_liability > 0 ? 'text-primary fw-bold' : 'text-muted' }}">
                            Rs. {{ number_format($booking->effective_advance_liability, 2) }}
                        </strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                        <span class="text-600">Earned Revenue Recognized:</span>
                        <strong class="font-monospace {{ $booking->is_revenue_recognized ? 'text-success fw-bold' : 'text-muted' }}">
                            Rs. {{ number_format($booking->revenue_recognized, 2) }}
                        </strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                        <span class="text-600">Accounts Receivable (Unpaid):</span>
                        <strong class="font-monospace {{ $booking->effective_receivable > 0 ? 'text-danger fw-bold' : 'text-success' }}">
                            Rs. {{ number_format($booking->effective_receivable, 2) }}
                        </strong>
                    </div>
                    <div class="d-flex justify-content-between pt-1">
                        <span class="text-800 fw-bold">Total Cash/Bank Collected:</span>
                        <strong class="font-monospace text-success fw-bold">Rs. {{ number_format($booking->total_paid, 2) }}</strong>
                    </div>
                </div>
            </div>
            <!-- If Final Bill exists, show Final Bill card first, then Original Bill below it! -->
            @if($booking->finalBill)
            <div class="card border border-success mb-3 shadow-sm">
                <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-white"><span class="fas fa-file-invoice-dollar me-2"></span>Event-Day Final Bill (Actual)</h6>
                    <a href="{{ route('bookings.final-bill-v2', $booking->id) }}" target="_blank" class="btn btn-xs btn-light text-success fw-bold">
                        <span class="fas fa-print me-1"></span> View / Print Invoice V2
                    </a>
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

                        <tr class="fs-9 fw-black text-primary border-bottom">
                            <td class="px-0 pt-3 pb-2">Grand Total:</td>
                            <td class="px-0 text-end pt-3 pb-2">Rs. {{ number_format($booking->grand_total, 2) }}</td>
                        </tr>

                        <!-- Two-Stage Payment Financial Status Breakdown -->
                        <tr>
                            <td class="px-0 pt-2 text-500">Total Received (Staff):</td>
                            <td class="px-0 text-end pt-2 font-monospace fw-bold">Rs. {{ number_format($booking->total_received_payments, 2) }}</td>
                        </tr>
                        @if($booking->advance_pending_posting > 0)
                            <tr class="text-warning-emphasis">
                                <td class="px-0 py-1">
                                    <span class="fas fa-clock me-1"></span>Pending Accountant Post:
                                </td>
                                <td class="px-0 text-end py-1 font-monospace fw-bold">Rs. {{ number_format($booking->advance_pending_posting, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="text-success">
                            <td class="px-0 py-1">
                                <span class="fas fa-check-double me-1"></span>Posted in Accounts:
                            </td>
                            <td class="px-0 text-end py-1 font-monospace fw-bold">Rs. {{ number_format($booking->total_paid, 2) }}</td>
                        </tr>
                        <tr class="border-top text-{{ $booking->remaining_customer_balance > 0 ? 'danger' : 'success' }} fw-bold">
                            <td class="px-0 pt-2">Remaining Balance:</td>
                            <td class="px-0 text-end pt-2 font-monospace">Rs. {{ number_format($booking->remaining_customer_balance, 2) }}</td>
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
                        <a class="btn btn-falcon-warning btn-sm w-100 mt-2" href="{{ route('bookings.final-bill-v2', $booking->id) }}" target="_blank">
                            <span class="fas fa-file-invoice me-1"></span> Print Final Bill Invoice (V2)
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
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fs-12 text-warning-900 fw-bold mb-0">Record Payment Receipt</h6>
                                    <span class="badge {{ $booking->is_revenue_recognized ? 'badge-subtle-success' : 'badge-subtle-info' }} fs-10">
                                        {{ $booking->is_revenue_recognized ? 'Receivable Settle' : 'Advance' }}
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fs-11 mb-1">Amount Received (Rs.) *</label>
                                    <input wire:model="amountPaid" type="number" step="0.01" class="form-control form-control-sm fs-12 font-monospace fw-bold" placeholder="e.g. 50000" />
                                    @error('amountPaid') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fs-11 mb-1">Payment Date *</label>
                                    <input wire:model="paymentDate" type="date" class="form-control form-control-sm fs-12" />
                                    @error('paymentDate') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fs-11 mb-1">Payment Method *</label>
                                    <select wire:model="paymentMethod" class="form-select form-select-sm fs-12">
                                        <option value="Cash">Cash</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Cheque">Cheque</option>
                                        <option value="Card">Card / POS</option>
                                        <option value="Online">Online Gateway</option>
                                    </select>
                                    @error('paymentMethod') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fs-11 mb-1">Transaction Ref / Cheque #</label>
                                    <input wire:model="transactionReference" type="text" class="form-control form-control-sm fs-12" placeholder="e.g. TXN-123456 / Cheque #" />
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fs-11 mb-1">Notes / Narration</label>
                                    <input wire:model="paymentNote" type="text" class="form-control form-control-sm fs-12" placeholder="e.g. Booking advance deposit" />
                                </div>
                                <div class="d-flex justify-content-end gap-1">
                                    <button wire:click="$set('showPaymentModal', false)" class="btn btn-falcon-default btn-xs" type="button">Cancel</button>
                                    <button wire:click="recordPayment" class="btn btn-warning btn-xs" type="button">
                                        <span class="fas fa-paper-plane me-1"></span>Submit for Accountant Post
                                    </button>
                                </div>
                            </div>
                        @else
                            @if(!$booking->is_financially_settled && $booking->booking_status !== 'Cancelled')
                                <button wire:click="openPaymentModal" class="btn btn-falcon-warning btn-sm w-100" type="button">
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
                        @php
                            $fbCalculatedGrandTotal = $fbGuestCount * $fbPerPlatePrice + $fbHallCharges + $fbExtraCharges + $fbVendorCharges - $fbDiscountAmount + $fbTaxAmount + $booking->security_deposit;
                            $fbTotalPaidByCust = (float) $booking->payments->sum('amount');
                            $fbNetRemainingCustDue = max(0.00, $fbCalculatedGrandTotal - $fbTotalPaidByCust);
                        @endphp
                        <div class="bg-light border rounded p-3 mb-2 fs-12">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Package Catering ({{ $fbGuestCount }} guests):</span>
                                <span class="fw-bold font-monospace">Rs. {{ number_format($fbGuestCount * $fbPerPlatePrice, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Hall Setup & Rent:</span>
                                <span class="fw-bold font-monospace">Rs. {{ number_format($fbHallCharges, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Extra Add-ons:</span>
                                <span class="fw-bold font-monospace">Rs. {{ number_format($fbExtraCharges, 2) }}</span>
                            </div>
                            @if($fbVendorCharges > 0)
                                <div class="d-flex justify-content-between mb-1 text-primary">
                                    <span>Service Providers (Billed via Marquee):</span>
                                    <span class="fw-bold font-monospace">Rs. {{ number_format($fbVendorCharges, 2) }}</span>
                                </div>
                            @endif
                            @if($fbDiscountAmount > 0)
                                <div class="d-flex justify-content-between mb-1 text-danger">
                                    <span>Discount Deducted:</span>
                                    <span class="fw-bold font-monospace">- Rs. {{ number_format($fbDiscountAmount, 2) }}</span>
                                </div>
                            @endif
                            <div class="d-flex justify-content-between border-top pt-1 mb-1">
                                <span>Adjusted Subtotal:</span>
                                <span class="fw-bold font-monospace">Rs. {{ number_format($fbGuestCount * $fbPerPlatePrice + $fbHallCharges + $fbExtraCharges + $fbVendorCharges - $fbDiscountAmount, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Estimated Tax:</span>
                                <span class="fw-bold font-monospace">Rs. {{ number_format($fbTaxAmount, 2) }}</span>
                            </div>
                            @if($booking->security_deposit > 0)
                                <div class="d-flex justify-content-between mb-1 text-info">
                                    <span>Refundable Security Deposit:</span>
                                    <span class="fw-bold font-monospace">Rs. {{ number_format($booking->security_deposit, 2) }}</span>
                                </div>
                            @endif
                            <div class="d-flex justify-content-between mt-2 border-top pt-2 text-dark fw-bold fs-13">
                                <span>Total Final Bill Amount:</span>
                                <span class="font-monospace">Rs. {{ number_format($fbCalculatedGrandTotal, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mt-1 text-success fw-bold">
                                <span>Less: Customer Advances / Payments Collected:</span>
                                <span class="font-monospace">- Rs. {{ number_format($fbTotalPaidByCust, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mt-1 border-top pt-2 {{ $fbNetRemainingCustDue > 0 ? 'text-danger' : 'text-success' }} fw-bold fs-13">
                                <span>Net Remaining Customer Balance Due:</span>
                                <span class="font-monospace">Rs. {{ number_format($fbNetRemainingCustDue, 2) }}</span>
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

    <!-- Assign Service Provider / Vendor Modal -->
    @if($showVendorSaleModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-success text-white py-2">
                        <h6 class="modal-title fw-bold fs-13"><i class="fas fa-handshake me-2"></i>Assign Service Provider to Booking #{{ $booking->booking_number }}</h6>
                        <button wire:click="$set('showVendorSaleModal', false)" type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="saveBookingVendorSale">
                        <div class="modal-body p-3 fs-12">
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Select Service Provider <span class="text-danger">*</span></label>
                                    <select wire:model.live="vsVendorId" class="form-select form-select-sm @error('vsVendorId') is-invalid @enderror">
                                        <option value="">-- Choose Service Provider --</option>
                                        @foreach($allVendors as $v)
                                            <option value="{{ $v->id }}">{{ $v->name }} ({{ $v->vendor_type }}){{ !empty($v->vendor_code) ? ' - ' . $v->vendor_code : '' }}</option>
                                        @endforeach
                                    </select>
                                    @error('vsVendorId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Service Package / Offering</label>
                                    <select wire:model.live="vsServiceId" class="form-select form-select-sm" {{ empty($vsVendorId) ? 'disabled' : '' }}>
                                        <option value="">-- Custom / Direct Service --</option>
                                        @foreach($vsVendorServices as $vs)
                                            <option value="{{ $vs->id }}">{{ $vs->service_name }} (Standard Price: Rs. {{ number_format($vs->default_sale_price) }})</option>
                                        @endforeach
                                    </select>
                                    @if(empty($vsVendorId))
                                        <div class="text-muted fs-11 mt-1">Please select a service provider first</div>
                                    @elseif($vsVendorServices->isEmpty())
                                        <div class="text-muted fs-11 mt-1"><i class="fas fa-info-circle me-1"></i>No preset packages found for this provider. Custom / Direct Service will be used.</div>
                                    @else
                                        <div class="text-success fs-11 mt-1"><i class="fas fa-check-circle me-1"></i>{{ $vsVendorServices->count() }} package(s) available</div>
                                    @endif
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-dark">Customer Charge (PKR) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" wire:model.live.debounce.400ms="vsCustomerCharge" class="form-control form-control-sm @error('vsCustomerCharge') is-invalid @enderror" placeholder="e.g. 25000">
                                    <div class="text-muted fs-11">Total agreed price billed to customer</div>
                                    @error('vsCustomerCharge') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-primary">Vendor Cost / Payable (PKR) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" wire:model.live.debounce.400ms="vsVendorCost" class="form-control form-control-sm @error('vsVendorCost') is-invalid @enderror" placeholder="e.g. 20000">
                                    <div class="text-muted fs-11">Total amount owed to vendor</div>
                                    @error('vsVendorCost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Commission Rate (%)</label>
                                    <input type="number" step="0.01" wire:model.live.debounce.400ms="vsCommissionRate" class="form-control form-control-sm" placeholder="Auto agreement rate">
                                    <div class="text-muted fs-11">Marquee margin percentage</div>
                                </div>
                            </div>

                            <!-- Customer Advance Section (Mandatory Option) -->
                            <div class="card border-success border-2 bg-light p-2 mb-2">
                                <div class="fw-bold text-uppercase fs-11 text-success mb-1">
                                    <i class="fas fa-hand-holding-usd me-1"></i>Customer Advance Collection (Mandatory Option)
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold fs-11 text-dark">Customer Advance Received (PKR) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" wire:model.live.debounce.400ms="vsCustomerAdvance" class="form-control form-control-sm @error('vsCustomerAdvance') is-invalid @enderror" placeholder="0.00">
                                        <div class="text-muted fs-10">Enter 0.00 if no advance received yet</div>
                                        @error('vsCustomerAdvance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold fs-11">Customer Payment Method</label>
                                        <select wire:model="vsCustomerPaymentMethod" class="form-select form-select-sm">
                                            <option value="Cash">Cash</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Cheque">Cheque</option>
                                            <option value="Online">Online / Card</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold fs-11">Customer Receipt / Ref #</label>
                                        <input type="text" wire:model="vsCustomerReference" class="form-control form-control-sm" placeholder="e.g. REC-0912">
                                    </div>
                                </div>
                            </div>

                            <!-- Billing Checkbox: Invoiced vs Direct Customer Payment -->
                            <div class="form-check form-switch mb-2 p-2 ps-5 border rounded bg-light">
                                <input class="form-check-input" type="checkbox" id="vsIncludeInInvoice" wire:model="vsIncludeInInvoice">
                                <label class="form-check-label fw-bold text-dark fs-12 mb-0" for="vsIncludeInInvoice">
                                    <span class="fas fa-file-invoice text-primary me-1"></span> Bill through Marquee Invoice (Customer pays Marquee)
                                </label>
                                <div class="text-muted fs-11 mt-1">
                                    When checked, remaining customer balance is charged on the event invoice and final bill. When unchecked, customer pays vendor directly.
                                </div>
                            </div>

                            <!-- Vendor Advance Payment Section (Optional) -->
                            <div class="card bg-light border p-2 mb-2">
                                <div class="fw-bold text-uppercase fs-11 text-700 mb-1"><i class="fas fa-money-bill-wave text-primary me-1"></i>Vendor Advance Payout (Disbursed to Vendor)</div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold fs-11">Advance Paid to Vendor (PKR)</label>
                                        <input type="number" step="0.01" wire:model.live.debounce.400ms="vsAdvanceAmount" class="form-control form-control-sm @error('vsAdvanceAmount') is-invalid @enderror" placeholder="0.00">
                                        @error('vsAdvanceAmount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold fs-11">Payout Method</label>
                                        <select wire:model="vsPaymentMethod" class="form-select form-select-sm">
                                            <option value="Cash">Cash</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Cheque">Cheque</option>
                                            <option value="Online">Online / Card</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold fs-11">Disbursed From Account</label>
                                        <select wire:model="vsAccountId" class="form-select form-select-sm">
                                            <option value="">-- Default Cash/Bank Account --</option>
                                            @foreach($accounts as $acc)
                                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->account_code }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Live Calculation Summary -->
                            @php
                                $custChargeVal = floatval($vsCustomerCharge);
                                $custAdvVal = floatval($vsCustomerAdvance);
                                $custRemVal = max(0.00, $custChargeVal - $custAdvVal);
                                $vendCostVal = floatval($vsVendorCost);
                                $vendAdvVal = floatval($vsAdvanceAmount);
                                $vendRemVal = max(0.00, $vendCostVal - $vendAdvVal);
                                $grossMarginVal = $custChargeVal - $vendCostVal;
                            @endphp
                            <div class="alert alert-info py-2 px-3 mb-2 fs-11">
                                <div class="row g-2 text-center">
                                    <div class="col-sm-3 border-end">
                                        <div class="text-500 fw-bold">Customer Charge</div>
                                        <div class="fw-bold fs-12 text-dark font-monospace">Rs. {{ number_format($custChargeVal, 2) }}</div>
                                        <div class="text-success fs-10">Adv: Rs. {{ number_format($custAdvVal, 2) }}</div>
                                    </div>
                                    <div class="col-sm-3 border-end">
                                        <div class="text-500 fw-bold text-danger">Cust. Remaining (Invoiced)</div>
                                        <div class="fw-bold fs-12 text-danger font-monospace">Rs. {{ number_format($custRemVal, 2) }}</div>
                                        <div class="text-muted fs-10">Charges on bill</div>
                                    </div>
                                    <div class="col-sm-3 border-end">
                                        <div class="text-500 fw-bold">Vendor Cost (Payable)</div>
                                        <div class="fw-bold fs-12 text-primary font-monospace">Rs. {{ number_format($vendCostVal, 2) }}</div>
                                        <div class="text-muted fs-10">Owed: Rs. {{ number_format($vendRemVal, 2) }}</div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="text-500 fw-bold">Marquee Margin</div>
                                        <div class="fw-bold fs-12 {{ $grossMarginVal >= 0 ? 'text-success' : 'text-danger' }} font-monospace">
                                            Rs. {{ number_format($grossMarginVal, 2) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-bold">Notes / Special Instructions</label>
                                <textarea wire:model="vsNotes" class="form-control form-control-sm" rows="2" placeholder="Arrangement notes, timings, specific requirements..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button wire:click="$set('showVendorSaleModal', false)" type="button" class="btn btn-secondary btn-sm px-3">Cancel</button>
                            <button type="submit" class="btn btn-success btn-sm px-4">
                                <i class="fas fa-check-circle me-1"></i> Save Assignment & Post Ledger
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Record Customer Advance / Installment Modal -->
    @if($showCustomerPaymentModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-success text-white py-2">
                        <h6 class="modal-title fw-bold fs-13"><i class="fas fa-hand-holding-usd me-2"></i>Record Customer Advance / Installment</h6>
                        <button wire:click="$set('showCustomerPaymentModal', false)" type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="recordCustomerAdvancePayment">
                        <div class="modal-body p-3 fs-12">
                            <div class="alert alert-secondary py-2 px-3 mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-700">Service Provider:</span>
                                    <strong class="text-dark">{{ $cpVendorName }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-700">Service:</span>
                                    <span class="badge badge-subtle-info">{{ $cpServiceName }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-700">Total Customer Charge:</span>
                                    <span class="font-monospace fw-bold text-dark">Rs. {{ number_format($cpCustomerCharge, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-700 text-success">Total Advances Paid So Far:</span>
                                    <span class="font-monospace text-success fw-bold">Rs. {{ number_format($cpCustomerPaid, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between border-top pt-1 mt-1">
                                    <span class="text-700 fw-bold">Remaining Customer Balance:</span>
                                    <strong class="text-danger font-monospace fs-12">Rs. {{ number_format($cpCustomerRemaining, 2) }}</strong>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Advance Payment Amount (PKR) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" wire:model="cpPaymentAmount" class="form-control form-control-sm @error('cpPaymentAmount') is-invalid @enderror">
                                    @error('cpPaymentAmount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Payment Date <span class="text-danger">*</span></label>
                                    <input type="date" wire:model="cpPaymentDate" class="form-control form-control-sm @error('cpPaymentDate') is-invalid @enderror">
                                    @error('cpPaymentDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Payment Method <span class="text-danger">*</span></label>
                                    <select wire:model="cpPaymentMethod" class="form-select form-select-sm">
                                        <option value="Cash">Cash</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Cheque">Cheque</option>
                                        <option value="Online">Online / Card</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Receipt / Transaction Ref #</label>
                                    <input type="text" wire:model="cpReference" class="form-control form-control-sm" placeholder="e.g. REC-8912">
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-bold">Notes / Remarks</label>
                                <textarea wire:model="cpNotes" class="form-control form-control-sm" rows="2" placeholder="e.g. Additional stage decoration advance received..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button wire:click="$set('showCustomerPaymentModal', false)" type="button" class="btn btn-secondary btn-sm px-3">Cancel</button>
                            <button type="submit" class="btn btn-success btn-sm px-4">
                                <i class="fas fa-check-circle me-1"></i> Record Customer Advance
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- View Service Provider Modal -->
    @if($showVendorViewModal && $viewingVendorSale)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-dark text-white py-2">
                        <h6 class="modal-title fw-bold fs-13">
                            <i class="fas fa-info-circle text-info me-2"></i>Service Provider Details — {{ $viewingVendorSale->vendor->name ?? 'Vendor' }}
                        </h6>
                        <button wire:click="$set('showVendorViewModal', false)" type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3 fs-12">
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <div class="border rounded p-2 bg-light">
                                    <div class="text-muted fs-11">Vendor / Partner</div>
                                    <div class="fw-bold text-dark fs-13">{{ $viewingVendorSale->vendor->name ?? '—' }}</div>
                                    <div class="text-secondary fs-11">{{ $viewingVendorSale->vendor->vendor_type ?? 'Vendor' }} ({{ $viewingVendorSale->vendor->vendor_code ?? '' }}) | Phone: {{ $viewingVendorSale->vendor->phone ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-2 bg-light">
                                    <div class="text-muted fs-11">Service Provided</div>
                                    <div class="fw-bold text-dark fs-13">{{ $viewingVendorSale->service->service_name ?? 'Custom / Direct Service' }}</div>
                                    <div class="mt-1">
                                        @if($viewingVendorSale->include_in_invoice)
                                            <span class="badge badge-subtle-success fs-11"><i class="fas fa-file-invoice me-1"></i>Billed in Customer Invoice</span>
                                        @else
                                            <span class="badge badge-subtle-warning fs-11"><i class="fas fa-hand-holding-usd me-1"></i>Direct Payment by Customer</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customer vs Vendor Financial Comparison -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <div class="card border-success border h-100 p-2 bg-light">
                                    <div class="fw-bold text-success fs-11 text-uppercase mb-2"><i class="fas fa-user-check me-1"></i>Customer Account Breakdown</div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Customer Charge:</span>
                                        <span class="font-monospace fw-bold text-dark">Rs. {{ number_format($viewingVendorSale->sale_amount, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Total Customer Advances:</span>
                                        <span class="font-monospace text-success fw-bold">Rs. {{ number_format($viewingVendorSale->customer_paid, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between border-top pt-1">
                                        <span class="fw-bold text-dark">Remaining to Charge Customer:</span>
                                        <span class="font-monospace fw-bold {{ $viewingVendorSale->customer_remaining > 0 ? 'text-danger' : 'text-success' }}">
                                            Rs. {{ number_format($viewingVendorSale->customer_remaining, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-primary border h-100 p-2 bg-light">
                                    <div class="fw-bold text-primary fs-11 text-uppercase mb-2"><i class="fas fa-truck me-1"></i>Vendor Payable Breakdown</div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Vendor Cost (Payable):</span>
                                        <span class="font-monospace fw-bold text-primary">Rs. {{ number_format($viewingVendorSale->vendor_net_amount, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Total Disbursed to Vendor:</span>
                                        <span class="font-monospace text-success fw-bold">Rs. {{ number_format($viewingVendorSale->paid_amount, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between border-top pt-1">
                                        <span class="fw-bold text-dark">Remaining Owed to Vendor:</span>
                                        <span class="font-monospace fw-bold {{ $viewingVendorSale->vendor_remaining > 0 ? 'text-danger' : 'text-success' }}">
                                            Rs. {{ number_format($viewingVendorSale->vendor_remaining, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Advance Payments Ledger History -->
                        <h6 class="fw-bold fs-12 mb-2 text-success"><i class="fas fa-receipt me-1"></i>Customer Advance Payments History</h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered align-middle mb-0 fs-11">
                                <thead class="bg-200">
                                    <tr>
                                        <th>Date</th>
                                        <th>Receipt / Ref #</th>
                                        <th>Method</th>
                                        <th>Recorded By</th>
                                        <th>Notes</th>
                                        <th class="text-end">Amount Paid</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($viewingVendorSale->customerPayments as $cPay)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($cPay->payment_date)->format('M d, Y') }}</td>
                                            <td class="font-monospace">{{ $cPay->transaction_reference ?? '—' }}</td>
                                            <td><span class="badge badge-subtle-info">{{ $cPay->payment_method }}</span></td>
                                            <td>{{ $cPay->recorder->name ?? 'System' }}</td>
                                            <td class="text-muted">{{ $cPay->notes ?? '—' }}</td>
                                            <td class="text-end font-monospace text-success fw-bold">Rs. {{ number_format($cPay->amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-2">
                                                @if((float)$viewingVendorSale->customer_advance_amount > 0)
                                                    Initial customer advance: Rs. {{ number_format($viewingVendorSale->customer_advance_amount, 2) }}
                                                @else
                                                    No advance payments received from customer for this service yet.
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Vendor Ledger History for this Sale -->
                        <h6 class="fw-bold fs-12 mb-2 text-700"><i class="fas fa-book me-1 text-primary"></i>Vendor Transaction & Payout Ledger History</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0 fs-11">
                                <thead class="bg-200">
                                    <tr>
                                        <th>Date</th>
                                        <th>Ref / Voucher</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th class="text-end">Credit (Payable)</th>
                                        <th class="text-end">Debit (Paid)</th>
                                        <th class="text-end">Running Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($viewingVendorSale->ledgers as $vLedger)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($vLedger->transaction_date)->format('M d, Y') }}</td>
                                            <td class="font-monospace">{{ $vLedger->reference_number ?? '—' }}</td>
                                            <td>
                                                <span class="badge badge-subtle-secondary text-uppercase fs-10">
                                                    {{ str_replace('_', ' ', $vLedger->transaction_type) }}
                                                </span>
                                            </td>
                                            <td class="text-wrap" style="max-width: 250px;">{{ $vLedger->description }}</td>
                                            <td class="text-end font-monospace text-primary">
                                                {{ (float)$vLedger->sale_amount > 0 ? 'Rs. ' . number_format($vLedger->sale_amount, 2) : '—' }}
                                            </td>
                                            <td class="text-end font-monospace text-success fw-bold">
                                                {{ (float)$vLedger->payment_amount > 0 ? 'Rs. ' . number_format($vLedger->payment_amount, 2) : '—' }}
                                            </td>
                                            <td class="text-end font-monospace fw-bold text-dark">
                                                Rs. {{ number_format($vLedger->running_balance, 2) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-2">No individual ledger records found for this sale.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        @if((float)$viewingVendorSale->customer_remaining > 0.01 && $viewingVendorSale->status !== 'cancelled')
                            <button wire:click="openCustomerPaymentModal({{ $viewingVendorSale->id }})" type="button" class="btn btn-success btn-xs px-3">
                                <i class="fas fa-hand-holding-usd me-1"></i> Record Customer Advance
                            </button>
                        @endif
                        @if((float)$viewingVendorSale->remaining_amount > 0.01 && $viewingVendorSale->status !== 'cancelled')
                            <button wire:click="openVendorPaymentModal({{ $viewingVendorSale->id }})" type="button" class="btn btn-primary btn-xs px-3">
                                <i class="fas fa-money-bill-wave me-1"></i> Disburse Vendor Installment
                            </button>
                        @endif
                        <button wire:click="$set('showVendorViewModal', false)" type="button" class="btn btn-secondary btn-xs px-3">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Service Provider Modal -->
    @if($showVendorEditModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-warning text-dark py-2">
                        <h6 class="modal-title fw-bold fs-13"><i class="fas fa-edit me-2"></i>Edit Service Provider Assignment — {{ $veVendorName }}</h6>
                        <button wire:click="$set('showVendorEditModal', false)" type="button" class="btn-close" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="saveEditedVendorSale">
                        <div class="modal-body p-3 fs-12">
                            <div class="alert alert-secondary py-2 px-3 mb-2">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <span class="text-700">Vendor:</span> <strong>{{ $veVendorName }}</strong>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-700">Service:</span> <span class="badge badge-subtle-info">{{ $veServiceName }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-dark">Customer Charge (PKR) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" wire:model.live.debounce.400ms="veCustomerCharge" class="form-control form-control-sm @error('veCustomerCharge') is-invalid @enderror">
                                    @error('veCustomerCharge') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-primary">Vendor Cost / Payable (PKR) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" wire:model.live.debounce.400ms="veVendorCost" class="form-control form-control-sm @error('veVendorCost') is-invalid @enderror">
                                    @error('veVendorCost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Commission Rate (%)</label>
                                    <input type="number" step="0.01" wire:model.live.debounce.400ms="veCommissionRate" class="form-control form-control-sm">
                                </div>
                            </div>

                            <!-- Billing Checkbox: Invoiced vs Direct Customer Payment -->
                            <div class="form-check form-switch mb-2 p-2 ps-5 border rounded bg-light">
                                <input class="form-check-input" type="checkbox" id="veIncludeInInvoice" wire:model="veIncludeInInvoice">
                                <label class="form-check-label fw-bold text-dark fs-12 mb-0" for="veIncludeInInvoice">
                                    <span class="fas fa-file-invoice text-primary me-1"></span> Bill through Marquee Invoice (Customer pays Marquee)
                                </label>
                                <div class="text-muted fs-11 mt-1">
                                    Uncheck if customer will pay the service provider directly without Marquee invoice billing.
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-bold">Notes / Reason for Edit</label>
                                <textarea wire:model="veNotes" class="form-control form-control-sm" rows="2" placeholder="Adjustment details..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button wire:click="$set('showVendorEditModal', false)" type="button" class="btn btn-secondary btn-sm px-3">Cancel</button>
                            <button type="submit" class="btn btn-warning btn-sm px-4 fw-bold">
                                <i class="fas fa-save me-1"></i> Update & Sync Ledger
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Cancel / Delete Service Provider Modal -->
    @if($showVendorDeleteModal && $deletingVendorSale)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-danger text-white py-2">
                        <h6 class="modal-title fw-bold fs-13"><i class="fas fa-exclamation-triangle me-2"></i>Cancel / Remove Service Provider</h6>
                        <button wire:click="$set('showVendorDeleteModal', false)" type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="executeDeleteOrCancelVendorSale">
                        <div class="modal-body p-3 fs-12">
                            <div class="alert alert-warning py-2 px-3 mb-2">
                                <div><strong>Vendor:</strong> {{ $deletingVendorSale->vendor->name ?? 'Vendor' }}</div>
                                <div><strong>Service:</strong> {{ $deletingVendorSale->service->service_name ?? 'Custom Service' }}</div>
                                <div><strong>Customer Charge:</strong> Rs. {{ number_format($deletingVendorSale->sale_amount, 2) }}</div>
                                <div><strong>Vendor Payable:</strong> Rs. {{ number_format($deletingVendorSale->vendor_net_amount, 2) }}</div>
                                <div><strong>Total Paid to Vendor:</strong> Rs. {{ number_format($deletingVendorSale->paid_amount, 2) }}</div>
                            </div>

                            @if((float)$deletingVendorSale->paid_amount > 0)
                                <div class="text-danger fw-bold mb-2">
                                    <i class="fas fa-info-circle me-1"></i> Note: Payments totaling Rs. {{ number_format($deletingVendorSale->paid_amount, 2) }} have already been disbursed to this vendor. Removing this service will mark it as <strong>Cancelled</strong> and reverse any unpaid balance obligations from the vendor ledger.
                                </div>
                            @else
                                <div class="text-muted mb-2">
                                    No payouts have been disbursed. This assignment will be completely removed and associated ledger entries reversed.
                                </div>
                            @endif

                            <div class="mb-2">
                                <label class="form-label fw-bold">Reason for Cancellation / Removal</label>
                                <textarea wire:model="cancelReason" class="form-control form-control-sm" rows="2" placeholder="e.g. Customer cancelled stage decorator request..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button wire:click="$set('showVendorDeleteModal', false)" type="button" class="btn btn-secondary btn-sm px-3">Keep Service</button>
                            <button type="submit" class="btn btn-danger btn-sm px-4">
                                <i class="fas fa-trash-alt me-1"></i> Confirm Cancellation / Removal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Vendor Installment Payment Modal -->
    @if($showVendorPaymentModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white py-2">
                        <h6 class="modal-title fw-bold fs-13"><i class="fas fa-money-bill-wave me-2"></i>Record Vendor Payment Installment</h6>
                        <button wire:click="$set('showVendorPaymentModal', false)" type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="recordVendorInstallmentPayment">
                        <div class="modal-body p-3 fs-12">
                            <div class="alert alert-secondary py-2 px-3 mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-700">Vendor:</span>
                                    <strong class="text-dark">{{ $vpVendorName }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-700">Service:</span>
                                    <span class="badge badge-subtle-info">{{ $vpServiceName }}</span>
                                </div>
                                <div class="d-flex justify-content-between border-top pt-1 mt-1">
                                    <span class="text-700 fw-bold">Current Remaining Balance:</span>
                                    <strong class="text-danger font-monospace fs-12">Rs. {{ number_format($vpRemainingBalance, 2) }}</strong>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Payment Amount (PKR) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" wire:model="vpPaymentAmount" class="form-control form-control-sm @error('vpPaymentAmount') is-invalid @enderror">
                                    @error('vpPaymentAmount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Payment Date <span class="text-danger">*</span></label>
                                    <input type="date" wire:model="vpPaymentDate" class="form-control form-control-sm @error('vpPaymentDate') is-invalid @enderror">
                                    @error('vpPaymentDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Payment Method <span class="text-danger">*</span></label>
                                    <select wire:model="vpPaymentMethod" class="form-select form-select-sm">
                                        <option value="Cash">Cash</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Cheque">Cheque</option>
                                        <option value="Online">Online</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Paid From Account</label>
                                    <select wire:model="vpAccountId" class="form-select form-select-sm">
                                        <option value="">-- Default Cash/Bank Account --</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->account_code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-bold">Reference / Cheque / Transaction #</label>
                                <input type="text" wire:model="vpReference" class="form-control form-control-sm" placeholder="e.g. CHQ-98231, TRX-812739">
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-bold">Remarks / Notes</label>
                                <textarea wire:model="vpRemarks" class="form-control form-control-sm" rows="2" placeholder="e.g. Stage decoration final installment payment..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button wire:click="$set('showVendorPaymentModal', false)" type="button" class="btn btn-secondary btn-sm px-3">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm px-4">
                                <i class="fas fa-check-circle me-1"></i> Post Payout & Update Ledger
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Booking Cancellation Settlement Modal -->
    @if($showBookingCancelModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-danger text-white py-2">
                        <h6 class="modal-title fw-bold fs-13"><i class="fas fa-ban me-2"></i>Booking Cancellation & Settlement</h6>
                        <button wire:click="$set('showBookingCancelModal', false)" type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="executeBookingCancellation">
                        <div class="modal-body p-3 fs-12">
                            <div class="alert alert-warning py-2 px-3 mb-3">
                                <div><strong>Total Advance Liability Held:</strong> <span class="font-monospace fw-bold text-dark">Rs. {{ number_format($booking->advance_received, 2) }}</span></div>
                                <div class="fs-11 text-600 mt-1">Specify the refund amount to return to the customer and the cancellation fee/penalty to retain as earned cancellation income.</div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-sm-6">
                                    <label class="form-label fw-bold">Refund to Customer (Rs.) *</label>
                                    <input type="number" step="0.01" wire:model.live="bkCancelRefundAmount" class="form-control form-control-sm font-monospace text-success fw-bold">
                                    @error('bkCancelRefundAmount') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-bold">Cancellation Fee / Penalty (Rs.) *</label>
                                    <input type="number" step="0.01" wire:model.live="bkCancelFeeAmount" class="form-control form-control-sm font-monospace text-danger fw-bold">
                                    @error('bkCancelFeeAmount') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            @error('bkCancelSum') <div class="alert alert-danger py-1 px-2 fs-11">{{ $message }}</div> @enderror

                            @if((float)$bkCancelRefundAmount > 0)
                                <div class="row g-2 mb-2">
                                    <div class="col-sm-6">
                                        <label class="form-label fw-bold">Disbursement Method *</label>
                                        <select wire:model="bkCancelPaymentMethod" class="form-select form-select-sm">
                                            <option value="Cash">Cash</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Cheque">Cheque</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label fw-bold">Disbursing Account</label>
                                        <select wire:model="bkCancelAccountId" class="form-select form-select-sm">
                                            <option value="">Auto Resolve by Method</option>
                                            @foreach($cashBankAccounts as $cb)
                                                <option value="{{ $cb->account_id }}">{{ $cb->account->name ?? $cb->bank_name }} ({{ strtoupper($cb->type) }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endif

                            <div class="mb-2">
                                <label class="form-label fw-bold">Cancellation Reason *</label>
                                <textarea wire:model="bkCancelReason" class="form-control form-control-sm" rows="2" placeholder="e.g. Event cancelled due to date clash..."></textarea>
                                @error('bkCancelReason') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                            </div>
                            @error('cancellation') <div class="alert alert-danger py-1 px-2 fs-11">{{ $message }}</div> @enderror
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button wire:click="$set('showBookingCancelModal', false)" type="button" class="btn btn-secondary btn-sm px-3">Keep Booking</button>
                            <button type="submit" class="btn btn-danger btn-sm px-4">
                                <i class="fas fa-times-circle me-1"></i> Confirm Cancellation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Accountant Post Modal for Individual Payments -->
    @if($showAccountantPostModal && $bvPostingPaymentId)
        @php
            $bvPostPayment = \App\Models\BookingPayment::find($bvPostingPaymentId);
        @endphp
        @if($bvPostPayment)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.55); z-index:1060;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-success text-white py-2">
                        <h6 class="modal-title text-white fw-bold">
                            <span class="fas fa-check-double me-2"></span>Post Payment: {{ $bvPostPayment->payment_number ?: ('PAY-'.$bvPostPayment->id) }}
                        </h6>
                        <button wire:click="$set('showAccountantPostModal', false)" type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3 fs-11">
                        <div class="alert alert-warning py-1 px-2 fs-11 mb-3">
                            <span class="fas fa-exclamation-triangle me-1"></span>
                            Posting this payment will create a double-entry Journal Voucher and credit Customer Advance Liability.
                        </div>

                        <div class="bg-light p-2 rounded mb-3">
                            <div class="row g-2">
                                <div class="col-6">
                                    <span class="text-500">Amount to Post:</span>
                                    <div class="fw-bold text-success fs-13 font-monospace">Rs. {{ number_format($bvPostPayment->amount, 2) }}</div>
                                </div>
                                <div class="col-6">
                                    <span class="text-500">Method:</span>
                                    <div class="fw-bold">{{ $bvPostPayment->payment_method }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-800">Target Cash / Bank Account <span class="text-danger">*</span></label>
                            <select wire:model="bvTargetAccountId" class="form-select form-select-sm">
                                <option value="">-- Select Cash in Hand / Bank Account --</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->account_code }} - {{ $acc->name }} ({{ $acc->nature }})</option>
                                @endforeach
                            </select>
                            @error('bvTargetAccountId') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-800">Posting Date <span class="text-danger">*</span></label>
                            <input wire:model="bvPostingDate" type="date" class="form-control form-control-sm" />
                            @error('bvPostingDate') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-bold text-800">Accountant Verification Notes</label>
                            <textarea wire:model="bvAccountantNotes" class="form-control form-control-sm" rows="2" placeholder="e.g. Cash verified and deposited..."></textarea>
                            @error('bvAccountantNotes') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer py-2 bg-light">
                        <button wire:click="$set('showAccountantPostModal', false)" type="button" class="btn btn-secondary btn-sm">Cancel</button>
                        <button wire:click="confirmAccountantPostPayment" type="button" class="btn btn-success btn-sm">
                            <span class="fas fa-check-circle me-1"></span>Confirm & Post to Accounts
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('open-print-window', (data) => {
                window.open(data.url, '_blank');
            });
        });
    </script>
</div>
