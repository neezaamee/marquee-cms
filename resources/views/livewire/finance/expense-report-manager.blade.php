<div class="row g-3">
    <!-- Sidebar Selector Pane -->
    <div class="col-lg-3 d-print-none">
        <div class="card h-100 shadow-none border">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 fs-11"><span class="fas fa-file-invoice me-2 text-primary"></span>Report Categories</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush font-sans-serif fs-11">
                    <button wire:click="$set('reportType', 'category_summary')" class="list-group-item list-group-item-action {{ $reportType === 'category_summary' ? 'active' : '' }} d-flex align-items-center gap-2 py-2">
                        <span class="fas fa-chart-pie"></span> Category-Wise Summary
                    </button>
                    <button wire:click="$set('reportType', 'payment_methods')" class="list-group-item list-group-item-action {{ $reportType === 'payment_methods' ? 'active' : '' }} d-flex align-items-center gap-2 py-2">
                        <span class="fas fa-credit-card"></span> Payment Method Breakdown
                    </button>
                    <button wire:click="$set('reportType', 'register')" class="list-group-item list-group-item-action {{ $reportType === 'register' ? 'active' : '' }} d-flex align-items-center gap-2 py-2">
                        <span class="fas fa-list-ol"></span> Detailed Register Log
                    </button>
                    <button wire:click="$set('reportType', 'budget_vs_actual')" class="list-group-item list-group-item-action {{ $reportType === 'budget_vs_actual' ? 'active' : '' }} d-flex align-items-center gap-2 py-2">
                        <span class="fas fa-balance-scale"></span> Budget vs Actuals
                    </button>
                    <button wire:click="$set('reportType', 'utility')" class="list-group-item list-group-item-action {{ $reportType === 'utility' ? 'active' : '' }} d-flex align-items-center gap-2 py-2">
                        <span class="fas fa-bolt"></span> Utility Service Billings
                    </button>
                    <button wire:click="$set('reportType', 'tax_summary')" class="list-group-item list-group-item-action {{ $reportType === 'tax_summary' ? 'active' : '' }} d-flex align-items-center gap-2 py-2">
                        <span class="fas fa-percentage"></span> Tax Summaries
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Output Pane -->
    <div class="col-lg-9">
        <!-- Criteria Filters -->
        <div class="card mb-3 shadow-none border d-print-none">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 fs-11"><span class="fas fa-filter me-2 text-primary"></span>Report Criteria Filters</h6>
            </div>
            <div class="card-body p-3">
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label fs-11" for="rep_br">Branch Scope</label>
                        <select wire:model="branch_id" class="form-select form-select-sm" id="rep_br">
                            <option value="">Company-Wide (All Branches)</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($reportType === 'register' || $reportType === 'budget_vs_actual')
                        <div class="col-md-4">
                            <label class="form-label fs-11" for="rep_cat">Expense Category</label>
                            <select wire:model="expense_category_id" class="form-select form-select-sm" id="rep_cat">
                                <option value="">All Categories (26 Marquee Types)</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($reportType === 'register')
                        <div class="col-md-4">
                            <label class="form-label fs-11" for="rep_sup">Payee / Vendor</label>
                            <select wire:model="supplier_id" class="form-select form-select-sm" id="rep_sup">
                                <option value="">All Vendors & Payees</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($reportType === 'budget_vs_actual')
                        <div class="col-md-4">
                            <label class="form-label fs-11" for="rep_yr">Budget Year</label>
                            <input wire:model="year" type="number" class="form-control form-control-sm" id="rep_yr">
                        </div>
                    @else
                        <div class="col-md-4">
                            <label class="form-label fs-11" for="rep_sdt">Start Date</label>
                            <input wire:model="start_date" type="date" class="form-control form-control-sm" id="rep_sdt">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fs-11" for="rep_edt">End Date</label>
                            <input wire:model="end_date" type="date" class="form-control form-control-sm" id="rep_edt">
                        </div>
                    @endif

                    <div class="col-12 mt-3 text-end">
                        <button wire:click="generateReport" wire:loading.attr="disabled" class="btn btn-primary btn-sm px-3 me-2">
                            <span wire:loading.remove wire:target="generateReport"><span class="fas fa-play me-1"></span>Apply Filters</span>
                            <span wire:loading wire:target="generateReport"><span class="spinner-border spinner-border-sm me-1"></span>Loading...</span>
                        </button>
                        <button wire:click="exportCSV" class="btn btn-falcon-default btn-sm px-3 me-2">
                            <span class="fas fa-file-csv me-1"></span>Export CSV
                        </button>
                        <button onclick="window.print()" class="btn btn-falcon-default btn-sm px-3">
                            <span class="fas fa-print me-1"></span>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary KPI Cards -->
        <div class="row g-2 mb-3">
            <div class="col-sm-4">
                <div class="card shadow-none border p-2">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <span class="text-muted fs-11 text-uppercase">Total Reported Spend</span>
                            <h5 class="fw-bold font-monospace text-primary mb-0">{{ number_format($reportTotal, 2) }} PKR</h5>
                        </div>
                        <div class="icon-item icon-item-sm bg-subtle-primary text-primary rounded-circle"><span class="fas fa-coins"></span></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card shadow-none border p-2">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <span class="text-muted fs-11 text-uppercase">Total Items / Records</span>
                            <h5 class="fw-bold font-monospace text-success mb-0">{{ $reportCount }}</h5>
                        </div>
                        <div class="icon-item icon-item-sm bg-subtle-success text-success rounded-circle"><span class="fas fa-list"></span></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card shadow-none border p-2">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <span class="text-muted fs-11 text-uppercase">Average per Item</span>
                            <h5 class="fw-bold font-monospace text-info mb-0">{{ $reportCount > 0 ? number_format($reportTotal / $reportCount, 2) : '0.00' }} PKR</h5>
                        </div>
                        <div class="icon-item icon-item-sm bg-subtle-info text-info rounded-circle"><span class="fas fa-calculator"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Results Card -->
        <div class="card shadow-none border d-print-block">
            <div class="card-header bg-light border-bottom py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fs-11"><span class="fas fa-table me-2 text-primary"></span>Report Results</h6>
                <span class="text-muted fs-11 font-monospace">Generated: {{ now()->format('d M Y, h:i A') }}</span>
            </div>
            
            <div class="card-body p-0">
                @if(!empty($reportData))
                    <div class="table-responsive">
                        <!-- 1. Category Summary -->
                        @if($reportType === 'category_summary')
                            <table class="table table-sm table-striped fs-10 align-middle mb-0">
                                <thead class="bg-200">
                                    <tr>
                                        <th class="px-3">Category Head</th>
                                        <th class="text-center" style="width: 15%;">Voucher Count</th>
                                        <th class="text-end" style="width: 25%;">Total Amount (PKR)</th>
                                        <th style="width: 30%;">Expenditure Share</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData as $row)
                                        <tr>
                                            <td class="px-3 fw-semi-bold">{{ $row['category_name'] }}</td>
                                            <td class="text-center font-monospace">{{ $row['voucher_count'] }}</td>
                                            <td class="text-end fw-bold font-monospace">{{ number_format($row['total_amount'], 2) }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress me-2 flex-grow-1" style="height: 6px;">
                                                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $row['percentage'] }}%;"></div>
                                                    </div>
                                                    <span class="font-monospace fs-11 fw-semi-bold">{{ $row['percentage'] }}%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-100 fw-bold">
                                    <tr>
                                        <td class="px-3">Total Consolidated</td>
                                        <td class="text-center font-monospace">{{ array_sum(array_column($reportData, 'voucher_count')) }}</td>
                                        <td class="text-end font-monospace text-primary">{{ number_format($reportTotal, 2) }}</td>
                                        <td>100%</td>
                                    </tr>
                                </tfoot>
                            </table>

                        <!-- 2. Payment Methods -->
                        @elseif($reportType === 'payment_methods')
                            <table class="table table-sm table-striped fs-10 align-middle mb-0">
                                <thead class="bg-200">
                                    <tr>
                                        <th class="px-3">Payment Channel</th>
                                        <th class="text-center" style="width: 15%;">Transactions</th>
                                        <th class="text-end" style="width: 25%;">Total Disbursed (PKR)</th>
                                        <th style="width: 30%;">Payment Ratio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData as $row)
                                        <tr>
                                            <td class="px-3 fw-semi-bold">
                                                @if($row['payment_method'] === 'Cash')
                                                    <span class="fas fa-money-bill text-success me-1"></span> Cash in Hand
                                                @elseif($row['payment_method'] === 'Bank')
                                                    <span class="fas fa-university text-primary me-1"></span> Bank Transfer / Cheque
                                                @elseif($row['payment_method'] === 'Petty Cash')
                                                    <span class="fas fa-wallet text-info me-1"></span> Petty Cash Drawer
                                                @else
                                                    <span class="fas fa-handshake text-warning me-1"></span> Accounts Payable (Credit)
                                                @endif
                                            </td>
                                            <td class="text-center font-monospace">{{ $row['voucher_count'] }}</td>
                                            <td class="text-end fw-bold font-monospace">{{ number_format($row['total_amount'], 2) }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress me-2 flex-grow-1" style="height: 6px;">
                                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $row['percentage'] }}%;"></div>
                                                    </div>
                                                    <span class="font-monospace fs-11 fw-semi-bold">{{ $row['percentage'] }}%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-100 fw-bold">
                                    <tr>
                                        <td class="px-3">Total Disbursed</td>
                                        <td class="text-center font-monospace">{{ array_sum(array_column($reportData, 'voucher_count')) }}</td>
                                        <td class="text-end font-monospace text-primary">{{ number_format($reportTotal, 2) }}</td>
                                        <td>100%</td>
                                    </tr>
                                </tfoot>
                            </table>

                        <!-- 3. Register -->
                        @elseif($reportType === 'register')
                            <table class="table table-sm table-striped fs-10 align-middle mb-0">
                                <thead class="bg-200">
                                    <tr>
                                        <th class="px-3">Voucher No</th>
                                        <th>Date</th>
                                        <th>Branch</th>
                                        <th>Category</th>
                                        <th>Payee</th>
                                        <th>Method</th>
                                        <th class="text-end">Amount (PKR)</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData as $row)
                                        <tr>
                                            <td class="px-3 fw-bold">{{ $row['expense_number'] }}</td>
                                            <td>{{ date('Y-m-d', strtotime($row['expense_date'])) }}</td>
                                            <td>{{ $row['branch']['name'] ?? 'Main' }}</td>
                                            <td>{{ $row['category']['name'] ?? 'Split' }}</td>
                                            <td>{{ $row['supplier']['name'] ?? '—' }}</td>
                                            <td>{{ $row['payment_method'] }}</td>
                                            <td class="text-end fw-bold font-monospace">{{ number_format($row['total_amount'], 2) }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-subtle-success rounded-pill">{{ $row['status'] }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                        <!-- 4. Budget vs Actual -->
                        @elseif($reportType === 'budget_vs_actual')
                            <table class="table table-sm table-striped fs-10 align-middle mb-0">
                                <thead class="bg-200">
                                    <tr>
                                        <th class="px-3">Category</th>
                                        <th>Scope</th>
                                        <th>Period</th>
                                        <th class="text-end">Allocated (PKR)</th>
                                        <th class="text-end">Consumed (PKR)</th>
                                        <th class="text-end">Variance (PKR)</th>
                                        <th class="text-center">Ratio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData as $row)
                                        @php
                                            $diff = (float)$row['allocated_amount'] - (float)$row['consumed_amount'];
                                            $ratio = $row['allocated_amount'] > 0 ? ($row['consumed_amount'] / $row['allocated_amount']) * 100 : 0;
                                        @endphp
                                        <tr>
                                            <td class="px-3 fw-semi-bold">{{ $row['category']['name'] ?? '—' }}</td>
                                            <td>{{ $row['branch']['name'] ?? 'Company-Wide' }}</td>
                                            <td>{{ $row['month'] ? date('F', mktime(0, 0, 0, $row['month'], 10)) . ' ' . $row['year'] : 'Annual ' . $row['year'] }}</td>
                                            <td class="text-end font-monospace">{{ number_format($row['allocated_amount'], 2) }}</td>
                                            <td class="text-end font-monospace">{{ number_format($row['consumed_amount'], 2) }}</td>
                                            <td class="text-end font-monospace fw-bold {{ $diff < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($diff, 2) }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-subtle-{{ $ratio >= 100 ? 'danger' : ($ratio >= 80 ? 'warning' : 'success') }}">{{ number_format($ratio, 0) }}%</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                        <!-- 5. Utility -->
                        @elseif($reportType === 'utility')
                            <table class="table table-sm table-striped fs-10 align-middle mb-0">
                                <thead class="bg-200">
                                    <tr>
                                        <th class="px-3">Voucher</th>
                                        <th>Service Type</th>
                                        <th>Consumer #</th>
                                        <th>Billing Period</th>
                                        <th class="text-end">Amount (PKR)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData as $row)
                                        <tr>
                                            <td class="px-3 fw-bold">{{ $row['expense_number'] }}</td>
                                            <td>{{ $row['utility_bill']['utility_type'] ?? 'Utility' }}</td>
                                            <td>{{ $row['utility_bill']['consumer_number'] ?? '—' }}</td>
                                            <td>{{ $row['utility_bill']['billing_period'] ?? '—' }}</td>
                                            <td class="text-end fw-bold font-monospace">{{ number_format($row['total_amount'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                        <!-- 6. Tax Summary -->
                        @elseif($reportType === 'tax_summary')
                            <table class="table table-sm table-striped fs-10 align-middle mb-0">
                                <thead class="bg-200">
                                    <tr>
                                        <th class="px-3">Voucher</th>
                                        <th>Date</th>
                                        <th>Reference</th>
                                        <th class="text-end">Tax Base</th>
                                        <th class="text-end">Tax Amount</th>
                                        <th class="text-end">Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData as $row)
                                        <tr>
                                            <td class="px-3 fw-bold">{{ $row['expense_number'] }}</td>
                                            <td>{{ date('Y-m-d', strtotime($row['expense_date'])) }}</td>
                                            <td>{{ $row['reference_number'] ?? '—' }}</td>
                                            <td class="text-end font-monospace">{{ number_format($row['amount'], 2) }}</td>
                                            <td class="text-end font-monospace text-danger fw-bold">{{ number_format($row['tax_amount'], 2) }}</td>
                                            <td class="text-end font-monospace fw-bold">{{ number_format($row['total_amount'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                @else
                    <div class="text-center py-5 text-muted">
                        <span class="fas fa-file-invoice fa-2x mb-2 d-block"></span>
                        No records match the selected report criteria.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
