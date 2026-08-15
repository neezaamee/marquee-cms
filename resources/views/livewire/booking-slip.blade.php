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

    <!-- Original Hidden Printable Area (Used as template source) -->
    <div id="original-invoice-content" style="display: none;">
        <div class="row align-items-center mb-2" id="brand-header">
            <!-- Brand Info -->
            <div class="col-sm-6 text-start">
                <h3 class="text-primary fw-black mb-1" id="brand-name">MARQUEE CMS</h3>
                <h6 class="text-secondary fw-bold">{{ auth()->user()->marquee->name ?? 'Royal Event Marquee' }}</h6>
                <div class="fs-12 text-600">
                    {{ auth()->user()->marquee->address ?? 'Main Boulevard, Gulberg' }}, {{ auth()->user()->marquee->city ?? 'Lahore' }}
                </div>
            </div>
            <!-- Invoice Title & QR Code Placeholder -->
            <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                <h4 class="text-800 fw-bold mb-1">BOOKING CONFIRMATION</h4>
                <div class="fs-11 font-monospace text-secondary">VOUCHER REFERENCE: #{{ $booking->booking_number }}</div>
                <div class="mt-2 d-inline-block p-1 border bg-light text-center" style="width: 50px; height: 50px; border-radius: 4px;">
                    <span class="fas fa-qrcode fa-2x text-secondary"></span>
                </div>
            </div>
        </div>

        <!-- Meta Grid -->
        <div class="row g-3 my-1" id="meta-grid">
            <div class="col-sm-6">
                <span class="text-500 fw-bold d-block text-uppercase fs-12 mb-1">Event Venue & Timings</span>
                <table class="table table-sm table-borderless fs-11 mb-0">
                    <tr>
                        <td class="text-600 px-0 py-1" style="width: 110px;">Booking Hall(s):</td>
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
                    <tr>
                        <td class="text-600 px-0 py-1">Privacy / Partition:</td>
                        <td class="text-800 fw-bold px-0 py-1">
                            @if($booking->privacy_required)
                                Yes (Ladies: {{ $booking->privacy_ladies_percentage }}%, Gents: {{ $booking->privacy_gents_percentage }}%)
                            @else
                                No
                            @endif
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
                    <p class="text-muted fs-11 mb-0">No customer detail attached.</p>
                @endif
            </div>
        </div>

        <!-- Financial Itemized Summary Table -->
        <div class="table-responsive my-2" id="financial-summary-table">
            <table class="table table-sm table-striped border fs-11 align-middle mb-0">
                <thead class="bg-light text-900">
                    <tr>
                        <th class="ps-3" style="width: 40px;">#</th>
                        <th>Charge Item Description</th>
                        <th class="text-center" style="width: 100px;">Rate</th>
                        <th class="text-center" style="width: 80px;">Qty/Guests</th>
                        <th class="text-end pe-3" style="width: 140px;">Line Status</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!$booking->no_food)
                        <tr>
                            <td class="ps-3">1</td>
                            <td>
                                <div class="fw-bold fs-11">{{ $booking->package->package_name ?? 'Catering Package' }}</div>
                                <div class="text-muted fs-10">Per Plate Menu Package Booking</div>
                            </td>
                            <td class="text-center font-monospace">Rs. {{ number_format($booking->per_plate_price, 2) }}</td>
                            <td class="text-center">{{ $booking->guest_count }}</td>
                            <td class="text-end pe-3 text-secondary italic fs-10">Confirmed Rate Only</td>
                        </tr>
                    @else
                        <tr>
                            <td class="ps-3">1</td>
                            <td>
                                <div class="fw-bold fs-11">Catering Plan</div>
                                <div class="text-muted fs-10">Sitting Plan Only (No Food Catering)</div>
                            </td>
                            <td class="text-center font-monospace">—</td>
                            <td class="text-center">—</td>
                            <td class="text-end pe-3 text-secondary italic fs-10">Sitting Only</td>
                        </tr>
                    @endif
                    @if($booking->hall_charges > 0)
                        <tr>
                            <td class="ps-3">2</td>
                            <td>
                                <div class="fw-bold fs-11">Hall rent & Setup</div>
                                <div class="text-muted fs-10">Exclusive venue occupancy charge</div>
                            </td>
                            <td class="text-center font-monospace">Rs. {{ number_format($booking->hall_charges, 2) }}</td>
                            <td class="text-center">1</td>
                            <td class="text-end pe-3 text-secondary italic fs-10">Confirmed Rent Only</td>
                        </tr>
                    @endif
                    @if($booking->extra_charges > 0)
                        <tr>
                            <td class="ps-3">3</td>
                            <td>
                                <div class="fw-bold fs-11">Extra Amenities / Decor Addons</div>
                                <div class="text-muted fs-10">Custom decor setup or audiovisual additions</div>
                            </td>
                            <td class="text-center font-monospace">—</td>
                            <td class="text-center">1</td>
                            <td class="text-end pe-3 text-secondary italic fs-10">Included in Setup</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Event Menu Checklist (Template) -->
        <span class="text-500 fw-bold d-block text-uppercase fs-12 mb-2" id="menu-details-title">Event Menu Selection Details</span>
        <div class="row g-2" id="menu-items-grid">
            @if($booking->menuItems->isNotEmpty())
                @foreach($booking->menuItems as $item)
                    <div class="col-md-6 col-sm-12 menu-item-col mb-1">
                        <div class="p-1 px-2 border rounded bg-light d-flex justify-content-between align-items-center fs-11">
                            <div>
                                <span class="fw-bold text-800">
                                    {{ $loop->iteration }}. {{ $item->item_name }}
                                    @if($item->urdu_name)
                                        <span class="text-muted fs-11 ms-1">({{ $item->urdu_name }})</span>
                                    @endif
                                </span>
                                @if(!empty($item->pivot->custom_note))
                                    <div class="text-muted fs-10 italic">({{ $item->pivot->custom_note }})</div>
                                @endif
                            </div>
                            @if(!empty($item->pivot->managed_by_host))
                                <span class="badge badge-subtle-warning fs-10 d-print-none">Managed by Host</span>
                                <span class="text-danger fw-bold fs-10 d-none d-print-inline-block">(By Host)</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <!-- Rate & Pricing Summary -->
        <div class="row justify-content-end mb-2" id="pricing-summary-card">
            <div class="col-sm-6 text-end fs-11">
                <div class="card bg-light border-0">
                    <div class="card-body p-2 text-start">
                        <h6 class="text-primary fw-bold mb-1 fs-11"><span class="fas fa-file-invoice-dollar me-2"></span>Rate & Occupancy Details</h6>
                        @if(!$booking->no_food)
                            <div class="fs-10">Rate per Head/Plate: <strong class="text-800">Rs. {{ number_format($booking->per_plate_price) }} per plate</strong></div>
                            <div class="fs-10 text-600 mt-1">Tentative Guests: <strong>{{ $booking->tentative_guests ?? $booking->guest_count }}</strong> | Confirmed Guests: <strong>{{ $booking->confirmed_guests ?? 'Pending Confirmation' }}</strong> (Status: <strong>{{ $booking->guest_status ?? 'Tentative' }}</strong>)</div>
                        @else
                            <div class="fs-10 text-success fw-bold">Sitting Plan Only (No Catering Food)</div>
                            <div class="fs-10 text-600 mt-1">Hall Setup / Occupancy Rent: <strong>Rs. {{ number_format($booking->hall_charges) }}</strong></div>
                        @endif
                        
                        @if($booking->security_deposit > 0)
                            <div class="fs-10 text-600 mt-1 border-top pt-1">Refundable Security Deposit (Held): <strong>Rs. {{ number_format($booking->security_deposit) }}</strong></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Terms and Conditions Section -->
        <div class="row g-3 fs-11 mt-1" id="terms-and-conditions">
            <div class="col-12">
                <h6 class="fw-bold text-800 mb-1 fs-11">Terms & Conditions</h6>
                <ol class="ps-3 text-600 mb-0 fs-10">
                    <li>The refundable security deposit remains strictly separate from event revenue and will be refunded within 3 working days post-event after evaluating any damage losses.</li>
                    <li>Cancellations are subject to structural marquee policies. Minimum headcounts must be adhered to once finalized.</li>
                    <li>Any extension of the time bounds stated above without written authorization may trigger extra hour charge policies.</li>
                </ol>
            </div>
        </div>

        <!-- Signature Layout -->
        <div class="row justify-content-between align-items-end mt-4 pt-2" id="signature-layout">
            <div class="col-sm-5 text-center">
                <hr class="mb-1" />
                <span class="fs-11 text-600">Customer Signature</span>
            </div>
            <div class="col-sm-5 text-center">
                <hr class="mb-1" />
                <span class="fs-11 text-600">Authorized Officer Stamp & Sign</span>
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
            padding: 8mm 12mm;
            box-sizing: border-box;
            background: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            border: 1px solid #dee2e6;
            display: flex;
            flex-direction: column;
            border-radius: 4px;
        }

        .page-content-wrapper {
            flex-grow: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .page-footer-wrapper {
            height: 25px;
            border-top: 1px solid #dee2e6;
            padding-top: 4px;
            margin-top: 5px;
            font-size: 8pt;
            color: #555;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: monospace;
        }

        .menu-item-col {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .compact-header {
            border-bottom: 2px solid #0056b3;
        }

        /* Spacing overrides for print slip */
        .table-sm th, .table-sm td {
            padding: 3px 6px !important;
        }

        hr {
            margin: 5px 0 !important;
            opacity: 0.15;
        }

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
                padding: 8mm 12mm !important;
                border-radius: 0 !important;
            }
            /* Hide UI components */
            nav, .navbar, .navbar-vertical, .d-print-none, footer, .footer {
                display: none !important;
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
            const financialTable = original.querySelector("#financial-summary-table")?.cloneNode(true);
            const menuTitle = original.querySelector("#menu-details-title")?.cloneNode(true);
            const menuItems = Array.from(original.querySelectorAll(".menu-item-col"));
            const pricingCard = original.querySelector("#pricing-summary-card")?.cloneNode(true);
            const termsBlock = original.querySelector("#terms-and-conditions")?.cloneNode(true);
            const signatureBlock = original.querySelector("#signature-layout")?.cloneNode(true);

            const pages = [];
            let currentPageIdx = 0;
            
            // Create Page 1
            let activePage = createPage(currentPageIdx + 1);
            container.appendChild(activePage.page);
            pages.push(activePage);

            let activeContent = activePage.pageContent;

            // Append initial structures
            if (brandHeader) activeContent.appendChild(brandHeader);
            
            const hrA = document.createElement("hr");
            activeContent.appendChild(hrA);
            
            if (metaGrid) activeContent.appendChild(metaGrid);
            
            const hrB = document.createElement("hr");
            activeContent.appendChild(hrB);
            
            if (financialTable) activeContent.appendChild(financialTable);

            // Handle Menu List with Dynamic Pagination
            if (menuItems.length > 0) {
                if (menuTitle) activeContent.appendChild(menuTitle);

                let activeMenuRow = document.createElement("div");
                activeMenuRow.className = "row g-2 menu-grid-row";
                activeContent.appendChild(activeMenuRow);

                let activeMenuTitle = menuTitle;

                for (let i = 0; i < menuItems.length; i++) {
                    const item = menuItems[i].cloneNode(true);
                    activeMenuRow.appendChild(item);

                    // Check if adding this menu item caused content wrapper overflow
                    if (activeContent.scrollHeight > activeContent.clientHeight) {
                        // Rollback item
                        activeMenuRow.removeChild(item);

                        // Clean up containers if empty
                        if (activeMenuRow.children.length === 0) {
                            activeContent.removeChild(activeMenuRow);
                            if (activeMenuTitle && activeContent.contains(activeMenuTitle)) {
                                activeContent.removeChild(activeMenuTitle);
                            }
                        }

                        // Transition to new page
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
                                <span class="fs-11 text-muted fw-bold">RESERVATION SLIP — CONTINUED</span>
                            </div>
                            <div class="text-end fs-10 font-monospace text-secondary">
                                <strong>Voucher:</strong> #${bookingNumber}<br>
                                <strong>Customer:</strong> ${customerName}<br>
                                <strong>Event Date:</strong> ${eventDate}
                            </div>
                        `;
                        activeContent.appendChild(compHeader);

                        // Menu continued title
                        activeMenuTitle = document.createElement("span");
                        activeMenuTitle.className = "text-500 fw-bold d-block text-uppercase fs-12 mb-1";
                        activeMenuTitle.textContent = "Event Menu Selection Details (Continued)";
                        activeContent.appendChild(activeMenuTitle);

                        // New Menu row
                        activeMenuRow = document.createElement("div");
                        activeMenuRow.className = "row g-2 menu-grid-row";
                        activeContent.appendChild(activeMenuRow);

                        // Place rolled back item
                        activeMenuRow.appendChild(item);
                    }
                }
            }

            // Function to safely append content with overflow logic
            function appendBlock(element) {
                if (!element) return;
                activeContent.appendChild(element);

                if (activeContent.scrollHeight > activeContent.clientHeight) {
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

            // Append pricing, terms, and signatures
            appendBlock(pricingCard);
            
            const divider = document.createElement("hr");
            divider.className = "my-2";
            appendBlock(divider);
            
            appendBlock(termsBlock);
            appendBlock(signatureBlock);

            // Update footer page numbers
            const totalPages = pages.length;
            pages.forEach((p, idx) => {
                p.footerRight.textContent = `Printed: ${printedAt} | Page ${idx + 1} of ${totalPages}`;
            });
        }
    </script>
</div>
