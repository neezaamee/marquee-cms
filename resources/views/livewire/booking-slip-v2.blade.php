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

    <!-- Original Hidden Printable Area (Used as template source) -->
    <div id="original-invoice-content" style="display: none;">
        <div class="row align-items-start pb-2 mb-2" id="brand-header" style="border-bottom: 2px solid #0056b3 !important;">
            <!-- Brand Info -->
            <div class="col-7 text-start">
                <div class="title-brand text-primary fw-bold text-uppercase fs-20" id="brand-name" style="font-size: 20px; color: #0056b3; font-weight: bold; text-transform: uppercase; margin: 0;">
                    {{ $booking->marquee->name ?? 'Royal Event Marquee' }}
                </div>
                <div class="text-600 fs-11" style="font-size: 11px; color: #666;">
                    <span class="fas fa-map-marker-alt me-1"></span>{{ $booking->marquee->address ?? '' }}, {{ $booking->marquee->city ?? '' }}
                    @if($booking->marquee->phone) | Ph: {{ $booking->marquee->phone }} @endif
                </div>
            </div>
            <!-- Title & References -->
            <div class="col-5 text-end">
                <div class="title-invoice text-success fw-bold text-uppercase fs-16" style="font-size: 16px; color: #28a745; font-weight: bold; text-align: right; margin: 0; text-transform: uppercase;">
                    {{ !empty($booking->finalBill) ? 'Final Slip' : 'Booking Slip' }}
                </div>
                <div class="ref-text font-monospace fs-11 text-secondary mt-1" style="font-size: 11px; color: #666; text-align: right; font-family: monospace; line-height: 1.35;">
                    <strong>Booking Reference:</strong> #{{ $booking->booking_number }}
                </div>
                {{--@if($booking->finalBill && $booking->finalBill->fbr_sync_status === 'synced')
                    <div class="mt-2 d-flex flex-column align-items-end">
                        <div class="p-1 border bg-white text-center mb-1" style="width: 80px; height: 80px; border-radius: 4px;">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=70x70&data={{ urlencode($booking->finalBill->qr_code) }}" alt="FBR Verification QR" width="72" height="72" />
                        </div>
                        <div class="fs-11 fw-bold text-success"><span class="fas fa-check-circle me-1"></span>FBR Tax Compliant</div>
                        <div class="fs-12 text-600 font-monospace">FBR INV: {{ $booking->finalBill->fbr_invoice_number }}</div>
                        <div class="fs-12 text-600 font-monospace">USIN: {{ $booking->finalBill->usin }}</div>
                    </div>
                @else
                    <div class="mt-2 d-inline-block p-2 border bg-light text-center" style="width: 70px; height: 70px; border-radius: 4px;">
                        <span class="fas fa-qrcode fa-3x text-secondary"></span>
                    </div>
                @endif --}}
            </div>
        </div>

        <!-- Meta Grid -->
        <div class="row g-3 my-2" id="meta-grid">
            <div class="col-6">
                <span class="text-500 fw-bold d-block text-uppercase fs-12 mb-1">Customer Details</span>
                @if($booking->customer)
                    <table class="table table-sm table-borderless fs-12 mb-0">
                        <tr>
                            <td class="text-600 px-0 py-1" style="width: 120px;">Full Name:</td>
                            <td class="text-800 fw-bold px-0 py-1">{{ $booking->customer->full_name }}</td>
                        </tr>
                        <tr>
                            <td class="text-600 px-0 py-1">Contact:</td>
                            <td class="text-800 fw-bold px-0 py-1">{{ $booking->customer->phone_number }}</td>
                        </tr>
                        <tr>
                            <td class="text-600 px-0 py-1">CNIC / NTN:</td>
                            <td class="text-800 fw-bold px-0 py-1">{{ $booking->customer->cnic_national_id ?? $booking->customer->ntn_number ?? 'N/A' }}</td>
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
                        <td class="text-600 px-0 py-1" style="width: 120px;">Event / Hall:</td>
                        <td class="text-800 fw-bold px-0 py-1">
                            {{ $booking->eventType->event_type_name ?? '—' }}
                            @if($booking->halls->isNotEmpty())
                                / {{ $booking->halls->pluck('hall_name')->implode(', ') }}
                            @else
                                / {{ $booking->hall->hall_name ?? '—' }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-600 px-0 py-1">Event Date:</td>
                        <td class="text-800 fw-bold px-0 py-1">{{ $booking->booking_date->format('l, F d, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-600 px-0 py-1">Event Time:</td>
                        <td class="text-800 fw-bold px-0 py-1">{{ $booking->slot->slot_name ?? 'Custom Schedule' }}<small class="text-danger-800 font-monospace fw-bold px-0 py-1">({{ $booking->start_time->format('h:i A') }} - {{ $booking->end_time->format('h:i A') }})<small> </td>
                    </tr>
                    <tr>
                        <td class="text-600 px-0 py-1">Guests Count:</td>
                        <td class="text-800 fw-bold px-0 py-1"> T: {{ $booking->tentative_guests ?? $booking->guest_count }}, C: {{ $booking->confirmed_guests ?? 'Pending' }}</td>
                    </tr>
                    {{--<tr>
                        <td class="text-600 px-0 py-1">Privacy:</td>
                        <td class="text-800 fw-bold px-0 py-1">
                            @if($booking->privacy_required)
                                Yes (Ladies: {{ $booking->privacy_ladies_percentage }}%, Gents: {{ $booking->privacy_gents_percentage }}%)
                            @else
                                No
                            @endif
                        </td>
                    </tr>--}}
                </table>
            </div>
        </div>

        <!-- Split Layout Body Components -->
        <span class="text-500 fw-bold d-block text-uppercase fs-12 mb-2" id="menu-details-title">Menu</span>
        <ol class="ps-4 mb-0 responsive-print-font fs-12 text-800" id="menu-items-list">
            @if($booking->menuItems->isNotEmpty())
                @foreach($booking->menuItems as $item)
                    <li class="mb-1 menu-item-li">
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
            @endif
        </ol>

        @if(!$booking->no_food)
            <div class="mt-2 pt-1 border-top" id="rate-block">
               {{--<span class="text-500 fw-bold text-uppercase fs-12 d-block">Rate</span>--}} 
                @php
                    $taxPercent = 13.00;
                    if ($booking->subtotal > 0 && $booking->tax_amount > 0) {
                        $taxPercent = round(($booking->tax_amount / $booking->subtotal) * 100, 2);
                    }
                    if (floor($taxPercent) == $taxPercent) {
                        $taxPercentStr = number_format($taxPercent, 0);
                    } else {
                        $taxPercentStr = number_format($taxPercent, 1);
                    }
                @endphp
                <span class="font-monospace fw-bold fs-18 text-primary">Rate: Rs. {{ number_format($booking->per_plate_price) }}/- + ({{ $taxPercentStr }}% Tax)</span>
            </div>
        @endif

        @if($booking->extraServices->isNotEmpty())
            <div class="mb-2" id="addons-block">
                <span class="text-500 fw-bold d-block text-uppercase fs-12 mb-1">Extra Add-ons / Services Details</span>
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
            <div id="instructions-block">
                <span class="text-500 fw-bold d-block text-uppercase fs-12 mb-1">Special Setup / Instructions</span>
                <div class="mb-2 fs-12 text-800">
                    <span class="text-600" data-test="Privacy / Partition:">Privacy:</span>
                    <span class="fw-bold">
                        @if($booking->privacy_required)
                            Yes (Ladies: {{ $booking->privacy_ladies_percentage }}%, Gents: {{ $booking->privacy_gents_percentage }}%)
                        @else
                            No
                        @endif
                    </span>
                </div>
                    @if($booking->special_instructions)
                <div class="p-2 border rounded bg-light fs-12 text-800 text-wrap" style="white-space: pre-wrap; word-break: break-word;">{{ $booking->special_instructions }}</div>
                @endif
            </div>
        

        <!-- Terms and Conditions Section -->
        <div class="row g-3 fs-13 mt-1" id="terms-and-conditions">
            <div class="col-12">
                <h6 class="fw-bold text-800 mb-1">Terms & Conditions</h6>
                <ol class="ps-3 text-600 mb-0 fs-11">
                    <li>The refundable security deposit remains strictly separate from event revenue and will be refunded within 3 working days post-event after evaluating any damage losses.</li>
                    <li>Cancellations are subject to structural marquee policies. Minimum headcounts must be adhered to once finalized.</li>
                    <li>Any extension of the time bounds stated above without written authorization may trigger extra hour charge policies.</li>
                </ol>
            </div>
        </div>

        <!-- Signature Layout -->
        <div class="row justify-content-between align-items-end mt-4 pt-1" id="signature-layout">
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

    <!-- Paginated Target Area for Print Rendering -->
    <div id="paginated-invoice-container">
        <div class="text-center py-5">
            <span class="spinner-border spinner-border-sm text-primary me-2"></span>Preparing print slip...
        </div>
    </div>

    <!-- Print Custom Stylesheet -->
    <style>
        #paginated-invoice-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .print-page {
            width: 210mm;
            height: 297mm;
            padding: 6mm 10mm;
            box-sizing: border-box;
            background: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            border: 1px solid #dee2e6;
            display: flex;
            flex-direction: column;
            border-radius: 4px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
            color: #1e293b !important;
        }

        .page-content-wrapper {
            flex-grow: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .page-footer-wrapper {
            height: 20px;
            border-top: 1px solid #dee2e6;
            padding-top: 2px;
            margin-top: 5px;
            font-size: 8pt;
            color: #555;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: monospace;
        }

        .menu-item-li {
            break-inside: avoid;
            page-break-inside: avoid;
            margin-bottom: 2px !important;
        }

        .compact-header {
            border-bottom: 2px solid #0056b3;
        }

        /* Spacing overrides for print slip */
        #meta-grid table td {
            padding: 1px 0 !important;
        }

        #brand-header {
            margin-bottom: 5px !important;
        }

        #meta-grid {
            margin-top: 4px !important;
            margin-bottom: 4px !important;
        }

        hr {
            margin: 4px 0 !important;
            opacity: 0.15;
        }

        #terms-and-conditions {
            margin-top: 4px !important;
        }

        #terms-and-conditions h6 {
            margin-bottom: 2px !important;
        }

        #signature-layout {
            margin-top: 0.75rem !important;
            padding-top: 0.1rem !important;
        }

        @media print {
            body {
                background: #fff !important;
                color: #1e293b !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .content, .main {
                padding: 0 !important;
                margin: 0 !important;
            }
            .container, .container-fluid {
                max-width: 100% !important;
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            #paginated-invoice-container {
                gap: 0 !important;
            }
            .print-page {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                page-break-after: always;
                break-after: page;
                width: 210mm !important;
                height: 297mm !important;
                padding: 4mm 8mm !important;
                border-radius: 0 !important;
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
                font-size: 9pt !important;
                line-height: 1.25 !important;
            }
            /* Hide UI components */
            nav, .navbar, .navbar-vertical, .d-print-none, footer, .footer {
                display: none !important;
            }
            
            /* Print Point Sizing Overrides */
            .fs-12 {
                font-size: 9pt !important;
            }
            .fs-13 {
                font-size: 9.5pt !important;
            }
            .fs-18 {
                font-size: 13pt !important;
            }
            .fs-11 {
                font-size: 8pt !important;
            }
            .text-500 {
                color: #475569 !important;
                font-weight: 700 !important;
                letter-spacing: 0.5px;
            }
            #brand-name {
                font-family: 'Outfit', 'Inter', sans-serif !important;
                font-size: 17pt !important;
                font-weight: 800 !important;
                color: #0d6efd !important;
            }
            .font-monospace {
                font-family: 'SFMono-Regular', Consolas, "Liberation Mono", Menlo, monospace !important;
                font-size: 8.5pt !important;
            }

            /* Compact terms and signatures for optimal page-fitting */
            #terms-and-conditions {
                margin-top: 2px !important;
            }
            #terms-and-conditions h6 {
                font-size: 8.5pt !important;
                margin-bottom: 1px !important;
            }
            #terms-and-conditions ol {
                font-size: 7.5pt !important;
                line-height: 1.2 !important;
            }
            #signature-layout {
                margin-top: 10px !important;
                padding-top: 0 !important;
            }
            #signature-layout hr {
                margin-bottom: 2px !important;
            }
            #signature-layout span {
                font-size: 8.5pt !important;
            }
        }
        @page {
            size: A4 portrait;
            margin: 0 !important;
        }
    </style>

    <!-- Pagination Script -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            paginateInvoice();
            window.addEventListener("load", paginateInvoice);
        });

        function paginateInvoice() {
            const original = document.getElementById("original-invoice-content");
            const container = document.getElementById("paginated-invoice-container");
            if (!original || !container) return;

            container.innerHTML = "";

            const bookingNumber = "{{ $booking->booking_number }}";
            const customerName = "{{ $booking->customer->full_name ?? '—' }}";
            const eventDate = "{{ $booking->booking_date->format('d-M-Y') }}";
            const printedAt = "{{ now()->format('d-M-Y h:i A') }}";

            // Helper to generate a new page structure
            function createPage(pageNum) {
                const page = document.createElement("div");
                page.className = "print-page bg-white";
                
                const pageContent = document.createElement("div");
                pageContent.className = "page-content-wrapper";
                
                const pageFooter = document.createElement("div");
                pageFooter.className = "page-footer-wrapper";
                
                const footerLeft = document.createElement("span");
                footerLeft.textContent = `Reservation: #${bookingNumber} | Customer: ${customerName}`;
                
                const footerRight = document.createElement("span");
                
                pageFooter.appendChild(footerLeft);
                pageFooter.appendChild(footerRight);
                
                page.appendChild(pageContent);
                page.appendChild(pageFooter);
                
                return { page, pageContent, footerRight };
            }

            // Extract template elements
            const brandHeader = original.querySelector("#brand-header")?.cloneNode(true);
            const metaGrid = original.querySelector("#meta-grid")?.cloneNode(true);
            
            // Body parts
            const rateBlock = original.querySelector("#rate-block")?.cloneNode(true);
            const addonsBlock = original.querySelector("#addons-block")?.cloneNode(true);
            const instructionsBlock = original.querySelector("#instructions-block")?.cloneNode(true);
            
            // Menu items
            const menuTitle = original.querySelector("#menu-details-title")?.cloneNode(true);
            const menuItems = Array.from(original.querySelectorAll(".menu-item-li"));
            
            // Terms & Signatures
            const termsBlock = original.querySelector("#terms-and-conditions")?.cloneNode(true);
            const signatureBlock = original.querySelector("#signature-layout")?.cloneNode(true);

            const pages = [];
            let currentPageIdx = 0;
            
            // Create Page 1
            let activePage = createPage(currentPageIdx + 1);
            container.appendChild(activePage.page);
            pages.push(activePage);

            let activeContent = activePage.pageContent;

            // Append initial structures to Page 1
            if (brandHeader) activeContent.appendChild(brandHeader);
            if (metaGrid) activeContent.appendChild(metaGrid);
            activeContent.appendChild(document.createElement("hr"));

            // Create Page 1 split layout container
            const splitBody = document.createElement("div");
            splitBody.className = "row g-4 my-1";
            
            const leftCol = document.createElement("div");
            leftCol.className = "col-6 border-end border-translucent";
            
            const rightCol = document.createElement("div");
            rightCol.className = "col-6";
            
            splitBody.appendChild(leftCol);
            splitBody.appendChild(rightCol);
            activeContent.appendChild(splitBody);

            // Populate Right Column of Page 1
            if (addonsBlock) rightCol.appendChild(addonsBlock);
            if (instructionsBlock) rightCol.appendChild(instructionsBlock);

            // Handle Menu List on Page 1 (using Left Column)
            // Handle Menu List on Page 1 (using Left Column first, then Right Column if Left overflows)
            if (menuItems.length > 0) {
                const N = menuItems.length;

                // Helper to clean column elements for planning
                function clearMenuContainers() {
                    leftCol.innerHTML = "";
                    rightCol.innerHTML = "";
                    // Re-append default right column blocks
                    if (addonsBlock) rightCol.appendChild(addonsBlock);
                    if (instructionsBlock) rightCol.appendChild(instructionsBlock);
                }

                // Try to find a split point K that fits all items + terms on Page 1 (max 40 items in left col)
                let successPage1Distribution = null;
                const endK = Math.ceil(N / 2);
                let startK = Math.min(N, 40);
                if (startK < endK) {
                    startK = endK;
                }

                for (let K = startK; K >= endK; K--) {
                    clearMenuContainers();

                    // Build Left Column with K items
                    if (menuTitle) leftCol.appendChild(menuTitle.cloneNode(true));
                    const leftList = document.createElement("ol");
                    leftList.className = "ps-4 mb-0 responsive-print-font fs-12 text-800";
                    leftList.start = 1;
                    leftCol.appendChild(leftList);
                    for (let i = 0; i < K; i++) {
                        leftList.appendChild(menuItems[i].cloneNode(true));
                    }
                    if (rateBlock) leftCol.appendChild(rateBlock.cloneNode(true));

                    // Build Right Column with N - K items (if any)
                    if (K < N) {
                        const rightTitle = document.createElement("span");
                        rightTitle.className = "text-500 fw-bold d-block text-uppercase fs-12 mt-3 mb-1";
                        rightTitle.textContent = "Menu (Continued)";
                        rightCol.appendChild(rightTitle);

                        const rightList = document.createElement("ol");
                        rightList.className = "ps-4 mb-0 responsive-print-font fs-12 text-800";
                        rightList.start = K + 1;
                        rightCol.appendChild(rightList);
                        for (let i = K; i < N; i++) {
                            rightList.appendChild(menuItems[i].cloneNode(true));
                        }
                    }

                    // Check if this distribution fits on Page 1 along with terms and signatures (allowing 15px layout tolerance)
                    let fitsWithTerms = false;
                    if (activeContent.scrollHeight - activeContent.clientHeight <= 15) {
                        const tempTerms = termsBlock ? termsBlock.cloneNode(true) : null;
                        const tempSig = signatureBlock ? signatureBlock.cloneNode(true) : null;

                        if (tempTerms) activeContent.appendChild(tempTerms);
                        if (tempSig) activeContent.appendChild(tempSig);

                        if (activeContent.scrollHeight - activeContent.clientHeight <= 15) {
                            fitsWithTerms = true;
                        }

                        if (tempSig) activeContent.removeChild(tempSig);
                        if (tempTerms) activeContent.removeChild(tempTerms);
                    }

                    if (fitsWithTerms) {
                        successPage1Distribution = K;
                        break;
                    }
                }

                if (successPage1Distribution !== null) {
                    // Apply the successful Page 1 distribution
                    clearMenuContainers();
                    
                    if (menuTitle) leftCol.appendChild(menuTitle.cloneNode(true));
                    const leftList = document.createElement("ol");
                    leftList.className = "ps-4 mb-0 responsive-print-font fs-12 text-800";
                    leftList.start = 1;
                    leftCol.appendChild(leftList);
                    for (let i = 0; i < successPage1Distribution; i++) {
                        leftList.appendChild(menuItems[i].cloneNode(true));
                    }
                    if (rateBlock) leftCol.appendChild(rateBlock.cloneNode(true));

                    if (successPage1Distribution < N) {
                        const rightTitle = document.createElement("span");
                        rightTitle.className = "text-500 fw-bold d-block text-uppercase fs-12 mt-3 mb-1";
                        rightTitle.textContent = "Menu (Continued)";
                        rightCol.appendChild(rightTitle);

                        const rightList = document.createElement("ol");
                        rightList.className = "ps-4 mb-0 responsive-print-font fs-12 text-800";
                        rightList.start = successPage1Distribution + 1;
                        rightCol.appendChild(rightList);
                        for (let i = successPage1Distribution; i < N; i++) {
                            rightList.appendChild(menuItems[i].cloneNode(true));
                        }
                    }
                } else {
                    // Fall back to sequential filling with spilling
                    clearMenuContainers();

                    if (menuTitle) leftCol.appendChild(menuTitle.cloneNode(true));
                    const menuList = document.createElement("ol");
                    menuList.className = "ps-4 mb-0 responsive-print-font fs-12 text-800";
                    menuList.start = 1;
                    leftCol.appendChild(menuList);

                    if (rateBlock) leftCol.appendChild(rateBlock.cloneNode(true));

                    let currentListTarget = menuList;
                    let currentColumn = 'left';
                    let rightColContinuationList = null;
                    let rightColTitle = null;

                    for (let i = 0; i < menuItems.length; i++) {
                        const item = menuItems[i].cloneNode(true);
                        currentListTarget.appendChild(item);

                        // Check if adding this item caused overflow on Page 1 (with 15px tolerance)
                        if (activeContent.scrollHeight - activeContent.clientHeight > 15) {
                            // Rollback item
                            currentListTarget.removeChild(item);

                            if (currentColumn === 'left') {
                                // Left column filled up, try flowing into Right Column (after special instructions)
                                currentColumn = 'right';

                                rightColTitle = document.createElement("span");
                                rightColTitle.className = "text-500 fw-bold d-block text-uppercase fs-12 mt-3 mb-1";
                                rightColTitle.textContent = "Menu (Continued)";
                                rightCol.appendChild(rightColTitle);

                                rightColContinuationList = document.createElement("ol");
                                rightColContinuationList.className = "ps-4 mb-0 responsive-print-font fs-12 text-800";
                                rightColContinuationList.start = i + 1;
                                rightCol.appendChild(rightColContinuationList);

                                currentListTarget = rightColContinuationList;

                                // Re-append rolled back item to right column list
                                currentListTarget.appendChild(item);

                                // Check if it immediately overflows the right column too (with 15px tolerance)
                                if (activeContent.scrollHeight - activeContent.clientHeight > 15) {
                                    // Right column also overflows immediately! Rollback and create Page 2
                                    currentListTarget.removeChild(item);
                                    rightCol.removeChild(rightColTitle);
                                    rightCol.removeChild(rightColContinuationList);

                                    createNextPageAndContinue(i);
                                    break;
                                }
                            } else {
                                // Right column also overflowed, rollback and create Page 2
                                createNextPageAndContinue(i);
                                break;
                            }
                        }
                    }
                }

                function createNextPageAndContinue(startIndex) {
                    // Create Page 2 (continuation full-width page)
                    currentPageIdx++;
                    activePage = createPage(currentPageIdx + 1);
                    container.appendChild(activePage.page);
                    pages.push(activePage);

                    activeContent = activePage.pageContent;

                    // Inject compact header
                    const compHeader = document.createElement("div");
                    compHeader.className = "compact-header mb-2 pb-1 d-flex justify-content-between align-items-end";
                    compHeader.innerHTML = `
                        <div>
                            <h5 class="text-primary fw-black mb-0">${original.querySelector("#brand-name")?.textContent || "MARQUEE CMS"}</h5>
                            <span class="fs-11 text-muted fw-bold">RESERVATION SLIP — MENU CONTINUED</span>
                        </div>
                        <div class="text-end fs-10 font-monospace text-secondary">
                            <strong>Voucher:</strong> #${bookingNumber}<br>
                            <strong>Customer:</strong> ${customerName}<br>
                            <strong>Event Date:</strong> ${eventDate}
                        </div>
                    `;
                    activeContent.appendChild(compHeader);

                    // Menu title
                    const nextMenuTitle = document.createElement("span");
                    nextMenuTitle.className = "text-500 fw-bold d-block text-uppercase fs-12 mb-1";
                    nextMenuTitle.textContent = "Event Menu Selection Details (Continued)";
                    activeContent.appendChild(nextMenuTitle);

                    // Full width menu ordered list on Page 2+
                    let activeContinuationList = document.createElement("ol");
                    activeContinuationList.className = "ps-4 mb-0 responsive-print-font fs-12 text-800";
                    activeContinuationList.start = startIndex + 1;
                    activeContent.appendChild(activeContinuationList);

                    // Continue adding remaining items starting from startIndex
                    for (let j = startIndex; j < menuItems.length; j++) {
                        const nextItem = menuItems[j].cloneNode(true);
                        activeContinuationList.appendChild(nextItem);

                        if (activeContent.scrollHeight - activeContent.clientHeight > 15) {
                            // Rollback
                            activeContinuationList.removeChild(nextItem);

                            // Create Page 3+
                            currentPageIdx++;
                            activePage = createPage(currentPageIdx + 1);
                            container.appendChild(activePage.page);
                            pages.push(activePage);

                            activeContent = activePage.pageContent;

                            // Compact Header
                            const pageNHeader = document.createElement("div");
                            pageNHeader.className = "compact-header mb-2 pb-1 d-flex justify-content-between align-items-end";
                            pageNHeader.innerHTML = `
                                <div>
                                    <h5 class="text-primary fw-black mb-0">${original.querySelector("#brand-name")?.textContent || "MARQUEE CMS"}</h5>
                                    <span class="fs-11 text-muted fw-bold">RESERVATION SLIP — MENU CONTINUED</span>
                                </div>
                                <div class="text-end fs-10 font-monospace text-secondary">
                                    <strong>Voucher:</strong> #${bookingNumber}<br>
                                    <strong>Customer:</strong> ${customerName}<br>
                                    <strong>Event Date:</strong> ${eventDate}
                                </div>
                            `;
                            activeContent.appendChild(pageNHeader);

                            // Title
                            const pageNTitle = document.createElement("span");
                            pageNTitle.className = "text-500 fw-bold d-block text-uppercase fs-12 mb-1";
                            pageNTitle.textContent = "Event Menu Selection Details (Continued)";
                            activeContent.appendChild(pageNTitle);

                            // New continuation list
                            activeContinuationList = document.createElement("ol");
                            activeContinuationList.className = "ps-4 mb-0 responsive-print-font fs-12 text-800";
                            activeContinuationList.start = j + 1;
                            activeContent.appendChild(activeContinuationList);

                            // Append
                            activeContinuationList.appendChild(nextItem);
                        }
                    }
                }
            }

            // Function to safely append remaining blocks (terms, signatures) with overflow logic
            function appendBlock(element) {
                if (!element) return;
                activeContent.appendChild(element);

                if (activeContent.scrollHeight - activeContent.clientHeight > 15) {
                    // Rollback
                    activeContent.removeChild(element);

                    // Start new page
                    currentPageIdx++;
                    activePage = createPage(currentPageIdx + 1);
                    container.appendChild(activePage.page);
                    pages.push(activePage);

                    activeContent = activePage.pageContent;

                    // Inject compact continuation header
                    const compHeader = document.createElement("div");
                    compHeader.className = "compact-header mb-2 pb-1 d-flex justify-content-between align-items-end";
                    compHeader.innerHTML = `
                        <div>
                            <h5 class="text-primary fw-black mb-0">${original.querySelector("#brand-name")?.textContent || "MARQUEE CMS"}</h5>
                            <span class="fs-11 text-muted fw-bold">RESERVATION SLIP</span>
                        </div>
                        <div class="text-end fs-10 font-monospace text-secondary">
                            <strong>Voucher:</strong> #${bookingNumber}<br>
                            <strong>Customer:</strong> ${customerName}<br>
                            <strong>Event Date:</strong> ${eventDate}
                        </div>
                    `;
                    activeContent.appendChild(compHeader);

                    // Re-append element
                    activeContent.appendChild(element);
                }
            }

            // Append terms and signatures
            appendBlock(termsBlock);
            appendBlock(signatureBlock);

            // Update footer page numbers
            const totalPages = pages.length;
            pages.forEach((p, idx) => {
                p.footerRight.textContent = `Printed: ${printedAt} | Page ${idx + 1} of ${totalPages}`;
            });
            
            console.log("Pagination complete. Total pages generated:", totalPages);
        }
    </script>
</div>
