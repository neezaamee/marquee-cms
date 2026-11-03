<div>
    {{-- Header & Breadcrumbs --}}
    <div class="row mb-3">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Administration</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('support.index') }}">Support Operations</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Executive Dashboard</li>
                    </ol>
                </nav>
                <h4 class="mb-0 text-1000 fw-bold">Support Operations &amp; SLA Analytics</h4>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                {{-- Date Range --}}
                <select wire:model.live="dateRange" class="form-select form-select-sm" style="width: auto;">
                    <option value="today">Today</option>
                    <option value="7_days">Last 7 Days</option>
                    <option value="30_days">Last 30 Days</option>
                    <option value="this_month">This Month</option>
                    <option value="all_time">All Time</option>
                </select>

                {{-- Marquee Filter --}}
                <select wire:model.live="marqueeFilter" class="form-select form-select-sm" style="width: auto;">
                    <option value="">All Marquee Tenants</option>
                    @foreach($marquees as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>

                {{-- Export CSV CTA --}}
                <a href="{{ route('admin.support.export.tickets', ['marquee_id' => $marqueeFilter]) }}" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-file-csv me-1"></i> Export Tickets CSV
                </a>
            </div>
        </div>
    </div>

    {{-- 4 Primary KPI Summary Cards --}}
    <div class="row g-3 mb-4">
        {{-- Card 1: Ticket Volumes --}}
        <div class="col-sm-6 col-xxl-3">
            <div class="card h-100 border-0 shadow-none bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-secondary fs-11 fw-semibold text-uppercase">Ticket Volume</span>
                        <div class="avatar avatar-m bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-1000 mb-1">{{ number_format($metrics['total_tickets']) }}</h3>
                    <div class="d-flex justify-content-between fs-11 text-secondary pt-2 border-top border-200">
                        <span><strong class="text-warning">{{ $metrics['open_tickets'] }}</strong> Active Open</span>
                        <span><strong class="text-success">{{ $metrics['resolved_tickets'] }}</strong> Resolved</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Response & Resolution Speeds --}}
        <div class="col-sm-6 col-xxl-3">
            <div class="card h-100 border-0 shadow-none bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-secondary fs-11 fw-semibold text-uppercase">Speed &amp; Turnaround</span>
                        <div class="avatar avatar-m bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center">
                            <i class="fas fa-stopwatch"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-1">
                        <h3 class="fw-bold text-1000 mb-0">{{ $metrics['avg_first_response_hours'] }}<span class="fs-11 fw-normal text-secondary"> hrs</span></h3>
                        <span class="fs-12 text-secondary">Avg Response</span>
                    </div>
                    <div class="d-flex justify-content-between fs-11 text-secondary pt-2 border-top border-200">
                        <span>Avg Resolution: <strong class="text-1000">{{ $metrics['avg_resolution_hours'] }} hrs</strong></span>
                        <span>Awaiting Client: <strong>{{ $metrics['awaiting_customer'] }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 3: SLA Compliance --}}
        <div class="col-sm-6 col-xxl-3">
            <div class="card h-100 border-0 shadow-none bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-secondary fs-11 fw-semibold text-uppercase">SLA Performance</span>
                        <div class="avatar avatar-m {{ $metrics['breached_count'] > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }} rounded-circle d-flex align-items-center justify-content-center">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-1">
                        <h3 class="fw-bold {{ $metrics['breached_count'] > 0 ? 'text-danger' : 'text-success' }} mb-0">{{ 100 - $metrics['breach_rate'] }}%</h3>
                        <span class="fs-12 text-secondary">Compliance Rate</span>
                    </div>
                    <div class="d-flex justify-content-between fs-11 text-secondary pt-2 border-top border-200">
                        <span>Breached: <strong class="text-danger">{{ $metrics['breached_count'] }} tickets</strong></span>
                        <span>Breach Rate: <strong>{{ $metrics['breach_rate'] }}%</strong></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 4: Customer Satisfaction (CSAT) --}}
        <div class="col-sm-6 col-xxl-3">
            <div class="card h-100 border-0 shadow-none bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-secondary fs-11 fw-semibold text-uppercase">Customer CSAT</span>
                        <div class="avatar avatar-m bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-1">
                        <h3 class="fw-bold text-1000 mb-0">{{ $metrics['csat_average'] }} <span class="fs-11 fw-normal text-secondary">/ 5.0</span></h3>
                        <div class="text-warning fs-11">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star {{ $i <= round($metrics['csat_average']) ? 'text-warning' : 'text-300' }}"></i>
                            @endfor
                        </div>
                    </div>
                    <div class="d-flex justify-content-between fs-11 text-secondary pt-2 border-top border-200">
                        <span>Total Ratings: <strong class="text-1000">{{ $metrics['csat_count'] }}</strong></span>
                        <span>5-Star Praise: <strong class="text-success">{{ $metrics['csat_breakdown'][5] }}</strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Middle Section: CSAT Breakdown & Agent Leaderboard --}}
    <div class="row g-3 mb-4">
        {{-- CSAT Scorecard Breakdown --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-body-tertiary py-3">
                    <h6 class="mb-0 text-1000 fw-semibold"><i class="fas fa-chart-pie me-2 text-warning"></i>Satisfaction Scorecard &amp; Priority</h6>
                </div>
                <div class="card-body p-3">
                    <span class="fs-11 fw-semibold text-secondary d-block mb-2">Rating Distribution:</span>
                    @foreach([5 => '5 Stars (Excellent)', 4 => '4 Stars (Very Good)', 3 => '3 Stars (Good)', 2 => '2 Stars (Fair)', 1 => '1 Star (Poor)'] as $stars => $label)
                        @php
                            $cnt = $metrics['csat_breakdown'][$stars] ?? 0;
                            $pct = $metrics['csat_count'] > 0 ? round(($cnt / $metrics['csat_count']) * 100) : 0;
                        @endphp
                        <div class="d-flex align-items-center gap-2 mb-2 fs-11">
                            <span class="text-secondary text-truncate" style="width: 130px;">{{ $label }}</span>
                            <div class="progress flex-grow-1" style="height: 6px;">
                                <div class="progress-bar {{ $stars >= 4 ? 'bg-success' : ($stars === 3 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="fw-bold font-monospace text-secondary" style="width: 50px; text-align: right;">{{ $cnt }} ({{ $pct }}%)</span>
                        </div>
                    @endforeach

                    <div class="border-top pt-3 mt-3">
                        <span class="fs-11 fw-semibold text-secondary d-block mb-2">Priority Distribution:</span>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle p-2 fs-11">
                                Urgent: <strong>{{ $metrics['priority_breakdown']['urgent'] }}</strong>
                            </span>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle p-2 fs-11">
                                High: <strong>{{ $metrics['priority_breakdown']['high'] }}</strong>
                            </span>
                            <span class="badge bg-info-subtle text-info border border-info-subtle p-2 fs-11">
                                Normal: <strong>{{ $metrics['priority_breakdown']['normal'] }}</strong>
                            </span>
                            <span class="badge bg-secondary-subtle text-secondary border p-2 fs-11">
                                Low: <strong>{{ $metrics['priority_breakdown']['low'] }}</strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Agent Workload Leaderboard --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-body-tertiary py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-1000 fw-semibold"><i class="fas fa-user-tie me-2 text-primary"></i>Support Specialist Workload &amp; Performance</h6>
                    <a href="{{ route('admin.support.tickets') }}" class="fs-11 text-decoration-none">Manage Queue &rarr;</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 fs-10">
                            <thead class="bg-body-secondary text-secondary">
                                <tr>
                                    <th class="ps-3">SPECIALIST</th>
                                    <th>ASSIGNED</th>
                                    <th>RESOLVED</th>
                                    <th>PENDING QUEUE</th>
                                    <th class="pe-3 text-end">RESOLUTION RATE</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($metrics['agent_leaderboard'] as $ag)
                                    @php
                                        $agRate = $ag['total_assigned'] > 0 ? round(($ag['resolved_count'] / $ag['total_assigned']) * 100) : 0;
                                    @endphp
                                    <tr>
                                        <td class="ps-3 fw-bold text-1000">
                                            <i class="fas fa-user-circle me-1 text-primary"></i> {{ $ag['name'] }}
                                        </td>
                                        <td class="font-monospace fw-semibold">{{ $ag['total_assigned'] }}</td>
                                        <td class="font-monospace text-success fw-bold">{{ $ag['resolved_count'] }}</td>
                                        <td>
                                            <span class="badge {{ $ag['pending_count'] > 3 ? 'bg-warning text-dark' : 'bg-light text-secondary border' }} font-monospace">
                                                {{ $ag['pending_count'] }} active
                                            </span>
                                        </td>
                                        <td class="pe-3 text-end font-monospace fw-bold {{ $agRate >= 75 ? 'text-success' : 'text-primary' }}">
                                            {{ $agRate }}%
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-secondary">No specialist assignments recorded.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom Section: Multi-Channel Volumes & Critical Escalations --}}
    <div class="row g-3">
        {{-- Multi-Channel Operational Volumes --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-body-tertiary py-3">
                    <h6 class="mb-0 text-1000 fw-semibold"><i class="fas fa-cubes me-2 text-info"></i>Omni-Channel Operations</h6>
                </div>
                <div class="card-body p-3">
                    <div class="list-group list-group-flush fs-11">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-comments text-success"></i>
                                <span>Live Chat Sessions</span>
                            </div>
                            <div>
                                <span class="badge bg-success-subtle text-success me-1">{{ $activeChats }} active</span>
                                <span class="text-secondary font-monospace">({{ $totalChats }} total)</span>
                            </div>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-comment-alt text-info"></i>
                                <span>Customer Feedback</span>
                            </div>
                            <div>
                                <span class="badge bg-warning-subtle text-warning me-1">{{ $pendingFeedbacks }} pending</span>
                                <span class="text-secondary font-monospace">({{ $totalFeedbacks }} total)</span>
                            </div>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-lightbulb text-warning"></i>
                                <span>Feature Roadmap Ideas</span>
                            </div>
                            <div>
                                <span class="badge bg-primary-subtle text-primary me-1">{{ $plannedFeatures }} roadmap</span>
                                <span class="text-secondary font-monospace">({{ $totalFeatures }} total)</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-grid gap-2">
                        <a href="{{ route('admin.support.chat') }}" class="btn btn-outline-success btn-xs">
                            <i class="fas fa-comments me-1"></i> Open Live Chat Inbox
                        </a>
                        <a href="{{ route('admin.support.feedback') }}" class="btn btn-outline-info btn-xs">
                            <i class="fas fa-comments me-1"></i> Moderate Feedback &amp; Ideas
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Urgent & SLA Breached Escalation Watchlist --}}
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-body-tertiary py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-1000 fw-semibold">
                        <i class="fas fa-exclamation-triangle me-2 text-danger"></i>Escalation &amp; SLA Breach Watchlist
                    </h6>
                    <span class="fs-11 text-secondary">Immediate attention required</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 fs-10">
                            <thead class="bg-body-secondary text-secondary">
                                <tr>
                                    <th class="ps-3">TICKET</th>
                                    <th>MARQUEE / TENANT</th>
                                    <th>SUBJECT</th>
                                    <th>PRIORITY</th>
                                    <th>SLA DEADLINE</th>
                                    <th class="pe-3 text-end">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($criticalTickets as $ct)
                                    @php
                                        $breached = $ct->is_sla_breached || ($ct->sla_due_at && $ct->sla_due_at->isPast());
                                    @endphp
                                    <tr class="{{ $breached ? 'bg-danger-subtle' : '' }}">
                                        <td class="ps-3 font-monospace fw-bold text-primary">
                                            <a href="{{ route('admin.support.tickets.show', $ct->ticket_number) }}">
                                                {{ $ct->ticket_number }}
                                            </a>
                                        </td>
                                        <td class="fw-semibold text-1000">{{ $ct->marquee?->name ?? 'Platform' }}</td>
                                        <td>
                                            <span class="text-truncate d-inline-block fw-semibold text-1000" style="max-width: 200px;">
                                                {{ $ct->subject }}
                                            </span>
                                        </td>
                                        <td><span class="{{ $ct->priority_badge }} px-2 py-0 fs-12">{{ ucfirst($ct->priority) }}</span></td>
                                        <td>
                                            @if($breached)
                                                <span class="badge bg-danger">BREACHED</span>
                                            @elseif($ct->sla_due_at)
                                                <span class="text-secondary">{{ $ct->sla_due_at->diffForHumans() }}</span>
                                            @endif
                                        </td>
                                        <td class="pe-3 text-end">
                                            <a href="{{ route('admin.support.tickets.show', $ct->ticket_number) }}" class="btn btn-xs btn-primary">
                                                Manage
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-success">
                                            <i class="fas fa-check-circle fa-2x mb-1 text-success d-block"></i>
                                            <strong>Zero Critical Escalations!</strong>
                                            <p class="fs-11 text-secondary mb-0">All tickets are operating safely within SLA limits.</p>
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
</div>
