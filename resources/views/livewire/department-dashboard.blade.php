<div class="container-fluid p-0">
    <!-- Header -->
    <div class="row mb-3 align-items-center justify-content-between">
        <div class="col-auto">
            <h3 class="mb-0 text-secondary">
                <span class="fas fa-sitemap me-2 text-primary"></span>Department Management Dashboard
            </h3>
            <p class="text-600 fs-10 mb-0">Overview of Cost Centers, Stock Requisitions, Attendance, & Production</p>
        </div>
        <div class="col-auto d-flex gap-2">
            <a href="{{ route('departments.requests') }}" class="btn btn-outline-primary btn-sm">
                <span class="fas fa-plus me-1"></span>New Request
            </a>
            <a href="{{ route('departments.attendance') }}" class="btn btn-outline-success btn-sm">
                <span class="fas fa-user-check me-1"></span>Attendance
            </a>
            <a href="{{ route('departments.production') }}" class="btn btn-primary btn-sm">
                <span class="fas fa-fire me-1"></span>Kitchen Batch
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="card h-100 border-start border-primary border-4 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-500 fs-11 fw-semi-bold text-uppercase">Departments</div>
                            <div class="fs-4 fw-bold text-dark font-monospace">{{ $totalDepartments }}</div>
                        </div>
                        <div class="icon-item bg-primary-subtle text-primary rounded-circle">
                            <span class="fas fa-building"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="card h-100 border-start border-info border-4 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-500 fs-11 fw-semi-bold text-uppercase">Staff Assigned</div>
                            <div class="fs-4 fw-bold text-dark font-monospace">{{ $totalEmployees }}</div>
                        </div>
                        <div class="icon-item bg-info-subtle text-info rounded-circle">
                            <span class="fas fa-users"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="card h-100 border-start border-success border-4 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-500 fs-11 fw-semi-bold text-uppercase">Today's Attendance</div>
                            <div class="fs-4 fw-bold text-success font-monospace">{{ $attendancePercentage }}%</div>
                            <div class="fs-11 text-muted">{{ $todayPresentCount }} / {{ $todayExpectedAttendance }} Present</div>
                        </div>
                        <div class="icon-item bg-success-subtle text-success rounded-circle">
                            <span class="fas fa-user-check"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="card h-100 border-start border-warning border-4 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-500 fs-11 fw-semi-bold text-uppercase">Pending Requisitions</div>
                            <div class="fs-4 fw-bold text-warning font-monospace">{{ $pendingRequestsCount }}</div>
                            <div class="fs-11 text-muted">{{ $approvedRequestsCount }} Approved & Ready</div>
                        </div>
                        <div class="icon-item bg-warning-subtle text-warning rounded-circle">
                            <span class="fas fa-clipboard-list"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="card h-100 border-start border-secondary border-4 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-500 fs-11 fw-semi-bold text-uppercase">Dispatches Today</div>
                            <div class="fs-4 fw-bold text-dark font-monospace">{{ $todayDispatches }}</div>
                        </div>
                        <div class="icon-item bg-200 text-600 rounded-circle">
                            <span class="fas fa-truck-loading"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="card h-100 border-start border-danger border-4 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-500 fs-11 fw-semi-bold text-uppercase">Kitchen Batches</div>
                            <div class="fs-4 fw-bold text-danger font-monospace">{{ $todayProductionBatches }}</div>
                        </div>
                        <div class="icon-item bg-danger-subtle text-danger rounded-circle">
                            <span class="fas fa-fire"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="row g-3 mb-4">
        <!-- Recent Stock Requisitions -->
        <div class="col-lg-7">
            <div class="card border border-200 h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-700">Recent Stock Requisitions</h5>
                    <a href="{{ route('departments.requests') }}" class="btn btn-link btn-sm p-0 text-decoration-none">View All &rarr;</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 align-middle fs-10">
                            <thead class="bg-200">
                                <tr>
                                    <th class="px-3">Req #</th>
                                    <th>Department</th>
                                    <th>Requested By</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentRequests as $req)
                                    <tr>
                                        <td class="px-3 font-monospace fw-bold">{{ $req->request_number }}</td>
                                        <td class="fw-semi-bold">{{ $req->department->name }}</td>
                                        <td>{{ $req->requester->name ?? '—' }}</td>
                                        <td>{{ $req->request_date->format('Y-m-d') }}</td>
                                        <td>
                                            @if($req->status === 'Approved')
                                                <span class="badge bg-success-subtle text-success">Approved</span>
                                            @elseif($req->status === 'Issued')
                                                <span class="badge bg-info-subtle text-info">Issued</span>
                                            @elseif($req->status === 'Rejected')
                                                <span class="badge bg-danger-subtle text-danger">Rejected</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No stock requisitions submitted yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Stock Consuming Departments -->
        <div class="col-lg-5">
            <div class="card border border-200 h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-700">Top Consuming Departments</h5>
                    <a href="{{ route('departments.ledger') }}" class="btn btn-link btn-sm p-0 text-decoration-none">View Ledger &rarr;</a>
                </div>
                <div class="card-body p-3">
                    @forelse($topConsumingDepartments as $item)
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-200">
                            <div>
                                <h6 class="mb-0 fw-semi-bold">{{ $item->department->name ?? 'Department #' . $item->department_id }}</h6>
                                <span class="fs-11 text-muted">{{ $item->item_types }} distinct inventory items</span>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary fs-11 font-monospace">{{ number_format($item->total_issued_units, 2) }} Units</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted fs-10">No stock movement recorded in department ledgers yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Department Master Status Summary Table -->
    <div class="row g-3">
        <div class="col-12">
            <div class="card border border-200">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-700">Departments Summary & Staffing Roster</h5>
                    <a href="{{ route('departments.index') }}" class="btn btn-primary btn-sm">Manage Departments</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 align-middle">
                            <thead class="bg-200">
                                <tr>
                                    <th class="px-3">Code</th>
                                    <th>Department Name</th>
                                    <th>Type</th>
                                    <th>Manager</th>
                                    <th class="text-center">Staff Count</th>
                                    <th class="text-center">Pending Req.</th>
                                    <th>Status</th>
                                    <th class="text-end px-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($departmentsOverview as $dept)
                                    <tr>
                                        <td class="px-3 font-monospace fw-bold">{{ $dept->department_code }}</td>
                                        <td class="fw-semi-bold">{{ $dept->name }}</td>
                                        <td>
                                            <span class="badge bg-200 text-800">{{ $dept->department_type }}</span>
                                        </td>
                                        <td>{{ $dept->manager->name ?? '—' }}</td>
                                        <td class="text-center font-monospace">{{ $dept->employees_count }}</td>
                                        <td class="text-center font-monospace">
                                            @if($dept->stock_requests_count > 0)
                                                <span class="badge bg-warning text-dark">{{ $dept->stock_requests_count }}</span>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($dept->status === 'Active')
                                                <span class="badge bg-success-subtle text-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-end px-3">
                                            <a href="{{ route('departments.employees') }}?filterDepartment={{ $dept->id }}" class="btn btn-outline-info btn-xs me-1">Roster</a>
                                            <a href="{{ route('departments.ledger') }}?filterDepartment={{ $dept->id }}" class="btn btn-outline-secondary btn-xs">Ledger</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No departments configured yet. Click 'Manage Departments' to create one.</td>
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
