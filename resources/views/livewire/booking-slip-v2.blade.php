<div class="container py-3">
    <!-- Screen-Only Controls (Hidden on Print) -->
    <div class="d-print-none card mb-3 bg-light">
        <div class="card-body d-flex justify-content-between align-items-center py-2">
            <span class="fs-12 text-700 fw-semi-bold">
                <span class="fas fa-info-circle me-1"></span>Use the button below or print page (Ctrl+P) to generate a PDF or paper copy (Version 2).
            </span>
            <button onclick="window.print();" class="btn btn-success btn-sm px-4">
                <span class="fas fa-print me-1"></span> Print / Save PDF
            </button>
        </div>
    </div>

    <!-- Printable Area -->
    <div class="card bg-white shadow-none border p-4 p-md-5" id="printable-invoice">
        <div class="row align-items-start mb-4">
            <!-- Brand Info -->
            <div class="col-6 text-start">
                <h2 class="mb-1 text-primary fw-bold">{{ $booking->marquee->name ?? 'Royal Event Marquee' }}</h2>
                <p class="mb-0 text-600 fs-12">
                    <span class="fas fa-map-marker-alt me-1"></span>{{ $booking->marquee->address ?? '' }}, {{ $booking->marquee->city ?? '' }}
                    @if($booking->marquee->phone) | <span class="fas fa-phone me-1"></span>{{ $booking->marquee->phone }} @endif
                </p>
            </div>
            <!-- Invoice Title & QR Code Placeholder -->
            <div class="col-6 text-end">
                <h4 class="text-800 fw-bold mb-1 fs-9 fs-md-7">BOOKING CONFIRMATION</h4>
                <div class="fs-12 font-monospace text-secondary">VOUCHER REFERENCE: #{{ $booking->booking_number }}</div>
                <div class="mt-2 d-inline-block p-2 border bg-light text-center" style="width: 70px; height: 70px; border-radius: 4px;">
                    <!-- Visual QR placeholder -->
                    <span class="fas fa-qrcode fa-3x text-secondary"></span>
                </div>
            </div>
        </div>

        <hr />

        <!-- Meta Grid -->
        <div class="row g-3 my-2">
            <div class="col-6">
                <span class="text-500 fw-bold d-block text-uppercase fs-12 mb-1">Customer / Host Details</span>
                @if($booking->customer)
                    <table class="table table-sm table-borderless fs-12 mb-0">
                        <tr>
                            <td class="text-600 px-0 py-1" style="width: 120px;">Full Name:</td>
                            <td class="text-800 fw-bold px-0 py-1">{{ $booking->customer->full_name }}</td>
                        </tr>
                        <tr>
                            <td class="text-600 px-0 py-1">Contact Phone:</td>
                            <td class="text-800 fw-bold px-0 py-1">{{ $booking->customer->phone_number }}</td>
                        </tr>
                        <tr>
                            <td class="text-600 px-0 py-1">Email:</td>
                            <td class="text-800 px-0 py-1">{{ $booking->customer->email ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-600 px-0 py-1">CNIC / ID:</td>
                            <td class="text-800 px-0 py-1">{{ $booking->customer->cnic_national_id ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-600 px-0 py-1">NTN Number:</td>
                            <td class="text-800 px-0 py-1">{{ $booking->customer->ntn_number ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-600 px-0 py-1">Referred By:</td>
                            <td class="text-800 px-0 py-1">
                                {{ $booking->customer->referred_by_name ?? '—' }}
                                @if($booking->customer->referred_by_contact)
                                    ({{ $booking->customer->referred_by_contact }})
                                @endif
                            </td>
                        </tr>
                    </table>
                @else
                    <p class="text-muted fs-12 mb-0">No customer detail attached.</p>
                @endif
            </div>

            <div class="col-6">
                <span class="text-500 fw-bold d-block text-uppercase fs-12 mb-1">Event Venue & Timings</span>
                <table class="table table-sm table-borderless fs-12 mb-0">
                    <tr>
                        <td class="text-600 px-0 py-1" style="width: 120px;">Booking Hall(s):</td>
                        <td class="text-800 fw-bold px-0 py-1">
                            @if($booking->halls->isNotEmpty())
                                {{ $booking->halls->pluck('hall_name')->implode(', ') }}
                            @else
                                {{ $booking->hall->hall_name ?? '—' }}
                            @endif
                        </td>
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
        </div>

        <!-- Financial Itemized Summary Table -->
        <div class="table-responsive my-4">
            <table class="table table-sm table-striped border fs-12 align-middle mb-0">
                <thead class="bg-light text-900">
                    <tr>
                        <th class="ps-3" style="width: 40px;">#</th>
                        <th>Item Description</th>
                        <th class="text-center" style="width: 140px;">Rate</th>
                        <th class="text-center" style="width: 120px;">Qty/Guests</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!$booking->no_food)
                        <tr>
                            <td class="ps-3">1</td>
                            <td>
                                <div class="fw-bold">{{ $booking->package->package_name ?? 'Catering Package' }}</div>
                                <div class="text-muted fs-11">Per Plate Menu Package Booking</div>
                            </td>
                            <td class="text-center font-monospace">Rs. {{ number_format($booking->per_plate_price, 2) }}</td>
                            <td class="text-center">{{ $booking->guest_count }}</td>
                        </tr>
                    @else
                        <tr>
                            <td class="ps-3">1</td>
                            <td>
                                <div class="fw-bold">Catering Plan</div>
                                <div class="text-muted fs-11">Sitting Plan Only (No Food Catering)</div>
                            </td>
                            <td class="text-center font-monospace">—</td>
                            <td class="text-center">—</td>
                        </tr>
                    @endif
                    @if($booking->hall_charges > 0)
                        <tr>
                            <td class="ps-3">2</td>
                            <td>
                                <div class="fw-bold">Hall rent & Setup</div>
                                <div class="text-muted fs-11">Exclusive venue occupancy charge</div>
                            </td>
                            <td class="text-center font-monospace">Rs. {{ number_format($booking->hall_charges, 2) }}</td>
                            <td class="text-center">1</td>
                        </tr>
                    @endif
                    @if($booking->extra_charges > 0)
                        <tr>
                            <td class="ps-3">3</td>
                            <td>
                                <div class="fw-bold">Extra Amenities / Decor Addons</div>
                                <div class="text-muted fs-11">Custom decor setup or audiovisual additions</div>
                            </td>
                            <td class="text-center font-monospace">Rs. {{ number_format($booking->extra_charges, 2) }}</td>
                            <td class="text-center">1</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Event Menu Details & Instructions (Split Layout) -->
        <div class="row g-4 my-2">
            <!-- Left Side: Menu Items Selection (Responsive Print Font) -->
            <div class="col-6 border-end border-translucent">
                @if($booking->menuItems->isNotEmpty())
                    <div>
                        <span class="text-500 fw-bold d-block text-uppercase fs-12 mb-2">Event Menu Selection Details</span>
                        <ul class="ps-3 mb-0 responsive-print-font fs-12 text-800">
                            @foreach($booking->menuItems as $item)
                                <li class="mb-1">
                                    <span class="fw-bold">{{ $item->item_name }}</span>
                                    @if($item->urdu_name)
                                        <span class="text-muted fs-11 ms-1">({{ $item->urdu_name }})</span>
                                    @endif
                                    @if(!empty($item->pivot->managed_by_host))
                                        <span class="badge badge-subtle-warning fs-11 ms-1 d-print-none">Managed by Host</span>
                                        <span class="text-danger fw-bold fs-11 ms-1 d-none d-print-inline-block">(By Host)</span>
                                    @endif
                                    @if(!empty($item->pivot->custom_note))
                                        <span class="text-muted fs-11 ms-1 italic">— ({{ $item->pivot->custom_note }})</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <p class="text-muted fs-12">No customized menu items selected.</p>
                @endif
            </div>

            <!-- Right Side: Special Instructions & Addons -->
            <div class="col-6">
                @if($booking->extraServices->isNotEmpty())
                    <div class="mb-3">
                        <span class="text-500 fw-bold d-block text-uppercase fs-12 mb-2">Extra Add-ons / Services Details</span>
                        <ul class="ps-3 mb-0 fs-12 text-800">
                            @foreach($booking->extraServices as $srv)
                                <li class="mb-1">
                                    <span class="fw-bold">{{ $srv->service_name }}</span> 
                                    @if($srv->total_price > 0)
                                        <span class="text-muted">({{ $srv->quantity }}x @ Rs. {{ number_format($srv->unit_price) }})</span>
                                    @else
                                        <span class="text-muted">({{ $srv->quantity }}x)</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($booking->special_instructions)
                    <div>
                        <span class="text-500 fw-bold d-block text-uppercase fs-12 mb-2">Special Setup / Instructions</span>
                        <div class="p-2 border rounded bg-light fs-12 text-800 text-wrap" style="white-space: pre-wrap; word-break: break-word;">{{ $booking->special_instructions }}</div>
                    </div>
                @endif
            </div>
        </div>

        <hr class="mt-4" />

        <!-- Terms and Conditions Section -->
        <div class="row g-3 fs-13 mt-1">
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
            @page {
                size: A4 portrait;
                margin: 8mm 12mm 10mm 12mm !important;
            }
            html, body {
                width: 100% !important;
                height: auto !important;
                background: #fff !important;
                color: #000 !important;
                margin: 0 !important;
                padding: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .main, .content, .container, .container-fluid {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            #printable-invoice {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            /* Avoid page breaks inside logical units */
            .card, table, tr, td, th, ul, li, .row, p, ol {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            /* Hide top navigation header and side navigation during print */
            nav, .navbar, .navbar-vertical, .d-print-none, footer, .footer {
                display: none !important;
            }
            /* Spacing overrides to guarantee layout fits A4 single-page height */
            hr {
                margin-top: 0.5rem !important;
                margin-bottom: 0.5rem !important;
            }
            .my-2 {
                margin-top: 0.25rem !important;
                margin-bottom: 0.25rem !important;
            }
            .my-4 {
                margin-top: 0.5rem !important;
                margin-bottom: 0.5rem !important;
            }
            .mb-4 {
                margin-bottom: 0.5rem !important;
            }
            .mt-5 {
                margin-top: 1.5rem !important;
            }
            .pt-4 {
                padding-top: 1rem !important;
            }
            /* Precise scaling of print typography */
            .fs-12 {
                font-size: 10pt !important;
            }
            .fs-13 {
                font-size: 10.5pt !important;
            }
            .responsive-print-font {
                font-size: 9.5pt !important;
            }
            h2 {
                font-size: 16pt !important;
            }
            h4 {
                font-size: 13pt !important;
            }
        }
    </style>
</div>
