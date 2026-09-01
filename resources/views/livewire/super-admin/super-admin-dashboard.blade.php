<div class="container-fluid px-0">
    <!-- Header Banner -->
    <div class="card mb-3 border-0 shadow-sm overflow-hidden" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
        <div class="card-body p-4 text-white position-relative">
            <div class="row align-items-center justify-content-between g-3">
                <div class="col-12 col-md-7">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="avatar avatar-xl bg-primary rounded-3 d-flex align-items-center justify-content-center shadow">
                            <span class="fas fa-crown fa-lg text-white"></span>
                        </div>
                        <div>
                            <h3 class="text-white mb-0 fw-bold">SaaS Executive Command Center</h3>
                            <span class="badge bg-primary-subtle text-primary rounded-pill fs-11">Platform Administrator Overview</span>
                        </div>
                    </div>
                    <p class="text-300 fs-10 mb-0">
                        Real-time telemetry across all multi-tenant banquets, SaaS recurring revenues, network branch capacity, and platform gross event volume.
                    </p>
                </div>
                <div class="col-12 col-md-5 text-md-end">
                    <div class="d-flex gap-2 justify-content-md-end flex-wrap">
                        <a href="{{ route('super-admin.synthetic-data') }}" class="btn btn-falcon-default btn-sm shadow-sm">
                            <span class="fas fa-magic text-primary me-1"></span> Synthetic Data Studio
                        </a>
                        <a href="{{ route('super-admin.global-defaults') }}" class="btn btn-falcon-default btn-sm shadow-sm">
                            <span class="fas fa-sliders-h text-info me-1"></span> Global Defaults
                        </a>
                        <a href="{{ route('super-admin.business-owners.create') }}" class="btn btn-primary btn-sm fw-bold shadow-sm">
                            <span class="fas fa-user-plus me-1"></span> New Business Owner
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Executive KPI Row -->
    <div class="row g-3 mb-3">
        <!-- 1. Total Tenants -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 mb-1">Total Tenants</h6>
                            <h3 class="mb-0 fw-bolder text-primary">{{ $totalMarquees }}</h3>
                            <span class="fs-11 text-success fw-semi-bold">
                                <span class="fas fa-check-circle me-1"></span>{{ $activeMarquees }} Active
                            </span>
                        </div>
                        <div class="avatar avatar-m bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-building fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Network Branches -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 mb-1">Network Branches</h6>
                            <h3 class="mb-0 fw-bolder text-info">{{ $totalBranches }}</h3>
                            <span class="fs-11 text-muted">Across all cities</span>
                        </div>
                        <div class="avatar avatar-m bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-code-branch fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Active MRR -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 mb-1">Estimated MRR</h6>
                            <h3 class="mb-0 fw-bolder text-success">PKR {{ number_format($estimatedMRR / 1000, 1) }}k</h3>
                            <span class="fs-11 text-success fw-semi-bold">{{ $totalSubscribers }} Subscribers</span>
                        </div>
                        <div class="avatar avatar-m bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-chart-line fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Platform Gross Volume (GMV) -->
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 mb-1">Platform GMV</h6>
                            <h3 class="mb-0 fw-bolder text-warning">PKR {{ number_format($platformGMV / 1000000, 2) }}M</h3>
                            <span class="fs-11 text-muted">{{ number_format($totalPlatformBookings) }} Total Bookings</span>
                        </div>
                        <div class="avatar avatar-m bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-coins fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Registered Platform Users & Staff -->
        <div class="col-12 col-md-4 col-xl-3">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 mb-1">Platform Workforce</h6>
                            <h3 class="mb-0 fw-bolder text-secondary">{{ $totalStaff }} <span class="fs-11 text-muted fw-normal">Staff</span></h3>
                            <span class="fs-11 text-muted">{{ $totalUsers }} CMS Accounts</span>
                        </div>
                        <div class="avatar avatar-m bg-secondary-subtle text-secondary rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-user-friends fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Section: Tenant Directory & Subscription Breakdown -->
    <div class="row g-3 mb-3">
        <!-- Tenants Table -->
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-body-tertiary py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0 fw-bold text-800"><span class="fas fa-hotel text-primary me-2"></span>Tenant Ecosystem Directory</h5>
                        <span class="fs-11 text-muted">All active marquee businesses hosted on the platform</span>
                    </div>
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control form-control-sm" placeholder="Search tenant or city..." wire:model.live.debounce.300ms="search" style="min-width: 180px;">
                        <select class="form-select form-select-sm" wire:model.live="statusFilter" style="width: 110px;">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive scrollbar">
                        <table class="table table-sm table-hover fs-10 mb-0">
                            <thead class="bg-200 text-800">
                                <tr>
                                    <th class="px-3 py-2">Marquee Name</th>
                                    <th class="py-2">City</th>
                                    <th class="py-2 text-center">Branches</th>
                                    <th class="py-2 text-center">Bookings</th>
                                    <th class="py-2">Owner</th>
                                    <th class="py-2 text-center">Setup Status</th>
                                    <th class="px-3 py-2 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tenants as $tenant)
                                <tr>
                                    <td class="px-3 py-2 align-middle fw-bold text-900">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar avatar-s bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                                                <span class="fas fa-building" style="font-size: 10px;"></span>
                                            </div>
                                            <div>
                                                <div class="text-primary">{{ $tenant->name }}</div>
                                                <span class="fs-11 text-muted">ID: #{{ $tenant->id }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-2 align-middle text-700">{{ $tenant->city }}</td>
                                    <td class="py-2 align-middle text-center">
                                        <span class="badge bg-info-subtle text-info rounded-pill">{{ $tenant->branches_count }} Br</span>
                                    </td>
                                    <td class="py-2 align-middle text-center">
                                        <span class="badge bg-primary-subtle text-primary rounded-pill">{{ $tenant->bookings_count }}</span>
                                    </td>
                                    <td class="py-2 align-middle text-700">
                                        {{ $tenant->owners->first()?->name ?? 'Unassigned' }}
                                    </td>
                                    <td class="py-2 align-middle text-center">
                                        @if($tenant->is_setup_completed)
                                            <span class="badge bg-success-subtle text-success rounded-pill"><span class="fas fa-check me-1"></span>Complete</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning rounded-pill"><span class="fas fa-hourglass-half me-1"></span>Incomplete</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 align-middle text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('super-admin.synthetic-data') }}" class="btn btn-outline-secondary btn-sm" title="Generate / Purge Demo Data">
                                                <span class="fas fa-magic"></span>
                                            </a>
                                            <a href="{{ route('marquees.show', $tenant->id) }}" class="btn btn-outline-primary btn-sm" title="Inspect Tenant">
                                                <span class="fas fa-eye"></span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <span class="fas fa-inbox fa-2x mb-2 d-block text-400"></span>
                                        No tenant marquees match the search criteria.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($tenants->hasPages())
                <div class="card-footer bg-body-tertiary py-2">
                    {{ $tenants->links() }}
                </div>
                @endif
            </div>
        </div>

        <!-- SaaS Plans Distribution & Platform Shortcuts -->
        <div class="col-12 col-xl-4">
            <!-- SaaS Plans Distribution -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body-tertiary py-2">
                    <h6 class="mb-0 fw-bold text-800"><span class="fas fa-tags text-primary me-2"></span>SaaS Plans Distribution</h6>
                </div>
                <div class="card-body p-3">
                    @forelse($plans as $plan)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between fs-11 mb-1">
                            <span class="fw-bold text-800">{{ $plan->name }}</span>
                            <span class="fw-bold text-primary">{{ $plan->users_count }} Subscribers (PKR {{ number_format($plan->price) }}/mo)</span>
                        </div>
                        @php
                            $percentage = $totalSubscribers > 0 ? round(($plan->users_count / $totalSubscribers) * 100) : 0;
                        @endphp
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted fs-11 mb-0">No subscription plans created yet.</p>
                    @endforelse
                </div>
            </div>

            <!-- Developer & Super Admin Tools -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-body-tertiary py-2">
                    <h6 class="mb-0 fw-bold text-800"><span class="fas fa-tools text-primary me-2"></span>Quick Platform Tools</h6>
                </div>
                <div class="card-body p-2">
                    <div class="list-group list-group-flush fs-11">
                        <a href="{{ route('super-admin.synthetic-data') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                            <span class="fas fa-magic text-primary"></span>
                            <div class="flex-1">
                                <div class="fw-bold">Synthetic Data Studio</div>
                                <span class="text-muted fs-11">Generate multi-branch demo data & purge on demand</span>
                            </div>
                            <span class="fas fa-chevron-right text-400 fs-11"></span>
                        </a>
                        <a href="{{ route('super-admin.global-defaults') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                            <span class="fas fa-globe text-info"></span>
                            <div class="flex-1">
                                <div class="fw-bold">Global Defaults Manager</div>
                                <span class="text-muted fs-11">Base menu items, extra services & shift templates</span>
                            </div>
                            <span class="fas fa-chevron-right text-400 fs-11"></span>
                        </a>
                        <a href="{{ route('super-admin.business-owners') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                            <span class="fas fa-user-shield text-success"></span>
                            <div class="flex-1">
                                <div class="fw-bold">Business Owners & Access</div>
                                <span class="text-muted fs-11">Tenant owner accounts, subscriptions, & passwords</span>
                            </div>
                            <span class="fas fa-chevron-right text-400 fs-11"></span>
                        </a>
                        <a href="{{ route('super-admin.backups') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                            <span class="fas fa-database text-warning"></span>
                            <div class="flex-1">
                                <div class="fw-bold">Database Backups & Health</div>
                                <span class="text-muted fs-11">System snapshot generation & audit logs</span>
                            </div>
                            <span class="fas fa-chevron-right text-400 fs-11"></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
