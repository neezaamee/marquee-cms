<div class="container-fluid px-0">
    <!-- Top Header Bar with Multi-Branch & Timeframe Switcher -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-3">
            <div class="row flex-between-center g-3">
                <div class="col-12 col-xl-auto">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-xl {{ $isBookingOfficer ? 'bg-info-subtle text-info' : 'bg-primary-subtle text-primary' }} rounded-3 d-flex align-items-center justify-content-center shadow-sm">
                            <span class="fas {{ $isBookingOfficer ? 'fa-calendar-alt' : 'fa-chart-pie' }} fa-lg"></span>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="mb-0 fw-bold text-900 d-flex align-items-center gap-2">
                                    {{ $marquee->name ?? 'Banquet Operations Hub' }}
                                    @if($isBookingOfficer)
                                        <span class="badge bg-info-subtle text-info rounded-pill fs-11">
                                            <span class="fas fa-calendar-check me-1"></span>Booking Desk & Operations
                                        </span>
                                    @else
                                        <span class="badge bg-success-subtle text-success rounded-pill fs-11">
                                            <span class="fas fa-chart-line me-1"></span>Live Financials
                                        </span>
                                    @endif
                                </h4>
                                <span class="badge badge-subtle-primary rounded-pill">{{ $periodLabel }}</span>
                            </div>
                            <p class="text-600 fs-11 mb-0">
                                @if($isBookingOfficer)
                                    Live event schedules, guest headcounts, banquet slot utilization, and booking confirmations.
                                @else
                                    Real-time double-entry ledger metrics, event schedules, sales, purchases, and cash liquidity.
                                @endif
                                <span class="text-muted ms-1">({{ $startDate->format('d M Y') }} &mdash; {{ $endDate->format('d M Y') }})</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-auto">
                    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-xl-end">
                        <!-- Owner View Mode Toggle (Preview Booking Officer View) -->
                        @if($isOwnerUser)
                        <div class="btn-group btn-group-sm" role="group" title="Switch dashboard view mode">
                            <button type="button" class="btn {{ !$isBookingOfficer ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setViewMode('executive')">
                                <span class="fas fa-briefcase me-1"></span> Owner View
                            </button>
                            <button type="button" class="btn {{ $isBookingOfficer ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setViewMode('operations')">
                                <span class="fas fa-calendar-alt me-1"></span> Booking Officer View
                            </button>
                        </div>
                        @endif

                        <!-- Quick New Booking -->
                        <a href="{{ route('bookings.create') }}" class="btn btn-primary btn-sm fw-bold shadow-sm">
                            <span class="fas fa-plus me-1"></span> Book an Event
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
                        <button type="button" wire:click="setFilterRange('today')" class="btn {{ $timeframe === 'today' ? 'btn-primary' : 'btn-outline-secondary' }}">Today</button>
                        <button type="button" wire:click="setFilterRange('this_week')" class="btn {{ in_array($timeframe, ['this_week', 'week']) ? 'btn-primary' : 'btn-outline-secondary' }}">This Week</button>
                        <button type="button" wire:click="setFilterRange('this_month')" class="btn {{ in_array($timeframe, ['this_month', 'month']) ? 'btn-primary' : 'btn-outline-secondary' }}">This Month</button>
                        <button type="button" wire:click="setFilterRange('this_quarter')" class="btn {{ in_array($timeframe, ['this_quarter', 'quarter']) ? 'btn-primary' : 'btn-outline-secondary' }}">Quarter</button>
                        <button type="button" wire:click="setFilterRange('this_year')" class="btn {{ in_array($timeframe, ['this_year', 'year']) ? 'btn-primary' : 'btn-outline-secondary' }}">This Year</button>
                        <button type="button" wire:click="setFilterRange('last_30_days')" class="btn {{ $timeframe === 'last_30_days' ? 'btn-primary' : 'btn-outline-secondary' }}">Last 30 Days</button>
                        <button type="button" wire:click="setFilterRange('custom')" class="btn {{ $timeframe === 'custom' ? 'btn-primary' : 'btn-outline-secondary' }}">Custom</button>
                    </div>
                </div>

                <div class="col-auto d-flex flex-wrap gap-2 align-items-center">
                    <!-- Multi-Business / Marquee Filter (For Super Admin or Multi-Business Owners) -->
                    @if(isset($accessibleMarquees) && $accessibleMarquees->count() > 1)
                    <div class="input-group input-group-sm" style="min-width: 220px;">
                        <span class="input-group-text bg-light text-700 fw-semibold"><span class="fas fa-building me-1 text-primary"></span> Business:</span>
                        <select wire:model.live="selectedMarqueeId" class="form-select form-select-sm fw-bold">
                            @foreach($accessibleMarquees as $m)
                                <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->city ?? 'Main' }})</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <!-- Multi-Branch Filter -->
                    @if($branches && $branches->count() > 1)
                    <div class="input-group input-group-sm" style="min-width: 200px;">
                        <span class="input-group-text bg-light text-700 fw-semibold"><span class="fas fa-code-branch me-1"></span> Branch:</span>
                        <select wire:model.live="selectedBranchId" class="form-select form-select-sm fw-bold">
                            <option value="">All Branches (Consolidated)</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <!-- Custom Date Range Pickers -->
                    @if($timeframe === 'custom')
                        <div class="d-flex align-items-center gap-1 bg-white p-1 rounded border shadow-none">
                            <span class="text-600 fs-11 ps-1 fw-semibold"><span class="fas fa-calendar-alt text-primary me-1"></span>From:</span>
                            <input type="date" wire:model.live="customDateFrom" class="form-control form-control-sm border-0 py-0" style="width: 125px;">
                            <span class="text-500 fs-11">To:</span>
                            <input type="date" wire:model.live="customDateTo" class="form-control form-control-sm border-0 py-0" style="width: 125px;">
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- OWNER / EXECUTIVE DASHBOARD CARDS --}}
    {{-- ========================================================================= --}}
    @if($canViewFinancials)
        <!-- Section 1: Sales, Purchases & Liquidity Position -->
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-800 fw-bold fs-11 text-uppercase">
                <span class="fas fa-coins me-1 text-primary"></span>Sales, Purchases & Working Capital
            </span>
            <span class="badge bg-light text-muted border fs-11">Selected Period: {{ $periodLabel }}</span>
        </div>

        <div class="row g-3 mb-3">
            <!-- 1. Total Sales -->
            <div class="col-6 col-md-4 col-xl">
                <div class="card h-100 border-0 shadow-sm bg-body-tertiary position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="d-flex align-items-center gap-1">
                                    <h6 class="text-700 fs-11 mb-0">Total Sales</h6>
                                    <span class="badge bg-primary-subtle text-primary rounded-pill" style="font-size: 8px;">Booked</span>
                                </div>
                                <h3 class="mb-0 fw-bolder text-primary mt-1">
                                    @if($totalSales >= 1000000)
                                        Rs. {{ number_format($totalSales / 1000000, 2) }}M
                                    @else
                                        Rs. {{ number_format($totalSales / 1000, 1) }}k
                                    @endif
                                </h3>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="fs-11 text-muted">{{ $totalBookingsPeriod }} event bookings</span>
                                    <span class="fas fa-arrow-right fs-11 text-primary ms-auto"></span>
                                </div>
                            </div>
                            <div class="avatar avatar-m bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-chart-line fa-lg"></span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('bookings.index') }}" class="stretched-link" title="View Bookings Register"></a>
                </div>
            </div>

            <!-- 2. Total Purchases -->
            <div class="col-6 col-md-4 col-xl">
                <div class="card h-100 border-0 shadow-sm bg-body-tertiary position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="d-flex align-items-center gap-1">
                                    <h6 class="text-700 fs-11 mb-0">Total Purchases</h6>
                                    <span class="badge bg-warning-subtle text-warning rounded-pill" style="font-size: 8px;">Procurement</span>
                                </div>
                                <h3 class="mb-0 fw-bolder text-warning mt-1">
                                    @if($totalPurchases >= 1000000)
                                        Rs. {{ number_format($totalPurchases / 1000000, 2) }}M
                                    @else
                                        Rs. {{ number_format($totalPurchases / 1000, 1) }}k
                                    @endif
                                </h3>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="fs-11 text-muted">Raw inventory & bills</span>
                                    <span class="fas fa-arrow-right fs-11 text-warning ms-auto"></span>
                                </div>
                            </div>
                            <div class="avatar avatar-m bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-shopping-cart fa-lg"></span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('purchases.dashboard') }}" class="stretched-link" title="View Purchases & Procurement Hub"></a>
                </div>
            </div>

            <!-- 3. Total Number of Guests -->
            <div class="col-6 col-md-4 col-xl">
                <div class="card h-100 border-0 shadow-sm bg-body-tertiary position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="d-flex align-items-center gap-1">
                                    <h6 class="text-700 fs-11 mb-0">Total Guests</h6>
                                    <span class="badge bg-info-subtle text-info rounded-pill" style="font-size: 8px;">Pax</span>
                                </div>
                                <h3 class="mb-0 fw-bolder text-info mt-1">{{ number_format($totalGuests) }}</h3>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="fs-11 text-muted">Avg. {{ $averageGuestsPerEvent }} pax / event</span>
                                    <span class="fas fa-arrow-right fs-11 text-info ms-auto"></span>
                                </div>
                            </div>
                            <div class="avatar avatar-m bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-users fa-lg"></span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('bookings.index') }}" class="stretched-link" title="View Guest Headcounts & Events"></a>
                </div>
            </div>

            <!-- 4. Bank Balance -->
            <div class="col-6 col-md-6 col-xl">
                <div class="card h-100 border-0 shadow-sm bg-body-tertiary position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="d-flex align-items-center gap-1">
                                    <h6 class="text-700 fs-11 mb-0">Bank Balance</h6>
                                    <span class="badge bg-primary-subtle text-primary rounded-pill" style="font-size: 8px;">Bank Accounts</span>
                                </div>
                                <h3 class="mb-0 fw-bolder text-primary mt-1">
                                    @if(abs($bankBalance) >= 1000000)
                                        Rs. {{ number_format($bankBalance / 1000000, 2) }}M
                                    @else
                                        Rs. {{ number_format($bankBalance / 1000, 1) }}k
                                    @endif
                                </h3>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="fs-11 text-muted">Bank accounts & ledger</span>
                                    <span class="fas fa-arrow-right fs-11 text-primary ms-auto"></span>
                                </div>
                            </div>
                            <div class="avatar avatar-m bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-university fa-lg"></span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('finance.cash-bank') }}" class="stretched-link" title="View Bank Accounts & Ledgers"></a>
                </div>
            </div>

            <!-- 5. Cash in Hand -->
            <div class="col-6 col-md-6 col-xl">
                <div class="card h-100 border-0 shadow-sm bg-body-tertiary position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="d-flex align-items-center gap-1">
                                    <h6 class="text-700 fs-11 mb-0">Cash in Hand</h6>
                                    <span class="badge bg-success-subtle text-success rounded-pill" style="font-size: 8px;">Cash Vault</span>
                                </div>
                                <h3 class="mb-0 fw-bolder text-success mt-1">
                                    @if(abs($cashInHand) >= 1000000)
                                        Rs. {{ number_format($cashInHand / 1000000, 2) }}M
                                    @else
                                        Rs. {{ number_format($cashInHand / 1000, 1) }}k
                                    @endif
                                </h3>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="fs-11 text-muted">Counter & petty drawer</span>
                                    <span class="fas fa-arrow-right fs-11 text-success ms-auto"></span>
                                </div>
                            </div>
                            <div class="avatar avatar-m bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-money-bill-wave fa-lg"></span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('expenses.petty-cash') }}" class="stretched-link" title="View Petty Cash & Cash Drawers"></a>
                </div>
            </div>
        </div>

        <!-- Section 2: P&L Operations & Liabilities -->
        <div class="row g-3 mb-3">
            <!-- 1. Realized Revenue -->
            <div class="col-6 col-md-4 col-xxl-2">
                <div class="card h-100 border-0 shadow-sm bg-body-tertiary position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="d-flex align-items-center gap-1">
                                    <h6 class="text-700 fs-11 mb-0">Realized Revenue</h6>
                                </div>
                                <h3 class="mb-0 fw-bolder text-success mt-1">Rs. {{ number_format($realizedRevenue / 1000, 1) }}k</h3>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="fs-11 text-muted">Completed events</span>
                                    <span class="fas fa-arrow-right fs-11 text-success ms-auto"></span>
                                </div>
                            </div>
                            <div class="avatar avatar-m bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-hand-holding-usd fa-lg"></span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('finance.revenue') }}" class="stretched-link" title="View Realized Revenue Register"></a>
                </div>
            </div>

            <!-- 2. Customer Advance Deposits Held -->
            <div class="col-6 col-md-4 col-xxl-2">
                <div class="card h-100 border-0 shadow-sm bg-body-tertiary position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="d-flex align-items-center gap-1">
                                    <h6 class="text-700 fs-11 mb-0">Advances Held</h6>
                                    <span class="badge bg-info-subtle text-info rounded-pill" style="font-size: 8px;">Liability</span>
                                </div>
                                <h3 class="mb-0 fw-bolder text-info mt-1">Rs. {{ number_format($customerAdvanceHeld / 1000, 1) }}k</h3>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="fs-11 text-muted">Upcoming token deposits</span>
                                    <span class="fas fa-arrow-right fs-11 text-info ms-auto"></span>
                                </div>
                            </div>
                            <div class="avatar avatar-m bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-piggy-bank fa-lg"></span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('finance.advance-liabilities') }}" class="stretched-link" title="View Customer Advance Liabilities"></a>
                </div>
            </div>

            <!-- 3. Pending Receivables -->
            <div class="col-6 col-md-4 col-xxl-2">
                <div class="card h-100 border-0 shadow-sm bg-body-tertiary position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-700 fs-11 mb-1">Receivables Due</h6>
                                <h3 class="mb-0 fw-bolder text-warning">Rs. {{ number_format($pendingReceivables / 1000, 1) }}k</h3>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="fs-11 text-muted">Outstanding balances</span>
                                    <span class="fas fa-arrow-right fs-11 text-warning ms-auto"></span>
                                </div>
                            </div>
                            <div class="avatar avatar-m bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-file-invoice fa-lg"></span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('finance.payments') }}" class="stretched-link" title="View Receivables & Payment Collections"></a>
                </div>
            </div>

            <!-- 4. Operating Expenses -->
            <div class="col-6 col-md-4 col-xxl-2">
                <div class="card h-100 border-0 shadow-sm bg-body-tertiary position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-700 fs-11 mb-1">Expenses Paid</h6>
                                <h3 class="mb-0 fw-bolder text-danger">Rs. {{ number_format($operatingExpenses / 1000, 1) }}k</h3>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="fs-11 text-muted">Approved & posted bills</span>
                                    <span class="fas fa-arrow-right fs-11 text-danger ms-auto"></span>
                                </div>
                            </div>
                            <div class="avatar avatar-m bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-receipt fa-lg"></span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('expenses.dashboard') }}" class="stretched-link" title="View Operating Expenses Dashboard"></a>
                </div>
            </div>

            <!-- 5. Net Operating Margin -->
            <div class="col-6 col-md-4 col-xxl-2">
                <div class="card h-100 border-0 shadow-sm bg-body-tertiary position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-700 fs-11 mb-1">Net Margin</h6>
                                <h3 class="mb-0 fw-bolder {{ $netOperatingCashflow >= 0 ? 'text-primary' : 'text-danger' }}">
                                    Rs. {{ number_format($netOperatingCashflow / 1000, 1) }}k
                                </h3>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="fs-11 text-muted">Revenue - Expenses</span>
                                    <span class="fas fa-arrow-right fs-11 text-primary ms-auto"></span>
                                </div>
                            </div>
                            <div class="avatar avatar-m bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-balance-scale fa-lg"></span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('finance.profit-loss') }}" class="stretched-link" title="View Profit & Loss Statement"></a>
                </div>
            </div>

            <!-- 6. Total Bookings -->
            <div class="col-6 col-md-4 col-xxl-2">
                <div class="card h-100 border-0 shadow-sm bg-body-tertiary position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-700 fs-11 mb-1">Total Bookings</h6>
                                <h3 class="mb-0 fw-bolder text-dark">{{ $totalBookings }}</h3>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="fs-11 text-success fw-semi-bold">{{ $confirmedBookings }} Confirmed</span>
                                    <span class="fas fa-arrow-right fs-11 text-secondary ms-auto"></span>
                                </div>
                            </div>
                            <div class="avatar avatar-m bg-secondary-subtle text-secondary rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-calendar-check fa-lg"></span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('bookings.index') }}" class="stretched-link" title="View All Banquet Bookings"></a>
                </div>
            </div>
        </div>

        <!-- Section 3: Commercial & Efficiency Quick Stats -->
        <div id="quick-stats-section" class="card border-0 shadow-sm mb-3 bg-body-tertiary">
            <div class="card-body p-2 p-md-3">
                <div class="row g-2 align-items-center text-center text-md-start">
                    <!-- Stat 1: Collection Rate -->
                    <div class="col-6 col-md-4 col-xl-2 border-end-md position-relative">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                            <div class="avatar avatar-m {{ $collectionRate >= 70 ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-check-double"></span>
                            </div>
                            <div>
                                <span class="text-600 fs-11 d-block text-uppercase fw-semi-bold">Collection Rate</span>
                                <span class="fw-bolder fs-10 text-900 font-monospace">{{ $collectionRate }}%</span>
                                <span class="text-muted fs-11 d-block">Recovered</span>
                            </div>
                        </div>
                        <a href="{{ route('finance.payments') }}" class="stretched-link" title="View Customer Receipts"></a>
                    </div>

                    <!-- Stat 2: Net Profit Margin -->
                    <div class="col-6 col-md-4 col-xl-2 border-end-md position-relative">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                            <div class="avatar avatar-m {{ $profitMarginPct >= 0 ? 'bg-primary-subtle text-primary' : 'bg-danger-subtle text-danger' }} rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-percentage"></span>
                            </div>
                            <div>
                                <span class="text-600 fs-11 d-block text-uppercase fw-semi-bold">Net Margin</span>
                                <span class="fw-bolder fs-10 {{ $profitMarginPct >= 0 ? 'text-primary' : 'text-danger' }} font-monospace">{{ $profitMarginPct }}%</span>
                                <span class="text-muted fs-11 d-block">Net / Realized</span>
                            </div>
                        </div>
                        <a href="{{ route('finance.profit-loss') }}" class="stretched-link" title="View Profit & Loss Statement"></a>
                    </div>

                    <!-- Stat 3: Avg Booking Value -->
                    <div class="col-6 col-md-4 col-xl-2 border-end-md position-relative">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                            <div class="avatar avatar-m bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-tag"></span>
                            </div>
                            <div>
                                <span class="text-600 fs-11 d-block text-uppercase fw-semi-bold">Avg Event Value</span>
                                <span class="fw-bolder fs-10 text-900 font-monospace">
                                    @if($avgBookingValue >= 1000000)
                                        Rs. {{ number_format($avgBookingValue / 1000000, 2) }}M
                                    @else
                                        Rs. {{ number_format($avgBookingValue / 1000, 1) }}k
                                    @endif
                                </span>
                                <span class="text-muted fs-11 d-block">Per booking</span>
                            </div>
                        </div>
                        <a href="{{ route('bookings.report') }}" class="stretched-link" title="View Booking Reports"></a>
                    </div>

                    <!-- Stat 4: Avg Spend Per Guest -->
                    <div class="col-6 col-md-4 col-xl-2 border-end-md position-relative">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                            <div class="avatar avatar-m bg-secondary-subtle text-secondary rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-utensils"></span>
                            </div>
                            <div>
                                <span class="text-600 fs-11 d-block text-uppercase fw-semi-bold">Pax Rate</span>
                                <span class="fw-bolder fs-10 text-900 font-monospace">Rs. {{ number_format($avgSpendPerGuest) }}</span>
                                <span class="text-muted fs-11 d-block">Avg / guest</span>
                            </div>
                        </div>
                        <a href="{{ route('bookings.report') }}" class="stretched-link" title="View Guest Analytics"></a>
                    </div>

                    <!-- Stat 5: Advance Held Ratio -->
                    <div class="col-6 col-md-4 col-xl-2 border-end-md position-relative">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                            <div class="avatar avatar-m bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-shield-alt"></span>
                            </div>
                            <div>
                                <span class="text-600 fs-11 d-block text-uppercase fw-semi-bold">Advance Secured</span>
                                <span class="fw-bolder fs-10 text-900 font-monospace">{{ $advanceCoverageRatio }}%</span>
                                <span class="text-muted fs-11 d-block">Deposits / Sales</span>
                            </div>
                        </div>
                        <a href="{{ route('finance.advance-liabilities') }}" class="stretched-link" title="View Customer Advance Liabilities"></a>
                    </div>

                    <!-- Stat 6: CRM Lead Conversion -->
                    <div class="col-6 col-md-4 col-xl-2 position-relative">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                            <div class="avatar avatar-m bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-funnel-dollar"></span>
                            </div>
                            <div>
                                <span class="text-600 fs-11 d-block text-uppercase fw-semi-bold">Lead Win Rate</span>
                                <span class="fw-bolder fs-10 text-success font-monospace">{{ $leadConversionRate }}%</span>
                                <span class="text-muted fs-11 d-block">{{ $convertedLeadsCount }} won / {{ $leadsInPeriodCount }} inquiries</span>
                            </div>
                        </div>
                        <a href="{{ route('customers.referral-analytics') }}" class="stretched-link" title="View CRM Lead Conversion Analytics"></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Visual Analytics Grid (6-Month Trend & Event Type Share) -->
        <div id="analytics-section" class="row g-3 mb-3">
            <!-- 6-Month Commercial & P&L Performance Trend -->
            <div class="col-12 col-xl-7">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-body-tertiary py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="mb-0 fw-bold text-800">
                                <span class="fas fa-chart-line me-2 text-primary"></span>6-Month Commercial & Margin Trend
                            </h6>
                            <span class="fs-11 text-muted d-none d-sm-inline">&bull; Bookings &bull; Realized &bull; Bills</span>
                        </div>
                        <a href="{{ route('finance.profit-loss') }}" class="btn btn-link btn-sm text-primary p-0 text-decoration-none fs-11 fw-semibold">
                            P&L Statement <span class="fas fa-chevron-right ms-1 fs-11"></span>
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive scrollbar">
                            <table class="table table-sm table-striped mb-0 align-middle fs-10">
                                <thead class="bg-200 text-800">
                                    <tr>
                                        <th class="px-3 py-2">Month</th>
                                        <th class="py-2 text-center">Events</th>
                                        <th class="py-2 text-end">Booked Sales</th>
                                        <th class="py-2 text-end">Realized Rev</th>
                                        <th class="py-2 text-end">Expenses</th>
                                        <th class="py-2 text-end">Net Margin</th>
                                        <th class="px-3 py-2 text-center">Health</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($monthlyPerformanceTrend as $mTrend)
                                    @php
                                        $mNetMargin = $mTrend['net'];
                                        $mRealized = $mTrend['realized'];
                                        $healthPct = $mRealized > 0 ? max(0, min(100, round(($mNetMargin / $mRealized) * 100))) : 0;
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 fw-bold text-900">{{ $mTrend['month'] }}</td>
                                        <td class="py-2 text-center">
                                            <span class="badge bg-light text-dark border">{{ $mTrend['bookings_count'] }}</span>
                                        </td>
                                        <td class="py-2 text-end font-monospace text-700">
                                            Rs. {{ number_format($mTrend['sales'] / 1000, 1) }}k
                                        </td>
                                        <td class="py-2 text-end font-monospace text-success fw-semi-bold">
                                            Rs. {{ number_format($mTrend['realized'] / 1000, 1) }}k
                                        </td>
                                        <td class="py-2 text-end font-monospace text-danger">
                                            Rs. {{ number_format($mTrend['expenses'] / 1000, 1) }}k
                                        </td>
                                        <td class="py-2 text-end font-monospace fw-bold {{ $mNetMargin >= 0 ? 'text-primary' : 'text-danger' }}">
                                            Rs. {{ number_format($mNetMargin / 1000, 1) }}k
                                        </td>
                                        <td class="px-3 py-2 text-center" style="min-width: 90px;">
                                            @if($mRealized > 0)
                                                <div class="progress" style="height: 6px;" title="{{ $healthPct }}% Margin">
                                                    <div class="progress-bar {{ $mNetMargin >= 0 ? 'bg-success' : 'bg-danger' }}" role="progressbar" style="width: {{ $healthPct }}%"></div>
                                                </div>
                                                <span class="fs-11 text-muted">{{ $healthPct }}%</span>
                                            @else
                                                <span class="text-muted fs-11">&mdash;</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event Type Distribution & Revenue Share -->
            <div class="col-12 col-xl-5">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-body-tertiary py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="mb-0 fw-bold text-800">
                            <span class="fas fa-layer-group me-2 text-primary"></span>Event Type Distribution
                        </h6>
                        <a href="{{ route('bookings.index') }}" class="btn btn-link btn-sm text-primary p-0 text-decoration-none fs-11 fw-semibold">
                            All Events <span class="fas fa-chevron-right ms-1 fs-11"></span>
                        </a>
                    </div>
                    <div class="card-body p-3">
                        @if($eventTypeBreakdown->isEmpty())
                            <div class="text-center py-4 text-muted">
                                <span class="fas fa-glass-cheers fa-2x text-300 mb-2 d-block"></span>
                                <span class="fs-11">No event type revenue recorded in this period.</span>
                            </div>
                        @else
                            @php
                                $totalEventRev = max(1, $eventTypeBreakdown->sum('total_revenue'));
                                $palette = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];
                            @endphp
                            @foreach($eventTypeBreakdown as $idx => $evType)
                                @php
                                    $color = $palette[$idx % count($palette)];
                                    $sharePct = round(($evType->total_revenue / $totalEventRev) * 100, 1);
                                @endphp
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center fs-11 mb-1">
                                        <div>
                                            <span class="badge bg-{{ $color }}-subtle text-{{ $color }} me-1">{{ $evType->event_type_name }}</span>
                                            <span class="text-muted fs-11">({{ $evType->booking_count }} events &bull; {{ number_format($evType->total_guests) }} pax)</span>
                                        </div>
                                        <span class="font-monospace fw-bold text-900">
                                            Rs. {{ number_format($evType->total_revenue / 1000, 1) }}k
                                            <span class="text-muted fw-normal">({{ $sharePct }}%)</span>
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-{{ $color }}" role="progressbar" style="width: {{ $sharePct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 5: Venue Capacity & Slot Utilization Analytics -->
        <div class="row g-3 mb-3">
            <!-- Hall Venue Performance -->
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-body-tertiary py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-800">
                            <span class="fas fa-archway me-2 text-primary"></span>Hall Venue Performance
                        </h6>
                        <a href="{{ route('halls.index') }}" class="btn btn-link btn-sm text-primary p-0 text-decoration-none fs-11 fw-semibold">
                            Manage Halls <span class="fas fa-chevron-right ms-1 fs-11"></span>
                        </a>
                    </div>
                    <div class="card-body p-3">
                        @if($hallBreakdown->isEmpty())
                            <div class="text-center py-4 text-muted fs-11">No hall bookings in this period.</div>
                        @else
                            @php $hallMaxRev = max(1, $hallBreakdown->sum('total_revenue')); @endphp
                            @foreach($hallBreakdown as $hIdx => $hall)
                                @php $hallPct = round(($hall->total_revenue / $hallMaxRev) * 100, 1); @endphp
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center fs-11 mb-1">
                                        <span class="fw-bold text-800">
                                            <span class="fas fa-building text-400 me-1"></span>{{ $hall->hall_name }}
                                            <span class="text-muted fw-normal">({{ $hall->booking_count }} bookings)</span>
                                        </span>
                                        <span class="font-monospace fw-semi-bold text-900">Rs. {{ number_format($hall->total_revenue / 1000, 1) }}k</span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $hallPct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <!-- Shift Slot Occupancy (Lunch vs Dinner) -->
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-body-tertiary py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-800">
                            <span class="fas fa-clock me-2 text-info"></span>Shift Slot Occupancy
                        </h6>
                        <a href="{{ route('slots.index') }}" class="btn btn-link btn-sm text-info p-0 text-decoration-none fs-11 fw-semibold">
                            Manage Shifts <span class="fas fa-chevron-right ms-1 fs-11"></span>
                        </a>
                    </div>
                    <div class="card-body p-3">
                        @if($slotBreakdown->isEmpty())
                            <div class="text-center py-4 text-muted fs-11">No shift slot data in this period.</div>
                        @else
                            @php $totalSlotEvents = max(1, $slotBreakdown->sum('booking_count')); @endphp
                            @foreach($slotBreakdown as $sIdx => $slot)
                                @php
                                    $slotPct = round(($slot->booking_count / $totalSlotEvents) * 100, 1);
                                    $slotColor = $sIdx % 2 === 0 ? 'info' : 'primary';
                                @endphp
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center fs-11 mb-1">
                                        <span class="fw-bold text-800">
                                            <span class="fas fa-sun text-warning me-1"></span>{{ $slot->slot_name }}
                                            <span class="text-muted fw-normal">({{ $slot->booking_count }} events &bull; {{ number_format($slot->total_guests) }} pax)</span>
                                        </span>
                                        <span class="badge bg-{{ $slotColor }}-subtle text-{{ $slotColor }} font-monospace">{{ $slotPct }}%</span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-{{ $slotColor }}" role="progressbar" style="width: {{ $slotPct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <!-- Operational Expense Breakdown -->
            <div class="col-12 col-xl-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-body-tertiary py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-800">
                            <span class="fas fa-receipt me-2 text-danger"></span>Top Expense Categories
                        </h6>
                        <a href="{{ route('expenses.categories') }}" class="btn btn-link btn-sm text-danger p-0 text-decoration-none fs-11 fw-semibold">
                            Categories <span class="fas fa-chevron-right ms-1 fs-11"></span>
                        </a>
                    </div>
                    <div class="card-body p-3">
                        @if($topExpenseCategories->isEmpty())
                            <div class="text-center py-4 text-muted fs-11">No approved expenses in this period.</div>
                        @else
                            @php $expTotalSum = max(1, $topExpenseCategories->sum('total_amount')); @endphp
                            @foreach($topExpenseCategories as $eIdx => $expCat)
                                @php $expPct = round(($expCat->total_amount / $expTotalSum) * 100, 1); @endphp
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center fs-11 mb-1">
                                        <span class="fw-bold text-800">
                                            <span class="fas fa-file-invoice text-400 me-1"></span>{{ $expCat->category_name }}
                                            <span class="text-muted fw-normal">({{ $expCat->expense_count }} bills)</span>
                                        </span>
                                        <span class="font-monospace text-danger fw-semi-bold">Rs. {{ number_format($expCat->total_amount / 1000, 1) }}k</span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $expPct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- ========================================================================= --}}
        {{-- BOOKING OFFICER DASHBOARD CARDS (FINANCIAL METRICS MINIMIZED) --}}
        {{-- ========================================================================= --}}
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-800 fw-bold fs-11 text-uppercase">
                <span class="fas fa-clipboard-list me-1 text-primary"></span>Guest Headcount & Booking Operations
            </span>
            <span class="badge bg-info-subtle text-info border fs-11">Period: {{ $periodLabel }}</span>
        </div>

        <div class="row g-3 mb-3">
            <!-- 1. Total Number of Guests -->
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card h-100 border-0 shadow-sm bg-body-tertiary position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-700 fs-11 mb-1">Total Guests</h6>
                                <h3 class="mb-0 fw-bolder text-primary">{{ number_format($totalGuests) }}</h3>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="fs-11 text-muted">Avg. {{ $averageGuestsPerEvent }} pax / event</span>
                                    <span class="fas fa-arrow-right fs-11 text-primary ms-auto"></span>
                                </div>
                            </div>
                            <div class="avatar avatar-m bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-users fa-lg"></span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('bookings.index') }}" class="stretched-link" title="View Guest Headcounts & Events"></a>
                </div>
            </div>

            <!-- 2. Confirmed Bookings -->
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card h-100 border-0 shadow-sm bg-body-tertiary position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-700 fs-11 mb-1">Confirmed Events</h6>
                                <h3 class="mb-0 fw-bolder text-success">{{ $confirmedBookingsPeriod }}</h3>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="fs-11 text-success fw-semi-bold">Ready for execution</span>
                                    <span class="fas fa-arrow-right fs-11 text-success ms-auto"></span>
                                </div>
                            </div>
                            <div class="avatar avatar-m bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-calendar-check fa-lg"></span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('bookings.index') }}" class="stretched-link" title="View Confirmed Events"></a>
                </div>
            </div>

            <!-- 3. Tentative & Inquiries -->
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card h-100 border-0 shadow-sm bg-body-tertiary position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-700 fs-11 mb-1">Pending Inquiries</h6>
                                <h3 class="mb-0 fw-bolder text-warning">{{ $tentativeBookingsPeriod }}</h3>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="fs-11 text-warning fw-semi-bold">Draft / Needs follow-up</span>
                                    <span class="fas fa-arrow-right fs-11 text-warning ms-auto"></span>
                                </div>
                            </div>
                            <div class="avatar avatar-m bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-hourglass-half fa-lg"></span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('bookings.index') }}" class="stretched-link" title="View Inquiries & Drafts"></a>
                </div>
            </div>

            <!-- 4. Today's Live Functions -->
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card h-100 border-0 shadow-sm bg-body-tertiary position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-700 fs-11 mb-1">Today's Functions</h6>
                                <h3 class="mb-0 fw-bolder text-danger">{{ $todayEvents->count() }}</h3>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="fs-11 text-danger fw-semi-bold">Live banquet events</span>
                                    <span class="fas fa-arrow-right fs-11 text-danger ms-auto"></span>
                                </div>
                            </div>
                            <div class="avatar avatar-m bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-glass-cheers fa-lg"></span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('bookings.calendar') }}" class="stretched-link" title="View Today's Banquet Schedule"></a>
                </div>
            </div>

            <!-- 5. 7-Day Pipeline -->
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card h-100 border-0 shadow-sm bg-body-tertiary position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-700 fs-11 mb-1">Next 7 Days</h6>
                                <h3 class="mb-0 fw-bolder text-info">{{ $upcomingEvents->count() }}</h3>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="fs-11 text-muted">Upcoming schedule</span>
                                    <span class="fas fa-arrow-right fs-11 text-info ms-auto"></span>
                                </div>
                            </div>
                            <div class="avatar avatar-m bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-calendar-alt fa-lg"></span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('bookings.calendar') }}" class="stretched-link" title="View Banquet Calendar"></a>
                </div>
            </div>

            <!-- 6. Kitchen Menus Slips Pending -->
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card h-100 border-0 shadow-sm bg-body-tertiary position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-700 fs-11 mb-1">Kitchen Slips Due</h6>
                                <h3 class="mb-0 fw-bolder {{ $unprintedKitchenSlipsCount > 0 ? 'text-danger' : 'text-success' }}">{{ $unprintedKitchenSlipsCount }}</h3>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="fs-11 text-muted">Menus pending print</span>
                                    <span class="fas fa-arrow-right fs-11 text-secondary ms-auto"></span>
                                </div>
                            </div>
                            <div class="avatar avatar-m bg-secondary-subtle text-secondary rounded-circle d-flex align-items-center justify-content-center">
                                <span class="fas fa-utensils fa-lg"></span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('bookings.index') }}" class="stretched-link" title="View Kitchen Menus"></a>
                </div>
            </div>
        </div>

        <!-- Operational Analytics Grid for Booking Officer -->
        <div class="row g-3 mb-3">
            <!-- Event Type Headcount Distribution -->
            <div class="col-12 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-body-tertiary py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-800">
                            <span class="fas fa-layer-group me-2 text-primary"></span>Event Type Distribution & Guests
                        </h6>
                        <span class="fs-11 text-muted">Selected Period</span>
                    </div>
                    <div class="card-body p-3">
                        @if($eventTypeBreakdown->isEmpty())
                            <div class="text-center py-4 text-muted fs-11">No event bookings in this period.</div>
                        @else
                            @php
                                $totEvents = max(1, $eventTypeBreakdown->sum('booking_count'));
                                $palette = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];
                            @endphp
                            @foreach($eventTypeBreakdown as $idx => $evType)
                                @php
                                    $color = $palette[$idx % count($palette)];
                                    $sharePct = round(($evType->booking_count / $totEvents) * 100, 1);
                                @endphp
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center fs-11 mb-1">
                                        <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">{{ $evType->event_type_name }}</span>
                                        <span class="font-monospace fw-bold text-900">
                                            {{ $evType->booking_count }} events ({{ number_format($evType->total_guests) }} pax &bull; {{ $sharePct }}%)
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-{{ $color }}" role="progressbar" style="width: {{ $sharePct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <!-- Hall & Shift Slot Occupancy -->
            <div class="col-12 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-body-tertiary py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-800">
                            <span class="fas fa-calendar-check me-2 text-info"></span>Hall & Slot Utilization
                        </h6>
                        <span class="fs-11 text-muted">Live utilization</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <h6 class="fs-11 text-700 text-uppercase fw-bold mb-2">By Hall Venue</h6>
                                @forelse($hallBreakdown as $hall)
                                    <div class="d-flex justify-content-between align-items-center fs-11 mb-2">
                                        <span class="text-800">{{ $hall->hall_name }}</span>
                                        <span class="badge bg-light text-dark border">{{ $hall->booking_count }} events</span>
                                    </div>
                                @empty
                                    <div class="text-muted fs-11">No hall data.</div>
                                @endforelse
                            </div>
                            <div class="col-12 col-sm-6">
                                <h6 class="fs-11 text-700 text-uppercase fw-bold mb-2">By Shift Slot</h6>
                                @forelse($slotBreakdown as $slot)
                                    <div class="d-flex justify-content-between align-items-center fs-11 mb-2">
                                        <span class="text-800">{{ $slot->slot_name }}</span>
                                        <span class="badge bg-info-subtle text-info">{{ $slot->booking_count }} events</span>
                                    </div>
                                @empty
                                    <div class="text-muted fs-11">No slot data.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Operational Grid -->
    <div class="row g-3 mb-3">
        <!-- Left Column: Today's Live Functions & 7-Day Pipeline -->
        <div class="col-12 col-xl-8">
            <!-- Today's Live Functions -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body-tertiary py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger rounded-pill px-2 py-1 fs-11">
                            <span class="fas fa-dot-circle me-1 text-white"></span> TODAY'S EVENTS
                        </span>
                        <h5 class="mb-0 fw-bold text-800">Live Banquet Functions</h5>
                    </div>
                    <span class="fs-11 text-muted">{{ Carbon\Carbon::today()->format('l, F d, Y') }}</span>
                </div>
                <div class="card-body p-0">
                    @if($todayEvents->isNotEmpty())
                    <div class="table-responsive scrollbar">
                        <table class="table table-sm table-hover fs-10 mb-0">
                            <thead class="bg-200 text-800">
                                <tr>
                                    <th class="px-3 py-2">Function & Customer</th>
                                    <th class="py-2">Hall / Venue</th>
                                    <th class="py-2 text-center">Shift / Slot</th>
                                    <th class="py-2 text-center">Headcount</th>
                                    @if($canViewFinancials)
                                        <th class="py-2 text-end">Grand Total</th>
                                        <th class="py-2 text-center">Financial Status</th>
                                    @else
                                        <th class="py-2">Menu / Package</th>
                                        <th class="py-2 text-center">Operational Status</th>
                                    @endif
                                    <th class="px-3 py-2 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($todayEvents as $event)
                                <tr>
                                    <td class="px-3 py-2 align-middle">
                                        <div class="fw-bold text-900">{{ $event->eventType->event_type_name ?? 'Wedding Reception' }}</div>
                                        <span class="fs-11 text-muted">
                                            Customer: <a href="{{ route('customers.show', $event->customer_id) }}">{{ $event->customer->full_name ?? 'N/A' }}</a>
                                            @if($event->customer?->phone) | <span class="fas fa-phone-alt fs-11 text-400"></span> {{ $event->customer->phone }} @endif
                                        </span>
                                    </td>
                                    <td class="py-2 align-middle">
                                        <span class="badge bg-info-subtle text-info rounded-pill">{{ $event->hall->hall_name ?? 'Main Hall' }}</span>
                                    </td>
                                    <td class="py-2 align-middle text-center text-700">
                                        <span class="fw-semi-bold">{{ $event->slot->slot_name ?? 'Custom' }}</span>
                                        @if($event->start_time && $event->end_time)
                                            <div class="fs-11 text-muted font-monospace">{{ $event->start_time->format('h:i A') }} - {{ $event->end_time->format('h:i A') }}</div>
                                        @endif
                                    </td>
                                    <td class="py-2 align-middle text-center fw-bold fs-11 text-primary">
                                        <span class="fas fa-user-friends me-1"></span>{{ number_format($event->guest_count) }} Pax
                                    </td>

                                    @if($canViewFinancials)
                                        <td class="py-2 align-middle text-end fw-bold text-900">
                                            Rs. {{ number_format($event->grand_total) }}
                                        </td>
                                        <td class="py-2 align-middle text-center">
                                            @if($event->receivable_amount <= 0)
                                                <span class="badge bg-success-subtle text-success rounded-pill">Fully Paid</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning rounded-pill">Due: {{ number_format($event->receivable_amount / 1000, 0) }}k</span>
                                            @endif
                                        </td>
                                    @else
                                        <td class="py-2 align-middle">
                                            <span class="text-800 fw-semi-bold">{{ $event->package->package_name ?? 'Custom Menu' }}</span>
                                            @if($event->no_food)
                                                <span class="badge bg-secondary-subtle text-secondary ms-1">Hall Only</span>
                                            @endif
                                        </td>
                                        <td class="py-2 align-middle text-center">
                                            <span class="badge bg-success-subtle text-success rounded-pill">{{ $event->booking_status }}</span>
                                            @if($event->kitchen_printed_at)
                                                <span class="badge bg-info-subtle text-info rounded-pill" title="Kitchen slip printed">Kitchen Ready</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning rounded-pill" title="Kitchen slip not printed">Menu Pending</span>
                                            @endif
                                        </td>
                                    @endif

                                    <td class="px-3 py-2 align-middle text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('bookings.show', $event->id) }}" class="btn btn-falcon-default btn-sm px-2" title="View Details">
                                                <span class="fas fa-eye"></span>
                                            </a>
                                            <a href="{{ route('bookings.slip', $event->id) }}" target="_blank" class="btn btn-falcon-primary btn-sm px-2" title="Print Booking Slip">
                                                <span class="fas fa-print"></span>
                                            </a>
                                            <a href="{{ route('bookings.kitchen-slip', ['booking' => $event->id, 'lang' => 'bilingual']) }}" target="_blank" class="btn btn-falcon-warning btn-sm px-2" title="Print Kitchen Menu">
                                                <span class="fas fa-utensils"></span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4 text-muted">
                        <span class="fas fa-calendar-day fa-2x mb-2 d-block text-400"></span>
                        No functions scheduled for today. Check upcoming pipeline below.
                    </div>
                    @endif
                </div>
            </div>

            <!-- 7-Day Upcoming Event Pipeline -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-body-tertiary py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0 fw-bold text-800"><span class="fas fa-calendar-alt text-primary me-2"></span>7-Day Event Pipeline</h5>
                        <span class="fs-11 text-muted">Upcoming confirmed and reserved banquet bookings</span>
                    </div>
                    <a href="{{ route('bookings.index') }}" class="btn btn-link btn-sm text-primary p-0 text-decoration-none">View All Bookings <span class="fas fa-chevron-right ms-1 fs-11"></span></a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive scrollbar">
                        <table class="table table-sm table-hover fs-10 mb-0">
                            <thead class="bg-200 text-800">
                                <tr>
                                    <th class="px-3 py-2">Event Date</th>
                                    <th class="py-2">Event Type</th>
                                    <th class="py-2">Customer</th>
                                    <th class="py-2">Hall</th>
                                    <th class="py-2 text-center">Headcount</th>
                                    @if($canViewFinancials)
                                        <th class="py-2 text-end">Advance Recv</th>
                                    @else
                                        <th class="py-2 text-center">Shift / Slot</th>
                                    @endif
                                    <th class="px-3 py-2 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($upcomingEvents as $upcoming)
                                <tr>
                                    <td class="px-3 py-2 align-middle fw-bold text-primary">
                                        <div>{{ $upcoming->booking_date->format('M d, Y') }}</div>
                                        <span class="fs-11 text-muted">{{ $upcoming->booking_date->diffForHumans() }}</span>
                                    </td>
                                    <td class="py-2 align-middle text-900 fw-semi-bold">
                                        {{ $upcoming->eventType->event_type_name ?? 'Wedding Reception' }}
                                    </td>
                                    <td class="py-2 align-middle text-700">
                                        {{ $upcoming->customer->full_name ?? 'N/A' }}
                                        @if($upcoming->customer?->phone)
                                            <div class="fs-11 text-muted">{{ $upcoming->customer->phone }}</div>
                                        @endif
                                    </td>
                                    <td class="py-2 align-middle text-700">
                                        <span class="badge bg-info-subtle text-info rounded-pill">{{ $upcoming->hall->hall_name ?? 'Main Hall' }}</span>
                                    </td>
                                    <td class="py-2 align-middle text-center fw-bold text-primary">
                                        <span class="fas fa-users me-1"></span>{{ number_format($upcoming->guest_count) }}
                                    </td>

                                    @if($canViewFinancials)
                                        <td class="py-2 align-middle text-end text-success fw-bold">
                                            Rs. {{ number_format($upcoming->advance_received) }}
                                        </td>
                                    @else
                                        <td class="py-2 align-middle text-center text-700">
                                            {{ $upcoming->slot->slot_name ?? 'Standard' }}
                                        </td>
                                    @endif

                                    <td class="px-3 py-2 align-middle text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('bookings.show', $upcoming->id) }}" class="btn btn-falcon-default btn-sm px-2" title="View Booking">
                                                <span class="fas fa-eye"></span>
                                            </a>
                                            <a href="{{ route('bookings.slip', $upcoming->id) }}" target="_blank" class="btn btn-falcon-primary btn-sm px-2" title="Print Booking Slip">
                                                <span class="fas fa-print"></span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <span class="fas fa-calendar-times fa-2x mb-2 d-block text-400"></span>
                                        No bookings scheduled in the next 7 days.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Operational Alerts & Quick Action Shortcuts -->
        <div class="col-12 col-xl-4">
            <!-- Operational Alerts Widget -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body-tertiary py-2">
                    <h6 class="mb-0 fw-bold text-800"><span class="fas fa-bell text-warning me-2"></span>Operational Alerts</h6>
                </div>
                <div class="card-body p-3">
                    <!-- Kitchen Slips Due Alert -->
                    @if($unprintedKitchenSlipsCount > 0)
                    <div class="alert alert-warning border-0 p-2 fs-11 mb-2 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fas fa-utensils me-1"></span> <strong>{{ $unprintedKitchenSlipsCount }} Kitchen Menus Due:</strong>
                            <div class="text-700 fs-11 mt-1">Today's catering menus pending kitchen print.</div>
                        </div>
                        <a href="{{ route('bookings.index') }}" class="btn btn-warning btn-sm fs-11 px-2 py-0">Review</a>
                    </div>
                    @endif

                    <!-- Low Stock Alerts (For Owner/Manager) -->
                    @if($canViewFinancials && $lowStockItems->isNotEmpty())
                    <div class="alert alert-warning border-0 p-2 fs-11 mb-2 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fas fa-boxes me-1"></span> <strong>{{ $lowStockItems->count() }} Low Stock Inventory Items:</strong>
                            <div class="text-700 fs-11 mt-1">
                                @foreach($lowStockItems->take(3) as $item)
                                    <span class="badge bg-warning-subtle text-warning me-1">{{ $item->name }}: {{ $item->current_stock }} {{ $item->unit->short_code ?? $item->unit->name ?? '' }}</span>
                                @endforeach
                            </div>
                        </div>
                        <a href="{{ route('inventory.stock') }}" class="btn btn-warning btn-sm fs-11 px-2 py-0">Restock</a>
                    </div>
                    @endif

                    <!-- Overdue Receivables Alert (For Owner/Finance) -->
                    @if($canViewFinancials && $overdueReceivablesCount > 0)
                    <div class="alert alert-danger border-0 p-2 fs-11 mb-2 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fas fa-exclamation-circle me-1"></span>
                            <strong>{{ $overdueReceivablesCount }} Overdue Receivables</strong> from past completed functions.
                        </div>
                        <a href="{{ route('finance.payments') }}" class="btn btn-danger btn-sm fs-11 px-2 py-0">Collect</a>
                    </div>
                    @endif

                    @if($unprintedKitchenSlipsCount === 0 && (!$canViewFinancials || ($lowStockItems->isEmpty() && $overdueReceivablesCount === 0)))
                    <div class="text-center py-3 text-success fs-11">
                        <span class="fas fa-check-circle fa-2x mb-1 d-block"></span>
                        All operational alerts clear. Schedule & operations running smoothly.
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quick Operational Shortcuts -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-body-tertiary py-2">
                    <h6 class="mb-0 fw-bold text-800"><span class="fas fa-bolt text-primary me-2"></span>Operational Shortcuts</h6>
                </div>
                <div class="card-body p-2">
                    <div class="list-group list-group-flush fs-11">
                        <a href="{{ route('bookings.create') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                            <span class="fas fa-calendar-plus text-primary"></span>
                            <div class="flex-1">
                                <div class="fw-bold">Book Hall / Event</div>
                                <span class="text-muted fs-11">One-page & wizard reservation</span>
                            </div>
                            <span class="fas fa-chevron-right text-400 fs-11"></span>
                        </a>

                        <a href="{{ route('bookings.calendar') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                            <span class="fas fa-calendar-alt text-info"></span>
                            <div class="flex-1">
                                <div class="fw-bold">Banquet Schedule Calendar</div>
                                <span class="text-muted fs-11">Monthly hall slot availability</span>
                            </div>
                            <span class="fas fa-chevron-right text-400 fs-11"></span>
                        </a>

                        <a href="{{ route('customers.create') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                            <span class="fas fa-user-plus text-success"></span>
                            <div class="flex-1">
                                <div class="fw-bold">Register Customer / Lead</div>
                                <span class="text-muted fs-11">Customer profile & CRM enquiry</span>
                            </div>
                            <span class="fas fa-chevron-right text-400 fs-11"></span>
                        </a>

                        @if($canViewFinancials)
                        <a href="{{ route('finance.payments') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                            <span class="fas fa-money-bill-wave text-success"></span>
                            <div class="flex-1">
                                <div class="fw-bold">Record Customer Receipt</div>
                                <span class="text-muted fs-11">Token advance or balance installment</span>
                            </div>
                            <span class="fas fa-chevron-right text-400 fs-11"></span>
                        </a>

                        <a href="{{ route('expenses.create') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                            <span class="fas fa-plus-circle text-danger"></span>
                            <div class="flex-1">
                                <div class="fw-bold">Record New Expense</div>
                                <span class="text-muted fs-11">Post operational voucher or bill</span>
                            </div>
                            <span class="fas fa-chevron-right text-400 fs-11"></span>
                        </a>

                        <a href="{{ route('expenses.dashboard') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                            <span class="fas fa-wallet text-danger"></span>
                            <div class="flex-1">
                                <div class="fw-bold">Expense & Petty Cash Hub</div>
                                <span class="text-muted fs-11">Drawers, budgets & expense trends</span>
                            </div>
                            <span class="fas fa-chevron-right text-400 fs-11"></span>
                        </a>

                        <a href="{{ route('purchases.dashboard') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                            <span class="fas fa-shopping-bag text-warning"></span>
                            <div class="flex-1">
                                <div class="fw-bold">Purchase & Procurement</div>
                                <span class="text-muted fs-11">Vendor POs & inventory analytics</span>
                            </div>
                            <span class="fas fa-chevron-right text-400 fs-11"></span>
                        </a>

                        <a href="{{ route('finance.cash-bank') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                            <span class="fas fa-university text-primary"></span>
                            <div class="flex-1">
                                <div class="fw-bold">Cash & Bank Accounts</div>
                                <span class="text-muted fs-11">Bank ledgers & cash vault balances</span>
                            </div>
                            <span class="fas fa-chevron-right text-400 fs-11"></span>
                        </a>

                        <a href="{{ route('finance.profit-loss') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                            <span class="fas fa-balance-scale text-primary"></span>
                            <div class="flex-1">
                                <div class="fw-bold">Profit & Loss Statement</div>
                                <span class="text-muted fs-11">Comprehensive revenue vs expense report</span>
                            </div>
                            <span class="fas fa-chevron-right text-400 fs-11"></span>
                        </a>
                        @endif

                        <a href="{{ route('departments.requests') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                            <span class="fas fa-dolly text-secondary"></span>
                            <div class="flex-1">
                                <div class="fw-bold">Kitchen Stock Request</div>
                                <span class="text-muted fs-11">Raw materials from main store</span>
                            </div>
                            <span class="fas fa-chevron-right text-400 fs-11"></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
