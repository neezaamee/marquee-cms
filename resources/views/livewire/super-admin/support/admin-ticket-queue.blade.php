<div>
    {{-- Header & Breadcrumbs --}}
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Administration</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('support.index') }}">Support Operations</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Support Desk Queue</li>
                    </ol>
                </nav>
                <h4 class="mb-0 text-1000 fw-bold">Support Desk Operations &amp; Ticket Queue</h4>
            </div>
            <div>
                <a href="{{ route('support.tickets.create') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> Open Ticket for Tenant
                </a>
            </div>
        </div>
    </div>

    {{-- Metrics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-0 shadow-none bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-l me-3">
                            <div class="avatar-name rounded-circle bg-primary-subtle text-primary">
                                <i class="fas fa-inbox"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="text-secondary mb-1">Active Queue</h6>
                            <h4 class="text-1000 mb-0 fw-bold">{{ number_format($totalOpen) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-0 shadow-none bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-l me-3">
                            <div class="avatar-name rounded-circle bg-warning-subtle text-warning">
                                <i class="fas fa-user-plus"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="text-secondary mb-1">Unassigned</h6>
                            <h4 class="text-warning mb-0 fw-bold">{{ number_format($unassignedCount) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-0 shadow-none bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-l me-3">
                            <div class="avatar-name rounded-circle bg-danger-subtle text-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="text-secondary mb-1">SLA At Risk / Breached</h6>
                            <h4 class="text-danger mb-0 fw-bold">{{ number_format($slaBreachedCount) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-0 shadow-none bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-l me-3">
                            <div class="avatar-name rounded-circle bg-success-subtle text-success">
                                <i class="fas fa-check-double"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="text-secondary mb-1">Resolved Today</h6>
                            <h4 class="text-success mb-0 fw-bold">{{ number_format($resolvedToday) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Filter & Management Card --}}
    <div class="card">
        <div class="card-header bg-body-tertiary py-3">
            <div class="row g-2">
                {{-- Search --}}
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-secondary"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search ticket #, subject, customer...">
                    </div>
                </div>
                {{-- Marquee Filter --}}
                <div class="col-md-2">
                    <select wire:model.live="marqueeFilter" class="form-select form-select-sm">
                        <option value="">All Marquees / Tenants</option>
                        @foreach($marquees as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Status Filter --}}
                <div class="col-md-2">
                    <select wire:model.live="statusFilter" class="form-select form-select-sm">
                        <option value="all">All Statuses</option>
                        <option value="open">Open</option>
                        <option value="assigned">Assigned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="awaiting_customer">Awaiting Customer</option>
                        <option value="awaiting_internal">Awaiting Staff</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                        <option value="reopened">Reopened</option>
                    </select>
                </div>
                {{-- Priority Filter --}}
                <div class="col-md-2">
                    <select wire:model.live="priorityFilter" class="form-select form-select-sm">
                        <option value="all">All Priorities</option>
                        <option value="urgent">Urgent</option>
                        <option value="high">High</option>
                        <option value="normal">Normal</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                {{-- SLA Filter --}}
                <div class="col-md-2">
                    <select wire:model.live="slaFilter" class="form-select form-select-sm">
                        <option value="all">SLA: All</option>
                        <option value="breached">Breached / Overdue</option>
                    </select>
                </div>
                {{-- Reset --}}
                <div class="col-md-1 text-end">
                    <button wire:click="$set('search', ''); $set('marqueeFilter', ''); $set('statusFilter', 'all'); $set('priorityFilter', 'all'); $set('slaFilter', 'all');" class="btn btn-outline-secondary btn-sm w-100" title="Reset Filters">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>

            {{-- Bulk Actions Toolbar (Only visible when items selected) --}}
            @if(count($selectedTickets) > 0)
                <div class="row g-2 align-items-center mt-2 pt-2 border-top border-200">
                    <div class="col-md-3">
                        <span class="fs-10 fw-bold text-primary">
                            <i class="fas fa-check-square me-1"></i> {{ count($selectedTickets) }} ticket(s) selected
                        </span>
                    </div>
                    <div class="col-md-3">
                        <select wire:model.live="bulkAction" class="form-select form-select-sm">
                            <option value="">Choose bulk action...</option>
                            <option value="assign">Assign to Support Agent</option>
                            <option value="status">Change Status</option>
                            <option value="close">Close Tickets</option>
                        </select>
                    </div>

                    @if($bulkAction === 'assign')
                        <div class="col-md-3">
                            <select wire:model="bulkAgentId" class="form-select form-select-sm">
                                <option value="">Select Agent...</option>
                                @foreach($agents as $ag)
                                    <option value="{{ $ag->id }}">{{ $ag->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @elseif($bulkAction === 'status')
                        <div class="col-md-3">
                            <select wire:model="bulkStatus" class="form-select form-select-sm">
                                <option value="">Select Status...</option>
                                <option value="in_progress">In Progress</option>
                                <option value="awaiting_customer">Awaiting Customer</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                    @endif

                    <div class="col-md-2">
                        <button wire:click="executeBulkAction" class="btn btn-primary btn-sm w-100">
                            Apply Action
                        </button>
                    </div>
                </div>
            @endif
        </div>

        <div class="card-body p-0">
            @if(session('success'))
                <div class="alert alert-success m-3 alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger m-3 alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 fs-10">
                    <thead class="bg-body-secondary text-secondary">
                        <tr>
                            <th class="ps-3" style="width: 35px;">
                                <input type="checkbox" wire:model.live="selectAll" class="form-check-input">
                            </th>
                            <th style="width: 130px;">TICKET ID</th>
                            <th>BUSINESS / TENANT</th>
                            <th>SUBJECT &amp; DETAILS</th>
                            <th>CATEGORY</th>
                            <th>PRIORITY</th>
                            <th>STATUS</th>
                            <th>ASSIGNED AGENT</th>
                            <th>SLA DUE</th>
                            <th class="pe-3 text-end" style="width: 90px;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $t)
                            @php
                                $isBreached = $t->is_sla_breached || ($t->sla_due_at && $t->sla_due_at->isPast() && !in_array($t->status, ['resolved', 'closed']));
                            @endphp
                            <tr class="{{ $isBreached ? 'bg-danger-subtle' : '' }}">
                                <td class="ps-3">
                                    <input type="checkbox" wire:model.live="selectedTickets" value="{{ $t->id }}" class="form-check-input">
                                </td>
                                <td class="font-monospace fw-bold text-primary">
                                    <a href="{{ route('admin.support.tickets.show', $t->ticket_number) }}">
                                        {{ $t->ticket_number }}
                                    </a>
                                </td>
                                <td>
                                    <span class="fw-semibold text-1000 d-block">{{ $t->marquee ? $t->marquee->name : 'Platform System' }}</span>
                                    <small class="text-secondary">{{ $t->creator?->name }} ({{ $t->branch?->name ?? 'Head Office' }})</small>
                                </td>
                                <td>
                                    <a href="{{ route('admin.support.tickets.show', $t->ticket_number) }}" class="text-1000 fw-semibold text-decoration-none d-block">
                                        {{ $t->subject }}
                                    </a>
                                    <span class="text-secondary text-truncate d-inline-block" style="max-width: 250px;">
                                        {{ Str::limit($t->description, 60) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $t->category?->name ?? 'General' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="{{ $t->priority_badge }} px-2 py-1">
                                        {{ ucfirst($t->priority) }}
                                    </span>
                                </td>
                                <td>
                                    <select wire:change="changeStatus({{ $t->id }}, $event.target.value)" class="form-select form-select-sm py-0 px-2" style="font-size: 0.75rem; width: auto;">
                                        <option value="open" {{ $t->status === 'open' ? 'selected' : '' }}>Open</option>
                                        <option value="assigned" {{ $t->status === 'assigned' ? 'selected' : '' }}>Assigned</option>
                                        <option value="in_progress" {{ $t->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="awaiting_customer" {{ $t->status === 'awaiting_customer' ? 'selected' : '' }}>Awaiting Customer</option>
                                        <option value="awaiting_internal" {{ $t->status === 'awaiting_internal' ? 'selected' : '' }}>Awaiting Staff</option>
                                        <option value="resolved" {{ $t->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                        <option value="closed" {{ $t->status === 'closed' ? 'selected' : '' }}>Closed</option>
                                        <option value="reopened" {{ $t->status === 'reopened' ? 'selected' : '' }}>Reopened</option>
                                    </select>
                                </td>
                                <td>
                                    <select wire:change="assignAgent({{ $t->id }}, $event.target.value)" class="form-select form-select-sm py-0 px-2" style="font-size: 0.75rem; width: auto;">
                                        <option value="">-- Unassigned --</option>
                                        @foreach($agents as $agent)
                                            <option value="{{ $agent->id }}" {{ $t->assigned_to == $agent->id ? 'selected' : '' }}>
                                                {{ $agent->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    @if($t->sla_due_at)
                                        @if($isBreached)
                                            <span class="badge bg-danger">BREACHED</span>
                                        @else
                                            <span class="text-secondary" title="{{ $t->sla_due_at->format('M d, Y h:i A') }}">
                                                {{ $t->sla_due_at->diffForHumans(['parts' => 1]) }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="pe-3 text-end">
                                    <a href="{{ route('admin.support.tickets.show', $t->ticket_number) }}" class="btn btn-xs btn-primary">
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-secondary">
                                    <i class="fas fa-inbox fa-3x mb-3 text-300"></i>
                                    <h5>No tickets in queue</h5>
                                    <p class="fs-10 text-muted mb-0">No tickets matching the current operational filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($tickets->hasPages())
            <div class="card-footer bg-body-tertiary py-2">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
