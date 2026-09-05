<div>
    <!-- Breadcrumbs & Header -->
    <div class="row mb-3">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Activity Logs</li>
                </ol>
            </nav>
            <h4 class="mb-0 text-primary fw-bold">
                <span class="fas fa-history me-2 text-primary"></span>System & Staff Activity Logs
            </h4>
            <p class="text-600 fs-11 mb-0">
                @if($isSuperAdmin)
                    Platform-wide audit trail tracking all user actions, security logins, and data modifications across all businesses.
                @else
                    Audit trail tracking actions, logins, and operational updates made by your venue staff and managers.
                @endif
            </p>
        </div>
        <div class="col-auto d-flex align-items-center gap-2">
            <!-- View Mode Switcher -->
            <div class="btn-group btn-group-sm" role="group" aria-label="View Mode">
                <button type="button" 
                        wire:click="$set('viewMode', 'timeline')" 
                        class="btn {{ $viewMode === 'timeline' ? 'btn-primary' : 'btn-outline-secondary' }}" 
                        title="Timeline View">
                    <span class="fas fa-stream me-1"></span>Timeline
                </button>
                <button type="button" 
                        wire:click="$set('viewMode', 'table')" 
                        class="btn {{ $viewMode === 'table' ? 'btn-primary' : 'btn-outline-secondary' }}" 
                        title="Table View">
                    <span class="fas fa-table me-1"></span>Table
                </button>
            </div>

            <!-- CSV Export -->
            <button type="button" wire:click="exportCsv" wire:loading.attr="disabled" class="btn btn-sm btn-falcon-default">
                <span class="fas fa-file-export me-1 text-primary"></span>
                <span wire:loading.remove wire:target="exportCsv">Export CSV</span>
                <span wire:loading wire:target="exportCsv">Exporting...</span>
            </button>
        </div>
    </div>

    <!-- KPI Summary Metrics (Falcon Card Style) -->
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-md-3">
            <div class="card h-100 border-start border-4 border-primary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 text-uppercase mb-1">Total Activities</h6>
                            <h4 class="fw-bold mb-0 text-900">{{ number_format($totalCount) }}</h4>
                        </div>
                        <div class="icon-item icon-item-sm rounded-circle bg-primary-subtle text-primary">
                            <span class="fas fa-layer-group"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card h-100 border-start border-4 border-success">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 text-uppercase mb-1">Today's Actions</h6>
                            <h4 class="fw-bold mb-0 text-success">{{ number_format($todayCount) }}</h4>
                        </div>
                        <div class="icon-item icon-item-sm rounded-circle bg-success-subtle text-success">
                            <span class="fas fa-calendar-day"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card h-100 border-start border-4 border-info">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 text-uppercase mb-1">Active Staff Today</h6>
                            <h4 class="fw-bold mb-0 text-info">{{ number_format($activeStaffToday) }}</h4>
                        </div>
                        <div class="icon-item icon-item-sm rounded-circle bg-info-subtle text-info">
                            <span class="fas fa-users"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card h-100 border-start border-4 border-warning">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 text-uppercase mb-1">Data Modifications</h6>
                            <h4 class="fw-bold mb-0 text-warning">{{ number_format($modificationsCount) }}</h4>
                        </div>
                        <div class="icon-item icon-item-sm rounded-circle bg-warning-subtle text-warning">
                            <span class="fas fa-edit"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Toolbar (Falcon Card) -->
    <div class="card mb-3 shadow-none border">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- Search Box -->
                <div class="col-12 col-md-3">
                    <div class="position-relative">
                        <input type="text" 
                               wire:model.live.debounce.300ms="search" 
                               class="form-control form-control-sm ps-4" 
                               placeholder="Search user, action, description, IP..." />
                        <span class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-2 text-400 fs-10"></span>
                    </div>
                </div>

                <!-- Marquee Selector (Super Admin or Multi-Business Owner) -->
                @if($isSuperAdmin || $marqueesList->count() > 1)
                    <div class="col-6 col-md-2">
                        <select wire:model.live="selectedMarquee" class="form-select form-select-sm">
                            <option value="">{{ $isSuperAdmin ? 'All Businesses' : 'All My Venues' }}</option>
                            @foreach($marqueesList as $m)
                                <option value="{{ $m->id }}">{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- User / Staff Selector -->
                <div class="col-6 col-md-2">
                    <select wire:model.live="selectedUser" class="form-select form-select-sm">
                        <option value="">All Staff / Users</option>
                        @foreach($usersList as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role->label ?? $u->role->name ?? 'User' }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Type -->
                <div class="col-6 col-md-2">
                    <select wire:model.live="selectedAction" class="form-select form-select-sm">
                        <option value="">All Actions</option>
                        <option value="created">Created</option>
                        <option value="updated">Updated</option>
                        <option value="deleted">Deleted</option>
                        <option value="login">Login</option>
                        <option value="logout">Logout</option>
                    </select>
                </div>

                <!-- Module / Model Type -->
                <div class="col-6 col-md-2">
                    <select wire:model.live="selectedModel" class="form-select form-select-sm">
                        <option value="">All Modules</option>
                        @foreach($modulesList as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Presets -->
                <div class="col-6 col-md-1">
                    <select wire:model.live="datePreset" class="form-select form-select-sm">
                        <option value="all">All Dates</option>
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="7days">Last 7 Days</option>
                        <option value="30days">Last 30 Days</option>
                    </select>
                </div>

                <!-- Reset Filters -->
                <div class="col-auto ms-auto">
                    @if($search || $selectedUser || $selectedAction || $selectedModel || ($isSuperAdmin && $selectedMarquee) || $datePreset !== 'all')
                        <button type="button" wire:click="resetFilters" class="btn btn-sm btn-link text-danger p-0 fs-11">
                            <span class="fas fa-times me-1"></span>Reset
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content: Timeline View or Table View -->
    @if($viewMode === 'timeline')
        <!-- Falcon Interactive Timeline View -->
        <div class="card mb-3">
            <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center py-2">
                <h6 class="mb-0 text-700 fs-11 text-uppercase fw-bold">
                    <span class="fas fa-stream me-1 text-primary"></span>Activity Feed ({{ $logs->total() }} events)
                </h6>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-500 fs-11">Per Page:</span>
                    <select wire:model.live="perPage" class="form-select form-select-sm py-0 fs-11" style="width: auto;">
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>

            <div class="card-body p-3">
                @if($logs->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <span class="fas fa-clipboard-list fa-3x text-300 mb-3 d-block"></span>
                        <h6 class="text-700">No activity logs found</h6>
                        <p class="fs-11 mb-0">Try changing your search keywords or clearing active filters.</p>
                    </div>
                @else
                    <div class="timeline-basic">
                        @foreach($logs as $log)
                            @php
                                $actionColor = match(strtolower($log->action)) {
                                    'created' => 'success',
                                    'updated' => 'primary',
                                    'deleted' => 'danger',
                                    'login' => 'info',
                                    'logout' => 'secondary',
                                    default => 'warning'
                                };
                                $actionIcon = match(strtolower($log->action)) {
                                    'created' => 'fas fa-plus-circle',
                                    'updated' => 'fas fa-pen',
                                    'deleted' => 'fas fa-trash-alt',
                                    'login' => 'fas fa-sign-in-alt',
                                    'logout' => 'fas fa-sign-out-alt',
                                    default => 'fas fa-bolt'
                                };
                                $modelName = class_basename($log->model_type);
                            @endphp

                            <div class="timeline-item pb-3 position-relative">
                                <div class="row g-3">
                                    <!-- Action Icon Node -->
                                    <div class="col-auto">
                                        <div class="icon-item icon-item-sm rounded-circle bg-{{ $actionColor }}-subtle text-{{ $actionColor }} shadow-none">
                                            <span class="{{ $actionIcon }} fs-11"></span>
                                        </div>
                                    </div>

                                    <!-- Content Card -->
                                    <div class="col">
                                        <div class="card border border-200 shadow-none bg-body-tertiary">
                                            <div class="card-body p-3">
                                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-1">
                                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                                        <!-- User Actor -->
                                                        <span class="fw-bold text-dark fs-11">
                                                            {{ $log->user->name ?? 'System / Automated' }}
                                                        </span>
                                                        @if($log->user && $log->user->role)
                                                            <span class="badge badge-subtle-secondary rounded-pill fs-10">
                                                                {{ $log->user->role->label ?? $log->user->role->name }}
                                                            </span>
                                                        @endif

                                                        <!-- Action Badge -->
                                                        <span class="badge badge-subtle-{{ $actionColor }} rounded-pill fs-10 text-uppercase">
                                                            {{ $log->action }}
                                                        </span>

                                                        <!-- Module Badge -->
                                                        @if($log->model_type)
                                                            <span class="badge bg-light text-secondary border fs-10">
                                                                {{ $modelName }}@if($log->model_id) #{{ $log->model_id }}@endif
                                                            </span>
                                                        @endif

                                                        <!-- Marquee Badge (Super Admin) -->
                                                        @if($isSuperAdmin && $log->marquee)
                                                            <span class="badge badge-subtle-info rounded-pill fs-10">
                                                                <span class="fas fa-building me-1"></span>{{ $log->marquee->name }}
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <!-- Timestamp -->
                                                    <div class="text-500 fs-10" title="{{ $log->created_at ? $log->created_at->format('d-M-Y h:i:s A') : '' }}">
                                                        <span class="far fa-clock me-1"></span>{{ $log->created_at ? $log->created_at->diffForHumans() : '—' }}
                                                    </div>
                                                </div>

                                                <!-- Description Text -->
                                                <p class="text-800 fs-11 mb-1">
                                                    {{ $log->description ?: "{$modelName} record was {$log->action}" }}
                                                </p>

                                                <!-- Metadata Badges & Details Action -->
                                                <div class="d-flex flex-wrap align-items-center justify-content-between pt-1 border-top border-200 mt-2 fs-10 text-500">
                                                    <div class="d-flex align-items-center gap-3">
                                                        @if($log->ip_address)
                                                            <span><span class="fas fa-network-wired me-1 text-400"></span>IP: <code>{{ $log->ip_address }}</code></span>
                                                        @endif
                                                        <span><span class="far fa-calendar-alt me-1 text-400"></span>{{ $log->created_at ? $log->created_at->format('d-M-Y h:i A') : '—' }}</span>
                                                    </div>

                                                    @if(!empty($log->old_values) || !empty($log->new_values))
                                                        <button type="button" 
                                                                wire:click="showDetailModal({{ $log->id }})" 
                                                                class="btn btn-link btn-sm p-0 fs-11 text-primary fw-semi-bold">
                                                            <span class="fas fa-info-circle me-1"></span>Inspect Changes
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            @if($logs->hasPages())
                <div class="card-footer bg-body-tertiary py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="fs-11 text-muted">
                            Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} events
                        </div>
                        <div>
                            {{ $logs->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

    @else
        <!-- Falcon High-Density Data Table View -->
        <div class="card mb-3">
            <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center py-2">
                <h6 class="mb-0 text-700 fs-11 text-uppercase fw-bold">
                    <span class="fas fa-table me-1 text-primary"></span>Audit Log Table ({{ $logs->total() }} records)
                </h6>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-500 fs-11">Per Page:</span>
                    <select wire:model.live="perPage" class="form-select form-select-sm py-0 fs-11" style="width: auto;">
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive fs-11">
                <table class="table table-sm table-striped table-hover mb-0">
                    <thead class="bg-200 text-800">
                        <tr>
                            <th class="py-2 px-3">Timestamp</th>
                            <th class="py-2">User / Staff</th>
                            @if($isSuperAdmin)
                                <th class="py-2">Business</th>
                            @endif
                            <th class="py-2 text-center">Action</th>
                            <th class="py-2">Target Module</th>
                            <th class="py-2">Description</th>
                            <th class="py-2">IP Address</th>
                            <th class="py-2 text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            @php
                                $actionColor = match(strtolower($log->action)) {
                                    'created' => 'success',
                                    'updated' => 'primary',
                                    'deleted' => 'danger',
                                    'login' => 'info',
                                    'logout' => 'secondary',
                                    default => 'warning'
                                };
                            @endphp
                            <tr>
                                <td class="py-2 px-3 align-middle text-nowrap text-600">
                                    <div class="fw-semi-bold text-dark">{{ $log->created_at ? $log->created_at->format('d-M-Y') : '—' }}</div>
                                    <div class="fs-10 text-muted">{{ $log->created_at ? $log->created_at->format('h:i:s A') : '—' }}</div>
                                </td>
                                <td class="py-2 align-middle">
                                    <div class="fw-bold text-dark">{{ $log->user->name ?? 'System' }}</div>
                                    <div class="fs-10 text-muted">{{ $log->user->role->label ?? $log->user->role->name ?? 'User' }}</div>
                                </td>
                                @if($isSuperAdmin)
                                    <td class="py-2 align-middle">
                                        <span class="badge badge-subtle-info rounded-pill fs-10">
                                            {{ $log->marquee->name ?? 'Global' }}
                                        </span>
                                    </td>
                                @endif
                                <td class="py-2 align-middle text-center">
                                    <span class="badge badge-subtle-{{ $actionColor }} rounded-pill text-uppercase fs-10">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="py-2 align-middle">
                                    <span class="badge bg-light text-secondary border fs-10">
                                        {{ class_basename($log->model_type) }}@if($log->model_id) #{{ $log->model_id }}@endif
                                    </span>
                                </td>
                                <td class="py-2 align-middle text-800">
                                    {{ $log->description }}
                                </td>
                                <td class="py-2 align-middle text-nowrap font-monospace fs-10 text-muted">
                                    {{ $log->ip_address ?? '—' }}
                                </td>
                                <td class="py-2 px-3 align-middle text-end">
                                    @if(!empty($log->old_values) || !empty($log->new_values))
                                        <button type="button" 
                                                wire:click="showDetailModal({{ $log->id }})" 
                                                class="btn btn-falcon-default btn-sm px-2 py-1 fs-11" 
                                                title="View Field Changes">
                                            <span class="fas fa-search me-1"></span>Diff
                                        </button>
                                    @else
                                        <button type="button" 
                                                wire:click="showDetailModal({{ $log->id }})" 
                                                class="btn btn-link btn-sm text-secondary p-0 fs-11" 
                                                title="View Details">
                                            <span class="fas fa-eye"></span>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isSuperAdmin ? 8 : 7 }}" class="text-center py-4 text-muted">
                                    No activity logs match your filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($logs->hasPages())
                <div class="card-footer bg-body-tertiary py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="fs-11 text-muted">
                            Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} records
                        </div>
                        <div>
                            {{ $logs->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Audit Details & Diffs Modal (Falcon Modal) -->
    @if($selectedLogDetails)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border">
                    <div class="modal-header bg-body-tertiary py-2 px-3">
                        <h6 class="modal-title text-dark fw-bold mb-0">
                            <span class="fas fa-file-alt me-2 text-primary"></span>Activity Audit Details #{{ $selectedLogDetails['id'] }}
                        </h6>
                        <button type="button" class="btn-close" wire:click="closeDetailModal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3 fs-11">
                        <!-- Overview Grid -->
                        <div class="row g-2 mb-3 bg-light p-2 rounded border">
                            <div class="col-sm-6">
                                <div><strong>Actor:</strong> <span class="text-dark fw-bold">{{ $selectedLogDetails['user_name'] }}</span> <span class="badge badge-subtle-primary fs-10">{{ $selectedLogDetails['user_role'] }}</span></div>
                                <div><strong>Email:</strong> <span class="text-muted">{{ $selectedLogDetails['user_email'] ?? '—' }}</span></div>
                                <div><strong>Business / Marquee:</strong> <span class="text-dark">{{ $selectedLogDetails['marquee_name'] }}</span></div>
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <div><strong>Timestamp:</strong> {{ $selectedLogDetails['created_at'] }} ({{ $selectedLogDetails['relative_time'] }})</div>
                                <div><strong>IP Address:</strong> <code>{{ $selectedLogDetails['ip_address'] }}</code></div>
                                <div><strong>Action:</strong> <span class="badge badge-subtle-primary text-uppercase">{{ $selectedLogDetails['action'] }}</span></div>
                            </div>
                        </div>

                        <!-- Description & Target -->
                        <div class="mb-3">
                            <h6 class="text-600 fs-11 text-uppercase fw-bold mb-1">Event Summary</h6>
                            <div class="p-2 border rounded bg-white">
                                <p class="mb-1 text-800 fw-semi-bold">{{ $selectedLogDetails['description'] }}</p>
                                <div class="text-muted fs-10">
                                    <strong>Model:</strong> <code>{{ $selectedLogDetails['model_full_type'] }}</code>
                                    @if($selectedLogDetails['model_id'])
                                        | <strong>Record ID:</strong> #{{ $selectedLogDetails['model_id'] }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Changed Fields Diff Table -->
                        @if(!empty($selectedLogDetails['new_values']) || !empty($selectedLogDetails['old_values']))
                            <div class="mb-2">
                                <h6 class="text-600 fs-11 text-uppercase fw-bold mb-1">
                                    <span class="fas fa-exchange-alt me-1 text-primary"></span>Field Changes (Before vs After)
                                </h6>
                                <div class="table-responsive border rounded">
                                    <table class="table table-sm table-striped mb-0">
                                        <thead class="bg-200">
                                            <tr>
                                                <th class="py-1 px-2" style="width: 25%;">Field Name</th>
                                                <th class="py-1 px-2 text-danger" style="width: 37.5%;">Old Value (Previous)</th>
                                                <th class="py-1 px-2 text-success" style="width: 37.5%;">New Value (Updated)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $allKeys = array_unique(array_merge(
                                                    array_keys($selectedLogDetails['old_values'] ?? []),
                                                    array_keys($selectedLogDetails['new_values'] ?? [])
                                                ));
                                            @endphp
                                            @foreach($allKeys as $key)
                                                @php
                                                    $oldVal = $selectedLogDetails['old_values'][$key] ?? null;
                                                    $newVal = $selectedLogDetails['new_values'][$key] ?? null;
                                                    
                                                    // Format values for human display
                                                    $oldDisplay = is_array($oldVal) ? json_encode($oldVal) : (is_bool($oldVal) ? ($oldVal ? 'true' : 'false') : (string)($oldVal ?? '—'));
                                                    $newDisplay = is_array($newVal) ? json_encode($newVal) : (is_bool($newVal) ? ($newVal ? 'true' : 'false') : (string)($newVal ?? '—'));
                                                @endphp
                                                <tr>
                                                    <td class="py-1 px-2 font-monospace fw-bold text-dark">{{ $key }}</td>
                                                    <td class="py-1 px-2 text-danger font-monospace">
                                                        <span class="text-decoration-line-through">{{ $oldDisplay }}</span>
                                                    </td>
                                                    <td class="py-1 px-2 text-success font-monospace fw-bold">
                                                        {{ $newDisplay }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        <!-- User Agent / Device -->
                        @if($selectedLogDetails['user_agent'])
                            <div class="mt-3">
                                <span class="text-500 fs-10">
                                    <span class="fas fa-desktop me-1"></span><strong>Device / Client:</strong> {{ $selectedLogDetails['user_agent'] }}
                                </span>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer py-2 px-3">
                        <button type="button" class="btn btn-sm btn-secondary" wire:click="closeDetailModal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
