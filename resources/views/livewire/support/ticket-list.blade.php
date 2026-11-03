<div>
    {{-- Header & Breadcrumbs --}}
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('support.index') }}">Support &amp; Help</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Support Tickets</li>
                    </ol>
                </nav>
                <h4 class="mb-0 text-1000 fw-bold">Support Tickets</h4>
            </div>
            <div>
                <a href="{{ route('support.tickets.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Submit New Ticket
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
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="text-secondary mb-1">Total Tickets</h6>
                            <h4 class="text-1000 mb-0 fw-bold">{{ number_format($totalTickets) }}</h4>
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
                                <i class="fas fa-spinner"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="text-secondary mb-1">Active &amp; Open</h6>
                            <h4 class="text-1000 mb-0 fw-bold">{{ number_format($openTickets) }}</h4>
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
                            <div class="avatar-name rounded-circle bg-info-subtle text-info">
                                <i class="fas fa-user-clock"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="text-secondary mb-1">Awaiting My Reply</h6>
                            <h4 class="text-1000 mb-0 fw-bold">{{ number_format($awaitingResponse) }}</h4>
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
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="text-secondary mb-1">Resolved &amp; Closed</h6>
                            <h4 class="text-1000 mb-0 fw-bold">{{ number_format($resolvedTickets) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Filter & Table Card --}}
    <div class="card">
        <div class="card-header bg-body-tertiary py-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-secondary"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search by Ticket #, subject, or issue description...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="status" class="form-select">
                        <option value="all">All Statuses</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="awaiting_customer">Awaiting My Reply</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                        <option value="reopened">Reopened</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="priority" class="form-select">
                        <option value="all">All Priorities</option>
                        <option value="urgent">Urgent</option>
                        <option value="high">High</option>
                        <option value="normal">Normal</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div class="col-md-1 text-end">
                    <button wire:click="$set('search', ''); $set('status', 'all'); $set('priority', 'all');" class="btn btn-outline-secondary w-100" title="Reset Filters">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            @if(session('success'))
                <div class="alert alert-success m-3 alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-body-secondary text-secondary fs-10">
                        <tr>
                            <th class="ps-3" style="width: 140px;">TICKET ID</th>
                            <th>SUBJECT &amp; DETAILS</th>
                            <th>CATEGORY</th>
                            <th>PRIORITY</th>
                            <th>STATUS</th>
                            <th>CREATED</th>
                            <th class="pe-3 text-end" style="width: 100px;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                            <tr>
                                <td class="ps-3 font-monospace fw-bold text-primary">
                                    <a href="{{ route('support.tickets.show', $ticket->ticket_number) }}">
                                        {{ $ticket->ticket_number }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('support.tickets.show', $ticket->ticket_number) }}" class="text-1000 fw-semibold text-decoration-none d-block">
                                        {{ $ticket->subject }}
                                    </a>
                                    <small class="text-secondary text-truncate d-inline-block" style="max-width: 320px;">
                                        {{ Str::limit($ticket->description, 75) }}
                                    </small>
                                    @if($ticket->related_module)
                                        <span class="badge bg-light text-secondary border ms-1 font-monospace fs-11">
                                            {{ ucfirst($ticket->related_module) }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $ticket->category ? $ticket->category->name : 'General' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="{{ $ticket->priority_badge }} px-2 py-1">
                                        {{ ucfirst($ticket->priority) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="{{ $ticket->status_badge }} px-2 py-1">
                                        {{ $ticket->status_label }}
                                    </span>
                                </td>
                                <td class="text-secondary fs-10">
                                    {{ $ticket->created_at->format('M d, Y') }}<br>
                                    <span class="fs-11 text-muted">{{ $ticket->created_at->diffForHumans() }}</span>
                                </td>
                                <td class="pe-3 text-end">
                                    <a href="{{ route('support.tickets.show', $ticket->ticket_number) }}" class="btn btn-sm btn-outline-primary">
                                        View <i class="fas fa-chevron-right ms-1"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="avatar avatar-2xl mx-auto mb-3">
                                        <div class="avatar-name rounded-circle bg-light text-secondary">
                                            <i class="fas fa-ticket-alt fs-3"></i>
                                        </div>
                                    </div>
                                    <h5 class="text-secondary fw-semibold">No tickets found</h5>
                                    <p class="text-muted fs-10 mb-3">You haven't submitted any support tickets matching this criteria.</p>
                                    <a href="{{ route('support.tickets.create') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus me-1"></i> Submit a Ticket
                                    </a>
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

