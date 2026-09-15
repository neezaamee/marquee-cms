<div class="container-fluid p-0">
    <!-- Header & Filter Toolbar -->
    <div class="card mb-3 border border-200">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center justify-content-between">
                <div class="col-12 col-xl-6">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar avatar-l bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-shopping-cart fa-lg"></span>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="mb-0 text-secondary fw-bold">Purchase & Procurement Dashboard</h4>
                                <span class="badge badge-subtle-primary rounded-pill">{{ $periodLabel }}</span>
                            </div>
                            <p class="fs-11 text-600 mb-0">
                                Monitor purchase orders, goods receipts (GRNs), supplier bills, returns, and AP liabilities.
                                <span class="text-muted ms-1">({{ $startDate->format('d M Y') }} &mdash; {{ $endDate->format('d M Y') }})</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-12 col-xl-6 text-xl-end">
                    <div class="d-flex flex-wrap gap-2 justify-content-xl-end">
                        <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary btn-sm">
                            <span class="fas fa-plus-circle me-1"></span>New PO
                        </a>
                        <a href="{{ route('goods-receipts.create') }}" class="btn btn-falcon-default btn-sm text-success">
                            <span class="fas fa-truck-loading me-1"></span>Receive Goods
                        </a>
                        <a href="{{ route('purchase-invoices.create') }}" class="btn btn-falcon-default btn-sm text-info">
                            <span class="fas fa-file-invoice-dollar me-1"></span>New Bill/Invoice
                        </a>
                        <a href="{{ route('purchase-returns.create') }}" class="btn btn-falcon-default btn-sm text-danger">
                            <span class="fas fa-undo me-1"></span>Return
                        </a>
                        <a href="{{ route('suppliers.index') }}" class="btn btn-falcon-default btn-sm">
                            <span class="fas fa-users me-1"></span>Suppliers
                        </a>
                    </div>
                </div>
            </div>

            <hr class="my-3 border-200">

            <!-- Filter Controls -->
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-auto">
                    <!-- Period Filter Tabs -->
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" wire:click="setFilterRange('today')" class="btn {{ $filterRange === 'today' ? 'btn-primary' : 'btn-outline-secondary' }}">Today</button>
                        <button type="button" wire:click="setFilterRange('this_week')" class="btn {{ $filterRange === 'this_week' ? 'btn-primary' : 'btn-outline-secondary' }}">This Week</button>
                        <button type="button" wire:click="setFilterRange('this_month')" class="btn {{ $filterRange === 'this_month' ? 'btn-primary' : 'btn-outline-secondary' }}">This Month</button>
                        <button type="button" wire:click="setFilterRange('this_quarter')" class="btn {{ $filterRange === 'this_quarter' ? 'btn-primary' : 'btn-outline-secondary' }}">Quarter</button>
                        <button type="button" wire:click="setFilterRange('this_year')" class="btn {{ $filterRange === 'this_year' ? 'btn-primary' : 'btn-outline-secondary' }}">This Year</button>
                        <button type="button" wire:click="setFilterRange('last_30_days')" class="btn {{ $filterRange === 'last_30_days' ? 'btn-primary' : 'btn-outline-secondary' }}">Last 30 Days</button>
                        <button type="button" wire:click="setFilterRange('custom')" class="btn {{ $filterRange === 'custom' ? 'btn-primary' : 'btn-outline-secondary' }}">Custom</button>
                    </div>
                </div>

                <div class="col-auto d-flex flex-wrap gap-2 align-items-center">
                    @if($branches->count() > 1)
                        <div style="min-width: 160px;">
                            <select wire:model.live="filterBranch" class="form-select form-select-sm">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div style="min-width: 180px;">
                        <select wire:model.live="filterSupplier" class="form-select form-select-sm">
                            <option value="">All Suppliers</option>
                            @foreach($allSuppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($filterRange === 'custom')
                        <div class="d-flex align-items-center gap-1">
                            <input type="date" wire:model.live="customDateFrom" class="form-control form-control-sm" style="width: 130px;">
                            <span class="text-500 fs-11">to</span>
                            <input type="date" wire:model.live="customDateTo" class="form-control form-control-sm" style="width: 130px;">
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 1. Top Key Performance Indicators (KPI Cards) -->
    <div class="row g-3 mb-3">
        <!-- Invoiced Purchases (Net Spend) -->
        <div class="col-sm-6 col-lg-3">
            <div class="card overflow-hidden h-100 border border-primary-subtle shadow-sm">
                <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-1.png') }});"></div>
                <div class="card-body position-relative p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="text-uppercase text-600 fw-bold fs-11 text-primary mb-0">Total Invoiced Spend</h6>
                        <span class="badge badge-subtle-primary fs-11"><span class="fas fa-receipt me-1"></span>{{ $totalInvoicesCount }} Bills</span>
                    </div>
                    <div class="display-6 fs-4 mb-1 fw-black text-primary font-monospace">Rs. {{ number_format($totalPurchasesNet, 2) }}</div>
                    <div class="d-flex justify-content-between fs-11 text-500">
                        <span>Posted: <strong class="text-success">{{ $postedInvoicesCount }}</strong></span>
                        <span>Draft: <strong class="text-warning">{{ $draftInvoicesCount }}</strong></span>
                        <span>Tax: <strong>{{ number_format($totalPurchasesTax, 0) }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Purchase Orders (Awaiting Delivery) -->
        <div class="col-sm-6 col-lg-3">
            <div class="card overflow-hidden h-100 border border-warning-subtle shadow-sm">
                <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-2.png') }});"></div>
                <div class="card-body position-relative p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="text-uppercase text-600 fw-bold fs-11 text-warning-emphasis mb-0">Pending Orders (POs)</h6>
                        <span class="badge badge-subtle-warning fs-11"><span class="fas fa-clock me-1"></span>{{ $pendingPOsCount }} Pending</span>
                    </div>
                    <div class="display-6 fs-4 mb-1 fw-black text-warning-emphasis font-monospace">Rs. {{ number_format($pendingPOsAmount, 2) }}</div>
                    <div class="d-flex justify-content-between fs-11 text-500">
                        <span>Total POs: <strong>{{ $totalPOsCount }}</strong></span>
                        <span>Completed: <strong class="text-success">{{ $completedPOsCount }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Goods Received (GRN) -->
        <div class="col-sm-6 col-lg-3">
            <div class="card overflow-hidden h-100 border border-success-subtle shadow-sm">
                <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-3.png') }});"></div>
                <div class="card-body position-relative p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="text-uppercase text-600 fw-bold fs-11 text-success mb-0">Goods Received (GRN)</h6>
                        <span class="badge badge-subtle-success fs-11"><span class="fas fa-boxes me-1"></span>{{ $totalGrnsCount }} GRNs</span>
                    </div>
                    <div class="display-6 fs-4 mb-1 fw-black text-success font-monospace">{{ number_format($totalReceivedQuantity, 2) }}</div>
                    <p class="fs-11 text-500 mb-0">Total inventory units received at warehouse</p>
                </div>
            </div>
        </div>

        <!-- Purchase Returns & Accounts Payable -->
        <div class="col-sm-6 col-lg-3">
            <div class="card overflow-hidden h-100 border border-info-subtle shadow-sm">
                <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-4.png') }});"></div>
                <div class="card-body position-relative p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="text-uppercase text-600 fw-bold fs-11 text-info-emphasis mb-0">Supplier Payables (AP)</h6>
                        <span class="badge badge-subtle-info fs-11"><span class="fas fa-users me-1"></span>{{ $activeSuppliersCount }} Suppliers</span>
                    </div>
                    <div class="display-6 fs-4 mb-1 fw-black text-info-emphasis font-monospace">Rs. {{ number_format($totalOutstandingPayables, 2) }}</div>
                    <div class="d-flex justify-content-between fs-11 text-500">
                        <span>Returns: <strong class="text-danger">Rs. {{ number_format($totalReturnsNet, 0) }}</strong> ({{ $totalReturnsCount }})</span>
                        <span>Net Spend: <strong class="text-dark">Rs. {{ number_format($effectiveProcurementSpend, 0) }}</strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Second Row: Purchase Order Pipeline & Category Spending Breakdown -->
    <div class="row g-3 mb-3">
        <!-- PO Status Pipeline Distribution -->
        <div class="col-lg-6">
            <div class="card h-100 border border-200">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-700">
                        <span class="fas fa-tasks me-2 text-primary"></span>Purchase Orders Status Pipeline
                    </h6>
                    <span class="badge bg-secondary fs-11">{{ $totalPOsCount }} Total</span>
                </div>
                <div class="card-body p-3">
                    @php
                        $poTotalSafe = max(1, $totalPOsCount);
                    @endphp

                    <div class="mb-3">
                        <div class="d-flex justify-content-between fs-11 mb-1">
                            <span class="fw-semi-bold text-secondary"><span class="fas fa-edit me-1 text-secondary"></span>Draft Orders</span>
                            <span class="fw-bold font-monospace">{{ $statusCounts['Draft'] }} <span class="text-muted">({{ round(($statusCounts['Draft'] / $poTotalSafe) * 100) }}%)</span></span>
                        </div>
                        <div class="progress" style="height: 7px;">
                            <div class="progress-bar bg-secondary" role="progressbar" style="width: {{ ($statusCounts['Draft'] / $poTotalSafe) * 100 }}%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between fs-11 mb-1">
                            <span class="fw-semi-bold text-info"><span class="fas fa-check-circle me-1 text-info"></span>Approved (Ready for Delivery)</span>
                            <span class="fw-bold font-monospace">{{ $statusCounts['Approved'] }} <span class="text-muted">({{ round(($statusCounts['Approved'] / $poTotalSafe) * 100) }}%)</span></span>
                        </div>
                        <div class="progress" style="height: 7px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ ($statusCounts['Approved'] / $poTotalSafe) * 100 }}%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between fs-11 mb-1">
                            <span class="fw-semi-bold text-warning"><span class="fas fa-hourglass-half me-1 text-warning"></span>Partially Received</span>
                            <span class="fw-bold font-monospace">{{ $statusCounts['Partially Received'] }} <span class="text-muted">({{ round(($statusCounts['Partially Received'] / $poTotalSafe) * 100) }}%)</span></span>
                        </div>
                        <div class="progress" style="height: 7px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: {{ ($statusCounts['Partially Received'] / $poTotalSafe) * 100 }}%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between fs-11 mb-1">
                            <span class="fw-semi-bold text-success"><span class="fas fa-box-open me-1 text-success"></span>Completed / Fully Received</span>
                            <span class="fw-bold font-monospace">{{ $statusCounts['Completed'] }} <span class="text-muted">({{ round(($statusCounts['Completed'] / $poTotalSafe) * 100) }}%)</span></span>
                        </div>
                        <div class="progress" style="height: 7px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ ($statusCounts['Completed'] / $poTotalSafe) * 100 }}%"></div>
                        </div>
                    </div>

                    <div class="mb-1">
                        <div class="d-flex justify-content-between fs-11 mb-1">
                            <span class="fw-semi-bold text-danger"><span class="fas fa-times-circle me-1 text-danger"></span>Cancelled</span>
                            <span class="fw-bold font-monospace">{{ $statusCounts['Cancelled'] }} <span class="text-muted">({{ round(($statusCounts['Cancelled'] / $poTotalSafe) * 100) }}%)</span></span>
                        </div>
                        <div class="progress" style="height: 7px;">
                            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ ($statusCounts['Cancelled'] / $poTotalSafe) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Spend by Supplier Category -->
        <div class="col-lg-6">
            <div class="card h-100 border border-200">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-700">
                        <span class="fas fa-tags me-2 text-primary"></span>Spending by Supplier Category
                    </h6>
                    <span class="fs-11 text-muted">Active Invoices</span>
                </div>
                <div class="card-body p-3">
                    @if($categoryBreakdown->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <span class="fas fa-chart-pie fa-2x text-300 mb-2 d-block"></span>
                            <span class="fs-11">No category spend recorded in selected period.</span>
                        </div>
                    @else
                        @php
                            $catTotalSpend = max(1, $categoryBreakdown->sum('total_spend'));
                            $palette = ['primary', 'info', 'success', 'warning', 'danger', 'secondary'];
                        @endphp
                        @foreach($categoryBreakdown as $idx => $cat)
                            @php
                                $color = $palette[$idx % count($palette)];
                                $pct = round(($cat->total_spend / $catTotalSpend) * 100, 1);
                            @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center fs-11 mb-1">
                                    <span class="fw-semi-bold text-700">
                                        <span class="badge badge-subtle-{{ $color }} me-1">{{ $cat->category_name }}</span>
                                        <span class="text-muted fs-10">({{ $cat->invoice_count }} invoices)</span>
                                    </span>
                                    <span class="font-monospace fw-bold text-dark">
                                        Rs. {{ number_format($cat->total_spend, 2) }}
                                        <span class="text-muted fw-normal">({{ $pct }}%)</span>
                                    </span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-{{ $color }}" role="progressbar" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Third Row: 6-Month Procurement Trend & Critical Stock Reorder Alerts -->
    <div class="row g-3 mb-3">
        <!-- 6-Month Spend Trend Table -->
        <div class="col-lg-7">
            <div class="card h-100 border border-200">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-700">
                        <span class="fas fa-chart-line me-2 text-primary"></span>6-Month Procurement Spend Trend
                    </h6>
                    <span class="fs-11 text-muted">Invoices vs Returns</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 align-middle">
                            <thead class="bg-200 fs-11">
                                <tr>
                                    <th class="px-3">Month</th>
                                    <th class="text-end">Invoiced Spend</th>
                                    <th class="text-end">Returns</th>
                                    <th class="text-end">Net Spend</th>
                                    <th style="width: 140px;">Volume Visual</th>
                                </tr>
                            </thead>
                            <tbody class="fs-11">
                                @php
                                    $maxMonthlyNet = max(1, collect($monthlyTrend)->max('net'));
                                @endphp
                                @foreach($monthlyTrend as $trend)
                                    @php
                                        $barPct = round(($trend['net'] / $maxMonthlyNet) * 100);
                                    @endphp
                                    <tr>
                                        <td class="px-3 fw-bold text-700">{{ $trend['month'] }}</td>
                                        <td class="text-end font-monospace">Rs. {{ number_format($trend['invoices'], 2) }}</td>
                                        <td class="text-end font-monospace text-danger">Rs. {{ number_format($trend['returns'], 2) }}</td>
                                        <td class="text-end font-monospace fw-bold text-primary">Rs. {{ number_format($trend['net'], 2) }}</td>
                                        <td>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-primary" style="width: {{ $barPct }}%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Critical Reorder & Low Stock Alerts -->
        <div class="col-lg-5">
            <div class="card h-100 border border-200">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-700">
                        <span class="fas fa-exclamation-triangle me-2 text-warning"></span>Low Stock & Reorder Alerts
                    </h6>
                    <a href="{{ route('purchase-orders.create') }}" class="btn btn-outline-primary btn-xs">
                        <span class="fas fa-plus me-1"></span>Procure Stock
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($reorderAlerts->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <span class="fas fa-check-circle fa-2x text-success mb-2 d-block"></span>
                            <span class="fs-11">All central warehouse inventory is adequately stocked above reorder thresholds!</span>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0 align-middle">
                                <thead class="bg-200 fs-11">
                                    <tr>
                                        <th class="px-3">Item</th>
                                        <th class="text-end">Current</th>
                                        <th class="text-end">Reorder Level</th>
                                        <th class="text-end px-3">Deficit</th>
                                    </tr>
                                </thead>
                                <tbody class="fs-11">
                                    @foreach($reorderAlerts as $alert)
                                        <tr>
                                            <td class="px-3">
                                                <span class="fw-bold d-block text-dark">{{ $alert['name'] }}</span>
                                                <span class="font-monospace fs-10 text-muted">{{ $alert['item_code'] }}</span>
                                            </td>
                                            <td class="text-end font-monospace text-danger fw-bold">
                                                {{ number_format($alert['current_stock'], 2) }} {{ $alert['unit'] }}
                                            </td>
                                            <td class="text-end font-monospace text-muted">
                                                {{ number_format($alert['reorder_level'], 2) }}
                                            </td>
                                            <td class="text-end font-monospace text-danger px-3">
                                                <span class="badge badge-subtle-danger font-monospace">-{{ number_format($alert['deficit'], 2) }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Fourth Row: Recent Purchase Orders & Recent Invoices -->
    <div class="row g-3 mb-3">
        <!-- Recent Purchase Orders -->
        <div class="col-lg-6">
            <div class="card h-100 border border-200">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-700">
                        <span class="fas fa-clipboard-list me-2 text-primary"></span>Recent Purchase Orders
                    </h6>
                    <a href="{{ route('purchase-orders.index') }}" class="btn btn-link btn-sm p-0 fs-11 text-decoration-none">
                        View All ({{ $totalPOsCount }}) &rarr;
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 align-middle">
                            <thead class="bg-200 fs-11">
                                <tr>
                                    <th class="px-3">PO #</th>
                                    <th>Supplier</th>
                                    <th>Date</th>
                                    <th class="text-end">Amount</th>
                                    <th>Status</th>
                                    <th class="text-end px-3">Action</th>
                                </tr>
                            </thead>
                            <tbody class="fs-11">
                                @forelse($recentOrders as $po)
                                    <tr>
                                        <td class="px-3 font-monospace fw-bold text-primary">{{ $po->po_number }}</td>
                                        <td>{{ Str::limit($po->supplier->name ?? '—', 16) }}</td>
                                        <td class="text-muted">{{ $po->order_date ? $po->order_date->format('d M') : '—' }}</td>
                                        <td class="text-end font-monospace fw-bold">Rs. {{ number_format($po->total_amount, 2) }}</td>
                                        <td>
                                            <span class="badge badge-subtle-{{ $po->status === 'Completed' ? 'success' : ($po->status === 'Approved' ? 'info' : ($po->status === 'Partially Received' ? 'warning' : ($po->status === 'Draft' ? 'secondary' : 'danger'))) }}">
                                                {{ $po->status }}
                                            </span>
                                        </td>
                                        <td class="text-end px-3">
                                            <a href="{{ route('purchase-orders.edit', $po->id) }}" class="btn btn-outline-primary btn-xs">
                                                <span class="fas fa-edit"></span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No purchase orders found for this period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Purchase Invoices -->
        <div class="col-lg-6">
            <div class="card h-100 border border-200">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-700">
                        <span class="fas fa-file-invoice me-2 text-primary"></span>Recent Purchase Invoices
                    </h6>
                    <a href="{{ route('purchase-invoices.index') }}" class="btn btn-link btn-sm p-0 fs-11 text-decoration-none">
                        View All ({{ $totalInvoicesCount }}) &rarr;
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 align-middle">
                            <thead class="bg-200 fs-11">
                                <tr>
                                    <th class="px-3">Invoice #</th>
                                    <th>Supplier</th>
                                    <th>Date</th>
                                    <th class="text-end">Net Amount</th>
                                    <th>Status</th>
                                    <th class="text-end px-3">Action</th>
                                </tr>
                            </thead>
                            <tbody class="fs-11">
                                @forelse($recentInvoices as $inv)
                                    <tr>
                                        <td class="px-3 font-monospace fw-bold text-dark">{{ $inv->invoice_number }}</td>
                                        <td>{{ Str::limit($inv->supplier->name ?? '—', 16) }}</td>
                                        <td class="text-muted">{{ $inv->purchase_date ? $inv->purchase_date->format('d M') : '—' }}</td>
                                        <td class="text-end font-monospace fw-bold text-success">Rs. {{ number_format($inv->net_amount, 2) }}</td>
                                        <td>
                                            <span class="badge badge-subtle-{{ $inv->status === 'Posted' ? 'success' : ($inv->status === 'Draft' ? 'secondary' : 'info') }}">
                                                {{ $inv->status }}
                                            </span>
                                        </td>
                                        <td class="text-end px-3">
                                            <a href="{{ route('purchase-invoices.pdf', $inv->id) }}" target="_blank" class="btn btn-outline-info btn-xs me-1" title="PDF">
                                                <span class="fas fa-file-pdf"></span>
                                            </a>
                                            <a href="{{ route('purchase-invoices.edit', $inv->id) }}" class="btn btn-outline-primary btn-xs" title="Edit">
                                                <span class="fas fa-edit"></span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No purchase invoices found for this period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Fifth Row: Top Suppliers Ranking -->
    <div class="row g-3">
        <div class="col-12">
            <div class="card border border-200">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-700">
                        <span class="fas fa-trophy me-2 text-warning"></span>Top Suppliers by Spend Volume
                    </h6>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-link btn-sm p-0 fs-11 text-decoration-none">
                        Suppliers Directory &rarr;
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 align-middle">
                            <thead class="bg-200 fs-11">
                                <tr>
                                    <th class="px-3" style="width: 50px;">Rank</th>
                                    <th>Supplier Code & Name</th>
                                    <th>Contact</th>
                                    <th class="text-center">Bills Count</th>
                                    <th class="text-end">Total Purchases</th>
                                    <th class="text-end">Current Ledger Balance</th>
                                    <th class="text-end px-3">Action</th>
                                </tr>
                            </thead>
                            <tbody class="fs-11">
                                @forelse($topSuppliers as $idx => $s)
                                    <tr>
                                        <td class="px-3">
                                            <span class="badge {{ $idx === 0 ? 'bg-warning text-dark' : ($idx === 1 ? 'bg-secondary' : ($idx === 2 ? 'bg-primary' : 'badge-subtle-secondary')) }} rounded-pill">
                                                #{{ $idx + 1 }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="font-monospace fs-10 text-muted d-block">{{ $s->supplier_code }}</span>
                                            <span class="fw-bold text-dark">{{ $s->name }}</span>
                                        </td>
                                        <td class="font-monospace text-muted">{{ $s->mobile_number ?? '—' }}</td>
                                        <td class="text-center font-monospace">{{ $s->invoice_count }}</td>
                                        <td class="text-end font-monospace fw-bold text-primary">Rs. {{ number_format($s->total_purchases, 2) }}</td>
                                        <td class="text-end font-monospace fw-bold {{ $s->current_balance > 0 ? 'text-danger' : 'text-success' }}">
                                            Rs. {{ number_format($s->current_balance, 2) }}
                                        </td>
                                        <td class="text-end px-3">
                                            <a href="{{ route('suppliers.ledger', $s->id) }}" class="btn btn-outline-info btn-xs">
                                                <span class="fas fa-book-open me-1"></span>Ledger
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No supplier spend data in this period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
