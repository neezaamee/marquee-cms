<div class="container py-3">
    <!-- Screen-Only Controls (Hidden on Print) -->
    <div class="d-print-none card mb-3 bg-light">
        <div class="card-body d-flex justify-content-between align-items-center py-2">
            <span class="fs-11 text-700 fw-semi-bold">
                <span class="fas fa-info-circle me-1"></span>Use the button below or print page (Ctrl+P) to generate a PDF or paper copy.
            </span>
            <button onclick="window.print();" class="btn btn-success btn-sm px-4">
                <span class="fas fa-print me-1"></span> Print / Save PDF
            </button>
        </div>
    </div>

    <!-- Printable Area -->
    <div class="card bg-white shadow-none border p-4 p-md-5" id="printable-invoice">
        <div class="row align-items-center mb-4">
            <!-- Brand Info -->
            <div class="col-sm-6 text-start">
                <h3 class="text-primary fw-black mb-1">MARQUEE CMS</h3>
                <h6 class="text-secondary fw-bold">{{ auth()->user()->marquee->name ?? 'Royal Event Marquee' }}</h6>
                <div class="fs-12 text-600">
                    {{ auth()->user()->marquee->address ?? 'Main Boulevard, Gulberg' }}, {{ auth()->user()->marquee->city ?? 'Lahore' }}
                </div>
            </div>
            <!-- Invoice Title & QR Code Placeholder -->
            <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                <h4 class="text-800 fw-bold mb-1">BOOKING CONFIRMATION</h4>
                <div class="fs-11 font-monospace text-secondary">VOUCHER REFERENCE: #{{ $booking->booking_number }}</div>
                <div class="mt-2 d-inline-block p-2 border bg-light text-center" style="width: 70px; height: 70px; border-radius: 4px;">
                    <!-- Visual QR placeholder -->
                    <span class="fas fa-qrcode fa-3x text-secondary"></span>
                </div>
            </div>
        </div>

        <hr />

        <!-- Meta Grid -->
        <div class="row g-3 my-2">
            <div class="col-sm-6">
                <span class="text-500 fw-bold d-block text-uppercase fs-12 mb-1">Event Venue & Timings</span>
                <table class="table table-sm table-borderless fs-11 mb-0">
                    <tr>
                        <td class="text-600 px-0 py-1" style="width: 110px;">Booking Hall:</td>
                        <td class="text-800 fw-bold px-0 py-1">{{ $booking->hall->hall_name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-600 px-0 py-1">Booking Date:</td>
                        <td class="text-800 fw-bold px-0 py-1">{{ $booking->booking_date->format('l, F d, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-600 px-0 py-1">Shift Slot:</td>
                        <td class="text-800 px-0 py-1">{{ $booking->slot->slot_name ?? 'Custom Schedule' }}</td>
                    </tr>
                    <tr>
                        <td class="text-600 px-0 py-1">Timings:</td>
                        <td class="text-danger-800 font-monospace fw-bold px-0 py-1">
                            {{ $booking->start_time->format('h:i A') }} - {{ $booking->end_time->format('h:i A') }}
                        </td>
                    </tr>
                </table>
            </div>

            <div class="col-sm-6">
                <span class="text-500 fw-bold d-block text-uppercase fs-12 mb-1">Customer / Host Details</span>
                @if($booking->customer)
                    <table class="table table-sm table-borderless fs-11 mb-0">
                        <tr>
                            <td class="text-600 px-0 py-1" style="width: 110px;">Full Name:</td>
                            <td class="text-800 fw-bold px-0 py-1">{{ $booking->customer->full_name }}</td>
                        </tr>
                        <tr>
                            <td class="text-600 px-0 py-1">Contact:</td>
                            <td class="text-800 fw-bold px-0 py-1">{{ $booking->customer->phone_number }}</td>
                        </tr>
                        <tr>
                            <td class="text-600 px-0 py-1">CNIC / ID:</td>
                            <td class="text-800 px-0 py-1">{{ $booking->customer->cnic_national_id ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-600 px-0 py-1">Address:</td>
                            <td class="text-800 px-0 py-1">{{ $booking->customer->address ?? '—' }}</td>
                        </tr>
                    </table>
                @else
                    <p class="text-muted fs-11 mb-0">No customer detail attached.</p>
                @endif
            </div>
        </div>

        <!-- Financial Itemized Summary Table -->
        <div class="table-responsive my-4">
            <table class="table table-sm table-striped border fs-11 align-middle mb-0">
                <thead class="bg-light text-900">
                    <tr>
                        <th class="ps-3" style="width: 40px;">#</th>
                        <th>Charge Item Description</th>
                        <th class="text-center" style="width: 100px;">Rate</th>
                        <th class="text-center" style="width: 80px;">Qty/Guests</th>
                        <th class="text-end pe-3" style="width: 140px;">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="ps-3">1</td>
                        <td>
                            <div class="fw-bold">{{ $booking->package->package_name ?? 'Catering Package' }}</div>
                            <div class="text-muted fs-12">Per Plate Menu Package Booking</div>
                        </td>
                        <td class="text-center font-monospace">Rs. {{ number_format($booking->per_plate_price, 2) }}</td>
                        <td class="text-center">{{ $booking->guest_count }}</td>
                        <td class="text-end pe-3 font-monospace">Rs. {{ number_format($booking->package_amount, 2) }}</td>
                    </tr>
                    @if($booking->hall_charges > 0)
                        <tr>
                            <td class="ps-3">2</td>
                            <td>
                                <div class="fw-bold">Hall rent & Setup</div>
                                <div class="text-muted fs-12">Exclusive venue occupancy charge</div>
                            </td>
                            <td class="text-center font-monospace">Rs. {{ number_format($booking->hall_charges, 2) }}</td>
                            <td class="text-center">1</td>
                            <td class="text-end pe-3 font-monospace">Rs. {{ number_format($booking->hall_charges, 2) }}</td>
                        </tr>
                    @endif
                    @if($booking->extra_charges > 0)
                        <tr>
                            <td class="ps-3">3</td>
                            <td>
                                <div class="fw-bold">Extra Amenities / Decor Addons</div>
                                <div class="text-muted fs-12">Custom decor setup or audiovisual additions</div>
                            </td>
                            <td class="text-center font-monospace">Rs. {{ number_format($booking->extra_charges, 2) }}</td>
                            <td class="text-center">1</td>
                            <td class="text-end pe-3 font-monospace">Rs. {{ number_format($booking->extra_charges, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Invoice Calculation Math Grid -->
        <div class="row justify-content-end mb-4">
            <div class="col-sm-5 text-end fs-11">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-600">Subtotal:</span>
                    <span class="font-monospace">Rs. {{ number_format($booking->subtotal, 2) }}</span>
                </div>
                @if($booking->discount_amount > 0)
                    <div class="d-flex justify-content-between mb-1 text-danger">
                        <span>Discount Allowed:</span>
                        <span class="font-monospace">- Rs. {{ number_format($booking->discount_amount, 2) }}</span>
                    </div>
                @endif
                <div class="d-flex justify-content-between mb-1 border-bottom pb-1 text-secondary">
                    <span>Tax amount:</span>
                    <span class="font-monospace">Rs. {{ number_format($booking->tax_amount, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2 border-bottom pb-1 text-info fw-semi-bold">
                    <span>Security Deposit (Refundable):</span>
                    <span class="font-monospace">Rs. {{ number_format($booking->security_deposit, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between fs-10 fw-black text-primary">
                    <span>Grand Total:</span>
                    <span class="font-monospace">Rs. {{ number_format($booking->grand_total, 2) }}</span>
                </div>
            </div>
        </div>

        <hr />

        <!-- Terms and Conditions Section -->
        <div class="row g-3 fs-12 mt-1">
            <div class="col-12">
                <h6 class="fw-bold text-800 mb-2">Terms & Conditions</h6>
                <ol class="ps-3 text-600 mb-0">
                    <li>The refundable security deposit remains strictly separate from event revenue and will be refunded within 3 working days post-event after evaluating any damage losses.</li>
                    <li>Cancellations are subject to structural marquee policies. Minimum headcounts must be adhered to once finalized.</li>
                    <li>Any extension of the time bounds stated above without written authorization may trigger extra hour charge policies.</li>
                </ol>
            </div>
        </div>

        <!-- Signature Layout -->
        <div class="row justify-content-between align-items-end mt-5 pt-4">
            <div class="col-sm-5 text-center">
                <hr class="mb-1" />
                <span class="fs-12 text-600">Customer Signature</span>
            </div>
            <div class="col-sm-5 text-center">
                <hr class="mb-1" />
                <span class="fs-12 text-600">Authorized Officer Stamp & Sign</span>
            </div>
        </div>
    </div>

    <!-- Print Custom stylesheet injection -->
    <style>
        @media print {
            body {
                background: #fff !important;
                color: #000 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .content, .main {
                padding: 0 !important;
                margin: 0 !important;
            }
            #printable-invoice {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            /* Hide top navigation header and side navigation during print */
            nav, .navbar, .navbar-vertical, .d-print-none, footer, .footer {
                display: none !important;
            }
        }
    </style>
</div>
