<div>
    <!-- Page Header -->
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-l bg-subtle-primary text-primary rounded-circle d-flex align-items-center justify-content-center">
                        <span class="fas fa-database fs-8"></span>
                    </div>
                    <div>
                        <h5 class="mb-0 text-primary fw-bold">Database Migrations & System Maintenance</h5>
                        <p class="text-600 fs-11 mb-0">Execute database migrations, seed default data, and clear system caches on the live server.</p>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button wire:click="$refresh" class="btn btn-falcon-default btn-sm" type="button" title="Refresh migration status">
                    <span class="fas fa-sync-alt {{ $errors->isEmpty() ? '' : 'fa-spin' }} me-1"></span> Refresh
                </button>
                <button wire:click="clearSystemCache" wire:loading.attr="disabled" class="btn btn-falcon-default btn-sm text-nowrap" type="button">
                    <span wire:loading.remove wire:target="clearSystemCache" class="fas fa-broom text-warning me-1"></span>
                    <span wire:loading wire:target="clearSystemCache" class="spinner-border spinner-border-sm me-1"></span>
                    Clear Caches
                </button>
                <button wire:click="runGlobalDefaultsSeeder" wire:loading.attr="disabled" class="btn btn-falcon-default btn-sm text-nowrap" type="button">
                    <span wire:loading.remove wire:target="runGlobalDefaultsSeeder" class="fas fa-seedling text-success me-1"></span>
                    <span wire:loading wire:target="runGlobalDefaultsSeeder" class="spinner-border spinner-border-sm me-1"></span>
                    Sync Global Defaults
                </button>
                <button wire:click="promptRunMigrations" wire:loading.attr="disabled" class="btn btn-primary btn-sm text-nowrap shadow-sm" type="button">
                    <span wire:loading.remove wire:target="runMigrations" class="fas fa-play me-1"></span>
                    <span wire:loading wire:target="runMigrations" class="spinner-border spinner-border-sm me-1"></span>
                    Run Pending Migrations
                    @if($stats['pending'] > 0)
                        <span class="badge bg-warning text-dark rounded-pill ms-1">{{ $stats['pending'] }}</span>
                    @endif
                </button>
            </div>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="alert alert-success border-2 d-flex align-items-center mb-3 alert-dismissible fade show shadow-sm" role="alert">
            <span class="fas fa-check-circle me-2 fs-9"></span>
            <div class="flex-1 fs-10 fw-semi-bold">{{ session('success') }}</div>
            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-2 d-flex align-items-center mb-3 alert-dismissible fade show shadow-sm" role="alert">
            <span class="fas fa-exclamation-circle me-2 fs-9"></span>
            <div class="flex-1 fs-10 fw-semi-bold">{{ session('error') }}</div>
            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- KPI Metric Cards -->
    <div class="row g-3 mb-3">
        <!-- Total Migrations -->
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 shadow-sm border-0 border-start border-4 border-primary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 text-uppercase mb-1">Total Migrations</h6>
                            <h3 class="fw-bold mb-0 text-900">{{ $stats['total'] }}</h3>
                        </div>
                        <div class="avatar avatar-xl bg-subtle-primary text-primary rounded-3 d-flex align-items-center justify-content-center">
                            <span class="fas fa-layer-group fs-7"></span>
                        </div>
                    </div>
                    <div class="mt-2 text-muted fs-11">
                        Files in <code>database/migrations</code>
                    </div>
                </div>
            </div>
        </div>

        <!-- Executed Migrations -->
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 shadow-sm border-0 border-start border-4 border-success">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 text-uppercase mb-1">Executed (Ran)</h6>
                            <h3 class="fw-bold mb-0 text-success">{{ $stats['ran'] }}</h3>
                        </div>
                        <div class="avatar avatar-xl bg-subtle-success text-success rounded-3 d-flex align-items-center justify-content-center">
                            <span class="fas fa-check-double fs-7"></span>
                        </div>
                    </div>
                    <div class="mt-2 text-muted fs-11">
                        Recorded in <code>migrations</code> table
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Migrations -->
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 shadow-sm border-0 border-start border-4 {{ $stats['pending'] > 0 ? 'border-warning' : 'border-secondary' }}">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 text-uppercase mb-1">Pending Updates</h6>
                            <h3 class="fw-bold mb-0 {{ $stats['pending'] > 0 ? 'text-warning' : 'text-700' }}">{{ $stats['pending'] }}</h3>
                        </div>
                        <div class="avatar avatar-xl {{ $stats['pending'] > 0 ? 'bg-subtle-warning text-warning' : 'bg-subtle-secondary text-secondary' }} rounded-3 d-flex align-items-center justify-content-center">
                            <span class="fas fa-clock fs-7"></span>
                        </div>
                    </div>
                    <div class="mt-2 fs-11">
                        @if($stats['pending'] > 0)
                            <span class="badge badge-subtle-warning">Action Required</span>
                        @else
                            <span class="text-success"><span class="fas fa-check me-1"></span>Up to date</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Environment & DB Connection -->
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 shadow-sm border-0 border-start border-4 border-info">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 text-uppercase mb-1">Target Database</h6>
                            <h6 class="fw-bold mb-0 text-900 text-truncate" style="max-width: 150px;" title="{{ $stats['db_name'] }}">
                                {{ $stats['db_name'] }}
                            </h6>
                        </div>
                        <div class="avatar avatar-xl bg-subtle-info text-info rounded-3 d-flex align-items-center justify-content-center">
                            <span class="fas fa-server fs-7"></span>
                        </div>
                    </div>
                    <div class="mt-2 text-muted fs-11 d-flex gap-2">
                        <span class="badge badge-subtle-primary">{{ strtoupper($stats['db_driver']) }}</span>
                        <span class="badge badge-subtle-dark">{{ ucfirst($stats['environment']) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Console Output Terminal -->
    <div class="card mb-3 shadow-sm border-0" style="background-color: #12131a;">
        <div class="card-header border-0 py-2 d-flex justify-content-between align-items-center" style="background-color: #181926;">
            <div class="d-flex align-items-center gap-2">
                <!-- Mac Terminal Dots -->
                <span class="d-inline-block rounded-circle bg-danger" style="width: 10px; height: 10px;"></span>
                <span class="d-inline-block rounded-circle bg-warning" style="width: 10px; height: 10px;"></span>
                <span class="d-inline-block rounded-circle bg-success" style="width: 10px; height: 10px;"></span>
                <span class="text-300 fs-11 fw-semi-bold font-monospace ms-2">
                    <span class="fas fa-terminal me-1 text-primary"></span> Live Artisan Console Output
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button wire:click="relinkStorage" wire:loading.attr="disabled" class="btn btn-link btn-sm text-400 p-0 text-decoration-none fs-11" title="Ensure storage symlink is active">
                    <span class="fas fa-link me-1"></span> storage:link
                </button>
                <span class="text-500">|</span>
                <button wire:click="clearConsoleLogs" class="btn btn-link btn-sm text-400 p-0 text-decoration-none fs-11">
                    <span class="fas fa-trash-alt me-1"></span> Clear Console
                </button>
            </div>
        </div>
        <div class="card-body p-3" style="max-height: 260px; overflow-y: auto;">
            <div class="font-monospace fs-11">
                @forelse($consoleLogs as $log)
                    <div class="mb-2 pb-2 border-bottom border-dark border-opacity-50">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-info">[{{ $log['timestamp'] }}]</span>
                            <span class="text-success fw-bold">$</span>
                            <span class="text-warning fw-semi-bold">{{ $log['command'] }}</span>
                        </div>
                        <pre class="text-300 mb-0 mt-1 ps-3" style="white-space: pre-wrap; font-family: inherit; font-size: inherit;">{{ $log['output'] }}</pre>
                    </div>
                @empty
                    <div class="text-500 font-italic py-3 text-center">
                        <span class="fas fa-info-circle me-1"></span> No console output yet. Click an action above to execute commands.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Migrations Directory Explorer -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light py-2">
            <div class="row align-items-center justify-content-between g-2">
                <div class="col-12 col-md-auto">
                    <h6 class="mb-0 text-900 fw-bold">
                        <span class="fas fa-list-ul me-2 text-primary"></span>Migrations Registry
                    </h6>
                </div>
                <div class="col-12 col-md-auto d-flex align-items-center gap-2 flex-wrap">
                    <!-- Status Filter Tabs -->
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" wire:click="$set('statusFilter', 'all')" class="btn {{ $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            All ({{ $stats['total'] }})
                        </button>
                        <button type="button" wire:click="$set('statusFilter', 'pending')" class="btn {{ $statusFilter === 'pending' ? 'btn-warning text-dark' : 'btn-outline-secondary' }}">
                            Pending ({{ $stats['pending'] }})
                        </button>
                        <button type="button" wire:click="$set('statusFilter', 'ran')" class="btn {{ $statusFilter === 'ran' ? 'btn-success' : 'btn-outline-secondary' }}">
                            Executed ({{ $stats['ran'] }})
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="position-relative" style="min-width: 220px;">
                        <input wire:model.live.debounce.300ms="search" class="form-control form-control-sm ps-4" type="search" placeholder="Search migrations..." />
                        <span class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-2 text-400 fs-11"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover mb-0 fs-11">
                <thead class="bg-200 text-800">
                    <tr>
                        <th class="py-2 px-3" style="width: 120px;">Status</th>
                        <th class="py-2">Readable Name</th>
                        <th class="py-2">File Name</th>
                        <th class="py-2 text-center" style="width: 100px;">Batch</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($migrations as $m)
                        <tr class="{{ !$m['is_ran'] ? 'table-warning' : '' }}">
                            <td class="py-2 px-3 align-middle">
                                @if($m['is_ran'])
                                    <span class="badge badge-subtle-success rounded-pill">
                                        <span class="fas fa-check-circle me-1"></span> Ran
                                    </span>
                                @else
                                    <span class="badge badge-subtle-warning rounded-pill">
                                        <span class="fas fa-clock me-1"></span> Pending
                                    </span>
                                @endif
                            </td>
                            <td class="py-2 align-middle fw-semi-bold text-900">
                                {{ $m['readable_name'] }}
                            </td>
                            <td class="py-2 align-middle font-monospace text-600">
                                {{ $m['file_name'] }}
                            </td>
                            <td class="py-2 align-middle text-center">
                                @if($m['is_ran'])
                                    <span class="badge bg-secondary">Batch #{{ $m['batch'] }}</span>
                                @else
                                    <span class="text-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <span class="fas fa-folder-open me-2 text-400 fs-8"></span> No migrations match the selected filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-light py-2 text-muted fs-11 d-flex justify-content-between align-items-center">
            <span>Showing <strong>{{ count($migrations) }}</strong> of {{ $stats['total'] }} migrations</span>
            <span class="text-500">Live migrations directory: <code>database/migrations</code></span>
        </div>
    </div>

    <!-- Confirmation Modal for Running Migrations -->
    @if($showConfirmModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header bg-primary text-white py-2">
                        <h6 class="modal-title mb-0">
                            <span class="fas fa-exclamation-triangle me-2"></span>Confirm Database Migration
                        </h6>
                        <button type="button" wire:click="cancelRunMigrations" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="d-flex gap-3">
                            <div class="avatar avatar-2xl bg-subtle-warning text-warning rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                                <span class="fas fa-database fs-7"></span>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Execute pending migrations on the live database?</h6>
                                <p class="text-600 fs-10 mb-2">
                                    This action runs <code>php artisan migrate --force</code> against database 
                                    <strong>{{ $stats['db_name'] }}</strong> in the <strong>{{ strtoupper($stats['environment']) }}</strong> environment.
                                </p>
                                <div class="alert alert-warning py-2 px-3 fs-11 mb-0 border-0">
                                    <strong>{{ $stats['pending'] }}</strong> pending migration(s) will be executed. Ensure you have an active backup before continuing.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" wire:click="cancelRunMigrations" class="btn btn-falcon-default btn-sm">Cancel</button>
                        <button type="button" wire:click="runMigrations" wire:loading.attr="disabled" class="btn btn-primary btn-sm shadow-sm">
                            <span wire:loading.remove wire:target="runMigrations" class="fas fa-play me-1"></span>
                            <span wire:loading wire:target="runMigrations" class="spinner-border spinner-border-sm me-1"></span>
                            Yes, Execute Migrations
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
