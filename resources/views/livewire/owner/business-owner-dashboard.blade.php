<div class="container-fluid px-0">
    <!-- Top Header Bar with Multi-Branch & Timeframe Switcher -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-3">
            <div class="row flex-between-center g-3">
                <div class="col-12 col-md-auto">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-xl bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center shadow-sm">
                            <span class="fas fa-chart-pie fa-lg"></span>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-900 d-flex align-items-center gap-2">
                                {{ $marquee->name ?? 'Banquet Operations & Financial Hub' }}
                                <span class="badge bg-success-subtle text-success rounded-pill fs-11">Live Financials</span>
                            </h4>
                            <p class="text-600 fs-11 mb-0">
                                Real-time double-entry ledger metrics, event schedules, and branch operational performance.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-auto">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
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

                        <!-- Timeframe Selector -->
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn {{ $timeframe === 'today' ? 'btn-primary' : 'btn-outline-secondary' }}" wire:click="$set('timeframe', 'today')">Today</button>
                            <button type="button" class="btn {{ $timeframe === 'week' ? 'btn-primary' : 'btn-outline-secondary' }}" wire:click="$set('timeframe', 'week')">Week</button>
                            <button type="button" class="btn {{ $timeframe === 'month' ? 'btn-primary' : 'btn-outline-secondary' }}" wire:click="$set('timeframe', 'month')">This Month</button>
                            <button type="button" class="btn {{ $timeframe === 'year' ? 'btn-primary' : 'btn-outline-secondary' }}" wire:click="$set('timeframe', 'year')">Year</button>
                        </div>

                        <!-- Quick New Booking -->
                        <a href="{{ route('bookings.create') }}" class="btn btn-primary btn-sm fw-bold shadow-sm">
                            <span class="fas fa-plus me-1"></span> Book an Event
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Double-Entry Financial P&L Cards Row -->
    <div class="row g-3 mb-3">
        <!-- 1. Realized Revenue -->
        <div class="col-6 col-md-4 col-xxl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-1">
                                <h6 class="text-700 fs-11 mb-0">Realized Revenue</h6>
                            </div>
                            <h3 class="mb-0 fw-bolder text-success mt-1">PKR {{ number_format($realizedRevenue / 1000, 1) }}k</h3>
                            <span class="fs-11 text-muted">From completed events</span>
                        </div>
                        <div class="avatar avatar-m bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-hand-holding-usd fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Customer Advance Deposits Held -->
        <div class="col-6 col-md-4 col-xxl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-1">
                                <h6 class="text-700 fs-11 mb-0">Advances Held</h6>
                                <span class="badge bg-info-subtle text-info rounded-pill" style="font-size: 8px;">Liability</span>
                            </div>
                            <h3 class="mb-0 fw-bolder text-info mt-1">PKR {{ number_format($customerAdvanceHeld / 1000, 1) }}k</h3>
                            <span class="fs-11 text-muted">Upcoming token deposits</span>
                        </div>
                        <div class="avatar avatar-m bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-piggy-bank fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Pending Receivables -->
        <div class="col-6 col-md-4 col-xxl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 mb-1">Receivables Due</h6>
                            <h3 class="mb-0 fw-bolder text-warning">PKR {{ number_format($pendingReceivables / 1000, 1) }}k</h3>
                            <span class="fs-11 text-muted">Outstanding balances</span>
                        </div>
                        <div class="avatar avatar-m bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-file-invoice fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Operating Expenses -->
        <div class="col-6 col-md-4 col-xxl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 mb-1">Expenses Paid</h6>
                            <h3 class="mb-0 fw-bolder text-danger">PKR {{ number_format($operatingExpenses / 1000, 1) }}k</h3>
                            <span class="fs-11 text-muted">Approved operational bills</span>
                        </div>
                        <div class="avatar avatar-m bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-receipt fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Net Operating Cashflow -->
        <div class="col-6 col-md-4 col-xxl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 mb-1">Net Margin</h6>
                            <h3 class="mb-0 fw-bolder {{ $netOperatingCashflow >= 0 ? 'text-primary' : 'text-danger' }}">
                                PKR {{ number_format($netOperatingCashflow / 1000, 1) }}k
                            </h3>
                            <span class="fs-11 text-muted">Revenue - Expenses</span>
                        </div>
                        <div class="avatar avatar-m bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-balance-scale fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. Operational Footprint -->
        <div class="col-6 col-md-4 col-xxl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 mb-1">Total Bookings</h6>
                            <h3 class="mb-0 fw-bolder text-dark">{{ $totalBookings }}</h3>
                            <span class="fs-11 text-success fw-semi-bold">{{ $confirmedBookings }} Confirmed</span>
                        </div>
                        <div class="avatar avatar-m bg-secondary-subtle text-secondary rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-calendar-check fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                                    <th class="py-2 text-end">Grand Total</th>
                                    <th class="py-2 text-center">Financial Status</th>
                                    <th class="px-3 py-2 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($todayEvents as $event)
                                <tr>
                                    <td class="px-3 py-2 align-middle">
                                        <div class="fw-bold text-900">{{ $event->eventType->event_type_name ?? 'Wedding Reception' }}</div>
                                        <span class="fs-11 text-muted">Customer: <a href="{{ route('customers.show', $event->customer_id) }}">{{ $event->customer->full_name ?? 'N/A' }}</a></span>
                                    </td>
                                    <td class="py-2 align-middle">
                                        <span class="badge bg-info-subtle text-info rounded-pill">{{ $event->hall->hall_name ?? 'Main Hall' }}</span>
                                    </td>
                                    <td class="py-2 align-middle text-center text-700">
                                        {{ $event->slot->slot_name ?? 'Night Shift' }}
                                    </td>
                                    <td class="py-2 align-middle text-center fw-bold">
                                        {{ number_format($event->guest_count) }} Pax
                                    </td>
                                    <td class="py-2 align-middle text-end fw-bold text-900">
                                        PKR {{ number_format($event->grand_total) }}
                                    </td>
                                    <td class="py-2 align-middle text-center">
                                        @if($event->receivable_amount <= 0)
                                            <span class="badge bg-success-subtle text-success rounded-pill">Fully Paid</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning rounded-pill">Due: {{ number_format($event->receivable_amount / 1000, 0) }}k</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 align-middle text-end">
                                        <a href="{{ route('bookings.show', $event->id) }}" class="btn btn-outline-primary btn-sm px-2">
                                            <span class="fas fa-eye"></span>
                                        </a>
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
                                    <th class="py-2 text-center">Guests</th>
                                    <th class="py-2 text-end">Advance Recv</th>
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
                                    </td>
                                    <td class="py-2 align-middle text-700">
                                        {{ $upcoming->hall->hall_name ?? 'Main Hall' }}
                                    </td>
                                    <td class="py-2 align-middle text-center fw-bold">
                                        {{ number_format($upcoming->guest_count) }}
                                    </td>
                                    <td class="py-2 align-middle text-end text-success fw-bold">
                                        PKR {{ number_format($upcoming->advance_received) }}
                                    </td>
                                    <td class="px-3 py-2 align-middle text-end">
                                        <a href="{{ route('bookings.show', $upcoming->id) }}" class="btn btn-falcon-default btn-sm px-2">
                                            <span class="fas fa-eye"></span>
                                        </a>
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
                    <!-- Low Stock Alerts -->
                    @if($lowStockItems->isNotEmpty())
                    <div class="alert alert-warning border-0 p-2 fs-11 mb-2 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fas fa-boxes me-1"></span> <strong>{{ $lowStockItems->count() }} Low Stock Inventory Items:</strong>
                            <div class="text-700 fs-11 mt-1">
                                @foreach($lowStockItems->take(3) as $item)
                                    <span class="badge bg-warning-subtle text-warning me-1">{{ $item->name }}: {{ $item->current_stock }} {{ $item->unit->unit_symbol ?? '' }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Overdue Receivables Alert -->
                    @if($overdueReceivablesCount > 0)
                    <div class="alert alert-danger border-0 p-2 fs-11 mb-2 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fas fa-exclamation-circle me-1"></span>
                            <strong>{{ $overdueReceivablesCount }} Overdue Receivables</strong> from past completed functions.
                        </div>
                        <a href="{{ route('finance.payments') }}" class="btn btn-danger btn-sm fs-11 px-2 py-0">Collect</a>
                    </div>
                    @endif

                    @if($lowStockItems->isEmpty() && $overdueReceivablesCount === 0)
                    <div class="text-center py-3 text-success fs-11">
                        <span class="fas fa-check-circle fa-2x mb-1 d-block"></span>
                        All operational alerts clear. Inventory & ledgers healthy.
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
                                <span class="text-muted fs-11">Wizard / One-page event booking</span>
                            </div>
                            <span class="fas fa-chevron-right text-400 fs-11"></span>
                        </a>
                        <a href="{{ route('finance.payments') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                            <span class="fas fa-money-bill-wave text-success"></span>
                            <div class="flex-1">
                                <div class="fw-bold">Record Customer Receipt</div>
                                <span class="text-muted fs-11">Token advance or balance installment</span>
                            </div>
                            <span class="fas fa-chevron-right text-400 fs-11"></span>
                        </a>
                        <a href="{{ route('expenses.index') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                            <span class="fas fa-file-invoice-dollar text-danger"></span>
                            <div class="flex-1">
                                <div class="fw-bold">Log Operating Expense</div>
                                <span class="text-muted fs-11">Salaries, utilities, maintenance</span>
                            </div>
                            <span class="fas fa-chevron-right text-400 fs-11"></span>
                        </a>
                        <a href="{{ route('customers.create') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                            <span class="fas fa-user-plus text-info"></span>
                            <div class="flex-1">
                                <div class="fw-bold">Register Customer / Lead</div>
                                <span class="text-muted fs-11">Customer profile & CRM log</span>
                            </div>
                            <span class="fas fa-chevron-right text-400 fs-11"></span>
                        </a>
                        <a href="{{ route('departments.requests') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                            <span class="fas fa-dolly text-secondary"></span>
                            <div class="flex-1">
                                <div class="fw-bold">Kitchen Stock Request</div>
                                <span class="text-muted fs-11">Raw materials from main warehouse</span>
                            </div>
                            <span class="fas fa-chevron-right text-400 fs-11"></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
