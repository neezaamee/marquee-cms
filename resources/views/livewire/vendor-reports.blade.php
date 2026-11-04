<div class="p-3">
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm 10mm;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 10pt !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print,
            .navbar,
            .navbar-vertical,
            footer,
            .btn,
            .card-header .btn {
                display: none !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .card-header {
                display: none !important;
            }
            .card-body {
                padding: 0 !important;
            }
            .table-responsive {
                overflow: visible !important;
                width: 100% !important;
            }
            table {
                width: 100% !important;
                font-size: 9pt !important;
                border-collapse: collapse !important;
            }
            thead {
                display: table-header-group !important;
            }
            thead th {
                background-color: #f1f3f5 !important;
                color: #000 !important;
                border-top: 1px solid #333 !important;
                border-bottom: 2px solid #333 !important;
                padding: 6px 8px !important;
            }
            tbody td {
                border-bottom: 1px solid #ddd !important;
                padding: 5px 8px !important;
            }
            tfoot {
                display: table-footer-group !important;
            }
            tfoot td {
                border-top: 2px solid #333 !important;
                border-bottom: 2px solid #333 !important;
                font-weight: bold !important;
                background-color: #f8f9fa !important;
                padding: 6px 8px !important;
            }
            tr {
                page-break-inside: avoid !important;
            }
            a {
                text-decoration: none !important;
                color: #000 !important;
            }
            .badge {
                border: 1px solid #ccc !important;
                background: transparent !important;
                color: #000 !important;
                font-weight: normal !important;
                padding: 2px 4px !important;
            }
            .print-letterhead {
                display: block !important;
                margin-bottom: 12px !important;
                border-bottom: 2px solid #1a1a1a !important;
                padding-bottom: 8px !important;
            }
        }
        .print-letterhead {
            display: none;
        }
    </style>

    <!-- Executive Letterhead (Visible in Print Mode) -->
    <div class="print-letterhead">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h3 class="fw-bold mb-1 text-uppercase text-dark">{{ $marquee?->name ?? config('app.name', 'MarqueeCMS') }}</h3>
                @if($marquee?->address || $marquee?->phone || $marquee?->email)
                    <div class="fs-11 text-muted">
                        @if($marquee?->address) <span><i class="fas fa-map-marker-alt me-1"></i>{{ $marquee->address }}</span> @endif
                        @if($marquee?->phone) <span class="ms-2"><i class="fas fa-phone me-1"></i>{{ $marquee->phone }}</span> @endif
                        @if($marquee?->email) <span class="ms-2"><i class="fas fa-envelope me-1"></i>{{ $marquee->email }}</span> @endif
                    </div>
                @endif
                <div class="mt-2">
                    <span class="fs-13 fw-bold text-uppercase border-bottom border-dark pb-1">
                        @if($reportType === 'sales') Service Provider Sales Report
                        @elseif($reportType === 'commission') Service Provider Commission Income Report
                        @elseif($reportType === 'ledger') Service Provider Financial Ledger Statement
                        @elseif($reportType === 'settlement') Service Provider Settlement Report
                        @elseif($reportType === 'monthly') Monthly Commission Summary Report
                        @endif
                    </span>
                </div>
            </div>
            <div class="text-end fs-11">
                <div><strong>Period:</strong> {{ $periodLabel }}</div>
                <div><strong>Provider:</strong> {{ $selectedVendor ? $selectedVendor->name . ' (' . ($selectedVendor->vendor_type ?? 'Vendor') . ')' : 'All Service Providers' }}</div>
                <div class="text-muted mt-1">Printed: {{ now()->format('d-M-Y h:i A') }}</div>
                <div class="text-muted">Generated by: {{ auth()->user()?->name ?? 'System' }}</div>
            </div>
        </div>
    </div>

    <!-- Header Toolbar -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="fas fa-chart-bar text-primary me-2"></i>Service Provider Financial Reports</h4>
            <p class="text-secondary fs-12 mb-0">Filter, inspect, export, and print provider sales, commission income, ledgers, and payouts.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button wire:click="exportCsv" wire:loading.attr="disabled" class="btn btn-falcon-default btn-sm">
                <span wire:loading.remove wire:target="exportCsv"><i class="fas fa-file-csv text-success me-1"></i>Export CSV</span>
                <span wire:loading wire:target="exportCsv"><span class="spinner-border spinner-border-sm me-1"></span>Exporting...</span>
            </button>
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print me-1"></i> Print Report</button>
        </div>
    </div>

    <!-- Filters Bar (Hidden during print) -->
    <div class="card border-0 shadow-sm mb-3 no-print">
        <div class="card-body p-3 fs-12">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Report Type</label>
                    <select wire:model.live="reportType" class="form-select form-select-sm">
                        <option value="sales">1. Provider Sales Report</option>
                        <option value="commission">2. Commission Income Report</option>
                        <option value="ledger">3. Provider Ledger Statement</option>
                        <option value="settlement">4. Provider Settlement Report</option>
                        <option value="monthly">5. Monthly Commission Summary</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Service Provider Filter</label>
                    <select wire:model.live="vendor_id" class="form-select form-select-sm">
                        <option value="">-- All Service Providers --</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}">{{ $v->name }} ({{ $v->vendor_type }})</option>
                        @endforeach
                    </select>
                </div>

                @if($reportType !== 'monthly')
                    <div class="col-md-2">
                        <label class="form-label fw-bold">From Date</label>
                        <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">To Date</label>
                        <input type="date" wire:model.live="dateTo" class="form-control form-control-sm">
                    </div>
                @else
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Year</label>
                        <select wire:model.live="year" class="form-select form-select-sm">
                            <option value="2026">2026</option>
                            <option value="2025">2025</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Month</label>
                        <select wire:model.live="month" class="form-select form-select-sm">
                            <option value="01">January</option>
                            <option value="02">February</option>
                            <option value="03">March</option>
                            <option value="04">April</option>
                            <option value="05">May</option>
                            <option value="06">June</option>
                            <option value="07">July</option>
                            <option value="08">August</option>
                            <option value="09">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                    </div>
                @endif
                <div class="col-md-2 d-flex align-items-end">
                    <button wire:click="$set('vendor_id', ''); $set('dateFrom', '{{ date('Y-m-01') }}'); $set('dateTo', '{{ date('Y-m-d') }}');" class="btn btn-outline-secondary btn-sm w-100"><i class="fas fa-undo me-1"></i> Reset</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="fw-bold mb-0 text-uppercase">
                    @if($reportType === 'sales') SERVICE PROVIDER SALES REPORT
                    @elseif($reportType === 'commission') SERVICE PROVIDER COMMISSION INCOME REPORT
                    @elseif($reportType === 'ledger') SERVICE PROVIDER LEDGER STATEMENT
                    @elseif($reportType === 'settlement') SERVICE PROVIDER SETTLEMENT REPORT
                    @elseif($reportType === 'monthly') MONTHLY COMMISSION SUMMARY REPORT
                    @endif
                </h5>
                <div class="text-secondary fs-11 mt-1">
                    <span><strong>Period:</strong> {{ $periodLabel }}</span>
                    <span class="ms-3"><strong>Provider:</strong> {{ $selectedVendor ? $selectedVendor->name : 'All Service Providers' }}</span>
                </div>
            </div>
            <div class="text-end fs-11 text-muted">
                <div>Generated: {{ date('d-M-Y h:i A') }}</div>
                <div>System: {{ $marquee?->name ?? 'MarqueeCMS ERP' }}</div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                @if($reportType === 'sales' || $reportType === 'commission')
                    <table class="table table-hover align-middle mb-0 fs-12">
                        <thead class="bg-200">
                            <tr>
                                <th>Date</th>
                                <th>Sale #</th>
                                <th>Service Provider</th>
                                <th>Booking & Customer</th>
                                <th>Sale Amount</th>
                                <th>Commission %</th>
                                <th>Commission Income</th>
                                <th>Provider Net Payable</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $row)
                                <tr>
                                    <td>{{ $row->sale_date->format('d-M-Y') }}</td>
                                    <td class="fw-bold font-monospace text-primary">{{ $row->vendor_sale_number }}</td>
                                    <td class="fw-semibold">{{ $row->vendor->name ?? '—' }}</td>
                                    <td>
                                        @if($row->booking)
                                            <a href="{{ route('bookings.show', $row->booking_id) }}" class="badge badge-subtle-info text-decoration-none">
                                                <i class="fas fa-calendar-check me-1"></i>{{ $row->booking->booking_number }}
                                            </a>
                                            @if($row->booking->customer)
                                                <div class="text-dark fw-semi-bold fs-11 mt-1">
                                                    <i class="fas fa-user me-1 text-400"></i>{{ $row->booking->customer->name }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted">Direct Sale</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold">Rs. {{ number_format($row->sale_amount) }}</td>
                                    <td><span class="badge badge-subtle-success">{{ $row->commission_rate }}%</span></td>
                                    <td class="fw-bold text-success">Rs. {{ number_format($row->commission_amount) }}</td>
                                    <td class="fw-bold text-primary">Rs. {{ number_format($row->vendor_net_amount) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center py-4 text-muted">No records match the selected parameters.</td></tr>
                            @endforelse
                        </tbody>
                        @if(count($reportData) > 0)
                            <tfoot class="bg-light fw-bold">
                                <tr>
                                    <td colspan="4" class="text-end">TOTALS:</td>
                                    <td>Rs. {{ number_format($reportData->sum('sale_amount')) }}</td>
                                    <td>—</td>
                                    <td class="text-success">Rs. {{ number_format($reportData->sum('commission_amount')) }}</td>
                                    <td class="text-primary">Rs. {{ number_format($reportData->sum('vendor_net_amount')) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>

                @elseif($reportType === 'ledger')
                    <table class="table table-hover align-middle mb-0 fs-12">
                        <thead class="bg-200">
                            <tr>
                                <th>Date</th>
                                <th>Ref #</th>
                                <th>Service Provider</th>
                                <th>Booking & Customer</th>
                                <th>Description</th>
                                <th>Sale Credit</th>
                                <th>Commission Debit</th>
                                <th>Payout Amount</th>
                                <th>Running Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $row)
                                <tr>
                                    <td>{{ $row->transaction_date ? $row->transaction_date->format('d-M-Y') : '—' }}</td>
                                    <td class="fw-bold font-monospace text-primary">{{ $row->reference_number ?: '—' }}</td>
                                    <td class="fw-semibold">{{ $row->vendor->name ?? '—' }}</td>
                                    <td>
                                        @if($row->booking)
                                            <a href="{{ route('bookings.show', $row->booking_id) }}" class="badge badge-subtle-info text-decoration-none">
                                                <i class="fas fa-calendar-check me-1"></i>{{ $row->booking->booking_number }}
                                            </a>
                                            @if($row->booking->customer)
                                                <div class="text-dark fw-semi-bold fs-11 mt-1">
                                                    <i class="fas fa-user me-1 text-400"></i>{{ $row->booking->customer->name }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $row->description }}</td>
                                    <td class="fw-bold text-dark">{{ $row->sale_amount > 0 ? 'Rs. '.number_format($row->sale_amount) : '—' }}</td>
                                    <td class="fw-bold text-success">{{ $row->commission_amount > 0 ? 'Rs. '.number_format($row->commission_amount) : '—' }}</td>
                                    <td class="fw-bold text-primary">{{ $row->payment_amount > 0 ? 'Rs. '.number_format($row->payment_amount) : '—' }}</td>
                                    <td class="fw-bold text-danger">Rs. {{ number_format($row->running_balance) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center py-4 text-muted">No ledger transactions found.</td></tr>
                            @endforelse
                        </tbody>
                        @if(count($reportData) > 0)
                            <tfoot class="bg-light fw-bold">
                                <tr>
                                    <td colspan="5" class="text-end">TOTALS / CLOSING:</td>
                                    <td class="fw-bold text-dark">Rs. {{ number_format($reportData->sum('sale_amount')) }}</td>
                                    <td class="fw-bold text-success">Rs. {{ number_format($reportData->sum('commission_amount')) }}</td>
                                    <td class="fw-bold text-primary">Rs. {{ number_format($reportData->sum('payment_amount')) }}</td>
                                    <td class="fw-bold text-danger">
                                        @if($selectedVendor)
                                            Rs. {{ number_format($reportData->first()?->running_balance ?? 0) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>

                @elseif($reportType === 'settlement')
                    <table class="table table-hover align-middle mb-0 fs-12">
                        <thead class="bg-200">
                            <tr>
                                <th>Settlement Date</th>
                                <th>Settlement #</th>
                                <th>Service Provider</th>
                                <th>Net Payable</th>
                                <th>Paid Amount</th>
                                <th>Remaining Balance</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $row)
                                <tr>
                                    <td>{{ $row->settlement_date ? $row->settlement_date->format('d-M-Y') : '—' }}</td>
                                    <td class="fw-bold font-monospace text-primary">{{ $row->settlement_number }}</td>
                                    <td class="fw-semibold">{{ $row->vendor->name ?? '—' }}</td>
                                    <td class="fw-bold">Rs. {{ number_format($row->net_payable_amount) }}</td>
                                    <td class="fw-bold text-success">Rs. {{ number_format($row->paid_amount) }}</td>
                                    <td class="fw-bold text-danger">Rs. {{ number_format($row->remaining_balance) }}</td>
                                    <td><span class="badge badge-subtle-primary">{{ $row->payment_method }}</span></td>
                                    <td><span class="badge badge-subtle-success">{{ ucfirst($row->status) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center py-4 text-muted">No settlements recorded.</td></tr>
                            @endforelse
                        </tbody>
                        @if(count($reportData) > 0)
                            <tfoot class="bg-light fw-bold">
                                <tr>
                                    <td colspan="3" class="text-end">TOTALS:</td>
                                    <td class="fw-bold text-dark">Rs. {{ number_format($reportData->sum('net_payable_amount')) }}</td>
                                    <td class="fw-bold text-success">Rs. {{ number_format($reportData->sum('paid_amount')) }}</td>
                                    <td class="fw-bold text-danger">Rs. {{ number_format($reportData->sum('remaining_balance')) }}</td>
                                    <td colspan="2">—</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>

                @elseif($reportType === 'monthly')
                    <table class="table table-hover align-middle mb-0 fs-12">
                        <thead class="bg-200">
                            <tr>
                                <th>Service Provider</th>
                                <th>Event Sales Count</th>
                                <th>Total Sales Amount</th>
                                <th>Commission Income</th>
                                <th>Net Provider Payable</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $row)
                                <tr>
                                    <td class="fw-bold text-dark">{{ $row->vendor->name ?? '—' }}</td>
                                    <td class="fw-bold text-center">{{ number_format($row->total_events) }}</td>
                                    <td class="fw-bold text-dark">Rs. {{ number_format($row->total_sales) }}</td>
                                    <td class="fw-bold text-success">Rs. {{ number_format($row->total_commission) }}</td>
                                    <td class="fw-bold text-primary">Rs. {{ number_format($row->total_net_payable) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-4 text-muted">No monthly sales data found.</td></tr>
                            @endforelse
                        </tbody>
                        @if(count($reportData) > 0)
                            <tfoot class="bg-light fw-bold">
                                <tr>
                                    <td>TOTAL:</td>
                                    <td class="text-center">{{ number_format($reportData->sum('total_events')) }}</td>
                                    <td>Rs. {{ number_format($reportData->sum('total_sales')) }}</td>
                                    <td class="text-success">Rs. {{ number_format($reportData->sum('total_commission')) }}</td>
                                    <td class="text-primary">Rs. {{ number_format($reportData->sum('total_net_payable')) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>
