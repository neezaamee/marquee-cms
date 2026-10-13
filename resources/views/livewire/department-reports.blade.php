<div class="container-fluid p-0">
    <!-- Header -->
    <div class="row mb-3 align-items-center justify-content-between">
        <div class="col-auto">
            <h3 class="mb-0 text-secondary">
                <span class="fas fa-file-alt me-2 text-primary"></span>Department Analytical Reports
            </h3>
            <p class="text-600 fs-10 mb-0">Generate stock issues, consumption, attendance, and production audit reports</p>
        </div>
        <div class="col-auto d-flex gap-2">
            <button wire:click="exportCsv" class="btn btn-outline-success btn-sm">
                <span class="fas fa-file-csv me-1"></span>Export CSV
            </button>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                <span class="fas fa-print me-1"></span>Print View
            </button>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card border border-200 mb-3">
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
                <div class="col-md-3">
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
            </div>
        </div>
    </div>

    <!-- Stock Valuation Summary Cards (shown for stock-related reports) -->
    @if(in_array($reportType, ['consumption', 'ledger_summary']))
        <div class="row g-2 mb-3">
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
                                <small class="text-muted fs-11">{{ number_format($periodIssuedQty, 2) }} units in date range</small>
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
                                <h6 class="text-uppercase text-muted fs-11 fw-bold mb-1">Selected Department</h6>
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
            <div class="col-md-6 col-lg-3">
                <div class="card border border-200 bg-white h-100 shadow-none">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-uppercase text-muted fs-11 fw-bold mb-1">Valuation Basis</h6>
                                <div class="badge bg-secondary-subtle text-secondary fw-semi-bold">Default Purchase Rate</div>
                                <div class="text-muted fs-11 mt-1">Calculated via Purchase Rates</div>
                            </div>
                            <div class="avatar avatar-m bg-secondary-subtle text-secondary rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-calculator fs-8"></span>
                            </div>
                        </div>
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
                                    <td class="ps-3 font-monospace">{{ $row->transaction_date->format('Y-m-d') }}</td>
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
                                    <td class="ps-3 font-monospace">{{ $row->date->format('Y-m-d') }}</td>
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
                    </table>

                @elseif($reportType === 'production')
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
                                    <td>{{ $row->production_date->format('Y-m-d') }}</td>
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
                    </table>
                @endif
            </div>
        </div>
        @if($reportData && $reportData->hasPages())
            <div class="card-footer bg-light p-2">
                {{ $reportData->links() }}
            </div>
        @endif
    </div>
</div>
