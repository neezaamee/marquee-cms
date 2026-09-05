<div>
    <!-- Top Action Bar (Hidden in Print) -->
    <div class="card mb-3 no-print border-0 shadow-sm">
        <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('bookings.show', $booking->id) }}" class="btn btn-falcon-default btn-sm">
                    <span class="fas fa-arrow-left me-1"></span> Back to Booking #{{ $booking->booking_number }}
                </a>
                <span class="badge badge-subtle-{{ $isFinal ? 'success' : 'primary' }} fs-11">
                    <span class="fas fa-{{ $isFinal ? 'check-double' : 'file-contract' }} me-1"></span>
                    {{ $isFinal ? 'Final Event-Day Bill (V2)' : 'Interim Contract Invoice (V2)' }}
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-falcon-primary btn-sm fw-bold shadow-xs" data-bs-toggle="modal" data-bs-target="#printPageSizeModal">
                    <span class="fas fa-print me-1"></span> Print Invoice
                </button>
                <a href="{{ route('bookings.pdf', $booking->id) }}" class="btn btn-falcon-default btn-sm" target="_blank">
                    <span class="fas fa-file-pdf me-1 text-danger"></span> Download (.pdf)
                </a>
                @if($remainingBalance > 0)
                    <a href="{{ route('bookings.show', $booking->id) }}#payment-section" class="btn btn-falcon-success btn-sm">
                        <span class="fas fa-dollar-sign me-1"></span> Receive Payment
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Main Falcon eCommerce Invoice Card -->
    <div class="card mb-3 invoice-sheet border-0 shadow-sm" id="printable-invoice">
        <div class="card-body p-4 p-md-6">
            @php
                $marquee = $booking->effective_marquee ?? $booking->marquee ?? (auth()->user()->marquee ?? null);
                $branch = $booking->effective_branch ?? $booking->branch ?? ($booking->hall?->branch ?? null);
            @endphp

            <!-- Row 1: Brand Header & Invoice Title (Matching Falcon Reference Layout) -->
            <div class="row align-items-center text-center mb-3">
                <div class="col-6 col-sm-6 text-start text-sm-start mb-3 mb-sm-0">
                    @php
                        $branchLogo = $branch->logo ?? ($marquee->logo ?? null);
                        $logoUrl = null;
                        if ($branchLogo) {
                            $logoUrl = \Illuminate\Support\Str::startsWith($branchLogo, ['http://', 'https://'])
                                ? $branchLogo
                                : (\Illuminate\Support\Str::startsWith($branchLogo, 'storage/') ? asset($branchLogo) : asset('storage/' . $branchLogo));
                        }
                    @endphp
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $branch->name ?? $marquee->name }}" style="max-height: 75px; max-width: 180px; object-fit: contain;" />
                    @elseif(file_exists(public_path('assets/img/logos/logo-invoice.png')))
                        <img src="{{ asset('assets/img/logos/logo-invoice.png') }}" alt="{{ $marquee->name ?? 'Marquee Logo' }}" width="130" />
                    @else
                        <h3 class="text-primary fw-black mb-0 font-sans-serif text-uppercase">{{ $marquee->name ?? 'MARQUEE CMS' }}</h3>
                    @endif
                </div>
                <div class="col-6 col-sm-6 text-end text-sm-end mt-3 mt-sm-0">
                    <h2 class="mb-2 text-primary fw-bold text-uppercase">Sale Tax Invoice</h2>
                    <h5 class="fw-bold text-dark mb-1">{{ $marquee->name ?? 'Royal Event Marquee' }}</h5>
                    @if($branch)
                        <div class="text-800 fw-bold fs-11 text-uppercase text-secondary mb-1">
                            <span class="fas fa-building me-1 text-primary"></span>{{ $branch->name }}
                            @if($branch->is_head_office)
                                <span class="badge bg-primary-subtle text-primary ms-1 fs-12">Head Office</span>
                            @endif
                        </div>
                        <p class="fs-11 mb-0 text-600">
                            <span class="fas fa-map-marker-alt me-1 text-400"></span>
                            {{ $branch->address ? $branch->address . ', ' : '' }}{{ $branch->city ?? ($marquee->city ?? '') }}{{ $branch->province ? ', ' . $branch->province : '' }}
                            @if($branch->phone || ($marquee->phone ?? null))
                                <br /><span class="fas fa-phone me-1 text-400"></span>Phone: {{ $branch->phone ?: $marquee->phone }}
                            @endif
                            @if($marquee->ntn)
                                <span class="fas fa-certificate me-1 text-400"></span>NTN: {{ $marquee->ntn }}
                            @endif
                            {{--@if($branch->branch_manager)
                                <span class="ms-2">| <strong>Manager:</strong> {{ $branch->branch_manager }}</span>
                            @endif--}}
                        </p>
                    @else
                        <p class="fs-11 mb-0 text-600">
                            <span class="fas fa-map-marker-alt me-1 text-400"></span>
                            {{ $marquee->address ?? 'Main Boulevard, Gulberg' }}, {{ $marquee->city ?? 'Lahore' }}
                            @if($marquee->phone ?? null)
                                <br /><span class="fas fa-phone me-1 text-400"></span>Phone: {{ $marquee->phone }}
                            @endif
                        </p>
                    @endif
                </div>
                <div class="col-12">
                    <hr class="my-3 text-300" />
                </div>
            </div>

            <!-- Row 2: Customer Profile, Event Details & Invoice References (Equal Width 50% / 50%) -->
            <div class="row align-items-start g-3 fs-11">
                <!-- Left Side: Customer Info + Each Detail on a Separate Line -->
                <div class="col-6 col-md-6 col-lg-6 mt-3 mt-md-0">
                    <!-- Customer Information -->
                    <h6 class="text-500 text-uppercase fw-bold fs-11 text-secondary mb-1">
                        <span class="fas fa-user me-1 text-primary"></span>Invoice To
                    </h6>
                    <h5 class="text-dark mb-1 fw-bold">{{ $booking->customer->full_name ?? 'Valued Customer' }}</h5>
                    <div class="text-700 fs-11 mb-2">
                        <div class="d-flex flex-column gap-1">
                            <div><strong>Phone:</strong> <span class="fw-bold text-dark">{{ $booking->customer?->phone_number ?? '—' }}</span>@if($booking->customer?->alternate_phone) <span class="text-muted ms-1">|</span> <span class="fab fa-whatsapp ms-1 text-success"></span><strong>Alt:</strong> {{ $booking->customer->alternate_phone }}@endif</div>
                            <div><strong>CNIC / NTN:</strong> <span class="fw-bold text-dark">{{ $booking->customer?->cnic_national_id ?? $booking->customer?->ntn_number ?? '—' }}</span></div>
                            <div><strong>Event Date / Time:</strong> <span class="fw-bold text-dark">{{ $booking->booking_date->format('d-M-Y') }} {{ $booking->slot->slot_name ?? 'Shift' }} ({{ $booking->start_time->format('h:i A') }} - {{ $booking->end_time->format('h:i A') }})</span></div>
                            <div><strong>Hall:</strong> 
                                <span class="fw-bold text-dark">
                                    @if($booking->halls->isNotEmpty())
                                        {{ $booking->halls->pluck('hall_name')->implode(', ') }}
                                    @else
                                        {{ $booking->hall->hall_name ?? 'Event Hall' }}
                                    @endif
                                </span>
                            </div>
                            <div><strong>Guests:</strong> <span class="fw-bold text-dark">{{ number_format($billing->guest_count) }} pax</span></div>
                            <div><strong>Event Type:</strong> <span class="fw-bold text-dark">{{ $booking->eventType->event_type_name ?? 'Conference' }}</span></div>
                        </div>
                    </div>                    
                </div>                
                <!-- Right Side: Invoice Reference, Booking Reference, Date & Statuses (Equal Width 50%) -->
                <div class="col-6 col-md-6 col-lg-6 text-end text-sm-end mt-3 mt-md-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless fs-11 ms-md-auto mb-0" style="max-width: 320px;">
                            <tbody>
                                <tr>
                                    <th class="text-sm-end px-1 py-0.5 text-600">Invoice #:</th>
                                    <td class="text-end px-1 py-0.5 fw-bold font-monospace text-dark">
                                        {{ $projectInvoiceNumber }}
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-sm-end px-1 py-0.5 text-600">FBR Invoice #:</th>
                                    <td class="text-end px-1 py-0.5 fw-bold font-monospace text-primary">
                                        {{ $fbrInvoiceNumber }}
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-sm-end px-1 py-0.5 text-600">Booking Reference:</th>
                                    <td class="text-end px-1 py-0.5 fw-bold font-monospace text-dark">
                                        {{ $booking->booking_number }}
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-sm-end px-1 py-0.5 text-600">Invoice Date:</th>
                                    <td class="text-end px-1 py-0.5 fw-semi-bold text-dark">
                                        {{ $isFinal && $billing->created_at ? $billing->created_at->format('d-M-Y / h:i:s a') : now()->format('d-M-Y / h:i:s a') }}
                                    </td>
                                </tr>
                                {{--
                                <tr>
                                    <th class="text-sm-end px-1 py-0.5 text-600">Billing Type:</th>
                                    <td class="text-end px-1 py-0.5">
                                        <span class="badge badge-subtle-{{ $isFinal ? 'success' : 'primary' }} rounded-pill fs-11">
                                            {{ $isFinal ? 'FINAL BILL (V2)' : 'ORIGINAL BOOKING' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-sm-end px-1 py-0.5 text-600">Payment Status:</th>
                                    <td class="text-end px-1 py-0.5">
                                        <span class="badge badge-subtle-{{ $remainingBalance <= 0 ? 'success' : ($totalPaid > 0 ? 'warning' : 'danger') }} rounded-pill fs-11">
                                            {{ $remainingBalance <= 0 ? 'PAID IN FULL' : ($totalPaid > 0 ? 'PARTIALLY PAID' : 'UNPAID') }}
                                        </span>
                                    </td>
                                </tr>
                                <tr class="alert alert-{{ $remainingBalance > 0 ? 'danger' : 'success' }} fw-bold">
                                    <th class="text-sm-end px-2 py-1 text-{{ $remainingBalance > 0 ? 'danger' : 'success' }}">Amount Due:</th>
                                    <td class="text-end px-2 py-1 font-monospace fs-12 text-{{ $remainingBalance > 0 ? 'danger' : 'success' }}">
                                        Rs. {{ number_format($remainingBalance, 2) }}
                                    </td>
                                </tr> --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Row 3: Itemized Billing Table (Falcon Styled Table) -->
            <div class="table-responsive scrollbar my-4 fs-11">
                <table class="table table-striped table-sm border-bottom mb-0">
                    <thead data-bs-theme="light">
                        <tr class="bg-primary text-white">
                            <th class="text-white border-0 px-3 py-2">Service / Item Description</th>
                            {{--<th class="text-white border-0 text-center py-2">Category</th>--}}
                            <th class="text-white border-0 text-center py-2">Qty</th>
                            <th class="text-white border-0 text-end py-2">Unit Rate (PKR)</th>
                            <th class="text-white border-0 text-end px-3 py-2">Total Amount (PKR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- 1. Catering Plan / Per-plate Food -->
                        @if(!$booking->no_food)
                            <tr>
                                <td class="px-3 align-middle">
                                    <h6 class="mb-0 text-dark fw-bold fs-11">
                                        {{ $booking->package->package_name ?? 'Custom Menu & Catering Package' }}
                                    </h6>
                                    <p class="mb-0 text-muted fs-10">
                                        Event catering package calculation {{-- ({{ number_format($billing->guest_count) }} actual guests @ Rs. {{ number_format($billing->per_plate_price, 2) }} per plate)--}}
                                    </p>
                                </td>
                                {{-- <td class="align-middle text-center">
                                    <span class="badge bg-secondary-subtle text-secondary">Catering</span>
                                </td> --}}
                                <td class="align-middle text-center font-monospace fw-bold">
                                    {{ number_format($billing->guest_count) }} pax
                                </td>
                                <td class="align-middle text-end font-monospace">
                                    {{ number_format($billing->per_plate_price, 2) }}
                                </td>
                                <td class="align-middle text-end px-3 font-monospace fw-bold text-dark">
                                    {{ number_format($billing->package_amount, 2) }}
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td class="px-3 align-middle">
                                    <h6 class="mb-0 text-dark fw-bold fs-11">Sitting Plan & Venue Hospitality (No Food)</h6>
                                    <p class="mb-0 text-muted fs-10">Client arranged self-catering; venue seating and hospitality provisions only.</p>
                                </td>
                                <td class="align-middle text-center"><span class="badge bg-light text-secondary border">Sitting Only</span></td>
                                <td class="align-middle text-center font-monospace">{{ number_format($billing->guest_count) }} pax</td>
                                <td class="align-middle text-end font-monospace">—</td>
                                <td class="align-middle text-end px-3 font-monospace fw-bold">Rs. 0.00</td>
                            </tr>
                        @endif

                        <!-- 2. Venue Hall Rent -->
                        <tr>
                            <td class="px-3 align-middle">
                                <h6 class="mb-0 text-dark fw-bold fs-11">
                                    Venue Hall Charges & Facility Rent
                                </h6>
                                <p class="mb-0 text-muted fs-10">
                                    Hall: {{ $booking->halls->isNotEmpty() ? $booking->halls->pluck('hall_name')->implode(', ') : ($booking->hall->hall_name ?? 'Event Hall') }}
                                    ({{ $booking->slot->slot_name ?? 'Reserved Slot' }})
                                </p>
                            </td>
                            {{--<td class="align-middle text-center">
                                <span class="badge bg-info-subtle text-info">Hall Rent</span>
                            </td>--}}
                            <td class="align-middle text-center font-monospace">
                                1 Shift
                            </td>
                            <td class="align-middle text-end font-monospace">
                                {{ number_format($billing->hall_charges, 2) }}
                            </td>
                            <td class="align-middle text-end px-3 font-monospace fw-bold text-dark">
                                {{ number_format($billing->hall_charges, 2) }}
                            </td>
                        </tr>

                        <!-- 3. Extra Addons & Services -->
                        @forelse($addonsList as $addon)
                            <tr>
                                <td class="px-3 align-middle">
                                    <h6 class="mb-0 text-dark fw-bold fs-11">{{ $addon->service_name }}</h6>
                                    <p class="mb-0 text-muted fs-10">Extra requested facility/decor service</p>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="badge bg-light text-dark border">Add-on</span>
                                </td>
                                <td class="align-middle text-center font-monospace">
                                    {{ $addon->quantity }}x
                                </td>
                                <td class="align-middle text-end font-monospace">
                                    {{ number_format($addon->unit_price, 2) }}
                                </td>
                                <td class="align-middle text-end px-3 font-monospace fw-bold text-dark">
                                    {{ number_format($addon->total_price, 2) }}
                                </td>
                            </tr>
                        @empty
                        @endforelse

                        <!-- 4. Direct / Invoiced Vendor Services -->
                        @forelse($vendorSalesList as $vs)
                            <tr>
                                <td class="px-3 align-middle">
                                    <h6 class="mb-0 text-dark fw-bold fs-11">
                                        {{ $vs->service->service_name ?? 'Vendor Service' }}
                                    </h6>
                                    <p class="mb-0 text-muted fs-10">
                                        Provider: {{ $vs->vendor->name ?? 'External Vendor' }} | Invoiced through Marquee
                                    </p>
                                </td>
                                {{--<td class="align-middle text-center">
                                    <span class="badge bg-warning-subtle text-warning">Vendor Service</span>
                                </td>--}}
                                <td class="align-middle text-center font-monospace">1 Job</td>
                                <td class="align-middle text-end font-monospace">
                                    {{ number_format($vs->sale_amount, 2) }}
                                </td>
                                <td class="align-middle text-end px-3 font-monospace fw-bold text-dark">
                                    {{ number_format($vs->sale_amount, 2) }}
                                </td>
                            </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Row 4: FBR POS Certification (Left) & Financial Summary Box (Right) -->
            <div class="row align-items-center g-3 my-2">
                <!-- Left Side: FBR Digital Invoicing System Certification (Logo + Dynamic QR Code) -->
                <div class="col-7 col-md-7 col-print-fbr">
                    <div class="border rounded p-2 p-md-3 bg-light-subtle shadow-2xs">
                        <div class="d-flex align-items-center gap-3">
                            <!-- FBR Official Logo (Uploaded Asset) -->
                            <div class="text-center flex-shrink-0">
                                <img src="{{ asset('assets/img/logos/fbr-digital-invoice.png') }}" 
                                     alt="FBR Digital Invoicing System" 
                                     style="max-height: 80px; max-width: 95px; object-fit: contain;" 
                                     class="rounded border bg-white p-1" />
                            </div>

                            <!-- Dynamic QR Code containing FBR Invoice # ONLY -->
                            <div class="text-center flex-shrink-0">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=95x95&margin=3&data={{ urlencode($fbrInvoiceNumber) }}" 
                                     alt="FBR POS QR Code" 
                                     width="78" 
                                     height="78" 
                                     class="border rounded bg-white p-1" />
                                {{--<div class="fs-10 text-muted font-monospace mt-1">Scan to Verify</div>--}}
                            </div>

                            <!-- FBR Digital Invoicing Details -->
                            <div class="flex-grow-1 fs-11">
                                <div class="d-flex align-items-center mb-1 flex-wrap gap-1">
                                    <span class="badge bg-primary-subtle text-primary fw-bold text-uppercase fs-11">
                                        <span class="fas fa-shield-alt me-1"></span>FBR POS Registered
                                    </span>
                                    @if($isFinal && ($billing->fbr_sync_status ?? null) === 'synced')
                                        <span class="badge bg-success-subtle text-success fs-11">Verified Synced</span>
                                    @endif
                                </div>
                                <div class="text-dark fw-bold mb-0 fs-11">FBR Digital Invoicing System</div>
                                {{--<div class="text-600 font-monospace fs-11">
                                    FBR Invoice #: <strong class="text-primary">{{ $fbrInvoiceNumber }}</strong>
                                </div>
                                @if(!empty($billing->usin))
                                    <div class="text-muted fs-10 font-monospace">USIN: {{ $billing->usin }}</div>
                                @endif
                                <div class="text-500 fs-10 mt-1">
                                    Official sales tax invoice verified under FBR POS integration. QR contains FBR Invoice number for real-time verification.
                                </div>--}}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Financial Summary Box (Falcon Style Right-Aligned) -->
                <div class="col-5 col-md-5 col-print-summary">
                    <table class="table table-sm table-borderless fs-11 text-end mb-0">
                        <tr>
                            <th class="text-700 px-0">Gross Subtotal:</th>
                            <td class="fw-semi-bold font-monospace px-0 text-dark">
                                {{ number_format($billing->subtotal + $billing->discount_amount, 2) }}
                            </td>
                        </tr>
                        @if($billing->discount_amount > 0)
                            <tr class="text-danger">
                                <th class="px-0">Promotional Discount:</th>
                                <td class="fw-bold font-monospace px-0">
                                    {{ number_format($billing->discount_amount, 2) }}
                                </td>
                            </tr>
                        @endif
                        <tr class="border-top">
                            <th class="text-700 px-0">Net Subtotal:</th>
                            <td class="fw-semi-bold font-monospace px-0 text-dark">
                                {{ number_format($billing->subtotal, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <th class="text-700 px-0">Applicable Tax ({{ $taxRate }}%):</th>
                            <td class="fw-semi-bold font-monospace px-0 text-dark">
                                {{ number_format($billing->tax_amount, 2) }}
                            </td>
                        </tr>
                        @if($booking->security_deposit > 0)
                            <tr class="text-info">
                                <th class="px-0">Refundable Security Deposit:</th>
                                <td class="fw-bold font-monospace px-0">
                                    {{ number_format($booking->security_deposit, 2) }}
                                </td>
                            </tr>
                        @endif
                        <tr class="border-top border-2">
                            <th class="text-primary fs-12 fw-bold px-0 py-1">Grand Total Invoiced:</th>
                            <td class="fw-bold fs-12 text-primary font-monospace px-0 py-1">
                                {{ number_format($grandTotal, 2) }}
                            </td>
                        </tr>
                        <tr class="text-success">
                            <th class="px-0 py-1">
                                Total Payments Received:
                            </th>
                            <td class="fw-bold font-monospace px-0 py-1">
                                Rs. {{ number_format($totalPaid, 2) }}
                            </td>
                        </tr>
                        <tr class="border-top border-top-2 alert alert-{{ $remainingBalance > 0 ? 'danger' : 'success' }}">
                            <th class="text-uppercase fw-bold fs-12 py-2 px-2 text-{{ $remainingBalance > 0 ? 'danger' : 'success' }}">
                                Net Outstanding Balance Due:
                            </th>
                            <td class="fw-bold fs-13 font-monospace py-2 px-2 text-{{ $remainingBalance > 0 ? 'danger' : 'success' }}">
                                Rs. {{ number_format($remainingBalance, 2) }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Row 5: Payment Audit History (If Any Payments Have Been Made) -->
            @if($booking->payments->isNotEmpty())
                <div class="mt-4 pt-2 border-top">
                    <h6 class="text-700 fw-bold fs-11 text-uppercase mb-2">
                        <span class="fas fa-receipt me-1 text-success"></span>Recorded Payment Transactions Breakdown
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered fs-10 mb-0 font-monospace">
                            <thead class="bg-light text-700">
                                <tr>
                                    <th>Receipt Ref #</th>
                                    <th>Payment Date</th>
                                    <th>Method</th>
                                    <th>Transaction / Cheque Ref</th>
                                    <th class="text-end">Amount Paid</th>
                                    {{--<th class="text-center">Accounting Status</th>--}}
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($booking->payments as $p)
                                    <tr>
                                        <td>{{ $p->payment_number ?: ('REC-' . str_pad($p->id, 5, '0', STR_PAD_LEFT)) }}</td>
                                        <td>{{ $p->payment_date ? $p->payment_date->format('d-M-Y') : '—' }}</td>
                                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $p->payment_method }}</span></td>
                                        <td>{{ $p->transaction_reference ?: ($p->cheque_number ?: 'Counter Cash') }}</td>
                                        <td class="text-end fw-bold text-success">Rs. {{ number_format($p->amount, 2) }}</td>
                                        {{--<td class="text-center">
                                            @if($p->status === 'posted')
                                                <span class="badge bg-success-subtle text-success">Posted in Accounts</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning">{{ ucfirst($p->status) }}</span>
                                            @endif
                                        </td>--}}
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Row 7: Bank Details & Special Notes -->
            <div class="row g-3 mt-3 pt-2 border-top fs-11">
                <!-- Left: Bank Accounts for Customer Direct Transfer -->
                <div class="col-6 col-sm-6">
                    @if($bankAccounts->isNotEmpty())
                        <h6 class="fw-bold text-secondary mb-1 fs-11">
                            <span class="fas fa-university me-1 text-primary"></span>Bank Account Details for Direct Transfer:
                        </h6>
                        <div class="row g-2">
                            @foreach($bankAccounts->take(1) as $ba)
                                <div class="col-12 col-sm-12">
                                    <div class="p-2 border rounded bg-light font-monospace fs-10">
                                        <div class="fw-bold text-dark">{{ $ba->bank_name }}</div>
                                        {{--<div>A/C: {{ $ba->account_number }}</div>--}}
                                        @if($ba->iban)
                                             <div>IBAN: {{ $ba->iban }}</div>
                                        @endif
                                        {{--@if($ba->branch_name)
                                             <div class="text-muted">{{ $ba->branch_name }}</div>
                                        @endif--}}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                {{--@if(!empty($billing->notes) || !empty($booking->special_instructions))
                        <div class="mt-2 text-600">
                            <strong>Special Remarks / Instructions:</strong>
                            <p class="mb-0 text-muted fs-10">{{ $billing->notes ?: $booking->special_instructions }}</p>
                        </div>
                    @endif--}}
                </div>
                <!-- Right: Signature Stamps -->
                <div class="col-6 col-sm-6 d-flex flex-column justify-content-between text-center pt-2">
                    <div class="row g-2 justify-content-between align-items-end pt-6">
                        <div class="col-6">
                            <div class="border-top pt-1 text-600 fs-10">Client / Host Signature</div>
                        </div>
                        <div class="col-6">
                            <div class="border-top pt-1 text-600 fs-10">Authorized Stamp & Sign</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Falcon Card Footer -->
        <div class="card-footer bg-body-tertiary py-2 px-4 no-print text-center fs-11 text-muted">
            <span class="fas fa-shield-alt me-1 text-primary"></span>
            Thank you for choosing <strong>{{ $marquee->name ?? 'our marquee' }}</strong>! This is an official computer-generated billing statement.
        </div>
    </div>

    <!-- Print Paper Size Selection Modal (Hidden in Print) -->
    <div class="modal fade no-print" id="printPageSizeModal" tabindex="-1" aria-labelledby="printPageSizeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-light py-2 px-3 border-bottom">
                    <h5 class="modal-title fs-11 fw-bold text-800" id="printPageSizeModalLabel">
                        <span class="fas fa-print text-primary me-2"></span>Print Sale Tax Invoice - Select Paper Format
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3 p-md-4">
                    <div class="alert alert-info py-2 px-3 fs-11 mb-3 d-flex align-items-center">
                        <span class="fas fa-magic me-2 text-primary fa-lg"></span>
                        <div>
                            <strong>Auto-Fit Page Calibration:</strong> Typography, margins, and table spacing automatically adjust to fit on a single page cleanly across all printer paper types without manual scaling.
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- A4 Standard -->
                        <div class="col-sm-6 col-md-4">
                            <div class="card h-100 border border-2 border-primary paper-size-card p-3 text-center position-relative shadow-xs"
                                 onclick="selectPaperSize('a4', this)" role="button" style="cursor: pointer;">
                                <span class="badge bg-primary position-absolute top-0 end-0 m-2 fs-10">Recommended</span>
                                <div class="mb-2 text-primary">
                                    <span class="fas fa-file-invoice fa-3x"></span>
                                </div>
                                <h6 class="fw-bold mb-1 text-dark">A4 Standard</h6>
                                <div class="text-600 font-monospace fs-11">210 × 297 mm</div>
                                <div class="text-muted fs-11 mt-1">Standard international & Pakistan marquee office format.</div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-primary w-100 fs-11 py-1 fw-bold">
                                        <span class="fas fa-print me-1"></span> Print A4
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Letter (US / Canada) -->
                        <div class="col-sm-6 col-md-4">
                            <div class="card h-100 border paper-size-card p-3 text-center position-relative shadow-xs"
                                 onclick="selectPaperSize('letter', this)" role="button" style="cursor: pointer;">
                                <div class="mb-2 text-secondary">
                                    <span class="fas fa-file-alt fa-3x"></span>
                                </div>
                                <h6 class="fw-bold mb-1 text-dark">Letter</h6>
                                <div class="text-600 font-monospace fs-11">8.5 × 11.0 in (216 × 279 mm)</div>
                                <div class="text-muted fs-11 mt-1">Standard North American stationery sheet.</div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary w-100 fs-11 py-1">
                                        <span class="fas fa-print me-1"></span> Print Letter
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Legal (Extended Sheet) -->
                        <div class="col-sm-6 col-md-4">
                            <div class="card h-100 border paper-size-card p-3 text-center position-relative shadow-xs"
                                 onclick="selectPaperSize('legal', this)" role="button" style="cursor: pointer;">
                                <div class="mb-2 text-success">
                                    <span class="fas fa-scroll fa-3x"></span>
                                </div>
                                <h6 class="fw-bold mb-1 text-dark">Legal</h6>
                                <div class="text-600 font-monospace fs-11">8.5 × 14.0 in (216 × 356 mm)</div>
                                <div class="text-muted fs-11 mt-1">Extended length for long itemized bookings.</div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-success w-100 fs-11 py-1">
                                        <span class="fas fa-print me-1"></span> Print Legal
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Executive -->
                        <div class="col-sm-6 col-md-4">
                            <div class="card h-100 border paper-size-card p-3 text-center position-relative shadow-xs"
                                 onclick="selectPaperSize('executive', this)" role="button" style="cursor: pointer;">
                                <div class="mb-2 text-warning">
                                    <span class="fas fa-file-contract fa-3x"></span>
                                </div>
                                <h6 class="fw-bold mb-1 text-dark">Executive</h6>
                                <div class="text-600 font-monospace fs-11">7.25 × 10.5 in (184 × 267 mm)</div>
                                <div class="text-muted fs-11 mt-1">Corporate executive letterhead format.</div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-warning w-100 fs-11 py-1">
                                        <span class="fas fa-print me-1"></span> Print Executive
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- A5 (Half Sheet Voucher) -->
                        <div class="col-sm-6 col-md-4">
                            <div class="card h-100 border paper-size-card p-3 text-center position-relative shadow-xs"
                                 onclick="selectPaperSize('a5', this)" role="button" style="cursor: pointer;">
                                <div class="mb-2 text-info">
                                    <span class="fas fa-receipt fa-3x"></span>
                                </div>
                                <h6 class="fw-bold mb-1 text-dark">A5 Voucher</h6>
                                <div class="text-600 font-monospace fs-11">148 × 210 mm (Half of A4)</div>
                                <div class="text-muted fs-11 mt-1">Compact receipt format for quick filing.</div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-info w-100 fs-11 py-1">
                                        <span class="fas fa-print me-1"></span> Print A5
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Custom / Preferences -->
                        <div class="col-sm-6 col-md-4">
                            <div class="card h-100 border bg-light p-3 d-flex flex-column justify-content-between">
                                <div>
                                    <h6 class="fw-bold mb-2 fs-11 text-700">
                                        <span class="fas fa-sliders-h me-1 text-primary"></span>Orientation & Auto-Fit
                                    </h6>
                                    <div class="mb-2">
                                        <label class="form-label fs-11 mb-1 fw-semi-bold">Orientation:</label>
                                        <select id="printOrientation" class="form-select form-select-sm fs-11">
                                            <option value="portrait" selected>Portrait (Standard)</option>
                                            <option value="landscape">Landscape (Wide)</option>
                                        </select>
                                    </div>
                                    <div class="form-check fs-11">
                                        <input class="form-check-input" type="checkbox" id="printAutoFit" checked>
                                        <label class="form-check-label text-600" for="printAutoFit">
                                            Auto-Fit within 1 page
                                        </label>
                                    </div>
                                </div>
                                <button type="button" onclick="executeSelectedPrint()" class="btn btn-primary btn-sm w-100 fs-11 py-1 fw-bold mt-2">
                                    <span class="fas fa-print me-1"></span> Print Current Choice
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-between">
                    <span class="fs-11 text-muted">
                        <span class="fas fa-info-circle me-1"></span>Tip: Click any paper size card to print immediately.
                    </span>
                    <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Dynamic Print Stylesheet for Auto-Fit Page Sizing -->
    <style id="dynamic-print-page-style">
        @media print {
            @page {
                size: a4 portrait;
                margin: 7mm 9mm;
            }
        }
    </style>

    <!-- Professional Print Custom Stylesheet -->
    <style>
        .paper-size-card {
            transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .paper-size-card:hover {
            transform: translateY(-2px);
            border-color: #0056b3 !important;
            box-shadow: 0 4px 12px rgba(0, 86, 179, 0.15) !important;
        }
        .paper-size-card.active {
            border-color: #0056b3 !important;
            background-color: #f0f7ff !important;
        }

        @media print {
            html, body {
                background: #fff !important;
                color: #000 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print,
            .navbar,
            .navbar-vertical,
            footer,
            .footer,
            .modal,
            .modal-backdrop {
                display: none !important;
            }

            .invoice-sheet {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            .card-body {
                padding: 0 !important;
                margin: 0 !important;
            }

            .container, .container-fluid {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }

            /* =========================================================================
               Print Grid Synchronization (Strict 50/50 Dual-Column Across All Papers)
               ========================================================================= */
            .row {
                display: flex !important;
                flex-wrap: wrap !important;
                margin-right: -4px !important;
                margin-left: -4px !important;
            }

            .row > * {
                box-sizing: border-box !important;
                padding-right: 4px !important;
                padding-left: 4px !important;
            }
            /* Row 2 (Customer & Event Details on left vs Invoice Details on right), Row 1 header & Row 7 bank/signatures: Strict 50/50 split */
            .col-6,
            .col-sm-6,
            .col-md-6,
            .col-lg-6 {
                flex: 0 0 50% !important;
                max-width: 50% !important;
                width: 50% !important;
            }

            /* Row 4: FBR Verification on left (56%) vs Financial Billing Summary on right (44%) */
            .col-print-fbr,
            .col-7,
            .col-md-7,
            .col-lg-7 {
                flex: 0 0 56% !important;
                max-width: 56% !important;
                width: 56% !important;
            }
            .col-print-summary,
            .col-5,
            .col-md-5,
            .col-lg-5 {
                flex: 0 0 44% !important;
                max-width: 44% !important;
                width: 44% !important;
            }

            /* Full width columns (e.g. Event Date in Row 2, dividers, notes) */
            .col-12,
            .col-sm-12,
            .col-md-12,
            .col-lg-12 {
                flex: 0 0 100% !important;
                max-width: 100% !important;
                width: 100% !important;
            }

            /* Alignment & Flexbox synchronization */
            .d-flex {
                display: flex !important;
            }

            .justify-content-end {
                justify-content: flex-end !important;
            }

            .justify-content-between {
                justify-content: space-between !important;
            }

            .align-items-start {
                align-items: flex-start !important;
            }

            .align-items-center {
                align-items: center !important;
            }

            .align-items-end {
                align-items: flex-end !important;
            }

            .text-sm-end,
            .text-md-end,
            .text-end {
                text-align: right !important;
            }

            .text-sm-start,
            .text-md-start,
            .text-start {
                text-align: left !important;
            }

            .text-center {
                text-align: center !important;
            }

            .ms-md-auto,
            .ms-sm-auto,
            .ms-auto {
                margin-left: auto !important;
            }

            .me-md-auto,
            .me-sm-auto,
            .me-auto {
                margin-right: auto !important;
            }

            /* Margin normalization for print */
            .mt-3, .mt-md-0, .mt-sm-0 {
                margin-top: 0 !important;
            }
            .mb-3, .mb-sm-0 {
                margin-bottom: 2px !important;
            }
            .my-4 {
                margin-top: 6px !important;
                margin-bottom: 6px !important;
            }

            /* Professional Typography Calibration (Crisp, High-Contrast & Legible) */
            body {
                font-size: 9pt !important;
                line-height: 1.25 !important;
            }

            h2 { font-size: 14pt !important; margin-bottom: 2px !important; }
            h3 { font-size: 12pt !important; margin-bottom: 2px !important; }
            h5 { font-size: 10.5pt !important; margin-bottom: 2px !important; }
            h6 { font-size: 9pt !important; margin-bottom: 2px !important; }

            hr {
                margin: 5px 0 !important;
                border-top: 1px solid #aaa !important;
            }

            table {
                width: 100% !important;
                border-collapse: collapse !important;
            }

            .table-sm th,
            .table-sm td {
                padding: 2.5px 5px !important;
                font-size: 8.5pt !important;
                vertical-align: middle !important;
            }

            .table-striped tbody tr:nth-of-type(odd) {
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .bg-primary {
                background-color: #0056b3 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .bg-primary th {
                color: #fff !important;
                font-size: 8.8pt !important;
                font-weight: 700 !important;
                padding: 3.5px 5px !important;
            }

            .alert {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                padding: 3px 6px !important;
                margin-bottom: 3px !important;
            }

            .badge {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                border: 1px solid #bbb !important;
                padding: 1.5px 4px !important;
                font-size: 7.5pt !important;
            }

            /* Prevent awkward multi-page breaks */
            .row, tr, td, th, .card, .signature-section {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            /* Specific Paper Auto-Fit Calibrations */
            /* Letter Paper Format */
            .print-letter {
                font-size: 8.6pt !important;
            }
            .print-letter .table-sm th,
            .print-letter .table-sm td {
                padding: 2px 4px !important;
                font-size: 8pt !important;
            }
            .print-letter h2 { font-size: 13pt !important; }

            /* Legal Paper Format */
            .print-legal {
                font-size: 9.5pt !important;
            }
            .print-legal .table-sm th,
            .print-legal .table-sm td {
                padding: 3px 6px !important;
                font-size: 9pt !important;
            }

            /* Executive Paper Format */
            .print-executive {
                font-size: 8.5pt !important;
            }
            .print-executive .table-sm th,
            .print-executive .table-sm td {
                padding: 2px 4px !important;
                font-size: 8pt !important;
            }

            /* A5 Compact Half-Sheet Format */
            .print-a5 {
                font-size: 7.2pt !important;
            }
            .print-a5 h2 { font-size: 10.5pt !important; }
            .print-a5 h5 { font-size: 8.5pt !important; }
            .print-a5 h6 { font-size: 7.5pt !important; }
            .print-a5 .table-sm th,
            .print-a5 .table-sm td {
                padding: 1.5px 3px !important;
                font-size: 6.8pt !important;
            }
            .print-a5 img {
                max-height: 40px !important;
            }

            /* Landscape Auto-Fit Format */
            .print-landscape {
                font-size: 8.5pt !important;
            }
        }
    </style>

    <!-- Paper Sizing Selection & Auto-Fit Script -->
    <script>
        let currentSelectedPaper = 'a4';

        function selectPaperSize(paperSize, cardEl) {
            currentSelectedPaper = paperSize;
            document.querySelectorAll('.paper-size-card').forEach(c => {
                c.classList.remove('active', 'border-primary');
                c.classList.add('border');
            });
            if (cardEl) {
                cardEl.classList.remove('border');
                cardEl.classList.add('active', 'border-primary');
            }
            executeSelectedPrint();
        }

        function executeSelectedPrint() {
            const orientation = document.getElementById('printOrientation') ? document.getElementById('printOrientation').value : 'portrait';
            const autoFit = document.getElementById('printAutoFit') ? document.getElementById('printAutoFit').checked : true;
            printWithPageSize(currentSelectedPaper, orientation, autoFit);
        }

        function printWithPageSize(paperSize, orientation, autoFit) {
            // 1. Hide Bootstrap modal cleanly
            const modalEl = document.getElementById('printPageSizeModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }

            // 2. Apply classes to printable container
            const invoiceEl = document.getElementById('printable-invoice');
            if (invoiceEl) {
                invoiceEl.classList.remove('print-a4', 'print-letter', 'print-legal', 'print-executive', 'print-a5', 'print-landscape');
                invoiceEl.classList.add(`print-${paperSize}`);
                if (orientation === 'landscape') {
                    invoiceEl.classList.add('print-landscape');
                }
            }

            // 3. Inject dynamic @page rule for selected size
            let styleTag = document.getElementById('dynamic-print-page-style');
            if (!styleTag) {
                styleTag = document.createElement('style');
                styleTag.id = 'dynamic-print-page-style';
                document.head.appendChild(styleTag);
            }

            let sizeCss = 'a4 portrait';
            let marginCss = '7mm 9mm';

            switch(paperSize) {
                case 'a4':
                    sizeCss = `a4 ${orientation}`;
                    marginCss = orientation === 'landscape' ? '7mm 10mm' : '7mm 9mm';
                    break;
                case 'letter':
                    sizeCss = `letter ${orientation}`;
                    marginCss = orientation === 'landscape' ? '6mm 9mm' : '5mm 8mm';
                    break;
                case 'legal':
                    sizeCss = `legal ${orientation}`;
                    marginCss = orientation === 'landscape' ? '7mm 10mm' : '7mm 9mm';
                    break;
                case 'executive':
                    sizeCss = `7.25in 10.5in ${orientation}`;
                    marginCss = '5mm 7mm';
                    break;
                case 'a5':
                    sizeCss = `a5 ${orientation}`;
                    marginCss = '4mm 5mm';
                    break;
                default:
                    sizeCss = `a4 ${orientation}`;
                    marginCss = '7mm 9mm';
            }

            styleTag.innerHTML = `
                @media print {
                    @page {
                        size: ${sizeCss};
                        margin: ${marginCss};
                    }
                }
            `;

            // 4. Slight delay to let modal backdrop fade out and styles take effect
            setTimeout(function() {
                window.print();
            }, 180);
        }
    </script>
</div>
