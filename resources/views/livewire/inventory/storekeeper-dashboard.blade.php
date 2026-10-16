<div class="container-fluid px-0">
    <!-- Header -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-3">
            <div class="row flex-between-center g-3">
                <div class="col-12 col-md-auto">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-xl bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center shadow-sm">
                            <span class="fas fa-boxes fa-lg"></span>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="mb-0 fw-bold text-900">
                                    {{ $marquee->name ?? 'Inventory & Store Hub' }}
                                </h4>
                                <span class="badge bg-success-subtle text-success rounded-pill fs-11">
                                    <span class="fas fa-warehouse me-1"></span>Storekeeper Desk
                                </span>
                            </div>
                            <p class="text-600 fs-11 mb-0">
                                Monitor real-time stock levels, department issue requisitions, material dispatches, and incoming goods receipts.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-auto">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('purchase-orders.create') }}" class="btn btn-success btn-sm fw-bold shadow-sm">
                            <span class="fas fa-cart-plus me-1"></span> Purchase Order
                        </a>
                        <a href="{{ route('purchase-invoices.create') }}" class="btn btn-info btn-sm fw-bold shadow-sm text-white">
                            <span class="fas fa-file-invoice-dollar me-1"></span> Purchase Invoice
                        </a>
                        <a href="{{ route('departments.issue') }}" class="btn btn-primary btn-sm fw-bold shadow-sm">
                            <span class="fas fa-dolly-flatbed me-1"></span> Issue Stock
                        </a>
                        <a href="{{ route('inventory.stock-takes.index') }}" class="btn btn-outline-secondary btn-sm fw-bold">
                            <span class="fas fa-clipboard-check me-1"></span> Stock Adjustments
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Action Ribbon -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-4 col-xl flex-xl-fill">
            <a href="{{ route('purchase-orders.create') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift bg-body border-start border-success border-3">
                <div class="card-body p-3 d-flex align-items-center gap-2">
                    <div class="bg-success text-white rounded p-2 text-center" style="width: 38px; height: 38px;">
                        <span class="fas fa-cart-plus fs-10"></span>
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="mb-0 text-900 fs-10 fw-bold text-truncate">Purchase Order</h6>
                        <span class="text-500 fs-11">Purchase order</span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl flex-xl-fill">
            <a href="{{ route('purchase-invoices.create') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift bg-body border-start border-info border-3">
                <div class="card-body p-3 d-flex align-items-center gap-2">
                    <div class="bg-info text-white rounded p-2 text-center" style="width: 38px; height: 38px;">
                        <span class="fas fa-file-invoice-dollar fs-10"></span>
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="mb-0 text-900 fs-10 fw-bold text-truncate">Purchase Invoice</h6>
                        <span class="text-500 fs-11">Purchase bill</span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl flex-xl-fill">
            <a href="{{ route('inventory.stock') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift bg-body">
                <div class="card-body p-3 d-flex align-items-center gap-2">
                    <div class="bg-primary text-white rounded p-2 text-center" style="width: 38px; height: 38px;">
                        <span class="fas fa-eye fs-10"></span>
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="mb-0 text-900 fs-10 fw-bold text-truncate">Live Stock</h6>
                        <span class="text-500 fs-11">View balances</span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl flex-xl-fill">
            <a href="{{ route('inventory.items') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift bg-body">
                <div class="card-body p-3 d-flex align-items-center gap-2">
                    <div class="bg-info text-white rounded p-2 text-center" style="width: 38px; height: 38px;">
                        <span class="fas fa-tags fs-10"></span>
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="mb-0 text-900 fs-10 fw-bold text-truncate">Item Catalog</h6>
                        <span class="text-500 fs-11">Manage items</span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl flex-xl-fill">
            <a href="{{ route('departments.requests') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift bg-body">
                <div class="card-body p-3 d-flex align-items-center gap-2">
                    <div class="bg-warning text-white rounded p-2 text-center" style="width: 38px; height: 38px;">
                        <span class="fas fa-clipboard-list fs-10"></span>
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="mb-0 text-900 fs-10 fw-bold text-truncate">Requisitions</h6>
                        <span class="text-500 fs-11">Dept requests</span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl flex-xl-fill">
            <a href="{{ route('departments.issue') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift bg-body">
                <div class="card-body p-3 d-flex align-items-center gap-2">
                    <div class="bg-success text-white rounded p-2 text-center" style="width: 38px; height: 38px;">
                        <span class="fas fa-sign-out-alt fs-10"></span>
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="mb-0 text-900 fs-10 fw-bold text-truncate">Dispatch Stock</h6>
                        <span class="text-500 fs-11">Issue goods</span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl flex-xl-fill">
            <a href="{{ route('goods-receipts.index') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift bg-body">
                <div class="card-body p-3 d-flex align-items-center gap-2">
                    <div class="bg-danger text-white rounded p-2 text-center" style="width: 38px; height: 38px;">
                        <span class="fas fa-truck-loading fs-10"></span>
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="mb-0 text-900 fs-10 fw-bold text-truncate">Receiving (GRN)</h6>
                        <span class="text-500 fs-11">Incoming orders</span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl flex-xl-fill">
            <a href="{{ route('departments.ledger') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift bg-body">
                <div class="card-body p-3 d-flex align-items-center gap-2">
                    <div class="bg-secondary text-white rounded p-2 text-center" style="width: 38px; height: 38px;">
                        <span class="fas fa-book fs-10"></span>
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="mb-0 text-900 fs-10 fw-bold text-truncate">Stock Ledger</h6>
                        <span class="text-500 fs-11">Movement trail</span>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fs-11 fw-semi-bold text-uppercase">Catalog Items</span>
                            <h3 class="mb-0 fw-bold text-primary mt-1">{{ number_format($totalItems) }}</h3>
                            <span class="fs-11 text-muted">{{ $totalCategories }} Active Categories</span>
                        </div>
                        <div class="avatar avatar-lg bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-box-open fs-8"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fs-11 fw-semi-bold text-uppercase">Pending Requisitions</span>
                            <h3 class="mb-0 fw-bold text-warning mt-1">{{ number_format($pendingRequisitionsCount) }}</h3>
                            <span class="fs-11 text-muted">Awaiting store dispatch</span>
                        </div>
                        <div class="avatar avatar-lg bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-clipboard-check fs-8"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fs-11 fw-semi-bold text-uppercase">Today's Dispatches</span>
                            <h3 class="mb-0 fw-bold text-success mt-1">{{ number_format($todayIssuesCount) }}</h3>
                            <span class="fs-11 text-muted">Material issue slips</span>
                        </div>
                        <div class="avatar avatar-lg bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-shipping-fast fs-8"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fs-11 fw-semi-bold text-uppercase">Pending Purchases</span>
                            <h3 class="mb-0 fw-bold text-success mt-1">{{ number_format($pendingPurchaseOrdersCount) }}</h3>
                            <span class="fs-11 text-muted">Active purchase orders</span>
                        </div>
                        <div class="avatar avatar-lg bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-cart-arrow-down fs-8"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Prominent "New Purchase or Add New Purchase" Feature Card -->
    <div class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, rgba(0, 210, 141, 0.08) 0%, rgba(44, 123, 229, 0.05) 100%); border-left: 5px solid #00d27a !important;">
        <div class="card-body p-4">
            <div class="row align-items-center justify-content-between g-3">
                <div class="col-12 col-lg-7">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-2xl bg-success text-white rounded-3 d-flex align-items-center justify-content-center shadow">
                            <span class="fas fa-cart-plus fa-2x"></span>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                <h5 class="mb-0 fw-bold text-900">Purchase Order /New Purchase Order</h5>
                                <span class="badge bg-success-subtle text-success rounded-pill fs-11 fw-semi-bold">
                                    <span class="fas fa-shield-alt me-1"></span>Procurement Desk
                                </span>
                            </div>
                            <p class="text-700 fs-11 mb-0">
                                Create and record purchase orders from suppliers for raw materials, inventory restocks, or requisition fulfillment. 
                                Manage supplier procurement, monitor approval status, and process incoming Goods Receiving Notes (GRN).
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-5 text-lg-end">
                    <div class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                        <a href="{{ route('purchase-orders.create') }}" class="btn btn-success btn-lg px-3 py-2 fw-bold shadow-sm d-inline-flex align-items-center">
                            <span class="fas fa-plus-circle me-2"></span> Purchase Order
                        </a>
                        <a href="{{ route('goods-receipts.create') }}" class="btn btn-outline-primary btn-sm px-3 py-2 fw-semi-bold">
                            <span class="fas fa-truck-loading me-1"></span> Receive Goods (GRN)
                        </a>
                        <a href="{{ route('purchase-orders.index') }}" class="btn btn-link text-600 btn-sm text-decoration-none fw-bold">
                            View All Orders ({{ $pendingPurchaseOrdersCount }}) &rarr;
                        </a>
                    </div>
                </div>
            </div>

            @if(isset($recentPurchaseOrders) && $recentPurchaseOrders->isNotEmpty())
                <hr class="my-3 text-200">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-700 fs-11 fw-bold text-uppercase">
                        <span class="fas fa-history me-1 text-primary"></span>Recent Purchase Orders
                    </span>
                    <a href="{{ route('purchase-orders.index') }}" class="fs-11 text-primary fw-bold text-decoration-none">
                        All Purchase Orders &rarr;
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 fs-10 bg-white rounded shadow-2xs">
                        <thead class="bg-light text-700">
                            <tr>
                                <th class="ps-3 py-2">PO #</th>
                                <th>Supplier</th>
                                <th>Date</th>
                                <th class="text-end">Amount</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentPurchaseOrders as $po)
                                <tr>
                                    <td class="ps-3 fw-bold text-900 font-monospace">
                                        <a href="{{ route('purchase-orders.edit', $po->id) }}" class="text-decoration-none">
                                            {{ $po->po_number }}
                                        </a>
                                    </td>
                                    <td class="fw-semi-bold text-800">{{ $po->supplier->name ?? 'N/A' }}</td>
                                    <td class="text-muted">{{ $po->po_date ? \Carbon\Carbon::parse($po->po_date)->format('d M Y') : '—' }}</td>
                                    <td class="text-end font-monospace fw-bold text-900">
                                        {{ number_format($po->grand_total ?? $po->total_amount ?? 0, 2) }}
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $badgeClass = match($po->status) {
                                                'Approved' => 'bg-success-subtle text-success',
                                                'Received' => 'bg-info-subtle text-info',
                                                'PendingApproval' => 'bg-warning-subtle text-warning',
                                                'Cancelled' => 'bg-danger-subtle text-danger',
                                                default => 'bg-secondary-subtle text-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }} rounded-pill px-2 py-1">
                                            {{ $po->status }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('purchase-orders.edit', $po->id) }}" class="btn btn-outline-secondary btn-xs px-2">
                                            <span class="fas fa-edit me-1"></span>Edit
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Prominent "New Purchase Invoice or Add New Purchase Invoice" Feature Card -->
    <div class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, rgba(44, 123, 229, 0.08) 0%, rgba(20, 162, 184, 0.05) 100%); border-left: 5px solid #2c7be5 !important;">
        <div class="card-body p-4">
            <div class="row align-items-center justify-content-between g-3">
                <div class="col-12 col-lg-7">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-2xl bg-primary text-white rounded-3 d-flex align-items-center justify-content-center shadow">
                            <span class="fas fa-file-invoice-dollar fa-2x"></span>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                <h5 class="mb-0 fw-bold text-900">Purchase Invoice / New Purchase Invoice</h5>
                                <span class="badge bg-primary-subtle text-primary rounded-pill fs-11 fw-semi-bold">
                                    <span class="fas fa-receipt me-1"></span>Supplier Billing Desk
                                </span>
                            </div>
                            <p class="text-700 fs-11 mb-0">
                                Record and process vendor purchase invoices and bills against received goods (GRN) or purchase orders (PO). 
                                Validate billed line items, calculate taxes and discounts, and post directly to Chart of Accounts and supplier ledgers.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-5 text-lg-end">
                    <div class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                        <a href="{{ route('purchase-invoices.create') }}" class="btn btn-primary btn-lg px-3 py-2 fw-bold shadow-sm d-inline-flex align-items-center">
                            <span class="fas fa-plus-circle me-2"></span>Purchase Invoice
                        </a>
                        <a href="{{ route('purchase-invoices.index') }}" class="btn btn-outline-secondary btn-sm px-3 py-2 fw-semi-bold">
                            <span class="fas fa-file-invoice me-1"></span> View Invoices ({{ $pendingInvoicesCount }})
                        </a>
                        <a href="{{ route('suppliers.index') }}" class="btn btn-link text-600 btn-sm text-decoration-none fw-bold">
                            Suppliers Directory &rarr;
                        </a>
                    </div>
                </div>
            </div>

            @if(isset($recentPurchaseInvoices) && $recentPurchaseInvoices->isNotEmpty())
                <hr class="my-3 text-200">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-700 fs-11 fw-bold text-uppercase">
                        <span class="fas fa-history me-1 text-primary"></span>Recent Purchase Invoices
                    </span>
                    <a href="{{ route('purchase-invoices.index') }}" class="fs-11 text-primary fw-bold text-decoration-none">
                        All Purchase Invoices &rarr;
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 fs-10 bg-white rounded shadow-2xs">
                        <thead class="bg-light text-700">
                            <tr>
                                <th class="ps-3 py-2">Invoice #</th>
                                <th>Supplier</th>
                                <th>Billing Date</th>
                                <th>Ref Invoice #</th>
                                <th class="text-end">Net Amount</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentPurchaseInvoices as $inv)
                                <tr>
                                    <td class="ps-3 fw-bold text-900 font-monospace">
                                        <a href="{{ route('purchase-invoices.edit', $inv->id) }}" class="text-decoration-none">
                                            {{ $inv->invoice_number }}
                                        </a>
                                    </td>
                                    <td class="fw-semi-bold text-800">{{ $inv->supplier->name ?? 'N/A' }}</td>
                                    <td class="text-muted">{{ $inv->purchase_date ? \Carbon\Carbon::parse($inv->purchase_date)->format('d M Y') : '—' }}</td>
                                    <td class="text-600 font-monospace">{{ $inv->reference_number ?: '—' }}</td>
                                    <td class="text-end font-monospace fw-bold text-900">
                                        Rs. {{ number_format($inv->net_amount ?? 0, 2) }}
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $badgeClass = match($inv->status) {
                                                'Posted' => 'bg-success-subtle text-success',
                                                'Draft' => 'bg-warning-subtle text-warning',
                                                'Cancelled' => 'bg-danger-subtle text-danger',
                                                default => 'bg-secondary-subtle text-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }} rounded-pill px-2 py-1">
                                            {{ $inv->status }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('purchase-invoices.edit', $inv->id) }}" class="btn btn-outline-secondary btn-xs px-2">
                                            <span class="fas fa-edit me-1"></span>Edit
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Main Tables Section -->
    <div class="row g-3">
        <!-- Pending Requisitions from Departments -->
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-900">
                        <span class="fas fa-clock text-warning me-2"></span>Pending Department Requisitions
                    </h6>
                    <a href="{{ route('departments.requests') }}" class="fs-11 text-primary fw-bold text-decoration-none">
                        View All ({{ $pendingRequisitionsCount }}) &rarr;
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 fs-10">
                            <thead class="bg-light text-700">
                                <tr>
                                    <th class="ps-3 py-2">Req #</th>
                                    <th>Department</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingRequests as $req)
                                    <tr>
                                        <td class="ps-3 fw-bold text-900 font-monospace">{{ $req->request_number }}</td>
                                        <td>{{ $req->department->name ?? 'General' }}</td>
                                        <td class="text-muted">{{ $req->request_date ? $req->request_date->format('d M Y') : '—' }}</td>
                                        <td>
                                            <span class="badge bg-warning-subtle text-warning rounded-pill px-2 py-1">Pending</span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="{{ route('departments.issue') }}" class="btn btn-primary btn-xs px-2">
                                                <span class="fas fa-dolly me-1"></span>Issue
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <span class="fas fa-check-circle text-success fs-7 d-block mb-1"></span>
                                            All department stock requisitions have been fulfilled.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Stock Dispatches -->
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-900">
                        <span class="fas fa-history text-primary me-2"></span>Recent Stock Issues
                    </h6>
                    <a href="{{ route('departments.ledger') }}" class="fs-11 text-primary fw-bold text-decoration-none">
                        Stock Ledger &rarr;
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 fs-10">
                            <thead class="bg-light text-700">
                                <tr>
                                    <th class="ps-3 py-2">Issue #</th>
                                    <th>Department</th>
                                    <th>Date</th>
                                    <th>Receiver</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentIssues as $issue)
                                    <tr>
                                        <td class="ps-3 fw-bold text-primary font-monospace">{{ $issue->issue_number }}</td>
                                        <td>{{ $issue->department->name ?? 'General' }}</td>
                                        <td class="text-muted">{{ $issue->issue_date ? $issue->issue_date->format('d M Y') : '—' }}</td>
                                        <td class="text-600">{{ $issue->received_by ?? 'Kitchen / Staff' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            No recent stock issues recorded yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Threshold & Reorder Monitoring -->
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-900">
                <span class="fas fa-exclamation-triangle text-warning me-2"></span>Inventory Catalog & Reorder Thresholds
            </h6>
            <a href="{{ route('inventory.stock') }}" class="fs-11 text-primary fw-bold text-decoration-none">
                Full Stock Matrix &rarr;
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 fs-10">
                    <thead class="bg-light text-700">
                        <tr>
                            <th class="ps-3 py-2">Item Code</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>UOM</th>
                            <th class="text-center">Min Threshold</th>
                            <th class="text-center">Reorder Level</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($itemsWithThresholds as $item)
                            <tr>
                                <td class="ps-3 font-monospace text-muted">{{ $item->item_code }}</td>
                                <td class="fw-bold text-900">{{ $item->name }}</td>
                                <td><span class="badge bg-light text-dark">{{ $item->category->name ?? 'General' }}</span></td>
                                <td>{{ $item->unit->short_name ?? ($item->unit->name ?? 'units') }}</td>
                                <td class="text-center fw-bold text-danger">{{ number_format($item->minimum_stock_level, 2) }}</td>
                                <td class="text-center fw-bold text-warning">{{ number_format($item->reorder_level, 2) }}</td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('inventory.stock-takes.index') }}" class="btn btn-outline-secondary btn-xs">
                                        <span class="fas fa-sliders-h me-1"></span>Adjust
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No item thresholds defined. Update item minimum levels in Item Catalog.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
