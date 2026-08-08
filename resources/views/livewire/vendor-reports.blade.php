<div class="p-3">
    <!-- Header Toolbar -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <div>
            <h4 class="fw-bold mb-1"><i class="fas fa-chart-bar text-primary me-2"></i>Vendor & Partnership Financial Reports</h4>
            <p class="text-secondary fs-12 mb-0">Filter, inspect, and print vendor sales, commission income, ledgers, and payouts.</p>
        </div>
        <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print me-1"></i> Print Report</button>
    </div>

    <!-- Filters Bar (Hidden during print) -->
    <div class="card border-0 shadow-sm mb-3 no-print">
        <div class="card-body p-3 fs-12">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Report Type</label>
                    <select wire:model.live="reportType" class="form-select form-select-sm">
                        <option value="sales">1. Vendor Sales Report</option>
                        <option value="commission">2. Commission Income Report</option>
                        <option value="ledger">3. Vendor Ledger Statement</option>
                        <option value="settlement">4. Vendor Settlement Report</option>
                        <option value="monthly">5. Monthly Commission Summary</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Vendor Filter</label>
                    <select wire:model.live="vendor_id" class="form-select form-select-sm">
                        <option value="">-- All Vendors --</option>
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

    <!-- Printable Report Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light py-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0 text-uppercase">
                    @if($reportType === 'sales') VENDOR SALES REPORT
                    @elseif($reportType === 'commission') VENDOR COMMISSION INCOME REPORT
                    @elseif($reportType === 'ledger') VENDOR LEDGER STATEMENT
                    @elseif($reportType === 'settlement') VENDOR SETTLEMENT REPORT
                    @elseif($reportType === 'monthly') MONTHLY COMMISSION SUMMARY REPORT
                    @endif
                </h5>
                <div class="text-secondary fs-11 mt-1">Period: {{ $dateFrom }} to {{ $dateTo }}</div>
            </div>
            <div class="text-end fs-11 text-muted">
                <div>Printed On: {{ date('d-M-Y h:i A') }}</div>
                <div>System: MarqueeCMS ERP</div>
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
                                <th>Vendor</th>
                                <th>Booking #</th>
                                <th>Sale Amount</th>
                                <th>Commission %</th>
                                <th>Commission Income</th>
                                <th>Vendor Net Payable</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $row)
                                <tr>
                                    <td>{{ $row->sale_date->format('d-M-Y') }}</td>
                                    <td class="fw-bold font-monospace text-primary">{{ $row->vendor_sale_number }}</td>
                                    <td class="fw-semibold">{{ $row->vendor->name ?? '—' }}</td>
                                    <td>{{ $row->booking->booking_number ?? 'Direct Sale' }}</td>
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
                                <th>Vendor</th>
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
                                    <td>{{ $row->transaction_date->format('d-M-Y') }}</td>
                                    <td class="fw-bold font-monospace text-primary">{{ $row->reference_number }}</td>
                                    <td class="fw-semibold">{{ $row->vendor->name ?? '—' }}</td>
                                    <td>{{ $row->description }}</td>
                                    <td class="fw-bold text-dark">{{ $row->sale_amount > 0 ? 'Rs. '.number_format($row->sale_amount) : '—' }}</td>
                                    <td class="fw-bold text-success">{{ $row->commission_amount > 0 ? 'Rs. '.number_format($row->commission_amount) : '—' }}</td>
                                    <td class="fw-bold text-primary">{{ $row->payment_amount > 0 ? 'Rs. '.number_format($row->payment_amount) : '—' }}</td>
                                    <td class="fw-bold text-danger">Rs. {{ number_format($row->running_balance) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center py-4 text-muted">No ledger transactions found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                @elseif($reportType === 'settlement')
                    <table class="table table-hover align-middle mb-0 fs-12">
                        <thead class="bg-200">
                            <tr>
                                <th>Settlement Date</th>
                                <th>Settlement #</th>
                                <th>Vendor</th>
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
                                    <td>{{ $row->settlement_date->format('d-M-Y') }}</td>
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
                    </table>

                @elseif($reportType === 'monthly')
                    <table class="table table-hover align-middle mb-0 fs-12">
                        <thead class="bg-200">
                            <tr>
                                <th>Vendor</th>
                                <th>Event Sales Count</th>
                                <th>Total Sales Amount</th>
                                <th>Commission Income</th>
                                <th>Net Vendor Payable</th>
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
