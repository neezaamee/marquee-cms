<div>
    <!-- KPI Row -->
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden" style="min-width: 12rem">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase fs-11">Today's Expenses</h6>
                            <h3 class="fw-bold font-monospace text-primary mb-0">{{ number_format($todayExpenses, 2) }}</h3>
                            <span class="fs-11 text-muted">Base Currency (PKR)</span>
                        </div>
                        <div class="icon-item bg-subtle-primary text-primary rounded-circle"><span class="fas fa-coins"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden" style="min-width: 12rem">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase fs-11">This Month</h6>
                            <h3 class="fw-bold font-monospace text-success mb-0">{{ number_format($monthExpenses, 2) }}</h3>
                            <span class="fs-11 text-muted">Base Currency (PKR)</span>
                        </div>
                        <div class="icon-item bg-subtle-success text-success rounded-circle"><span class="fas fa-calendar-alt"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden" style="min-width: 12rem">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase fs-11">Vendor AP Balance</h6>
                            <h3 class="fw-bold font-monospace text-danger mb-0">{{ number_format($vendorOutstanding, 2) }}</h3>
                            <span class="fs-11 text-muted">Outstanding Payables</span>
                        </div>
                        <div class="icon-item bg-subtle-danger text-danger rounded-circle"><span class="fas fa-handshake"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden" style="min-width: 12rem">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase fs-11">Utilities Overdue</h6>
                            <h3 class="fw-bold font-monospace text-warning mb-0">{{ $utilityBillsDue }}</h3>
                            <span class="fs-11 text-muted">Unpaid Utility Bills</span>
                        </div>
                        <div class="icon-item bg-subtle-warning text-warning rounded-circle"><span class="fas fa-bolt"></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Center Section: Budgets, Categories & Branch Grid -->
    <div class="row g-3 mb-3">
        <!-- Budget Consumption card -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><span class="fas fa-chart-pie me-2 text-primary"></span>Monthly Budget Consumption</h6>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    @php
                        $percent = $allocatedBudget > 0 ? ($consumedBudget / $allocatedBudget) * 100 : 0;
                        $pb = 'bg-success';
                        if ($percent >= 90) { $pb = 'bg-danger'; }
                        elseif ($percent >= 70) { $pb = 'bg-warning'; }
                    @endphp
                    <div class="text-center mb-3">
                        <h1 class="fw-bold text-700 mb-1">{{ number_format($percent, 1) }}%</h1>
                        <span class="text-muted fs-11">Spent of allocated budget limits</span>
                    </div>
                    <div class="progress mb-3" style="height: 12px;">
                        <div class="progress-bar {{ $pb }}" role="progressbar" style="width: {{ min($percent, 100) }}%;" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-2 font-monospace fs-11 fw-bold text-700">
                        <div>Allocated: {{ number_format($allocatedBudget, 2) }} PKR</div>
                        <div>Spent: {{ number_format($consumedBudget, 2) }} PKR</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending workflow alerts -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><span class="fas fa-tasks me-2 text-primary"></span>Pending Workflow Tasks</h6>
                </div>
                <div class="card-body d-flex flex-column justify-content-center text-center">
                    @if($pendingApprovals > 0)
                        <div class="icon-item bg-subtle-warning text-warning rounded-circle mx-auto mb-3" style="width: 60px; height: 60px; font-size: 28px;">
                            <span class="fas fa-clock"></span>
                        </div>
                        <h4 class="fw-bold text-800">{{ $pendingApprovals }} Pending Approvals</h4>
                        <p class="text-muted fs-11">Expenses are waiting for authorization checkpoints.</p>
                        <a href="{{ route('expenses.index') }}?status=Pending+Approval" class="btn btn-falcon-warning btn-sm mx-auto">Authorize Now</a>
                    @else
                        <div class="icon-item bg-subtle-success text-success rounded-circle mx-auto mb-3" style="width: 60px; height: 60px; font-size: 28px;">
                            <span class="fas fa-check-circle"></span>
                        </div>
                        <h4 class="fw-bold text-800">All Clear</h4>
                        <p class="text-muted fs-11">You have no pending expense approvals.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Distributions Lists -->
    <div class="row g-3 mb-3">
        <!-- Categories breakdown -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><span class="fas fa-tags me-2 text-primary"></span>Top MTD Expense Categories</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush fs-11 font-sans-serif">
                        @forelse($categoryBreakdown as $cat)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="fw-semi-bold">{{ $cat->category->name ?? 'Unclassified' }}</span>
                                <span class="fw-bold font-monospace text-secondary">{{ number_format($cat->total, 2) }} PKR</span>
                            </li>
                        @empty
                            <li class="list-group-item text-center py-4 text-muted">No expenses recorded this month.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <!-- Branch breakdown -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><span class="fas fa-building me-2 text-primary"></span>Branch Expenditures MTD</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush fs-11 font-sans-serif">
                        @forelse($branchBreakdown as $br)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="fw-semi-bold">{{ $br->branch->name ?? 'Head Office' }}</span>
                                <span class="fw-bold font-monospace text-secondary">{{ number_format($br->total, 2) }} PKR</span>
                            </li>
                        @empty
                            <li class="list-group-item text-center py-4 text-muted">No expenses recorded this month.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Expenses Table -->
    <div class="card">
        <div class="card-header bg-light">
            <h6 class="mb-0"><span class="fas fa-history me-2 text-primary"></span>Recent Expense Vouchers</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped fs-11 mb-0 align-middle table-hover">
                    <thead class="bg-200">
                        <tr>
                            <th class="px-3">Voucher No</th>
                            <th>Date</th>
                            <th>Branch</th>
                            <th>Category</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Status</th>
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
                                <td class="px-3"><a href="{{ route('expenses.show', $rec->id) }}" class="fw-bold">{{ $rec->expense_number }}</a></td>
                                <td>{{ $rec->expense_date->format('Y-m-d') }}</td>
                                <td>{{ $rec->branch->name ?? 'Head Office' }}</td>
                                <td>{{ $rec->is_multiline ? 'Split Entries' : ($rec->category->name ?? '—') }}</td>
                                <td class="text-end fw-bold font-monospace">{{ number_format($rec->total_amount, 2) }} {{ $rec->currency->code }}</td>
                                <td class="text-center"><span class="badge badge-subtle-{{ $c }} rounded-pill">{{ $rec->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No expenses recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
