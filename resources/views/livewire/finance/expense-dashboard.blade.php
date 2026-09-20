<div>
    <!-- Action Toolbar & Filter Bar -->
    <div class="card mb-3 shadow-none border">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 text-800"><span class="fas fa-chart-line me-2 text-primary"></span>Expenses & Payables Dashboard</h5>
                    <span class="text-muted fs-11">Overview of operational costs, petty cash drawers, and budget allocations</span>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm px-3">
                        <span class="fas fa-plus me-1"></span> New Expense
                    </a>
                    <a href="{{ route('expenses.create') }}?payment_method=Petty+Cash" class="btn btn-falcon-success btn-sm">
                        <span class="fas fa-wallet me-1"></span> Disburse Petty Cash
                    </a>
                    <a href="{{ route('expenses.budgets') }}" class="btn btn-falcon-default btn-sm">
                        <span class="fas fa-shield-alt me-1"></span> Set Budget
                    </a>
                    <a href="{{ route('expenses.index') }}" class="btn btn-falcon-default btn-sm">
                        <span class="fas fa-list me-1"></span> Expense Register
                    </a>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="row g-2 align-items-center mt-2 pt-2 border-top">
                <div class="col-md-7">
                    <div class="btn-group btn-group-sm" role="group">
                        <button wire:click="$set('period', 'today')" class="btn {{ $period === 'today' ? 'btn-primary' : 'btn-falcon-default' }}" type="button">Today</button>
                        <button wire:click="$set('period', 'this_month')" class="btn {{ $period === 'this_month' ? 'btn-primary' : 'btn-falcon-default' }}" type="button">This Month</button>
                        <button wire:click="$set('period', 'last_month')" class="btn {{ $period === 'last_month' ? 'btn-primary' : 'btn-falcon-default' }}" type="button">Last Month</button>
                        <button wire:click="$set('period', 'this_quarter')" class="btn {{ $period === 'this_quarter' ? 'btn-primary' : 'btn-falcon-default' }}" type="button">This Quarter</button>
                        <button wire:click="$set('period', 'this_year')" class="btn {{ $period === 'this_year' ? 'btn-primary' : 'btn-falcon-default' }}" type="button">This Year</button>
                    </div>
                </div>
                <div class="col-md-5 d-flex justify-content-md-end">
                    <select wire:model.live="branch_id" class="form-select form-select-sm" style="max-width: 250px;">
                        <option value="">Company-Wide (All Branches)</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Row -->
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden shadow-none border h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase fs-11 mb-1">Today's Expenses</h6>
                            <h3 class="fw-bold font-monospace text-primary mb-0">{{ number_format($todayExpenses, 2) }}</h3>
                            <span class="fs-11 text-muted">PKR (Posted vouchers)</span>
                        </div>
                        <div class="icon-item bg-subtle-primary text-primary rounded-circle"><span class="fas fa-coins"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden shadow-none border h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase fs-11 mb-1">{{ $periodLabel }} Spend</h6>
                            <h3 class="fw-bold font-monospace text-success mb-0">{{ number_format($periodExpenses, 2) }}</h3>
                            <span class="fs-11 text-muted">PKR (Total in period)</span>
                        </div>
                        <div class="icon-item bg-subtle-success text-success rounded-circle"><span class="fas fa-calendar-alt"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden shadow-none border h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase fs-11 mb-1">Petty Cash In Drawers</h6>
                            <h3 class="fw-bold font-monospace text-info mb-0">{{ number_format($pettyCashBalance, 2) }}</h3>
                            <a href="{{ route('expenses.petty-cash') }}" class="fs-11 text-info text-decoration-none">
                                View Drawers <span class="fas fa-chevron-right ms-1"></span>
                            </a>
                        </div>
                        <div class="icon-item bg-subtle-info text-info rounded-circle"><span class="fas fa-wallet"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden shadow-none border h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase fs-11 mb-1">Accounts Payable</h6>
                            <h3 class="fw-bold font-monospace text-danger mb-0">{{ number_format($vendorOutstanding, 2) }}</h3>
                            <span class="fs-11 text-muted">Unpaid credit purchases (PKR)</span>
                        </div>
                        <div class="icon-item bg-subtle-danger text-danger rounded-circle"><span class="fas fa-handshake"></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Center Section: Budgets & Pending Approvals -->
    <div class="row g-3 mb-3">
        <!-- Budget Consumption card -->
        <div class="col-lg-6">
            <div class="card h-100 shadow-none border">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fs-11"><span class="fas fa-chart-pie me-2 text-primary"></span>Monthly Budget Consumption</h6>
                    <a href="{{ route('expenses.budgets') }}" class="fs-11 text-primary text-decoration-none">Configure Limits</a>
                </div>
                <div class="card-body d-flex flex-column justify-content-center p-4">
                    @php
                        $percent = $allocatedBudget > 0 ? ($consumedBudget / $allocatedBudget) * 100 : 0;
                        $pb = 'bg-success';
                        if ($percent >= 100) { $pb = 'bg-danger'; }
                        elseif ($percent >= 80) { $pb = 'bg-warning'; }
                    @endphp
                    <div class="text-center mb-3">
                        <h2 class="fw-bold text-800 mb-1">{{ number_format($percent, 1) }}%</h2>
                        <span class="text-muted fs-11">Of allocated monthly budget consumed</span>
                    </div>
                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar {{ $pb }}" role="progressbar" style="width: {{ min($percent, 100) }}%;" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-2 font-monospace fs-11 fw-bold text-700">
                        <div>Allocated: {{ number_format($allocatedBudget, 2) }} PKR</div>
                        <div>Consumed: {{ number_format($consumedBudget, 2) }} PKR</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending workflow alerts -->
        <div class="col-lg-6">
            <div class="card h-100 shadow-none border">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0 fs-11"><span class="fas fa-tasks me-2 text-primary"></span>Expense Approvals Workflow</h6>
                </div>
                <div class="card-body d-flex flex-column justify-content-center text-center p-4">
                    @if($pendingApprovals > 0)
                        <div class="icon-item bg-subtle-warning text-warning rounded-circle mx-auto mb-2" style="width: 50px; height: 50px; font-size: 22px;">
                            <span class="fas fa-clock"></span>
                        </div>
                        <h4 class="fw-bold text-800 mb-1">{{ $pendingApprovals }} Pending Approvals</h4>
                        <p class="text-muted fs-11 mb-3">Expense vouchers awaiting review and authorization.</p>
                        <a href="{{ route('expenses.index') }}?status=Pending+Approval" class="btn btn-falcon-warning btn-sm mx-auto px-3">
                            <span class="fas fa-check-double me-1"></span>Review & Authorize
                        </a>
                    @else
                        <div class="icon-item bg-subtle-success text-success rounded-circle mx-auto mb-2" style="width: 50px; height: 50px; font-size: 22px;">
                            <span class="fas fa-check-circle"></span>
                        </div>
                        <h4 class="fw-bold text-800 mb-1">All Clear</h4>
                        <p class="text-muted fs-11 mb-0">No expense vouchers are currently pending approval.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Distributions Lists -->
    <div class="row g-3 mb-3">
        <!-- Categories breakdown -->
        <div class="col-md-6">
            <div class="card h-100 shadow-none border">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fs-11"><span class="fas fa-tags me-2 text-primary"></span>Top Spending Heads ({{ $periodLabel }})</h6>
                    <a href="{{ route('expenses.reports') }}" class="fs-11 text-primary text-decoration-none">Full Report</a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush fs-10 font-sans-serif">
                        @forelse($categoryBreakdown as $cat)
                            @php
                                $share = $periodExpenses > 0 ? ($cat->total / $periodExpenses) * 100 : 0;
                            @endphp
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <div class="flex-grow-1 me-3">
                                    <div class="fw-semi-bold">{{ $cat->category->name ?? 'Unclassified / Split' }}</div>
                                    <div class="progress mt-1" style="height: 4px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $share }}%;"></div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold font-monospace text-800">{{ number_format($cat->total, 2) }} PKR</div>
                                    <span class="text-muted fs-11">{{ number_format($share, 1) }}%</span>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-center py-4 text-muted">No expenses recorded for this period.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <!-- Branch breakdown -->
        <div class="col-md-6">
            <div class="card h-100 shadow-none border">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0 fs-11"><span class="fas fa-building me-2 text-primary"></span>Branch Expenditures ({{ $periodLabel }})</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush fs-10 font-sans-serif">
                        @forelse($branchBreakdown as $br)
                            @php
                                $brShare = $periodExpenses > 0 ? ($br->total / $periodExpenses) * 100 : 0;
                            @endphp
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <div class="flex-grow-1 me-3">
                                    <div class="fw-semi-bold">{{ $br->branch->name ?? 'Main Branch / Head Office' }}</div>
                                    <div class="progress mt-1" style="height: 4px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $brShare }}%;"></div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold font-monospace text-800">{{ number_format($br->total, 2) }} PKR</div>
                                    <span class="text-muted fs-11">{{ number_format($brShare, 1) }}%</span>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-center py-4 text-muted">No branch expenses recorded for this period.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Expenses Table -->
    <div class="card shadow-none border">
        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fs-11"><span class="fas fa-history me-2 text-primary"></span>Recent Expense Vouchers</h6>
            <a href="{{ route('expenses.index') }}" class="fs-11 text-primary text-decoration-none">View All in Register</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle table-hover">
                    <thead class="bg-200">
                        <tr>
                            <th class="px-3">Voucher No</th>
                            <th>Date</th>
                            <th>Branch</th>
                            <th>Category</th>
                            <th>Method</th>
                            <th class="text-end">Amount (PKR)</th>
                            <th class="text-center">Status</th>
                            <th class="text-end px-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentExpenses as $rec)
                            @php
                                $scs = [
                                    'Draft' => 'secondary',
                                    'Submitted' => 'info',
                                    'Pending Approval' => 'warning',
                                    'Approved' => 'primary',
                                    'Paid' => 'success',
                                    'Posted' => 'success',
                                    'Rejected' => 'danger',
                                ];
                                $c = $scs[$rec->status] ?? 'secondary';
                            @endphp
                            <tr>
                                <td class="px-3">
                                    <a href="{{ route('expenses.show', $rec->id) }}" class="fw-bold font-monospace">{{ $rec->expense_number }}</a>
                                </td>
                                <td>{{ $rec->expense_date ? $rec->expense_date->format('Y-m-d') : '—' }}</td>
                                <td>{{ $rec->branch->name ?? 'Main' }}</td>
                                <td>{{ $rec->is_multiline ? 'Split Items' : ($rec->category->name ?? '—') }}</td>
                                <td>{{ $rec->payment_method }}</td>
                                <td class="text-end fw-bold font-monospace">{{ number_format($rec->total_amount, 2) }}</td>
                                <td class="text-center"><span class="badge badge-subtle-{{ $c }} rounded-pill">{{ $rec->status }}</span></td>
                                <td class="text-end px-3">
                                    <a href="{{ route('expenses.show', $rec->id) }}" class="btn btn-sm btn-link p-0 text-secondary" title="View details">
                                        <span class="fas fa-eye"></span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No expense records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
