<div>
    <!-- Top Header Bar -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
            <div>
                <h5 class="mb-0 text-primary fw-bold">
                    <span class="fas fa-funnel-dollar me-2"></span>Leads & Inquiries Operational CRM
                </h5>
                <div class="text-muted fs-11 mt-1">
                    Capture event inquiries, schedule site visits, track customer follow-up timelines, and convert inquiries into official bookings.
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Table / Kanban View Mode Switcher -->
                <div class="btn-group btn-group-sm" role="group">
                    <button wire:click="setViewMode('table')" type="button" class="btn {{ $viewMode === 'table' ? 'btn-primary' : 'btn-falcon-default' }}" title="Table List View">
                        <span class="fas fa-list me-1"></span>Table
                    </button>
                    <button wire:click="setViewMode('kanban')" type="button" class="btn {{ $viewMode === 'kanban' ? 'btn-primary' : 'btn-falcon-default' }}" title="Kanban Pipeline Board">
                        <span class="fas fa-columns me-1"></span>Pipeline
                    </button>
                </div>

                <button wire:click="$refresh" class="btn btn-falcon-default btn-sm" type="button" data-bs-toggle="tooltip" title="Refresh Inquiries">
                    <span class="fas fa-sync-alt me-1"></span>Refresh
                </button>
                <button wire:click="exportCsv" class="btn btn-falcon-default btn-sm text-nowrap" type="button">
                    <span class="fas fa-file-excel me-1 text-success"></span>Export CSV
                </button>
                <button wire:click="openCreateModal" class="btn btn-falcon-primary btn-sm text-nowrap" type="button">
                    <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> New Inquiry
                </button>
            </div>
        </div>
    </div>

    <!-- Interactive Operational Metrics Cards (Grid of Real DB Metrics) -->
    <div class="row g-2 mb-3">
        <!-- 1. Total Inquiries -->
        <div class="col-6 col-sm-4 col-md-3 col-lg-2 col-xl">
            <div class="card card-span h-100 cursor-pointer border {{ $filterQuickShortcut === 'all' ? 'border-primary shadow-sm bg-primary-subtle' : 'border-200' }}" wire:click="applyShortcutFilter('all')">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-500 fw-bold text-uppercase fs-12">Total Inquiries</div>
                        <h4 class="mb-0 font-monospace text-primary fw-bold">{{ number_format($totalLeadsCount) }}</h4>
                    </div>
                    <div class="icon-item bg-primary-subtle text-primary rounded-3"><span class="fas fa-funnel-dollar fs-9"></span></div>
                </div>
            </div>
        </div>

        <!-- 2. New / Uncontacted -->
        <div class="col-6 col-sm-4 col-md-3 col-lg-2 col-xl">
            <div class="card card-span h-100 cursor-pointer border {{ $filterQuickShortcut === 'new' ? 'border-primary shadow-sm bg-primary-subtle' : 'border-200' }}" wire:click="applyShortcutFilter('new')">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-500 fw-bold text-uppercase fs-12">New Inquiries</div>
                        <h4 class="mb-0 font-monospace text-primary fw-bold">{{ number_format($newLeadsCount) }}</h4>
                    </div>
                    <div class="icon-item bg-primary-subtle text-primary rounded-3"><span class="fas fa-inbox fs-9"></span></div>
                </div>
            </div>
        </div>

        <!-- 3. Contacted / Discussion -->
        <div class="col-6 col-sm-4 col-md-3 col-lg-2 col-xl">
            <div class="card card-span h-100 cursor-pointer border {{ $filterQuickShortcut === 'contacted' ? 'border-info shadow-sm bg-info-subtle' : 'border-200' }}" wire:click="applyShortcutFilter('contacted')">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-500 fw-bold text-uppercase fs-12">In Discussion</div>
                        <h4 class="mb-0 font-monospace text-info fw-bold">{{ number_format($contactedLeadsCount) }}</h4>
                    </div>
                    <div class="icon-item bg-info-subtle text-info rounded-3"><span class="fas fa-comments fs-9"></span></div>
                </div>
            </div>
        </div>

        <!-- 4. Site Visits -->
        <div class="col-6 col-sm-4 col-md-3 col-lg-2 col-xl">
            <div class="card card-span h-100 cursor-pointer border {{ $filterQuickShortcut === 'site_visit' ? 'border-warning shadow-sm bg-warning-subtle' : 'border-200' }}" wire:click="applyShortcutFilter('site_visit')">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-500 fw-bold text-uppercase fs-12">Site Visits</div>
                        <h4 class="mb-0 font-monospace text-warning fw-bold">{{ number_format($siteVisitLeadsCount) }}</h4>
                    </div>
                    <div class="icon-item bg-warning-subtle text-warning rounded-3"><span class="fas fa-eye fs-9"></span></div>
                </div>
            </div>
        </div>

        <!-- 5. Quotation / Negotiation -->
        <div class="col-6 col-sm-4 col-md-3 col-lg-2 col-xl">
            <div class="card card-span h-100 cursor-pointer border {{ $filterQuickShortcut === 'negotiation' ? 'border-secondary shadow-sm bg-secondary-subtle' : 'border-200' }}" wire:click="applyShortcutFilter('negotiation')">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-500 fw-bold text-uppercase fs-12">Negotiation</div>
                        <h4 class="mb-0 font-monospace text-secondary fw-bold">{{ number_format($negotiationLeadsCount) }}</h4>
                    </div>
                    <div class="icon-item bg-secondary-subtle text-secondary rounded-3"><span class="fas fa-file-invoice-dollar fs-9"></span></div>
                </div>
            </div>
        </div>

        <!-- 6. Converted to Booking -->
        <div class="col-6 col-sm-4 col-md-3 col-lg-2 col-xl">
            <div class="card card-span h-100 cursor-pointer border {{ $filterQuickShortcut === 'converted' ? 'border-success shadow-sm bg-success-subtle' : 'border-200' }}" wire:click="applyShortcutFilter('converted')">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-500 fw-bold text-uppercase fs-12">Converted</div>
                        <h4 class="mb-0 font-monospace text-success fw-bold">{{ number_format($convertedLeadsCount) }}</h4>
                    </div>
                    <div class="icon-item bg-success-subtle text-success rounded-3"><span class="fas fa-check-double fs-9"></span></div>
                </div>
            </div>
        </div>

        <!-- 7. Overdue Follow-ups -->
        @if($overdueFollowupsCount > 0)
        <div class="col-6 col-sm-4 col-md-3 col-lg-2 col-xl">
            <div class="card card-span h-100 cursor-pointer border {{ $filterQuickShortcut === 'overdue' ? 'border-danger shadow-sm bg-danger-subtle' : 'border-danger-subtle' }}" wire:click="applyShortcutFilter('overdue')">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-danger fw-bold text-uppercase fs-12">Overdue Alerts</div>
                        <h4 class="mb-0 font-monospace text-danger fw-bold">{{ number_format($overdueFollowupsCount) }}</h4>
                    </div>
                    <div class="icon-item bg-danger-subtle text-danger rounded-3"><span class="fas fa-bell fs-9"></span></div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Flash Notification Messages -->
    @if(session('success'))
        <div class="alert alert-success border-2 d-flex align-items-center mb-3 p-2" role="alert">
            <div class="bg-success me-3 icon-item" style="width: 28px; height: 28px;"><span class="fas fa-check text-white fs-10"></span></div>
            <p class="mb-0 flex-1 fs-12">{{ session('success') }}</p>
            <button class="btn-close fs-11" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Search and Filter Toolbar -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-12 col-md-3">
                    <div class="position-relative">
                        <input wire:model.live.debounce.300ms="search" type="search" class="form-control form-control-sm ps-4" placeholder="Search name, phone, notes...">
                        <span class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-2 text-400 fs-12"></span>
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-6 col-md-2">
                    <select wire:model.live="filterStatus" class="form-select form-select-sm">
                        <option value="">-- All Statuses --</option>
                        <option value="new">New Inquiry</option>
                        <option value="contacted">Contacted / Discussion</option>
                        <option value="site_visit">Site Visit Scheduled</option>
                        <option value="negotiation">Quotation / Negotiation</option>
                        <option value="converted">Converted to Booking</option>
                        <option value="lost">Lost / Dropped</option>
                    </select>
                </div>

                <!-- Priority Filter -->
                <div class="col-6 col-md-2">
                    <select wire:model.live="filterPriority" class="form-select form-select-sm">
                        <option value="">-- Priority --</option>
                        <option value="hot">🔥 Hot Priority</option>
                        <option value="warm">⚡ Warm Priority</option>
                        <option value="cold">❄️ Cold Priority</option>
                    </select>
                </div>

                <!-- Source Filter -->
                <div class="col-6 col-md-2">
                    <select wire:model.live="filterSource" class="form-select form-select-sm">
                        <option value="">-- Lead Source --</option>
                        <option value="walk_in">Walk-in</option>
                        <option value="call">Phone Call</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="facebook">Facebook</option>
                        <option value="instagram">Instagram</option>
                        <option value="website">Website</option>
                        <option value="referral">Referral</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <!-- Branch Filter -->
                <div class="col-6 col-md-2">
                    <select wire:model.live="filterBranch" class="form-select form-select-sm">
                        <option value="">-- Branch --</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Reset Button -->
                <div class="col-12 col-md-1 text-end">
                    <button wire:click="resetFilters" class="btn btn-outline-secondary btn-sm w-100" title="Reset Filters">
                        <span class="fas fa-undo"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN VIEW CONTAINER: Table View OR Kanban Pipeline Board -->
    @if($viewMode === 'table')
        <!-- TABLE LIST VIEW -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 fs-12">
                    <thead class="bg-200">
                        <tr>
                            <th>Inquiry Contact</th>
                            <th>Event Details</th>
                            <th>Hall & Shift</th>
                            <th>Budget / Guests</th>
                            <th>Priority</th>
                            <th>Source</th>
                            <th>Status & Follow-up</th>
                            <th>Assigned</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tableLeads as $lead)
                            <tr class="{{ $lead->is_overdue ? 'bg-danger-subtle' : '' }}">
                                <!-- Contact Info -->
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-l me-2">
                                            <div class="avatar-name rounded-circle bg-primary-subtle text-primary fw-bold">
                                                <span>{{ strtoupper(substr($lead->name, 0, 2)) }}</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $lead->name }}</div>
                                            <div class="font-monospace text-muted fs-11">
                                                <a href="tel:{{ $lead->phone }}" class="text-decoration-none text-muted"><span class="fas fa-phone me-1 text-400"></span>{{ $lead->phone }}</a>
                                                @if($lead->alternate_phone)
                                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $lead->alternate_phone) }}" target="_blank" class="text-success ms-1" title="Chat on WhatsApp"><span class="fab fa-whatsapp"></span></a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Event Details -->
                                <td>
                                    @if($lead->eventType)
                                        <span class="badge bg-secondary-subtle text-secondary mb-1">{{ $lead->eventType->event_type_name ?? $lead->eventType->name }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                    <div>
                                        @if($lead->preferred_date)
                                            <span class="fw-semi-bold text-dark"><span class="fas fa-calendar-alt me-1 text-primary"></span>{{ $lead->preferred_date->format('d-M-Y') }}</span>
                                        @else
                                            <span class="text-muted fs-11">Date flexible</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Hall & Slot -->
                                <td>
                                    <div class="fw-semi-bold text-900">{{ $lead->hall->hall_name ?? ($lead->hall->name ?? 'Any Hall') }}</div>
                                    <div class="text-muted fs-11">{{ $lead->slot->slot_name ?? ($lead->slot->name ?? 'Slot flexible') }}</div>
                                </td>

                                <!-- Guests & Budget -->
                                <td>
                                    <div><span class="fas fa-users me-1 text-muted"></span>{{ $lead->guest_count ? number_format($lead->guest_count) . ' pax' : '—' }}</div>
                                    <div class="fw-bold text-success fs-11">{{ $lead->estimated_budget ? 'Rs. ' . number_format($lead->estimated_budget) : '—' }}</div>
                                </td>

                                <!-- Priority -->
                                <td>
                                    <span class="badge {{ $lead->priority_badge_class }}">
                                        @if($lead->priority === 'hot') 🔥 Hot @elseif($lead->priority === 'warm') ⚡ Warm @else ❄️ Cold @endif
                                    </span>
                                </td>

                                <!-- Source -->
                                <td>
                                    <span class="badge badge-subtle-light text-secondary border">
                                        {{ $lead->source_label }}
                                    </span>
                                </td>

                                <!-- Status & Follow-up -->
                                <td>
                                    <div class="dropdown mb-1">
                                        <button class="btn btn-xs {{ $lead->status_badge_class }} dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            {{ $lead->status_label }}
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end fs-12">
                                            <li><button class="dropdown-item" wire:click="updateLeadStatus({{ $lead->id }}, 'new')">New Inquiry</button></li>
                                            <li><button class="dropdown-item" wire:click="updateLeadStatus({{ $lead->id }}, 'contacted')">Contacted / Discussion</button></li>
                                            <li><button class="dropdown-item" wire:click="updateLeadStatus({{ $lead->id }}, 'site_visit')">Site Visit Scheduled</button></li>
                                            <li><button class="dropdown-item" wire:click="updateLeadStatus({{ $lead->id }}, 'negotiation')">Quotation / Negotiation</button></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><button class="dropdown-item text-success fw-bold" wire:click="convertToBooking({{ $lead->id }})"><span class="fas fa-check-circle me-1"></span>Convert to Booking</button></li>
                                            <li><button class="dropdown-item text-danger" wire:click="openLostModal({{ $lead->id }})"><span class="fas fa-times-circle me-1"></span>Mark as Lost</button></li>
                                        </ul>
                                    </div>

                                    @if($lead->follow_up_date && !in_array($lead->status, ['converted', 'lost']))
                                        <div class="fs-11 {{ $lead->is_overdue ? 'text-danger fw-bold' : ($lead->is_due_today ? 'text-warning fw-bold' : 'text-muted') }}">
                                            <span class="fas fa-clock me-1"></span>
                                            @if($lead->is_overdue) Overdue: @elseif($lead->is_due_today) Due Today: @else Next: @endif
                                            {{ $lead->follow_up_date->format('d-M') }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Assigned To -->
                                <td>
                                    <span class="text-secondary">{{ $lead->assignedUser->name ?? 'Unassigned' }}</span>
                                </td>

                                <!-- Actions Dropdown -->
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Log Activity / Follow-up -->
                                        <button wire:click="openActivityModal({{ $lead->id }})" class="btn btn-falcon-default btn-xs" title="Log Activity / View History">
                                            <span class="fas fa-comment-dots text-primary"></span>
                                        </button>

                                        <!-- Convert to Booking -->
                                        @if($lead->status !== 'converted')
                                            <button wire:click="convertToBooking({{ $lead->id }})" class="btn btn-falcon-success btn-xs" title="Convert to Booking">
                                                <span class="fas fa-calendar-plus text-success"></span>
                                            </button>
                                        @else
                                            @if($lead->convertedBooking)
                                                <a href="{{ route('bookings.show', $lead->converted_booking_id) }}" class="btn btn-falcon-default btn-xs text-primary" title="View Linked Booking">
                                                    <span class="fas fa-external-link-alt"></span>
                                                </a>
                                            @endif
                                        @endif

                                        <!-- Edit Lead -->
                                        <button wire:click="editLead({{ $lead->id }})" class="btn btn-falcon-default btn-xs" title="Edit Inquiry">
                                            <span class="fas fa-edit text-secondary"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <span class="fas fa-search me-1 text-400"></span>No event leads or inquiries found matching your filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($tableLeads->hasPages())
                <div class="card-footer bg-light py-2">
                    {{ $tableLeads->links() }}
                </div>
            @endif
        </div>
    @else
        <!-- KANBAN PIPELINE BOARD VIEW -->
        <div class="row g-3 flex-nowrap overflow-x-auto pb-3 mb-3">
            @php
                $columns = [
                    'new' => ['title' => 'New Inquiry', 'badge' => 'bg-primary-subtle text-primary', 'border' => 'border-primary', 'icon' => 'fas fa-inbox'],
                    'contacted' => ['title' => 'In Discussion', 'badge' => 'bg-info-subtle text-info', 'border' => 'border-info', 'icon' => 'fas fa-comments'],
                    'site_visit' => ['title' => 'Site Visit Scheduled', 'badge' => 'bg-warning-subtle text-warning', 'border' => 'border-warning', 'icon' => 'fas fa-eye'],
                    'negotiation' => ['title' => 'Quotation & Negotiation', 'badge' => 'bg-secondary-subtle text-secondary', 'border' => 'border-secondary', 'icon' => 'fas fa-file-invoice-dollar'],
                    'converted' => ['title' => 'Won / Booked', 'badge' => 'bg-success-subtle text-success', 'border' => 'border-success', 'icon' => 'fas fa-check-circle'],
                    'lost' => ['title' => 'Lost / Dropped', 'badge' => 'bg-danger-subtle text-danger', 'border' => 'border-danger', 'icon' => 'fas fa-times-circle'],
                ];
            @endphp

            @foreach($columns as $stageKey => $stageMeta)
                <div class="col-12 col-md-4 col-lg-3" style="min-width: 290px; max-width: 320px;">
                    <div class="card border-0 shadow-sm bg-100 h-100">
                        <!-- Column Header -->
                        <div class="card-header bg-200 py-2 d-flex justify-content-between align-items-center border-bottom">
                            <div class="d-flex align-items-center">
                                <span class="{{ $stageMeta['icon'] }} me-1.5 fs-11"></span>
                                <h6 class="mb-0 fw-bold fs-12">{{ $stageMeta['title'] }}</h6>
                            </div>
                            <span class="badge {{ $stageMeta['badge'] }} rounded-pill font-monospace fs-11">
                                {{ $kanbanColumns[$stageKey]->count() }}
                            </span>
                        </div>

                        <!-- Column Lead Cards List -->
                        <div class="card-body p-2 d-flex flex-column gap-2 overflow-y-auto" style="max-height: 680px;">
                            @forelse($kanbanColumns[$stageKey] as $kLead)
                                <div class="card border border-200 shadow-sm cursor-pointer position-relative hover-shadow transition-base {{ $kLead->is_overdue ? 'border-start border-3 border-danger' : '' }}">
                                    <div class="card-body p-2.5">
                                        <!-- Top Row: Name & Priority -->
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h6 class="mb-0 fw-bold text-dark fs-12">{{ $kLead->name }}</h6>
                                            <span class="badge {{ $kLead->priority_badge_class }} fs-11">
                                                {{ ucfirst($kLead->priority) }}
                                            </span>
                                        </div>

                                        <!-- Phone & WhatsApp -->
                                        <div class="text-muted fs-11 mb-2 d-flex align-items-center gap-1 font-monospace">
                                            <span class="fas fa-phone text-400 fs-10"></span>
                                            <span>{{ $kLead->phone }}</span>
                                            @if($kLead->alternate_phone)
                                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $kLead->alternate_phone) }}" target="_blank" class="text-success ms-1"><span class="fab fa-whatsapp"></span></a>
                                            @endif
                                        </div>

                                        <!-- Event Details -->
                                        <div class="d-flex flex-wrap gap-1 mb-2">
                                            @if($kLead->eventType)
                                                <span class="badge bg-secondary-subtle text-secondary fs-10">{{ $kLead->eventType->event_type_name ?? $kLead->eventType->name }}</span>
                                            @endif
                                            @if($kLead->guest_count)
                                                <span class="badge bg-light text-dark border fs-10">{{ number_format($kLead->guest_count) }} pax</span>
                                            @endif
                                            @if($kLead->estimated_budget)
                                                <span class="badge bg-success-subtle text-success fs-10">Rs. {{ number_format($kLead->estimated_budget) }}</span>
                                            @endif
                                        </div>

                                        <!-- Preferred Date & Hall -->
                                        <div class="text-500 fs-11 mb-2">
                                            <div><span class="fas fa-calendar-day me-1 text-primary"></span>{{ $kLead->preferred_date ? $kLead->preferred_date->format('d-M-Y') : 'Date flexible' }}</div>
                                            <div><span class="fas fa-building me-1 text-muted"></span>{{ $kLead->hall->hall_name ?? ($kLead->hall->name ?? 'Hall flexible') }} ({{ $kLead->slot->slot_name ?? ($kLead->slot->name ?? 'Slot flexible') }})</div>
                                        </div>

                                        <!-- Follow-up Alert -->
                                        @if($kLead->follow_up_date && !in_array($kLead->status, ['converted', 'lost']))
                                            <div class="border rounded p-1 mb-2 fs-10 {{ $kLead->is_overdue ? 'bg-danger-subtle text-danger fw-bold' : ($kLead->is_due_today ? 'bg-warning-subtle text-warning fw-bold' : 'bg-light text-muted') }}">
                                                <span class="fas fa-bell me-1"></span>
                                                Follow-up: {{ $kLead->follow_up_date->format('d-M') }}
                                                @if($kLead->is_overdue) (Overdue) @elseif($kLead->is_due_today) (Today) @endif
                                            </div>
                                        @endif

                                        <!-- Bottom Action Buttons & Stage Mover -->
                                        <div class="border-top pt-2 d-flex justify-content-between align-items-center">
                                            <div class="btn-group btn-group-xs">
                                                <button wire:click="openActivityModal({{ $kLead->id }})" class="btn btn-falcon-default btn-xs" title="Log Follow-up Interaction">
                                                    <span class="fas fa-comment-dots text-primary"></span>
                                                </button>
                                                <button wire:click="editLead({{ $kLead->id }})" class="btn btn-falcon-default btn-xs" title="Edit Inquiry">
                                                    <span class="fas fa-edit text-secondary"></span>
                                                </button>
                                                @if($stageKey !== 'converted')
                                                    <button wire:click="convertToBooking({{ $kLead->id }})" class="btn btn-falcon-success btn-xs" title="Convert to Booking">
                                                        <span class="fas fa-calendar-plus text-success"></span>
                                                    </button>
                                                @endif
                                            </div>

                                            <!-- Move stage dropdown -->
                                            <div class="dropdown">
                                                <button class="btn btn-falcon-default btn-xs dropdown-toggle py-0 px-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Move
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end fs-11">
                                                    @foreach($columns as $targetKey => $targetMeta)
                                                        @if($targetKey !== $stageKey)
                                                            <li>
                                                                <button class="dropdown-item py-1" wire:click="updateLeadStatus({{ $kLead->id }}, '{{ $targetKey }}')">
                                                                    <span class="{{ $targetMeta['icon'] }} me-1"></span>{{ $targetMeta['title'] }}
                                                                </button>
                                                            </li>
                                                        @endif
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted fs-11">
                                    No inquiries in this stage
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- ============================================================= -->
    <!-- MODAL 1: CREATE / EDIT LEAD INQUIRY FORM                      -->
    <!-- ============================================================= -->
    @if($showLeadModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1050;">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light py-2">
                        <h6 class="modal-title fw-bold text-primary">
                            <span class="fas fa-{{ $leadId ? 'edit' : 'plus-circle' }} me-2"></span>{{ $leadId ? 'Edit Inquiry Profile' : 'Register New Event Inquiry' }}
                        </h6>
                        <button wire:click="$set('showLeadModal', false)" type="button" class="btn-close" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="saveLead">
                        <div class="modal-body p-3 fs-12">
                            <!-- Section 1: Customer Contact Details -->
                            <div class="row navbar-vertical-label-wrapper mb-2">
                                <div class="col-auto navbar-vertical-label text-primary fw-bold">1. Customer Contact Information</div>
                                <div class="col ps-0"><hr class="mb-0 text-300" /></div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semi-bold">Client Full Name <span class="text-danger">*</span></label>
                                    <input wire:model="name" type="text" class="form-control form-control-sm @error('name') is-invalid @enderror" placeholder="e.g. Imran Khan" required>
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semi-bold">Primary Phone <span class="text-danger">*</span></label>
                                    <input wire:model="phone" type="text" class="form-control form-control-sm @error('phone') is-invalid @enderror" placeholder="0300-1234567" required>
                                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semi-bold">Alternate Phone / WhatsApp</label>
                                    <input wire:model="alternate_phone" type="text" class="form-control form-control-sm @error('alternate_phone') is-invalid @enderror" placeholder="0321-7654321">
                                    @error('alternate_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semi-bold">Email Address</label>
                                    <input wire:model="email" type="email" class="form-control form-control-sm @error('email') is-invalid @enderror" placeholder="client@example.com">
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semi-bold">City</label>
                                    <input wire:model="city" type="text" class="form-control form-control-sm" placeholder="Lahore">
                                </div>
                            </div>

                            <!-- Section 2: Event Requirements -->
                            <div class="row navbar-vertical-label-wrapper mb-2">
                                <div class="col-auto navbar-vertical-label text-primary fw-bold">2. Event Requirements & Preferences</div>
                                <div class="col ps-0"><hr class="mb-0 text-300" /></div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semi-bold">Branch</label>
                                    <select wire:model="branch_id" class="form-select form-select-sm">
                                        <option value="">-- Any / Head Office --</option>
                                        @foreach($branches as $b)
                                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semi-bold">Event Type</label>
                                    <select wire:model="event_type_id" class="form-select form-select-sm">
                                        <option value="">-- Select Event Type --</option>
                                        @foreach($eventTypes as $et)
                                            <option value="{{ $et->id }}">{{ $et->event_type_name ?? $et->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semi-bold">Preferred Hall</label>
                                    <select wire:model="hall_id" class="form-select form-select-sm">
                                        <option value="">-- Any Available Hall --</option>
                                        @foreach($halls as $h)
                                            <option value="{{ $h->id }}">{{ $h->hall_name ?? $h->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-semi-bold">Preferred Event Date</label>
                                    <input wire:model="preferred_date" type="date" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semi-bold">Alternate Date</label>
                                    <input wire:model="alternate_date" type="date" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semi-bold">Shift / Slot</label>
                                    <select wire:model="slot_id" class="form-select form-select-sm">
                                        <option value="">-- Any Shift --</option>
                                        @foreach($slots as $s)
                                            <option value="{{ $s->id }}">{{ $s->slot_name ?? $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semi-bold">Estimated Guests</label>
                                    <input wire:model="guest_count" type="number" class="form-control form-control-sm" placeholder="e.g. 350">
                                </div>
                            </div>

                            <!-- Section 3: CRM Pipeline & Follow-up Tracking -->
                            <div class="row navbar-vertical-label-wrapper mb-2">
                                <div class="col-auto navbar-vertical-label text-primary fw-bold">3. CRM Status & Follow-up Management</div>
                                <div class="col ps-0"><hr class="mb-0 text-300" /></div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semi-bold">Inquiry Source <span class="text-danger">*</span></label>
                                    <select wire:model="lead_source" class="form-select form-select-sm">
                                        <option value="walk_in">Walk-in Visit</option>
                                        <option value="call">Phone Call</option>
                                        <option value="whatsapp">WhatsApp</option>
                                        <option value="facebook">Facebook</option>
                                        <option value="instagram">Instagram</option>
                                        <option value="website">Website Form</option>
                                        <option value="referral">Client Referral</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semi-bold">Pipeline Stage <span class="text-danger">*</span></label>
                                    <select wire:model="status" class="form-select form-select-sm">
                                        <option value="new">New Inquiry</option>
                                        <option value="contacted">Contacted / Discussion</option>
                                        <option value="site_visit">Site Visit Scheduled</option>
                                        <option value="negotiation">Quotation / Negotiation</option>
                                        <option value="converted">Converted to Booking</option>
                                        <option value="lost">Lost / Dropped</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semi-bold">Lead Priority <span class="text-danger">*</span></label>
                                    <select wire:model="priority" class="form-select form-select-sm">
                                        <option value="hot">🔥 Hot Priority</option>
                                        <option value="warm">⚡ Warm Priority</option>
                                        <option value="cold">❄️ Cold Priority</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semi-bold">Next Follow-up Date</label>
                                    <input wire:model="follow_up_date" type="date" class="form-control form-control-sm">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semi-bold">Estimated Budget (Rs.)</label>
                                    <input wire:model="estimated_budget" type="number" step="0.01" class="form-control form-control-sm" placeholder="e.g. 750000">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semi-bold">Assigned Sales Representative</label>
                                    <select wire:model="assigned_to" class="form-select form-select-sm">
                                        <option value="">-- Unassigned --</option>
                                        @foreach($staffUsers as $su)
                                            <option value="{{ $su->id }}">{{ $su->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semi-bold">General Notes / Requirements</label>
                                    <textarea wire:model="notes" class="form-control form-control-sm" rows="1" placeholder="Catering preferences, special decor requests..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer bg-light py-2">
                            <button wire:click="$set('showLeadModal', false)" type="button" class="btn btn-secondary btn-sm">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <span class="fas fa-check me-1"></span>Save Inquiry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================= -->
    <!-- MODAL 2: LOG FOLLOW-UP ACTIVITY & COMMUNICATION TIMELINE     -->
    <!-- ============================================================= -->
    @if($showActivityModal && $selectedLead)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1050;">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light py-2">
                        <div>
                            <h6 class="modal-title fw-bold text-primary mb-0">
                                <span class="fas fa-history me-2"></span>Lead CRM Timeline & Activity Log
                            </h6>
                            <div class="text-muted fs-11">
                                Client: <strong>{{ $selectedLead->name }}</strong> | Phone: {{ $selectedLead->phone }} | Stage: <span class="badge {{ $selectedLead->status_badge_class }}">{{ $selectedLead->status_label }}</span>
                            </div>
                        </div>
                        <button wire:click="$set('showActivityModal', false)" type="button" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3 fs-12">
                        <div class="row g-3">
                            <!-- Left: New Activity Form -->
                            <div class="col-md-5 border-end">
                                <h6 class="fw-bold mb-2 text-dark"><span class="fas fa-pen-nib me-1 text-primary"></span>Log Interaction</h6>
                                <form wire:submit.prevent="saveActivity">
                                    <div class="mb-2">
                                        <label class="form-label fw-semi-bold">Channel / Type <span class="text-danger">*</span></label>
                                        <select wire:model="activityType" class="form-select form-select-sm" required>
                                            <option value="call">📞 Phone Call</option>
                                            <option value="whatsapp">💬 WhatsApp Message</option>
                                            <option value="site_visit">🏛️ Site Visit / Tour</option>
                                            <option value="meeting">🤝 In-Person Meeting</option>
                                            <option value="quotation_sent">📄 Quotation Sent</option>
                                            <option value="note">📝 Internal Note</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label fw-semi-bold">Interaction Details <span class="text-danger">*</span></label>
                                        <textarea wire:model="activityNotes" class="form-control form-control-sm @error('activityNotes') is-invalid @enderror" rows="4" placeholder="Client feedback, requested package, quotation discussion..." required></textarea>
                                        @error('activityNotes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semi-bold">Next Follow-up Reminder</label>
                                        <input wire:model="activityFollowUpDate" type="date" class="form-control form-control-sm">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                        <span class="fas fa-plus me-1"></span>Log Activity & Update Follow-up
                                    </button>
                                </form>
                            </div>

                            <!-- Right: Activity History Timeline -->
                            <div class="col-md-7">
                                <h6 class="fw-bold mb-2 text-dark"><span class="fas fa-stream me-1 text-primary"></span>Activity History Timeline</h6>
                                <div class="overflow-y-auto pe-1" style="max-height: 380px;">
                                    @forelse($selectedLead->activities as $act)
                                        <div class="border rounded p-2 mb-2 bg-light shadow-none">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <div class="fw-bold fs-11 text-dark">
                                                    <span class="{{ $act->activity_icon }} me-1"></span>{{ $act->activity_label }}
                                                </div>
                                                <div class="text-muted fs-10 font-monospace">
                                                    {{ $act->created_at->format('d-M-Y H:i') }}
                                                </div>
                                            </div>
                                            <div class="text-800 fs-11 mb-1">{{ $act->notes }}</div>
                                            <div class="d-flex justify-content-between align-items-center fs-10 text-muted border-top pt-1">
                                                <span>By: {{ $act->user->name ?? 'System' }}</span>
                                                @if($act->follow_up_date)
                                                    <span class="text-primary"><span class="fas fa-calendar-alt me-1"></span>Follow-up: {{ $act->follow_up_date->format('d-M-Y') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-center py-4 text-muted">
                                            No activity logs recorded yet.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button wire:click="$set('showActivityModal', false)" type="button" class="btn btn-secondary btn-sm">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================= -->
    <!-- MODAL 3: MARK AS LOST / DROPPED DIALOG                        -->
    <!-- ============================================================= -->
    @if($showLostModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1050;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light py-2">
                        <h6 class="modal-title fw-bold text-danger">
                            <span class="fas fa-times-circle me-2"></span>Mark Inquiry as Lost / Dropped
                        </h6>
                        <button wire:click="$set('showLostModal', false)" type="button" class="btn-close" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="confirmLost">
                        <div class="modal-body p-3 fs-12">
                            <div class="mb-3">
                                <label class="form-label fw-semi-bold">Primary Reason for Lost Opportunity <span class="text-danger">*</span></label>
                                <select wire:model="lostReason" class="form-select form-select-sm" required>
                                    <option value="chose_competitor">Chose Another Marquee / Competitor</option>
                                    <option value="date_unavailable">Preferred Date / Slot Unavailable</option>
                                    <option value="price_high">Pricing / Package Out of Budget</option>
                                    <option value="capacity_mismatch">Hall Capacity Too Small / Too Large</option>
                                    <option value="cancelled">Event Postponed or Cancelled by Family</option>
                                    <option value="no_response">Client Unreachable / No Response</option>
                                    <option value="other">Other Reason</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semi-bold">Additional Notes / Feedback</label>
                                <textarea wire:model="lostNotes" class="form-control form-control-sm" rows="3" placeholder="Provide context or competitor name..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button wire:click="$set('showLostModal', false)" type="button" class="btn btn-secondary btn-sm">Cancel</button>
                            <button type="submit" class="btn btn-danger btn-sm">
                                <span class="fas fa-check me-1"></span>Confirm Lost
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
