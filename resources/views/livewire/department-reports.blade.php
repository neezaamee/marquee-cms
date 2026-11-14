<div class="container-fluid p-0">
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm 10mm;
            }
            html, body, .main, [data-layout="container"], .container, .container-fluid, .content {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 9pt !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                height: auto !important;
                min-height: auto !important;
                overflow: visible !important;
                display: block !important;
                position: static !important;
            }
            .no-print,
            .navbar,
            .navbar-vertical,
            .navbar-top,
            footer,
            .btn,
            .card-header .btn,
            .pagination,
            nav[aria-label="Pagination Navigation"] {
                display: none !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
                page-break-inside: auto !important;
                break-inside: auto !important;
                background: transparent !important;
            }
            .card-header {
                display: none !important;
            }
            .card-body {
                padding: 0 !important;
                overflow: visible !important;
                page-break-inside: auto !important;
                break-inside: auto !important;
            }
            .table-responsive {
                overflow: visible !important;
                width: 100% !important;
                display: block !important;
                page-break-inside: auto !important;
                break-inside: auto !important;
            }
            table {
                width: 100% !important;
                font-size: 8.5pt !important;
                border-collapse: collapse !important;
                page-break-inside: auto !important;
                break-inside: auto !important;
            }
            thead {
                display: table-header-group !important;
            }
            thead th {
                background-color: #f1f3f5 !important;
                color: #000 !important;
                border-top: 1px solid #333 !important;
                border-bottom: 2px solid #333 !important;
                padding: 5px 6px !important;
            }
            tbody td {
                border-bottom: 1px solid #ddd !important;
                padding: 4px 6px !important;
            }
            tfoot {
                display: table-footer-group !important;
            }
            tfoot tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            tfoot td {
                border-top: 2px solid #333 !important;
                border-bottom: 2px solid #333 !important;
                font-weight: bold !important;
                background-color: #f8f9fa !important;
                padding: 5px 6px !important;
                color: #000 !important;
            }
            tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .badge {
                border: 1px solid #777 !important;
                background: transparent !important;
                color: #000 !important;
                font-weight: normal !important;
                padding: 1px 4px !important;
            }
            .print-letterhead {
                display: block !important;
                margin-bottom: 8px !important;
                border-bottom: 2px solid #1a1a1a !important;
                padding-bottom: 6px !important;
            }
            .print-summary-box {
                display: block !important;
                margin-bottom: 10px !important;
            }
            .print-summary-box table {
                border: 1px solid #999 !important;
            }
            .print-summary-box td {
                border: 1px solid #ccc !important;
                padding: 4px 8px !important;
                background: #f8f9fa !important;
            }
        }
        .print-letterhead,
        .print-summary-box {
            display: none;
        }
    </style>

    <!-- Executive Letterhead (Visible in Print Mode) -->
    <div class="print-letterhead">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h3 class="fw-bold mb-1 text-uppercase text-dark">{{ $marquee?->name ?? 'Marquee CMS' }}</h3>
                <div class="fs-11 text-muted">
                    {{ $marquee?->address ?? '' }}
                    @if($marquee?->phone) &bull; Phone: {{ $marquee->phone }} @endif
                    @if($marquee?->email) &bull; Email: {{ $marquee->email }} @endif
                </div>
            </div>
            <div class="text-end">
                <h4 class="fw-bold text-uppercase text-primary mb-1">
                    @if($reportType === 'consumption') Stock Movement Audit Report
                    @elseif($reportType === 'attendance') Attendance Audit Log
                    @elseif($reportType === 'production') Kitchen Production Log
                    @elseif($reportType === 'ledger_summary') Department Stock Balance Summary
                    @endif
                </h4>
                <div class="fs-11 text-muted">
                    Period: <strong>{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}</strong> to <strong>{{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</strong>
                </div>
                <div class="fs-11 text-muted">
                    Dept: <strong>{{ $filterDepartment ? ($departments->firstWhere('id', $filterDepartment)->name ?? 'Filtered') : 'All Departments' }}</strong>
                    &bull; Branch: <strong>{{ $filterBranch ? ($branches->firstWhere('id', $filterBranch)->name ?? 'Branch') : 'All Branches' }}</strong>
                </div>
                <div class="fs-11 text-muted">Printed On: {{ date('d M Y, h:i A') }} &bull; By: {{ auth()->user()->name }}</div>
            </div>
        </div>
    </div>

    <!-- Executive Summary Box (Visible in Print Mode) -->
    <div class="print-summary-box">
        <table class="table mb-0">
            <tbody>
                <tr>
                    @if($reportType === 'consumption')
                        <td class="text-center"><strong>Period Total Turnover:</strong> Rs. {{ number_format($periodTotalValue, 2) }}</td>
                        <td class="text-center"><strong>Period Stock Issued Value:</strong> Rs. {{ number_format($periodIssuedValue, 2) }}</td>
                        <td class="text-center"><strong>Total Units Moved:</strong> {{ number_format($periodTotalQty, 2) }}</td>
                        <td class="text-center"><strong>Total Records:</strong> {{ number_format($periodTotalRows) }}</td>
                    @elseif($reportType === 'ledger_summary')
                        <td class="text-center"><strong>Total Issued Value:</strong> Rs. {{ number_format($ledgerTotalIssuedValue, 2) }}</td>
                        <td class="text-center"><strong>Total Issued Qty:</strong> {{ number_format($ledgerTotalIssuedQty, 2) }}</td>
                        <td class="text-center"><strong>Total Consumed:</strong> {{ number_format($ledgerTotalConsumedQty, 2) }}</td>
                        <td class="text-center"><strong>Net Stock Balance:</strong> {{ number_format($ledgerNetBalance, 2) }}</td>
                    @elseif($reportType === 'production')
                        <td class="text-center"><strong>Total Batches:</strong> {{ $productionTotals->total_batches ?? 0 }}</td>
                        <td class="text-center"><strong>Total Produced Qty:</strong> {{ number_format($productionTotals->total_produced ?? 0, 2) }}</td>
                        <td class="text-center"><strong>Total Wastage Qty:</strong> {{ number_format($productionTotals->total_wastage ?? 0, 2) }}</td>
                    @elseif($reportType === 'attendance')
                        <td class="text-center"><strong>Total Attendance Logs:</strong> {{ $attendanceTotals->total_logs ?? 0 }}</td>
                        <td class="text-center"><strong>Present:</strong> {{ $attendanceTotals->present_count ?? 0 }}</td>
                        <td class="text-center"><strong>Absent:</strong> {{ $attendanceTotals->absent_count ?? 0 }}</td>
                        <td class="text-center"><strong>Late:</strong> {{ $attendanceTotals->late_count ?? 0 }}</td>
                    @endif
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Screen Header (Hidden on Print) -->
    <div class="row mb-3 align-items-center justify-content-between no-print">
        <div class="col-auto">
            <h3 class="mb-0 text-secondary">
                <span class="fas fa-file-alt me-2 text-primary"></span>Department Analytical Reports
            </h3>
            <p class="text-600 fs-10 mb-0">Generate stock issues, consumption, attendance, and production audit reports</p>
        </div>
        <div class="col-auto d-flex gap-2">
            <button wire:click="exportCsv" wire:loading.attr="disabled" class="btn btn-outline-success btn-sm">
                <span wire:loading.remove wire:target="exportCsv"><span class="fas fa-file-csv me-1"></span>Export CSV</span>
                <span wire:loading wire:target="exportCsv"><span class="spinner-border spinner-border-sm me-1"></span>Exporting...</span>
            </button>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                <span class="fas fa-print me-1"></span>Print View
            </button>
        </div>
    </div>

    <!-- Filters Card (Hidden on Print) -->
    <div class="card border border-200 mb-3 no-print">
        <div class="card-body p-3 bg-light">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <label class="form-label fs-11 text-uppercase fw-semi-bold mb-1">Report Type</label>
                    <select wire:model.live="reportType" class="form-select form-select-sm">
                        <option value="consumption">Stock Issue & Movement Log</option>
                        <option value="attendance">Attendance Audit Log</option>
                        <option value="production">Kitchen Production Log</option>
                        <option value="ledger_summary">Department Stock Balance Summary</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11 text-uppercase fw-semi-bold mb-1">From Date</label>
                    <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11 text-uppercase fw-semi-bold mb-1">To Date</label>
                    <input type="date" wire:model.live="dateTo" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11 text-uppercase fw-semi-bold mb-1">Department</label>
                    <select wire:model.live="filterDepartment" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->department_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11 text-uppercase fw-semi-bold mb-1">Branch</label>
                    <select wire:model.live="filterBranch" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label fs-11 text-uppercase fw-semi-bold mb-1">Rows</label>
                    <select wire:model.live="perPage" class="form-select form-select-sm" title="Rows to display and print">
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="all">All</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards (Screen Only - Print has Dedicated Executive Summary Box) -->
    @if(in_array($reportType, ['consumption', 'ledger_summary']))
        <div class="row g-2 mb-3 no-print">
            <div class="col-md-6 col-lg-3">
                <div class="card border border-200 bg-white h-100 shadow-none">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-uppercase text-muted fs-11 fw-bold mb-1">Today's Stock Issued Value</h6>
                                <h4 class="mb-0 text-primary font-monospace fw-bold">Rs. {{ number_format($todayIssuedValue, 2) }}</h4>
                                <small class="text-muted fs-11">{{ number_format($todayIssuedQty, 2) }} units dispatched today</small>
                            </div>
                            <div class="avatar avatar-m bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-calendar-day fs-8"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card border border-200 bg-white h-100 shadow-none">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-uppercase text-muted fs-11 fw-bold mb-1">Period Total Issued Value</h6>
                                <h4 class="mb-0 text-success font-monospace fw-bold">Rs. {{ number_format($periodIssuedValue, 2) }}</h4>
                                <small class="text-muted fs-11">{{ number_format($periodIssuedQty, 2) }} units issued in range</small>
                            </div>
                            <div class="avatar avatar-m bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-money-bill-wave fs-8"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card border border-200 bg-white h-100 shadow-none">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-uppercase text-muted fs-11 fw-bold mb-1">Period Total Turnover Value</h6>
                                <h4 class="mb-0 text-dark font-monospace fw-bold">Rs. {{ number_format($periodTotalValue, 2) }}</h4>
                                <small class="text-muted fs-11">{{ number_format($periodTotalQty, 2) }} units total ({{ number_format($periodTotalRows) }} rows)</small>
                            </div>
                            <div class="avatar avatar-m bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-boxes fs-8"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card border border-200 bg-white h-100 shadow-none">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-uppercase text-muted fs-11 fw-bold mb-1">Scope & Filter</h6>
                                <h5 class="mb-0 text-dark fw-bold">
                                    {{ $filterDepartment ? ($departments->firstWhere('id', $filterDepartment)->name ?? 'Filtered Dept') : 'All Departments' }}
                                </h5>
                                <small class="text-muted fs-11">{{ $filterBranch ? ($branches->firstWhere('id', $filterBranch)->name ?? 'Branch') : 'All Branches' }}</small>
                            </div>
                            <div class="avatar avatar-m bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-warehouse fs-8"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @elseif($reportType === 'production')
        <div class="row g-2 mb-3 no-print">
            <div class="col-md-4">
                <div class="card border border-200 bg-white h-100 shadow-none">
                    <div class="card-body p-3">
                        <h6 class="text-uppercase text-muted fs-11 fw-bold mb-1">Total Batches Produced</h6>
                        <h4 class="mb-0 text-primary font-monospace fw-bold">{{ $productionTotals->total_batches ?? 0 }}</h4>
                        <small class="text-muted fs-11">batches in selected period</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border border-200 bg-white h-100 shadow-none">
                    <div class="card-body p-3">
                        <h6 class="text-uppercase text-muted fs-11 fw-bold mb-1">Total Produced Quantity</h6>
                        <h4 class="mb-0 text-success font-monospace fw-bold">{{ number_format($productionTotals->total_produced ?? 0, 2) }}</h4>
                        <small class="text-muted fs-11">units produced</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border border-200 bg-white h-100 shadow-none">
                    <div class="card-body p-3">
                        <h6 class="text-uppercase text-muted fs-11 fw-bold mb-1">Total Kitchen Wastage</h6>
                        <h4 class="mb-0 text-danger font-monospace fw-bold">{{ number_format($productionTotals->total_wastage ?? 0, 2) }}</h4>
                        <small class="text-muted fs-11">units wasted</small>
                    </div>
                </div>
            </div>
        </div>
    @elseif($reportType === 'attendance')
        <div class="row g-2 mb-3 no-print">
            <div class="col-md-3">
                <div class="card border border-200 bg-white h-100 shadow-none">
                    <div class="card-body p-3">
                        <h6 class="text-uppercase text-muted fs-11 fw-bold mb-1">Total Attendance Logs</h6>
                        <h4 class="mb-0 text-primary font-monospace fw-bold">{{ $attendanceTotals->total_logs ?? 0 }}</h4>
                        <small class="text-muted fs-11">logs recorded</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border border-200 bg-white h-100 shadow-none">
                    <div class="card-body p-3">
                        <h6 class="text-uppercase text-muted fs-11 fw-bold mb-1">Present Count</h6>
                        <h4 class="mb-0 text-success font-monospace fw-bold">{{ $attendanceTotals->present_count ?? 0 }}</h4>
                        <small class="text-muted fs-11">staff present</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border border-200 bg-white h-100 shadow-none">
                    <div class="card-body p-3">
                        <h6 class="text-uppercase text-muted fs-11 fw-bold mb-1">Absent Count</h6>
                        <h4 class="mb-0 text-danger font-monospace fw-bold">{{ $attendanceTotals->absent_count ?? 0 }}</h4>
                        <small class="text-muted fs-11">staff absent</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border border-200 bg-white h-100 shadow-none">
                    <div class="card-body p-3">
                        <h6 class="text-uppercase text-muted fs-11 fw-bold mb-1">Late Arrivals</h6>
                        <h4 class="mb-0 text-warning font-monospace fw-bold">{{ $attendanceTotals->late_count ?? 0 }}</h4>
                        <small class="text-muted fs-11">late check-ins</small>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Report Table Container -->
    <div class="card border border-200">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-700">
                @if($reportType === 'consumption') Stock Movement Audit Report
                @elseif($reportType === 'attendance') Attendance Log Report
                @elseif($reportType === 'production') Kitchen Production Log Report
                @elseif($reportType === 'ledger_summary') Department Stock Balance Summary
                @endif
            </h5>
            <span class="fs-11 text-muted">Showing data from {{ $dateFrom }} to {{ $dateTo }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                @if($reportType === 'consumption')
                    <table class="table table-sm table-striped mb-0 align-middle">
                        <thead class="bg-200">
                            <tr>
                                <th class="ps-3">Date</th>
                                <th>Department</th>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Type</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Unit Rate (Rs.)</th>
                                <th class="text-end">Total Value (Rs.)</th>
                                <th class="pe-3">Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $row)
                                <tr>
                                    <td class="ps-3 font-monospace">{{ $row->transaction_date ? $row->transaction_date->format('Y-m-d') : '—' }}</td>
                                    <td class="fw-semi-bold">{{ $row->department->name ?? '—' }}</td>
                                    <td class="font-monospace">{{ $row->inventoryItem->item_code ?? '—' }}</td>
                                    <td>{{ $row->inventoryItem->name ?? '—' }}</td>
                                    <td>
                                        @if($row->transaction_type === 'Issue')
                                            <span class="badge bg-primary-subtle text-primary">Issue</span>
                                        @elseif($row->transaction_type === 'Return')
                                            <span class="badge bg-info-subtle text-info">Return</span>
                                        @elseif($row->transaction_type === 'Consumption')
                                            <span class="badge bg-warning-subtle text-warning">Consumed</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Wastage</span>
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace fw-bold">{{ number_format($row->qty_in > 0 ? $row->qty_in : $row->qty_out, 2) }}</td>
                                    <td class="text-end font-monospace text-muted">{{ number_format($row->unit_price, 2) }}</td>
                                    <td class="text-end font-monospace fw-bold {{ $row->transaction_type === 'Issue' ? 'text-primary' : ($row->transaction_type === 'Return' ? 'text-info' : 'text-secondary') }}">
                                        {{ number_format($row->total_cost, 2) }}
                                    </td>
                                    <td class="pe-3 font-monospace text-muted">{{ $row->reference_type ? (str_replace(['App\\Models\\', 'DepartmentStock'], '', $row->reference_type) . ' #' . $row->reference_id) : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No stock movement records found for the selected period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-200 fw-bold">
                            <tr>
                                <td colspan="5" class="text-end ps-3 fw-bold">FILTERED TOTAL:</td>
                                <td class="text-end font-monospace fw-bold">{{ number_format($periodTotalQty, 2) }}</td>
                                <td class="text-end text-muted font-monospace">—</td>
                                <td class="text-end font-monospace fw-bold text-primary">Rs. {{ number_format($periodTotalValue, 2) }}</td>
                                <td class="pe-3 font-monospace text-muted">{{ number_format($periodTotalRows) }} rows</td>
                            </tr>
                        </tfoot>
                    </table>

                @elseif($reportType === 'attendance')
                    <table class="table table-sm table-striped mb-0 align-middle">
                        <thead class="bg-200">
                            <tr>
                                <th class="ps-3">Date</th>
                                <th>Department</th>
                                <th>Emp Code</th>
                                <th>Employee Name</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $row)
                                <tr>
                                    <td class="ps-3 font-monospace">{{ $row->date ? $row->date->format('Y-m-d') : '—' }}</td>
                                    <td class="fw-semi-bold">{{ $row->department->name ?? '—' }}</td>
                                    <td class="font-monospace">{{ $row->employee->employee_id ?? '—' }}</td>
                                    <td class="fw-semi-bold">{{ $row->employee->name ?? '—' }}</td>
                                    <td>{{ $row->check_in ?? '—' }}</td>
                                    <td>{{ $row->check_out ?? '—' }}</td>
                                    <td>
                                        @if($row->status === 'Present')
                                            <span class="badge bg-success-subtle text-success">Present</span>
                                        @elseif($row->status === 'Absent')
                                            <span class="badge bg-danger-subtle text-danger">Absent</span>
                                        @elseif($row->status === 'Late')
                                            <span class="badge bg-warning-subtle text-warning">Late</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info">{{ $row->status }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No attendance logs found for the selected period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-200 fw-bold">
                            <tr>
                                <td colspan="4" class="text-end ps-3 fw-bold">SUMMARY TOTALS:</td>
                                <td colspan="3" class="pe-3 fs-11">
                                    <span class="badge bg-success-subtle text-success me-1">Present: {{ $attendanceTotals->present_count ?? 0 }}</span>
                                    <span class="badge bg-danger-subtle text-danger me-1">Absent: {{ $attendanceTotals->absent_count ?? 0 }}</span>
                                    <span class="badge bg-warning-subtle text-warning me-1">Late: {{ $attendanceTotals->late_count ?? 0 }}</span>
                                    <span class="badge bg-secondary-subtle text-secondary">Total: {{ $attendanceTotals->total_logs ?? 0 }}</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    <table class="table table-sm table-striped mb-0 align-middle">
                        <thead class="bg-200">
                            <tr>
                                <th class="ps-3">Batch No</th>
                                <th>Date</th>
                                <th>Department</th>
                                <th>Recipe</th>
                                <th class="text-end">Produced Qty</th>
                                <th class="text-end">Wastage Qty</th>
                                <th>Prepared By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $row)
                                <tr>
                                    <td class="ps-3 font-monospace fw-bold">{{ $row->batch_number }}</td>
                                    <td>{{ $row->production_date ? $row->production_date->format('Y-m-d') : '—' }}</td>
                                    <td class="fw-semi-bold">{{ $row->department->name ?? '—' }}</td>
                                    <td>{{ $row->recipe->name ?? '—' }}</td>
                                    <td class="text-end font-monospace text-success fw-bold">{{ number_format($row->produced_qty, 2) }}</td>
                                    <td class="text-end font-monospace text-danger">{{ number_format($row->wastage_qty, 2) }}</td>
                                    <td>{{ $row->prepStaff->name ?? 'System' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No kitchen production batches found for the selected period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-200 fw-bold">
                            <tr>
                                <td colspan="4" class="text-end ps-3 fw-bold">FILTERED TOTAL:</td>
                                <td class="text-end font-monospace text-success fw-bold">{{ number_format($productionTotals->total_produced ?? 0, 2) }}</td>
                                <td class="text-end font-monospace text-danger fw-bold">{{ number_format($productionTotals->total_wastage ?? 0, 2) }}</td>
                                <td class="pe-3 font-monospace text-muted">{{ $productionTotals->total_batches ?? 0 }} batches</td>
                            </tr>
                        </tfoot>
                    </table>

                @elseif($reportType === 'ledger_summary')
                    <table class="table table-sm table-striped mb-0 align-middle">
                        <thead class="bg-200">
                            <tr>
                                <th class="ps-3">Department</th>
                                <th>Inventory Item</th>
                                <th class="text-end">Total Issued</th>
                                <th class="text-end">Total Returned</th>
                                <th class="text-end">Total Consumed</th>
                                <th class="text-end">Total Wastage</th>
                                <th class="text-end">Net Balance</th>
                                <th class="text-end pe-3">Issued Value (Rs.)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $row)
                                @php
                                    $netBalance = $row->total_issued - $row->total_returned - $row->total_consumed - $row->total_wastage;
                                @endphp
                                <tr>
                                    <td class="ps-3 fw-semi-bold">{{ $row->department->name ?? '—' }}</td>
                                    <td>{{ $row->inventoryItem->name ?? '—' }}</td>
                                    <td class="text-end font-monospace text-primary">{{ number_format($row->total_issued, 2) }}</td>
                                    <td class="text-end font-monospace text-info">{{ number_format($row->total_returned, 2) }}</td>
                                    <td class="text-end font-monospace text-warning">{{ number_format($row->total_consumed, 2) }}</td>
                                    <td class="text-end font-monospace text-danger">{{ number_format($row->total_wastage, 2) }}</td>
                                    <td class="text-end font-monospace fw-bold {{ $netBalance > 0 ? 'text-success' : 'text-muted' }}">
                                        {{ number_format($netBalance, 2) }}
                                    </td>
                                    <td class="text-end pe-3 font-monospace fw-bold text-primary">
                                        {{ number_format($row->total_issued_value, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No stock balance data found for the selected period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-200 fw-bold">
                            <tr>
                                <td colspan="2" class="text-end ps-3 fw-bold">FILTERED TOTAL:</td>
                                <td class="text-end font-monospace text-primary fw-bold">{{ number_format($ledgerTotalIssuedQty, 2) }}</td>
                                <td class="text-end font-monospace text-info fw-bold">{{ number_format($ledgerTotalReturnedQty, 2) }}</td>
                                <td class="text-end font-monospace text-warning fw-bold">{{ number_format($ledgerTotalConsumedQty, 2) }}</td>
                                <td class="text-end font-monospace text-danger fw-bold">{{ number_format($ledgerTotalWastageQty, 2) }}</td>
                                <td class="text-end font-monospace fw-bold {{ $ledgerNetBalance > 0 ? 'text-success' : 'text-muted' }}">{{ number_format($ledgerNetBalance, 2) }}</td>
                                <td class="text-end pe-3 font-monospace fw-bold text-primary">Rs. {{ number_format($ledgerTotalIssuedValue, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </div>
        </div>
        @if($reportData && $reportData->hasPages())
            <div class="card-footer bg-light p-2 no-print">
                {{ $reportData->links() }}
            </div>
        @endif
    </div>
</div>
