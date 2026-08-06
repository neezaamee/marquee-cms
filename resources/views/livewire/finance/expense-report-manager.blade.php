<div class="row g-3">
    <!-- Sidebar Selector Pane -->
    <div class="col-lg-3">
        <div class="card h-100">
            <div class="card-header bg-light">
                <h6 class="mb-0"><span class="fas fa-file-invoice me-2 text-primary"></span>Report Categories</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush font-sans-serif fs-11">
                    <button wire:click="$set('reportType', 'register')" class="list-group-item list-group-item-action {{ $reportType === 'register' ? 'active' : '' }} d-flex align-items-center gap-2">
                        <span class="fas fa-list-ol"></span> Expense Register Log
                    </button>
                    <button wire:click="$set('reportType', 'budget_vs_actual')" class="list-group-item list-group-item-action {{ $reportType === 'budget_vs_actual' ? 'active' : '' }} d-flex align-items-center gap-2">
                        <span class="fas fa-balance-scale"></span> Budget vs Actuals
                    </button>
                    <button wire:click="$set('reportType', 'utility')" class="list-group-item list-group-item-action {{ $reportType === 'utility' ? 'active' : '' }} d-flex align-items-center gap-2">
                        <span class="fas fa-bolt"></span> Utility Service Billings
                    </button>
                    <button wire:click="$set('reportType', 'maintenance')" class="list-group-item list-group-item-action {{ $reportType === 'maintenance' ? 'active' : '' }} d-flex align-items-center gap-2">
                        <span class="fas fa-tools"></span> Equipment Maintenances
                    </button>
                    <button wire:click="$set('reportType', 'tax_summary')" class="list-group-item list-group-item-action {{ $reportType === 'tax_summary' ? 'active' : '' }} d-flex align-items-center gap-2">
                        <span class="fas fa-percentage"></span> Tax Summaries
                    </button>
                    <button wire:click="$set('reportType', 'cost_center')" class="list-group-item list-group-item-action {{ $reportType === 'cost_center' ? 'active' : '' }} d-flex align-items-center gap-2">
                        <span class="fas fa-layer-group"></span> Cost Center Expenditures
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Output Pane -->
    <div class="col-lg-9">
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h5 class="mb-0"><span class="fas fa-filter me-2 text-primary"></span>Report Criteria Filters</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @if($reportType !== 'cost_center')
                        <div class="col-md-4">
                            <label class="form-label" for="rep_br">Branch</label>
                            <select wire:model="branch_id" class="form-select form-select-sm" id="rep_br">
                                <option value="">Company-Wide (All Branches)</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($reportType === 'register' || $reportType === 'budget_vs_actual')
                        <div class="col-md-4">
                            <label class="form-label" for="rep_cat">Expense Category</label>
                            <select wire:model="expense_category_id" class="form-select form-select-sm" id="rep_cat">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($reportType === 'register')
                        <div class="col-md-4">
                            <label class="form-label" for="rep_sup">Vendor / Supplier</label>
                            <select wire:model="supplier_id" class="form-select form-select-sm" id="rep_sup">
                                <option value="">All Suppliers</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($reportType === 'budget_vs_actual')
                        <div class="col-md-4">
                            <label class="form-label" for="rep_yr">Budget Year</label>
                            <input wire:model="year" type="number" class="form-control form-control-sm" id="rep_yr">
                        </div>
                    @else
                        <div class="col-md-3">
                            <label class="form-label" for="rep_sdt">Start Date</label>
                            <input wire:model="start_date" type="date" class="form-control form-control-sm" id="rep_sdt">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="rep_edt">End Date</label>
                            <input wire:model="end_date" type="date" class="form-control form-control-sm" id="rep_edt">
                        </div>
                    @endif

                    <div class="col-12 mt-4 text-end">
                        <button wire:click="generateReport" class="btn btn-primary btn-sm px-4 me-2">
                            <span class="fas fa-play me-1"></span>Generate Preview
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

        <!-- Preview Results Card -->
        <div class="card d-print-block">
            <div class="card-header bg-light border-bottom">
                <h6 class="mb-0"><span class="fas fa-eye me-2 text-primary"></span>Report Preview Register</h6>
            </div>
            
            <div class="card-body p-0">
                @if(!empty($reportData))
                    <div class="table-responsive">
                        @if($reportType === 'register')
                            <table class="table table-sm table-striped fs-11 align-middle mb-0">
                                <thead class="bg-200">
                                    <tr>
                                        <th class="px-3">Voucher</th>
                                        <th>Date</th>
                                        <th>Branch</th>
                                        <th>Category</th>
                                        <th>Payment</th>
                                        <th class="text-end">Tax</th>
                                        <th class="text-end">Discount</th>
                                        <th class="text-end">Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData as $row)
                                        <tr>
                                            <td class="px-3 fw-bold">{{ $row['expense_number'] }}</td>
                                            <td>{{ $row['expense_date'] }}</td>
                                            <td>{{ $row['branch']['name'] ?? 'Head Office' }}</td>
                                            <td>{{ $row['category']['name'] ?? 'Split' }}</td>
                                            <td>{{ $row['payment_method'] }}</td>
                                            <td class="text-end">{{ number_format($row['tax_amount'], 2) }}</td>
                                            <td class="text-end">{{ number_format($row['discount_amount'], 2) }}</td>
                                            <td class="text-end fw-bold font-monospace">{{ number_format($row['total_amount'], 2) }} PKR</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @elseif($reportType === 'budget_vs_actual')
                            <table class="table table-sm table-striped fs-11 align-middle mb-0">
                                <thead class="bg-200">
                                    <tr>
                                        <th class="px-3">Category</th>
                                        <th>Branch</th>
                                        <th>Period</th>
                                        <th class="text-end">Allocated</th>
                                        <th class="text-end">Consumed</th>
                                        <th class="text-end">Remaining Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData as $row)
                                        <tr>
                                            <td class="px-3 fw-bold">{{ $row['category']['name'] ?? '—' }}</td>
                                            <td>{{ $row['branch']['name'] ?? 'Company-Wide' }}</td>
                                            <td>{{ $row['month'] ? date('F', mktime(0, 0, 0, $row['month'], 10)) . ' ' . $row['year'] : 'Annual ' . $row['year'] }}</td>
                                            <td class="text-end font-monospace">{{ number_format($row['allocated_amount'], 2) }}</td>
                                            <td class="text-end font-monospace">{{ number_format($row['consumed_amount'], 2) }}</td>
                                            <td class="text-end font-monospace fw-bold {{ $row['allocated_amount'] - $row['consumed_amount'] < 0 ? 'text-danger' : 'text-success' }}">
                                                {{ number_format($row['allocated_amount'] - $row['consumed_amount'], 2) }} PKR
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @elseif($reportType === 'utility')
                            <table class="table table-sm table-striped fs-11 align-middle mb-0">
                                <thead class="bg-200">
                                    <tr>
                                        <th class="px-3">Voucher</th>
                                        <th>Utility Type</th>
                                        <th>Consumer Number</th>
                                        <th>Billing Period</th>
                                        <th>Meter Index (Prev/Curr)</th>
                                        <th class="text-end">Late Charges</th>
                                        <th class="text-end">Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData as $row)
                                        <tr>
                                            <td class="px-3 fw-bold">{{ $row['expense_number'] }}</td>
                                            <td>{{ $row['utility_bill']['utility_type'] ?? '—' }}</td>
                                            <td class="font-monospace">{{ $row['utility_bill']['consumer_number'] ?? '—' }}</td>
                                            <td>{{ $row['utility_bill']['billing_period'] ?? '—' }}</td>
                                            <td>{{ number_format($row['utility_bill']['previous_reading'] ?? 0, 0) }} - {{ number_format($row['utility_bill']['current_reading'] ?? 0, 0) }}</td>
                                            <td class="text-end">{{ number_format($row['utility_bill']['late_charges'] ?? 0, 2) }}</td>
                                            <td class="text-end fw-bold font-monospace">{{ number_format($row['total_amount'], 2) }} PKR</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @elseif($reportType === 'maintenance')
                            <table class="table table-sm table-striped fs-11 align-middle mb-0">
                                <thead class="bg-200">
                                    <tr>
                                        <th class="px-3">Voucher</th>
                                        <th>Category</th>
                                        <th>Asset Name</th>
                                        <th>Scheduled Date</th>
                                        <th>Completion Date</th>
                                        <th class="text-center">Warranty Cover</th>
                                        <th class="text-end">Total Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData as $row)
                                        <tr>
                                            <td class="px-3 fw-bold">{{ $row['expense_number'] }}</td>
                                            <td>{{ $row['maintenance_record']['maintenance_type'] ?? '—' }}</td>
                                            <td>{{ $row['maintenance_record']['asset_name'] ?? '—' }}</td>
                                            <td>{{ $row['maintenance_record']['scheduled_date'] ?? '—' }}</td>
                                            <td>{{ $row['maintenance_record']['completion_date'] ?? 'Pending' }}</td>
                                            <td class="text-center">{{ $row['maintenance_record']['warranty_period_months'] ?? 0 }} Months</td>
                                            <td class="text-end fw-bold font-monospace">{{ number_format($row['total_amount'], 2) }} PKR</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @elseif($reportType === 'tax_summary')
                            <table class="table table-sm table-striped fs-11 align-middle mb-0">
                                <thead class="bg-200">
                                    <tr>
                                        <th class="px-3">Voucher</th>
                                        <th>Date</th>
                                        <th>Reference / Bill ID</th>
                                        <th class="text-end">Subtotal Cost</th>
                                        <th class="text-end">Tax Amount</th>
                                        <th class="text-end">Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData as $row)
                                        <tr>
                                            <td class="px-3 fw-bold">{{ $row['expense_number'] }}</td>
                                            <td>{{ $row['expense_date'] }}</td>
                                            <td>{{ $row['reference_number'] ?? '—' }}</td>
                                            <td class="text-end">{{ number_format($row['amount'], 2) }}</td>
                                            <td class="text-end text-danger fw-bold">+ {{ number_format($row['tax_amount'], 2) }}</td>
                                            <td class="text-end fw-bold font-monospace">{{ number_format($row['total_amount'], 2) }} PKR</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @elseif($reportType === 'cost_center')
                            <table class="table table-sm table-striped fs-11 align-middle mb-0">
                                <thead class="bg-200">
                                    <tr>
                                        <th class="px-3">Cost Center Reference</th>
                                        <th class="text-end">Subtotal Cost</th>
                                        <th class="text-end">Total Tax</th>
                                        <th class="text-end">Total Amount (Base)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData as $row)
                                        <tr>
                                            <td class="px-3 fw-bold">{{ $row['cost_center'] }}</td>
                                            <td class="text-end">{{ number_format($row['subtotal'], 2) }}</td>
                                            <td class="text-end">{{ number_format($row['tax'], 2) }}</td>
                                            <td class="text-end fw-bold font-monospace">{{ number_format($row['total'], 2) }} PKR</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                @else
                    <div class="text-center py-5 text-muted">
                        <span class="fas fa-folder-open fa-2x mb-2 d-block"></span>
                        Generate report preview using filters above.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
