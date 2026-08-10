<div class="p-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="fas fa-handshake text-primary me-2"></i>Service Provider ERP Dashboard</h4>
            <p class="text-secondary fs-12 mb-0">Overview of external event service providers, commission earnings, ledgers, and payouts.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('vendors.index') }}" class="btn btn-falcon-primary btn-sm"><i class="fas fa-users me-1"></i> Manage Providers</a>
            <a href="{{ route('vendor-sales.index') }}" class="btn btn-falcon-success btn-sm"><i class="fas fa-plus-circle me-1"></i> Record Sale</a>
            <a href="{{ route('vendor-settlements.index') }}" class="btn btn-falcon-warning btn-sm text-dark"><i class="fas fa-wallet me-1"></i> Settlements</a>
        </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="row g-3 mb-4">
        <!-- Total & Active Vendors -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fs-11 fw-bold text-uppercase text-secondary">Active Providers</span>
                        <div class="icon-item bg-primary-subtle text-primary rounded-circle"><i class="fas fa-store fs-11"></i></div>
                    </div>
                    <h3 class="fw-bold text-dark mb-0">{{ number_format($activeVendors) }}</h3>
                    <div class="fs-11 text-muted">Out of {{ number_format($totalVendors) }} total registered</div>
                </div>
            </div>
        </div>

        <!-- Monthly Vendor Sales -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fs-11 fw-bold text-uppercase text-secondary">This Month Sales</span>
                        <div class="icon-item bg-success-subtle text-success rounded-circle"><i class="fas fa-chart-line fs-11"></i></div>
                    </div>
                    <h3 class="fw-bold text-success mb-0">Rs. {{ number_format($monthlySales) }}</h3>
                    <div class="fs-11 text-muted">Lifetime: Rs. {{ number_format($totalSales) }}</div>
                </div>
            </div>
        </div>

        <!-- Monthly Commission Income -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fs-11 fw-bold text-uppercase text-secondary">Commission Income</span>
                        <div class="icon-item bg-info-subtle text-info rounded-circle"><i class="fas fa-coins fs-11"></i></div>
                    </div>
                    <h3 class="fw-bold text-info mb-0">Rs. {{ number_format($monthlyCommission) }}</h3>
                    <div class="fs-11 text-muted">Lifetime: Rs. {{ number_format($totalCommission) }}</div>
                </div>
            </div>
        </div>

        <!-- Outstanding Payables -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fs-11 fw-bold text-uppercase text-secondary">Outstanding Payable</span>
                        <div class="icon-item bg-danger-subtle text-danger rounded-circle"><i class="fas fa-balance-scale fs-11"></i></div>
                    </div>
                    <h3 class="fw-bold text-danger mb-0">Rs. {{ number_format($outstandingPayable) }}</h3>
                    <div class="fs-11 text-muted">Pending provider payouts</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Recent Vendor Sales -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-shopping-cart me-2 text-primary"></i>Recent Provider Sales</h6>
                    <a href="{{ route('vendor-sales.index') }}" class="fs-11 text-primary">View All <i class="fas fa-chevron-right ms-1"></i></a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 fs-12">
                        <thead class="bg-200">
                            <tr>
                                <th>Sale #</th>
                                <th>Service Provider</th>
                                <th>Service</th>
                                <th>Sale Amount</th>
                                <th>Commission</th>
                                <th>Net Payable</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales as $sale)
                                <tr>
                                    <td class="fw-bold font-monospace text-primary">{{ $sale->vendor_sale_number }}</td>
                                    <td class="fw-semibold">{{ $sale->vendor->name ?? '—' }}</td>
                                    <td><span class="badge badge-subtle-info">{{ $sale->service->service_name ?? 'General' }}</span></td>
                                    <td class="fw-bold">Rs. {{ number_format($sale->sale_amount) }}</td>
                                    <td class="text-success fw-bold">Rs. {{ number_format($sale->commission_amount) }} ({{ $sale->commission_rate }}%)</td>
                                    <td class="fw-bold text-dark">Rs. {{ number_format($sale->vendor_net_amount) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No sales recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top Commission Vendors -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-trophy me-2 text-warning"></i>Top Commission Generating Providers</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush fs-12">
                        @forelse($topVendors as $vendor)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold text-dark">{{ $vendor->name }}</div>
                                    <div class="text-secondary fs-11">{{ $vendor->vendor_type }} — {{ $vendor->total_sales_count }} Event Sales</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-success">Rs. {{ number_format($vendor->total_commission_generated ?? 0) }}</div>
                                    <span class="badge badge-subtle-primary fs-10">Current Balance: Rs. {{ number_format($vendor->current_balance) }}</span>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted py-3">No active provider performance history.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
