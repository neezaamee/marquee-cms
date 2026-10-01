<div>
    <!-- Print Header (Visible only when printing) -->
    <div class="d-none d-print-block mb-4">
        <div class="text-center border-bottom pb-3">
            <h2 class="fw-bolder mb-1 text-uppercase">{{ $marquee->name ?? 'Marquee Management System' }}</h2>
            <h5 class="text-800 fw-bold mb-1">DAILY CASH & BANK TRANSACTIONS REPORT</h5>
            <div class="fs-11 text-600">
                <span><strong>Date:</strong> {{ $formattedDate }}</span>
                @if($branchId)
                    <span class="ms-3"><strong>Branch:</strong> {{ $branches->firstWhere('id', $branchId)->name ?? 'All Branches' }}</span>
                @endif
                <span class="ms-3"><strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }} by {{ auth()->user()->name }}</span>
            </div>
        </div>
    </div>

    <!-- Screen Header & Date Toolbar (Hidden on print) -->
    <div class="card mb-3 d-print-none border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center justify-content-between">
                <!-- Title & Current Scope -->
                <div class="col-12 col-md-5">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar avatar-m bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-file-invoice-dollar fs-9"></span>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold text-900">Daily Cash & Bank Report</h5>
                            <span class="text-muted fs-11">
                                Inflow, outflow, cash utilized & liquidity position for <strong>{{ $formattedDate }}</strong>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Controls: Date Picker, Quick Buttons, Branch Filter, Print -->
                <div class="col-12 col-md-7">
                    <div class="d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                        <!-- Quick Prev / Next -->
                        <div class="btn-group btn-group-sm">
                            <button type="button" wire:click="setPreviousDay" class="btn btn-outline-secondary" title="Previous Day">
                                <span class="fas fa-chevron-left"></span>
                            </button>
                            <button type="button" wire:click="setDateYesterday" class="btn btn-outline-secondary {{ $selectedDate === \Carbon\Carbon::yesterday()->format('Y-m-d') ? 'active fw-bold' : '' }}">
                                Yesterday
                            </button>
                            <button type="button" wire:click="setDateToday" class="btn btn-outline-primary {{ $isToday ? 'active fw-bold' : '' }}">
                                Today
                            </button>
                            <button type="button" wire:click="setNextDay" class="btn btn-outline-secondary" title="Next Day">
                                <span class="fas fa-chevron-right"></span>
                            </button>
                        </div>

                        <!-- Date Input -->
                        <div class="input-group input-group-sm" style="width: 150px;">
                            <span class="input-group-text bg-body-tertiary"><i class="fas fa-calendar-alt text-400"></i></span>
                            <input type="date" wire:model.live="reportDate" class="form-control form-control-sm" />
                        </div>

                        <!-- Branch Filter (if multi-branch) -->
                        @if($branches->count() > 1)
                            <select wire:model.live="branchId" class="form-select form-select-sm" style="width: 140px;">
                                <option value="">All Branches</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        @endif

                        <!-- Print Button -->
                        <button type="button" onclick="window.print()" class="btn btn-falcon-default btn-sm" title="Print Daily Report">
                            <span class="fas fa-print me-1"></span> Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5 Executive KPI Cards -->
    <div class="row g-2 mb-3">
        <!-- 1. Opening Balance -->
        <div class="col-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-700 fs-11 text-uppercase fw-semi-bold">Opening Balance</span>
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill fs-11">00:00 AM</span>
                    </div>
                    <h4 class="mb-1 fw-bolder text-900 font-monospace">
                        Rs. {{ number_format($totalOpeningBalance, 2) }}
                    </h4>
                    <div class="d-flex align-items-center justify-content-between fs-11 text-muted border-top pt-1 mt-1">
                        <span>Cash: <strong class="text-dark">Rs. {{ number_format($totalOpeningCash) }}</strong></span>
                        <span>Bank: <strong class="text-dark">Rs. {{ number_format($totalOpeningBank) }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Total Inflow / Received Today -->
        <div class="col-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary border-start border-3 border-success">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-700 fs-11 text-uppercase fw-semi-bold">Total Inflow Today</span>
                        <span class="badge bg-success-subtle text-success rounded-pill fs-11">{{ $inflowsCount }} Receipts</span>
                    </div>
                    <h4 class="mb-1 fw-bolder text-success font-monospace">
                        + Rs. {{ number_format($totalInflow, 2) }}
                    </h4>
                    <div class="d-flex align-items-center justify-content-between fs-11 text-muted border-top pt-1 mt-1">
                        <span class="text-success"><i class="fas fa-money-bill-wave me-1"></i>Cash: <strong>Rs. {{ number_format($totalCashInflow) }}</strong></span>
                        <span class="text-primary"><i class="fas fa-university me-1"></i>Bank: <strong>Rs. {{ number_format($totalBankInflow) }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Total Outflow / Utilized Today -->
        <div class="col-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary border-start border-3 border-danger">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-700 fs-11 text-uppercase fw-semi-bold">Cash & Bank Utilized</span>
                        <span class="badge bg-danger-subtle text-danger rounded-pill fs-11">{{ $outflowsCount }} Payments</span>
                    </div>
                    <h4 class="mb-1 fw-bolder text-danger font-monospace">
                        - Rs. {{ number_format($totalOutflow, 2) }}
                    </h4>
                    <div class="d-flex align-items-center justify-content-between fs-11 text-muted border-top pt-1 mt-1">
                        <span class="text-danger"><i class="fas fa-hand-holding-usd me-1"></i>Cash: <strong>Rs. {{ number_format($totalCashOutflow) }}</strong></span>
                        <span class="text-info"><i class="fas fa-credit-card me-1"></i>Bank: <strong>Rs. {{ number_format($totalBankOutflow) }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Net Day Position -->
        <div class="col-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary border-start border-3 {{ $netDailyPosition >= 0 ? 'border-primary' : 'border-warning' }}">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-700 fs-11 text-uppercase fw-semi-bold">Net Daily Cash Flow</span>
                        <span class="badge bg-{{ $netDailyPosition >= 0 ? 'primary' : 'warning' }}-subtle text-{{ $netDailyPosition >= 0 ? 'primary' : 'warning' }} rounded-pill fs-11">
                            {{ $netDailyPosition >= 0 ? 'Net Surplus' : 'Net Deficit' }}
                        </span>
                    </div>
                    <h4 class="mb-1 fw-bolder font-monospace {{ $netDailyPosition >= 0 ? 'text-primary' : 'text-warning' }}">
                        {{ $netDailyPosition >= 0 ? '+' : '' }} Rs. {{ number_format($netDailyPosition, 2) }}
                    </h4>
                    <div class="d-flex align-items-center justify-content-between fs-11 text-muted border-top pt-1 mt-1">
                        <span>Net Cash: <strong class="{{ $netCashPosition >= 0 ? 'text-success' : 'text-danger' }}">Rs. {{ number_format($netCashPosition) }}</strong></span>
                        <span>Net Bank: <strong class="{{ $netBankPosition >= 0 ? 'text-success' : 'text-danger' }}">Rs. {{ number_format($netBankPosition) }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Closing Balance -->
        <div class="col-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary border-start border-3 border-info">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-700 fs-11 text-uppercase fw-semi-bold">Closing Balance</span>
                        <span class="badge bg-info-subtle text-info rounded-pill fs-11">End of Day</span>
                    </div>
                    <h4 class="mb-1 fw-bolder text-info-emphasis font-monospace">
                        Rs. {{ number_format($totalClosingBalance, 2) }}
                    </h4>
                    <div class="d-flex align-items-center justify-content-between fs-11 text-muted border-top pt-1 mt-1">
                        <span>Cash: <strong class="text-dark">Rs. {{ number_format($totalClosingCash) }}</strong></span>
                        <span>Bank: <strong class="text-dark">Rs. {{ number_format($totalClosingBank) }}</strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liquidity Summary Comparative Matrix -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-header bg-body-tertiary py-2 d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-bold text-800">
                <span class="fas fa-balance-scale text-primary me-2"></span>Daily Liquidity Reconciliation
            </h6>
            <span class="badge bg-secondary-subtle text-secondary font-monospace fs-11">
                {{ $formattedDate }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 fs-11">
                    <thead class="bg-body-secondary text-uppercase text-700 fw-bold">
                        <tr>
                            <th class="ps-3 py-2">Account Segment</th>
                            <th class="text-end py-2">Opening Balance</th>
                            <th class="text-end py-2 text-success">Total Inflows (+)</th>
                            <th class="text-end py-2 text-danger">Total Utilized (-)</th>
                            <th class="text-end py-2">Net Day Change</th>
                            <th class="text-end pe-3 py-2 text-primary">Closing Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Cash in Hand -->
                        <tr>
                            <td class="ps-3 py-2 fw-bold text-900">
                                <span class="fas fa-money-bill-wave text-success me-2"></span>Cash in Hand (Drawer & Safe Vault)
                            </td>
                            <td class="text-end font-monospace">Rs. {{ number_format($totalOpeningCash, 2) }}</td>
                            <td class="text-end font-monospace text-success fw-bold">+ Rs. {{ number_format($totalCashInflow, 2) }}</td>
                            <td class="text-end font-monospace text-danger fw-bold">- Rs. {{ number_format($totalCashOutflow, 2) }}</td>
                            <td class="text-end font-monospace fw-bold {{ $netCashPosition >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $netCashPosition >= 0 ? '+' : '' }} Rs. {{ number_format($netCashPosition, 2) }}
                            </td>
                            <td class="text-end pe-3 font-monospace fw-bolder text-900 fs-10">Rs. {{ number_format($totalClosingCash, 2) }}</td>
                        </tr>

                        <!-- Bank Accounts -->
                        <tr>
                            <td class="ps-3 py-2 fw-bold text-900">
                                <span class="fas fa-university text-primary me-2"></span>Bank Accounts (Transfers, Cheques, Online)
                            </td>
                            <td class="text-end font-monospace">Rs. {{ number_format($totalOpeningBank, 2) }}</td>
                            <td class="text-end font-monospace text-success fw-bold">+ Rs. {{ number_format($totalBankInflow, 2) }}</td>
                            <td class="text-end font-monospace text-danger fw-bold">- Rs. {{ number_format($totalBankOutflow, 2) }}</td>
                            <td class="text-end font-monospace fw-bold {{ $netBankPosition >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $netBankPosition >= 0 ? '+' : '' }} Rs. {{ number_format($netBankPosition, 2) }}
                            </td>
                            <td class="text-end pe-3 font-monospace fw-bolder text-900 fs-10">Rs. {{ number_format($totalClosingBank, 2) }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-body-secondary fw-bolder border-top">
                        <tr>
                            <td class="ps-3 py-2 text-uppercase">Grand Total Liquidity</td>
                            <td class="text-end font-monospace">Rs. {{ number_format($totalOpeningBalance, 2) }}</td>
                            <td class="text-end font-monospace text-success">+ Rs. {{ number_format($totalInflow, 2) }}</td>
                            <td class="text-end font-monospace text-danger">- Rs. {{ number_format($totalOutflow, 2) }}</td>
                            <td class="text-end font-monospace {{ $netDailyPosition >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $netDailyPosition >= 0 ? '+' : '' }} Rs. {{ number_format($netDailyPosition, 2) }}
                            </td>
                            <td class="text-end pe-3 font-monospace text-primary fs-10">Rs. {{ number_format($totalClosingBalance, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs & Search Bar (Hidden on print) -->
    <div class="card mb-3 border-0 shadow-sm d-print-none">
        <div class="card-body p-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <!-- Main Tabs -->
            <ul class="nav nav-pills gap-1">
                <li class="nav-item">
                    <button type="button" wire:click="setTab('all')" class="nav-link py-1 px-3 fs-11 {{ $tab === 'all' ? 'active fw-bold' : 'text-700' }}">
                        <span class="fas fa-list me-1"></span> All Activity
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" wire:click="setTab('inflow')" class="nav-link py-1 px-3 fs-11 {{ $tab === 'inflow' ? 'active bg-success text-white fw-bold' : 'text-700' }}">
                        <span class="fas fa-arrow-down text-success me-1"></span> Incoming Receipts
                        <span class="badge {{ $tab === 'inflow' ? 'bg-white text-success' : 'bg-success text-white' }} ms-1">{{ $inflowsCount }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" wire:click="setTab('outflow')" class="nav-link py-1 px-3 fs-11 {{ $tab === 'outflow' ? 'active bg-danger text-white fw-bold' : 'text-700' }}">
                        <span class="fas fa-arrow-up text-danger me-1"></span> Utilized / Outflows
                        <span class="badge {{ $tab === 'outflow' ? 'bg-white text-danger' : 'bg-danger text-white' }} ms-1">{{ $outflowsCount }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" wire:click="setTab('accounts')" class="nav-link py-1 px-3 fs-11 {{ $tab === 'accounts' ? 'active bg-primary text-white fw-bold' : 'text-700' }}">
                        <span class="fas fa-landmark text-primary me-1"></span> Account Balances
                        <span class="badge {{ $tab === 'accounts' ? 'bg-white text-primary' : 'bg-primary text-white' }} ms-1">{{ $accountPositions->count() }}</span>
                    </button>
                </li>
            </ul>

            <!-- Filter Method & Search -->
            <div class="d-flex align-items-center gap-2">
                <!-- Method filter -->
                <div class="btn-group btn-group-sm">
                    <button type="button" wire:click="setMethodFilter('all')" class="btn btn-outline-secondary {{ $methodFilter === 'all' ? 'active' : '' }}">All</button>
                    <button type="button" wire:click="setMethodFilter('cash')" class="btn btn-outline-secondary {{ $methodFilter === 'cash' ? 'active text-success' : '' }}">Cash Only</button>
                    <button type="button" wire:click="setMethodFilter('bank')" class="btn btn-outline-secondary {{ $methodFilter === 'bank' ? 'active text-primary' : '' }}">Bank Only</button>
                </div>

                <!-- Search -->
                <div class="input-group input-group-sm" style="width: 220px;">
                    <span class="input-group-text bg-body-tertiary"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" placeholder="Search receipt, payee, customer..." />
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SECTION 1: INCOMING TRANSACTIONS (INFLOWS) -->
    <!-- ========================================================================= -->
    @if($tab === 'all' || $tab === 'inflow')
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-header bg-success-subtle py-2 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="fas fa-arrow-circle-down text-success fa-lg"></span>
                    <div>
                        <h6 class="mb-0 fw-bold text-success-emphasis">
                            Incoming Collections & Receipts (Inflows)
                        </h6>
                        <span class="fs-11 text-muted">Customer booking advances, final bill settlements, and security deposits</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success text-white font-monospace fs-11 px-2 py-1">
                        Total Inflow: Rs. {{ number_format($totalInflow, 2) }}
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                @if($allInflows->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 fs-11">
                            <thead class="bg-body-secondary text-uppercase text-700 fw-bold">
                                <tr>
                                    <th class="ps-3 py-2" style="width: 130px;">Receipt #</th>
                                    <th class="py-2">Customer & Contact</th>
                                    <th class="py-2">Booking & Hall</th>
                                    <th class="py-2 text-center" style="width: 100px;">Method</th>
                                    <th class="py-2">Receiving Account</th>
                                    <th class="py-2">Payment Type</th>
                                    <th class="py-2 text-center" style="width: 110px;">Verification</th>
                                    <th class="text-end pe-3 py-2" style="width: 130px;">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($allInflows as $payment)
                                    @php
                                        $isCash = strtolower($payment->payment_method) === 'cash';
                                    @endphp
                                    <tr>
                                        <td class="ps-3 py-2 font-monospace fw-bold text-900">
                                            {{ $payment->payment_number ?? $payment->receipt_number ?? ('PAY-' . $payment->id) }}
                                            <div class="text-muted fs-11">{{ $payment->created_at ? $payment->created_at->format('h:i A') : '' }}</div>
                                        </td>
                                        <td class="py-2">
                                            <div class="fw-bold text-900">{{ $payment->booking?->customer?->full_name ?? 'Walk-in Guest' }}</div>
                                            <div class="text-muted fs-11 font-monospace">{{ $payment->booking?->customer?->phone_number ?? '' }}</div>
                                        </td>
                                        <td class="py-2">
                                            @if($payment->booking)
                                                <a href="{{ route('bookings.show', $payment->booking->id) }}" target="_blank" class="fw-semi-bold text-primary">
                                                    #{{ $payment->booking->booking_number }}
                                                </a>
                                                <div class="text-muted fs-11">{{ $payment->booking->hall?->name ?? 'Hall' }}</div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="py-2 text-center">
                                            @if($isCash)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2">
                                                    <i class="fas fa-money-bill-wave me-1"></i>Cash
                                                </span>
                                            @else
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2">
                                                    <i class="fas fa-university me-1"></i>{{ $payment->payment_method }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-2">
                                            <div class="text-800">{{ $payment->account?->name ?? ($isCash ? 'Cash in Hand (1001)' : 'Bank Deposit') }}</div>
                                            @if($payment->bank_reference)
                                                <span class="text-muted fs-11 font-monospace">Ref: {{ $payment->bank_reference }}</span>
                                            @endif
                                        </td>
                                        <td class="py-2">
                                            <span class="badge bg-secondary-subtle text-secondary fs-11 text-capitalize">
                                                {{ str_replace('_', ' ', $payment->payment_type) }}
                                            </span>
                                        </td>
                                        <td class="py-2 text-center">
                                            @if($payment->status === 'posted')
                                                <span class="badge bg-success text-white rounded-pill fs-11">
                                                    <i class="fas fa-check-circle me-1"></i>Posted
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark rounded-pill fs-11">
                                                    <i class="fas fa-clock me-1"></i>Pending Verify
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-3 py-2 font-monospace fw-bolder text-success fs-10">
                                            + Rs. {{ number_format($payment->amount, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-body-secondary fw-bold">
                                <tr>
                                    <td colspan="7" class="ps-3 py-2 text-uppercase">Subtotal Inflows for {{ $formattedDate }}</td>
                                    <td class="text-end pe-3 font-monospace text-success fw-bolder fs-10">+ Rs. {{ number_format($totalInflow, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <span class="fas fa-receipt fa-2x mb-2 d-block text-400"></span>
                        No incoming payments or collections recorded for {{ $formattedDate }}.
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- SECTION 2: UTILIZED TRANSACTIONS (OUTFLOWS) -->
    <!-- ========================================================================= -->
    @if($tab === 'all' || $tab === 'outflow')
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-header bg-danger-subtle py-2 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="fas fa-arrow-circle-up text-danger fa-lg"></span>
                    <div>
                        <h6 class="mb-0 fw-bold text-danger-emphasis">
                            Utilized Funds & Disbursements (Outflows)
                        </h6>
                        <span class="fs-11 text-muted">Payment vouchers (CPV/BPV), supplier settlements, and operating expenses</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-danger text-white font-monospace fs-11 px-2 py-1">
                        Total Outflow: Rs. {{ number_format($totalOutflow, 2) }}
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                @if($allOutflows->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 fs-11">
                            <thead class="bg-body-secondary text-uppercase text-700 fw-bold">
                                <tr>
                                    <th class="ps-3 py-2" style="width: 140px;">Ref / Voucher #</th>
                                    <th class="py-2">Payee / Beneficiary</th>
                                    <th class="py-2">Classification / Category</th>
                                    <th class="py-2">Description / Purpose</th>
                                    <th class="py-2 text-center" style="width: 100px;">Method</th>
                                    <th class="py-2">Disbursing Account</th>
                                    <th class="py-2 text-center" style="width: 100px;">Status</th>
                                    <th class="text-end pe-3 py-2" style="width: 130px;">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($allOutflows as $item)
                                    <tr>
                                        <td class="ps-3 py-2 font-monospace fw-bold text-900">
                                            {{ $item['ref_no'] }}
                                            <div class="text-muted fs-11">{{ $item['time'] }}</div>
                                        </td>
                                        <td class="py-2 fw-semi-bold text-900">
                                            {{ $item['payee_title'] }}
                                        </td>
                                        <td class="py-2">
                                            <span class="badge bg-secondary-subtle text-secondary fs-11">
                                                {{ $item['category_label'] }}
                                            </span>
                                        </td>
                                        <td class="py-2 text-muted">
                                            {{ \Illuminate\Support\Str::limit($item['description'], 45) }}
                                        </td>
                                        <td class="py-2 text-center">
                                            @if($item['is_cash'])
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2">
                                                    <i class="fas fa-money-bill-wave me-1"></i>Cash
                                                </span>
                                            @else
                                                <span class="badge bg-info-subtle text-info border border-info-subtle px-2">
                                                    <i class="fas fa-university me-1"></i>{{ $item['method'] }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-2 text-800">
                                            {{ $item['account_name'] }}
                                        </td>
                                        <td class="py-2 text-center">
                                            <span class="badge bg-success-subtle text-success rounded-pill fs-11 text-capitalize">
                                                {{ $item['status'] }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-3 py-2 font-monospace fw-bolder text-danger fs-10">
                                            - Rs. {{ number_format($item['amount'], 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-body-secondary fw-bold">
                                <tr>
                                    <td colspan="7" class="ps-3 py-2 text-uppercase">Subtotal Outflows for {{ $formattedDate }}</td>
                                    <td class="text-end pe-3 font-monospace text-danger fw-bolder fs-10">- Rs. {{ number_format($totalOutflow, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <span class="fas fa-hand-holding-usd fa-2x mb-2 d-block text-400"></span>
                        No cash or bank disbursements recorded for {{ $formattedDate }}.
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- SECTION 3: ACCOUNT-WISE BALANCES & AUDIT TRAIL -->
    <!-- ========================================================================= -->
    @if($tab === 'all' || $tab === 'accounts')
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-header bg-primary-subtle py-2 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="fas fa-landmark text-primary fa-lg"></span>
                    <div>
                        <h6 class="mb-0 fw-bold text-primary-emphasis">
                            Individual Cash Drawers & Bank Account Ledger Audit
                        </h6>
                        <span class="fs-11 text-muted">Opening vs Inflow vs Outflow vs Closing Balance per registered account</span>
                    </div>
                </div>
                <a href="{{ route('finance.general-ledger') }}" target="_blank" class="btn btn-outline-primary btn-sm fs-11 d-print-none">
                    <i class="fas fa-book me-1"></i>General Ledger
                </a>
            </div>
            <div class="card-body p-0">
                @if($accountPositions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 fs-11">
                            <thead class="bg-body-secondary text-uppercase text-700 fw-bold">
                                <tr>
                                    <th class="ps-3 py-2" style="width: 100px;">COA Code</th>
                                    <th class="py-2">Account Title & Institution</th>
                                    <th class="py-2 text-center" style="width: 90px;">Type</th>
                                    <th class="text-end py-2">Opening</th>
                                    <th class="text-end py-2 text-success">Today Inflows (+)</th>
                                    <th class="text-end py-2 text-danger">Today Outflows (-)</th>
                                    <th class="text-end pe-3 py-2 text-primary">Closing Balance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($accountPositions as $acc)
                                    <tr>
                                        <td class="ps-3 py-2 font-monospace fw-bold text-700">
                                            {{ $acc['account_code'] }}
                                        </td>
                                        <td class="py-2">
                                            <div class="fw-bold text-900">{{ $acc['account_name'] }}</div>
                                            <span class="text-muted fs-11">{{ $acc['bank_name'] }} {{ $acc['account_number'] !== 'N/A' ? '• ' . $acc['account_number'] : '' }}</span>
                                        </td>
                                        <td class="py-2 text-center">
                                            <span class="badge bg-{{ $acc['is_cash'] ? 'success' : 'primary' }}-subtle text-{{ $acc['is_cash'] ? 'success' : 'primary' }} text-capitalize">
                                                {{ $acc['type'] }}
                                            </span>
                                        </td>
                                        <td class="text-end font-monospace">Rs. {{ number_format($acc['opening_balance'], 2) }}</td>
                                        <td class="text-end font-monospace text-success fw-bold">+ Rs. {{ number_format($acc['day_inflow'], 2) }}</td>
                                        <td class="text-end font-monospace text-danger fw-bold">- Rs. {{ number_format($acc['day_outflow'], 2) }}</td>
                                        <td class="text-end pe-3 font-monospace fw-bolder text-900 fs-10">Rs. {{ number_format($acc['closing_balance'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-body-secondary fw-bold">
                                <tr>
                                    <td colspan="3" class="ps-3 py-2 text-uppercase">Reconciled Total</td>
                                    <td class="text-end font-monospace">Rs. {{ number_format($totalOpeningBalance, 2) }}</td>
                                    <td class="text-end font-monospace text-success">+ Rs. {{ number_format($totalInflow, 2) }}</td>
                                    <td class="text-end font-monospace text-danger">- Rs. {{ number_format($totalOutflow, 2) }}</td>
                                    <td class="text-end pe-3 font-monospace text-primary fw-bolder fs-10">Rs. {{ number_format($totalClosingBalance, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <span class="fas fa-university fa-2x mb-2 d-block text-400"></span>
                        No Cash & Bank accounts configured. Configure them under <a href="{{ route('finance.cash-bank') }}">Finance &gt; Cash & Bank Accounts</a>.
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Formal Print Sign-off Box (Visible only when printing) -->
    <div class="d-none d-print-block mt-5 pt-4">
        <div class="row text-center">
            <div class="col-4">
                <div class="border-top pt-2" style="border-top: 1px dashed #666 !important;">
                    <div class="fw-bold">Prepared By</div>
                    <div class="fs-11 text-muted">Cashier / Front Desk Officer</div>
                </div>
            </div>
            <div class="col-4">
                <div class="border-top pt-2" style="border-top: 1px dashed #666 !important;">
                    <div class="fw-bold">Verified By</div>
                    <div class="fs-11 text-muted">Accountant / Internal Auditor</div>
                </div>
            </div>
            <div class="col-4">
                <div class="border-top pt-2" style="border-top: 1px dashed #666 !important;">
                    <div class="fw-bold">Approved By</div>
                    <div class="fs-11 text-muted">Business Owner / General Manager</div>
                </div>
            </div>
        </div>
    </div>
</div>
