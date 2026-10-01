<div class="container-fluid px-0">
    <!-- Header -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-3">
            <div class="row flex-between-center g-3">
                <div class="col-12 col-md-auto">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-xl bg-info-subtle text-info rounded-3 d-flex align-items-center justify-content-center shadow-sm">
                            <span class="fas fa-coins fa-lg"></span>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="mb-0 fw-bold text-900">
                                    {{ $marquee->name ?? 'Finance & Cashier Hub' }}
                                </h4>
                                <span class="badge bg-info-subtle text-info rounded-pill fs-11">
                                    <span class="fas fa-calculator me-1"></span>Accounts Desk
                                </span>
                            </div>
                            <p class="text-600 fs-11 mb-0">
                                Monitor cash liquidity, process customer payment verifications, manage vouchers, and track operating expenses.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-auto">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('finance.daily-cash-bank-report') }}" class="btn btn-falcon-default btn-sm fw-bold">
                            <span class="fas fa-file-invoice-dollar me-1 text-primary"></span> Daily Day-Book
                        </a>
                        <a href="{{ route('finance.payments') }}" class="btn btn-primary btn-sm fw-bold shadow-sm">
                            <span class="fas fa-check-double me-1"></span> Verify Payments
                        </a>
                        <a href="{{ route('expenses.create') }}" class="btn btn-outline-secondary btn-sm fw-bold">
                            <span class="fas fa-plus me-1"></span> Record Expense
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Action Ribbon -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('finance.payments') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift bg-body">
                <div class="card-body p-3 d-flex align-items-center gap-2">
                    <div class="bg-primary text-white rounded p-2 text-center" style="width: 38px; height: 38px;">
                        <span class="fas fa-receipt fs-10"></span>
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="mb-0 text-900 fs-10 fw-bold text-truncate">Post Payments</h6>
                        <span class="text-500 fs-11">Stage 2 verification</span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('finance.cash-bank') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift bg-body">
                <div class="card-body p-3 d-flex align-items-center gap-2">
                    <div class="bg-success text-white rounded p-2 text-center" style="width: 38px; height: 38px;">
                        <span class="fas fa-university fs-10"></span>
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="mb-0 text-900 fs-10 fw-bold text-truncate">Cash & Bank</h6>
                        <span class="text-500 fs-11">Liquidity balances</span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('expenses.index') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift bg-body">
                <div class="card-body p-3 d-flex align-items-center gap-2">
                    <div class="bg-danger text-white rounded p-2 text-center" style="width: 38px; height: 38px;">
                        <span class="fas fa-file-invoice-dollar fs-10"></span>
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="mb-0 text-900 fs-10 fw-bold text-truncate">Expenses</h6>
                        <span class="text-500 fs-11">Disbursements</span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('finance.journal-vouchers.index') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift bg-body">
                <div class="card-body p-3 d-flex align-items-center gap-2">
                    <div class="bg-warning text-white rounded p-2 text-center" style="width: 38px; height: 38px;">
                        <span class="fas fa-book fs-10"></span>
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="mb-0 text-900 fs-10 fw-bold text-truncate">Journal Vouchers</h6>
                        <span class="text-500 fs-11">Double entry</span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('finance.payment-vouchers.index') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift bg-body">
                <div class="card-body p-3 d-flex align-items-center gap-2">
                    <div class="bg-info text-white rounded p-2 text-center" style="width: 38px; height: 38px;">
                        <span class="fas fa-money-check-alt fs-10"></span>
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="mb-0 text-900 fs-10 fw-bold text-truncate">Payment Vouchers</h6>
                        <span class="text-500 fs-11">Vendor payouts</span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('finance.coa-categories') }}" class="card border-0 shadow-sm text-decoration-none h-100 hover-lift bg-body">
                <div class="card-body p-3 d-flex align-items-center gap-2">
                    <div class="bg-secondary text-white rounded p-2 text-center" style="width: 38px; height: 38px;">
                        <span class="fas fa-sitemap fs-10"></span>
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="mb-0 text-900 fs-10 fw-bold text-truncate">Chart of Accounts</h6>
                        <span class="text-500 fs-11">General ledger</span>
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
                            <span class="text-muted fs-11 fw-semi-bold text-uppercase">Cash & Bank Liquidity</span>
                            <h3 class="mb-0 fw-bold text-success mt-1">Rs. {{ number_format($totalCashBankBalance, 2) }}</h3>
                            <span class="fs-11 text-muted">{{ $cashBankAccounts->count() }} active accounts</span>
                        </div>
                        <div class="avatar avatar-lg bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-wallet fs-8"></span>
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
                            <span class="text-muted fs-11 fw-semi-bold text-uppercase">Unposted Payments</span>
                            <h3 class="mb-0 fw-bold text-warning mt-1">{{ number_format($pendingPaymentsCount) }}</h3>
                            <span class="fs-11 text-muted">Rs. {{ number_format($pendingPaymentsTotal, 2) }} pending</span>
                        </div>
                        <div class="avatar avatar-lg bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-hourglass-half fs-8"></span>
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
                            <span class="text-muted fs-11 fw-semi-bold text-uppercase">Today's Collections</span>
                            <h3 class="mb-0 fw-bold text-primary mt-1">Rs. {{ number_format($todayCollectionsTotal, 2) }}</h3>
                            <span class="fs-11 text-muted">Verified booking deposits</span>
                        </div>
                        <div class="avatar avatar-lg bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-hand-holding-usd fs-8"></span>
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
                            <span class="text-muted fs-11 fw-semi-bold text-uppercase">Active Bank Accounts</span>
                            <h3 class="mb-0 fw-bold text-info mt-1">{{ $cashBankAccounts->count() }}</h3>
                            <span class="fs-11 text-muted">Configured payment channels</span>
                        </div>
                        <div class="avatar avatar-lg bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-landmark fs-8"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Section -->
    <div class="row g-3">
        <!-- Stage 2 Unposted Payments Verification Queue -->
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-900">
                        <span class="fas fa-clock text-warning me-2"></span>Unposted Payments (Stage 2 Verification)
                    </h6>
                    <a href="{{ route('finance.payments') }}" class="fs-11 text-primary fw-bold text-decoration-none">
                        View All ({{ $pendingPaymentsCount }}) &rarr;
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 fs-10">
                            <thead class="bg-light text-700">
                                <tr>
                                    <th class="ps-3 py-2">Receipt #</th>
                                    <th>Customer / Booking</th>
                                    <th>Method</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPendingPayments as $payment)
                                    <tr>
                                        <td class="ps-3 fw-bold text-900 font-monospace">{{ $payment->payment_number ?? $payment->receipt_number ?? ('PAY-' . $payment->id) }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $payment->booking->customer->full_name ?? $payment->booking->customer->name ?? 'Guest' }}</div>
                                            <span class="text-muted font-monospace fs-11">{{ $payment->booking->booking_number ?? '' }}</span>
                                        </td>
                                        <td><span class="badge bg-light text-dark">{{ ucfirst($payment->payment_method) }}</span></td>
                                        <td class="text-end fw-bold text-success">Rs. {{ number_format($payment->amount, 2) }}</td>
                                        <td class="text-end pe-3">
                                            <a href="{{ route('finance.payments') }}" class="btn btn-primary btn-xs px-2">
                                                <span class="fas fa-check-double me-1"></span>Post
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <span class="fas fa-check-circle text-success fs-7 d-block mb-1"></span>
                                            All received payments have been verified and posted to ledgers.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cash & Bank Balances Breakdown -->
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-900">
                        <span class="fas fa-university text-primary me-2"></span>Cash & Bank Accounts
                    </h6>
                    <a href="{{ route('finance.cash-bank') }}" class="fs-11 text-primary fw-bold text-decoration-none">
                        Manage Accounts &rarr;
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 fs-10">
                            <thead class="bg-light text-700">
                                <tr>
                                    <th class="ps-3 py-2">Account Name</th>
                                    <th>Type</th>
                                    <th class="text-end pe-3">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cashBankAccounts as $account)
                                    <tr>
                                        <td class="ps-3 fw-bold text-900">
                                            {{ $account->bank_name ?: ($account->account->account_name ?? 'Cash Account') }}
                                            @if($account->account_number)
                                                <div class="text-muted font-monospace fs-11">{{ $account->account_number }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $account->type === 'cash' ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary' }}">
                                                {{ ucfirst($account->type) }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-3 fw-bold {{ ($account->account->current_balance ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                            Rs. {{ number_format($account->account->current_balance ?? 0, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">
                                            No cash or bank accounts mapped yet.
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

    <!-- Recent Expenses Table -->
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-900">
                <span class="fas fa-file-invoice-dollar text-danger me-2"></span>Recent Operating Disbursements
            </h6>
            <a href="{{ route('expenses.index') }}" class="fs-11 text-primary fw-bold text-decoration-none">
                All Expenses &rarr;
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 fs-10">
                    <thead class="bg-light text-700">
                        <tr>
                            <th class="ps-3 py-2">Voucher #</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Payment Method</th>
                            <th class="text-end pe-3">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentExpenses as $exp)
                            <tr>
                                <td class="ps-3 font-monospace fw-bold text-primary">{{ $exp->expense_number }}</td>
                                <td class="text-900">{{ $exp->description ?: 'Operating expense' }}</td>
                                <td class="text-muted">{{ $exp->expense_date ? $exp->expense_date->format('d M Y') : '—' }}</td>
                                <td><span class="badge bg-light text-dark">{{ ucfirst($exp->payment_method ?? 'Cash') }}</span></td>
                                <td class="text-end pe-3 fw-bold text-danger">Rs. {{ number_format($exp->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    No recent operating expenses recorded.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
