<div class="container-fluid px-0">
    <!-- Page Header & Tenant Selector -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <div class="row flex-between-center g-3">
                <div class="col-12 col-md-auto">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar avatar-l bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-magic fa-lg"></span>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-900 d-flex align-items-center gap-2">
                                Synthetic Data Studio
                                <span class="badge badge-subtle-primary rounded-pill fs-11">Super Admin Exclusive</span>
                            </h4>
                            <p class="text-600 fs-10 mb-0">Generate realistic, localized Pakistani test & demo data on demand using declarative model factories.</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-auto">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="input-group input-group-sm" style="min-width: 280px;">
                            <span class="input-group-text bg-light text-700 fw-semibold"><span class="fas fa-building me-1"></span> Tenant:</span>
                            <select wire:model.live="selectedMarqueeId" class="form-select form-select-sm fw-bold">
                                @foreach($marquees as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->city }}) [{{ $m->branches->count() }} Br]</option>
                                @endforeach
                            </select>
                        </div>

                        @if($selectedMarquee && $selectedMarquee->branches->count() > 1)
                        <div class="input-group input-group-sm" style="min-width: 220px;">
                            <span class="input-group-text bg-light text-700 fw-semibold"><span class="fas fa-code-branch me-1"></span> Branch:</span>
                            <select wire:model.live="selectedBranchId" class="form-select form-select-sm">
                                <option value="">All Branches (Distributed)</option>
                                @foreach($selectedMarquee->branches as $br)
                                    <option value="{{ $br->id }}">{{ $br->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <button class="btn btn-sm btn-outline-primary" wire:click="$set('showNewMarqueeModal', true)">
                            <span class="fas fa-plus me-1"></span> New Demo Marquee
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert / Feedback Notification -->
    @if($feedbackMessage)
        <div class="alert alert-{{ $feedbackType ?? 'info' }} alert-dismissible fade show d-flex align-items-center gap-2 mb-3 shadow-sm" role="alert">
            <span class="fas fa-info-circle fa-lg"></span>
            <div class="flex-1 fw-semibold fs-10">{{ $feedbackMessage }}</div>
            <button type="button" class="btn-close" wire:click="$set('feedbackMessage', null)" aria-label="Close"></button>
        </div>
    @endif

    <!-- Tenant Live Stats Snapshot -->
    @if($stats)
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3 col-xxl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 mb-1">Bookings</h6>
                            <h4 class="mb-0 fw-bolder text-primary">{{ $stats['bookings_count'] }}</h4>
                            <span class="fs-11 text-muted">{{ $stats['confirmed_bookings'] }} confirmed, {{ $stats['completed_bookings'] }} done</span>
                        </div>
                        <div class="icon-circle bg-primary-subtle text-primary">
                            <span class="fas fa-calendar-check fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xxl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 mb-1">Total Payments</h6>
                            <h4 class="mb-0 fw-bolder text-success">PKR {{ number_format($stats['total_payments_amount'] / 1000, 1) }}k</h4>
                            <span class="fs-11 text-muted">{{ $stats['payments_count'] }} receipts posted</span>
                        </div>
                        <div class="icon-circle bg-success-subtle text-success">
                            <span class="fas fa-money-bill-wave fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xxl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 mb-1">Customers</h6>
                            <h4 class="mb-0 fw-bolder text-info">{{ $stats['customers_count'] }}</h4>
                            <span class="fs-11 text-muted">CRM accounts</span>
                        </div>
                        <div class="icon-circle bg-info-subtle text-info">
                            <span class="fas fa-users fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xxl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 mb-1">Staff & Team</h6>
                            <h4 class="mb-0 fw-bolder text-warning">{{ $stats['staff_count'] }}</h4>
                            <span class="fs-11 text-muted">Kitchen & Service</span>
                        </div>
                        <div class="icon-circle bg-warning-subtle text-warning">
                            <span class="fas fa-user-tie fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xxl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 mb-1">Kitchen Inventory</h6>
                            <h4 class="mb-0 fw-bolder text-secondary">{{ $stats['inventory_items_count'] }}</h4>
                            <span class="fs-11 text-muted">{{ $stats['suppliers_count'] }} active suppliers</span>
                        </div>
                        <div class="icon-circle bg-secondary-subtle text-secondary">
                            <span class="fas fa-boxes fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xxl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-700 fs-11 mb-1">Operating Expenses</h6>
                            <h4 class="mb-0 fw-bolder text-danger">PKR {{ number_format($stats['total_expenses_amount'] / 1000, 1) }}k</h4>
                            <span class="fs-11 text-muted">{{ $stats['expenses_count'] }} voucher items</span>
                        </div>
                        <div class="icon-circle bg-danger-subtle text-danger">
                            <span class="fas fa-file-invoice-dollar fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row g-3">
        <!-- Preset Cards & Options -->
        <div class="col-12 col-xl-7">
            <!-- 1. Preset Selector Cards -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body-tertiary py-2">
                    <h6 class="mb-0 fw-bold text-800"><span class="fas fa-layer-group me-1 text-primary"></span> 1. Select Generation Preset</h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <!-- Quick Starter -->
                        <div class="col-12 col-md-4">
                            <div class="card h-100 border {{ $selectedPreset === 'quick' ? 'border-primary shadow-sm bg-primary-subtle' : 'border-200' }} cursor-pointer" wire:click="applyPreset('quick')">
                                <div class="card-body p-3 text-center">
                                    <div class="badge bg-primary rounded-pill mb-2">Starter Pack</div>
                                    <h6 class="fw-bold mb-1">⚡ Quick Launch</h6>
                                    <p class="fs-11 text-600 mb-2">Ideal for quick visual testing & UI sanity checks.</p>
                                    <ul class="list-unstyled fs-11 text-start mb-0 text-700">
                                        <li><span class="fas fa-check text-success me-1"></span> 10 Bookings + Payments</li>
                                        <li><span class="fas fa-check text-success me-1"></span> 10 Customers</li>
                                        <li><span class="fas fa-check text-success me-1"></span> 5 Staff & Attendance</li>
                                        <li><span class="fas fa-check text-success me-1"></span> 8 Inventory Items</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Full Showcase -->
                        <div class="col-12 col-md-4">
                            <div class="card h-100 border {{ $selectedPreset === 'full' ? 'border-primary shadow-sm bg-primary-subtle' : 'border-200' }} cursor-pointer" wire:click="applyPreset('full')">
                                <div class="card-body p-3 text-center">
                                    <div class="badge bg-success rounded-pill mb-2">Recommended</div>
                                    <h6 class="fw-bold mb-1">🌟 Full Showcase</h6>
                                    <p class="fs-11 text-600 mb-2">Complete banquet lifecycle with full double-entry accounting.</p>
                                    <ul class="list-unstyled fs-11 text-start mb-0 text-700">
                                        <li><span class="fas fa-check text-success me-1"></span> 25 Bookings (All stages)</li>
                                        <li><span class="fas fa-check text-success me-1"></span> 25 Pakistani Customers + CRM</li>
                                        <li><span class="fas fa-check text-success me-1"></span> 12 Staff + 10-day logs</li>
                                        <li><span class="fas fa-check text-success me-1"></span> 20 Inventory + 5 Vendors</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Stress Test -->
                        <div class="col-12 col-md-4">
                            <div class="card h-100 border {{ $selectedPreset === 'stress' ? 'border-primary shadow-sm bg-primary-subtle' : 'border-200' }} cursor-pointer" wire:click="applyPreset('stress')">
                                <div class="card-body p-3 text-center">
                                    <div class="badge bg-danger rounded-pill mb-2">High Volume</div>
                                    <h6 class="fw-bold mb-1">💥 Stress Test</h6>
                                    <p class="fs-11 text-600 mb-2">High load testing for reporting, filters, and multi-branch ledgers.</p>
                                    <ul class="list-unstyled fs-11 text-start mb-0 text-700">
                                        <li><span class="fas fa-check text-success me-1"></span> 60 Bookings + JVs</li>
                                        <li><span class="fas fa-check text-success me-1"></span> 60 Customers + CRM</li>
                                        <li><span class="fas fa-check text-success me-1"></span> 25 Staff + Attendance</li>
                                        <li><span class="fas fa-check text-success me-1"></span> +1 Branch & 40 Inventory</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Granular Customization Panel -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body-tertiary py-2 d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold text-800"><span class="fas fa-sliders-h me-1 text-primary"></span> 2. Fine-Tune Quantities & Toggles</h6>
                    <button class="btn btn-link btn-sm text-primary p-0 text-decoration-none" wire:click="$set('selectedPreset', 'custom')">Switch to Custom</button>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <!-- Bookings -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fs-10 fw-bold text-700 d-flex justify-content-between mb-1">
                                <span><span class="fas fa-calendar-alt text-primary me-1"></span> Bookings Count</span>
                                <span class="badge bg-primary-subtle text-primary">{{ $bookingCount }}</span>
                            </label>
                            <input type="range" class="form-range" min="0" max="100" step="5" wire:model.live="bookingCount">
                            <div class="d-flex gap-2 flex-wrap mt-1">
                                <div class="form-check form-check-inline fs-11">
                                    <input class="form-check-input" type="checkbox" id="advanceCheck" wire:model="includeAdvancePayments">
                                    <label class="form-check-label" for="advanceCheck">Advance Deposits</label>
                                </div>
                                <div class="form-check form-check-inline fs-11">
                                    <input class="form-check-input" type="checkbox" id="revenueCheck" wire:model="includeRevenueRecognition">
                                    <label class="form-check-label" for="revenueCheck">Revenue Recognition</label>
                                </div>
                            </div>
                        </div>

                        <!-- Customers -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fs-10 fw-bold text-700 d-flex justify-content-between mb-1">
                                <span><span class="fas fa-users text-info me-1"></span> Customers Count</span>
                                <span class="badge bg-info-subtle text-info">{{ $customerCount }}</span>
                            </label>
                            <input type="range" class="form-range" min="0" max="100" step="5" wire:model.live="customerCount">
                            <div class="form-check fs-11 mt-1">
                                <input class="form-check-input" type="checkbox" id="crmCheck" wire:model="includeCommunicationLogs">
                                <label class="form-check-label" for="crmCheck">Include CRM Follow-up & Communication Logs</label>
                            </div>
                        </div>

                        <!-- Staff & Attendance -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fs-10 fw-bold text-700 d-flex justify-content-between mb-1">
                                <span><span class="fas fa-user-tie text-warning me-1"></span> Staff & Attendance</span>
                                <span class="badge bg-warning-subtle text-warning">{{ $staffCount }} Staff / {{ $attendanceDays }} Days</span>
                            </label>
                            <input type="range" class="form-range" min="0" max="40" step="2" wire:model.live="staffCount">
                        </div>

                        <!-- Operating Expenses -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fs-10 fw-bold text-700 d-flex justify-content-between mb-1">
                                <span><span class="fas fa-file-invoice-dollar text-danger me-1"></span> Operating Expenses</span>
                                <span class="badge bg-danger-subtle text-danger">{{ $expenseCount }} Vouchers</span>
                            </label>
                            <input type="range" class="form-range" min="0" max="50" step="5" wire:model.live="expenseCount">
                        </div>

                        <!-- Inventory & Suppliers -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fs-10 fw-bold text-700 d-flex justify-content-between mb-1">
                                <span><span class="fas fa-boxes text-secondary me-1"></span> Suppliers & Inventory</span>
                                <span class="badge bg-secondary-subtle text-secondary">{{ $supplierCount }} Sup / {{ $inventoryCount }} Items</span>
                            </label>
                            <div class="d-flex gap-2">
                                <input type="number" class="form-control form-control-sm" placeholder="Suppliers" wire:model="supplierCount" min="0" max="30">
                                <input type="number" class="form-control form-control-sm" placeholder="Items" wire:model="inventoryCount" min="0" max="60">
                            </div>
                        </div>

                        <!-- Vendor Partners -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fs-10 fw-bold text-700 d-flex justify-content-between mb-1">
                                <span><span class="fas fa-handshake text-success me-1"></span> Partner Vendors</span>
                                <span class="badge bg-success-subtle text-success">{{ $vendorCount }} Vendors</span>
                            </label>
                            <input type="number" class="form-control form-control-sm" placeholder="Vendor Partners" wire:model="vendorCount" min="0" max="25">
                        </div>

                        <!-- Generate Extra Branches -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fs-10 fw-bold text-700 d-flex justify-content-between mb-1">
                                <span><span class="fas fa-code-branch text-primary me-1"></span> Additional Branches to Create</span>
                                <span class="badge bg-primary-subtle text-primary">+{{ $newBranchesCount }} Branches</span>
                            </label>
                            <input type="range" class="form-range" min="0" max="5" step="1" wire:model.live="newBranchesCount">
                            <span class="fs-11 text-muted">Creates branches with halls under this marquee.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <button class="btn btn-outline-danger btn-sm" wire:click="confirmPurge">
                        <span class="fas fa-trash-alt me-1"></span> Reset / Purge Demo Data
                    </button>
                    <button class="btn btn-primary px-4 fw-bold shadow-sm" wire:click="runGenerator" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="runGenerator">
                            <span class="fas fa-play me-1"></span> Run Synthetic Generator Now
                        </span>
                        <span wire:loading wire:target="runGenerator">
                            <span class="spinner-border spinner-border-sm me-1" role="status"></span> Generating Factory Data...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Terminal Console Execution Log & Summary -->
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100 bg-dark text-light" style="min-height: 520px; font-family: 'Consolas', 'Courier New', monospace;">
                <div class="card-header bg-200 py-2 d-flex align-items-center justify-content-between border-bottom border-700" style="background-color: #1e293b !important;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success-subtle text-success rounded-pill" style="font-size: 9px;">● LIVE</span>
                        <h6 class="mb-0 fw-bold text-300 fs-11"><span class="fas fa-terminal me-1 text-primary"></span> Execution Log Console</h6>
                    </div>
                    <button class="btn btn-link btn-sm text-400 p-0 text-decoration-none fs-11" wire:click="$set('executionLogs', [])">Clear</button>
                </div>
                <div class="card-body p-3 overflow-auto fs-11" style="max-height: 480px; color: #a5f3fc; background-color: #0f172a;">
                    @if(empty($executionLogs))
                        <div class="text-500 py-4 text-center">
                            <span class="fas fa-terminal fa-2x mb-2 d-block text-600"></span>
                            Ready to synthesize data. Select a preset or customize quantities and click <strong>"Run Synthetic Generator Now"</strong>.
                        </div>
                    @else
                        @foreach($executionLogs as $log)
                            <div class="py-1 line-height-sm">{{ $log }}</div>
                        @endforeach
                    @endif

                    @if($lastExecutionSummary)
                        <div class="mt-3 p-2 rounded border border-success-subtle bg-success-subtle text-success-emphasis">
                            <div class="fw-bold mb-1"><span class="fas fa-check-circle me-1"></span> Generation Summary:</div>
                            <div class="d-flex flex-wrap gap-2 fs-11">
                                @if(isset($lastExecutionSummary['branches'])) <span class="badge bg-primary">🏢 {{ $lastExecutionSummary['branches'] }} Branches</span> @endif
                                @if(isset($lastExecutionSummary['customers'])) <span class="badge bg-info">👥 {{ $lastExecutionSummary['customers'] }} Customers</span> @endif
                                @if(isset($lastExecutionSummary['bookings'])) <span class="badge bg-primary">📅 {{ $lastExecutionSummary['bookings'] }} Bookings</span> @endif
                                @if(isset($lastExecutionSummary['payments'])) <span class="badge bg-success">💰 {{ $lastExecutionSummary['payments'] }} Payments</span> @endif
                                @if(isset($lastExecutionSummary['staff'])) <span class="badge bg-warning text-dark">👨‍🍳 {{ $lastExecutionSummary['staff'] }} Staff</span> @endif
                                @if(isset($lastExecutionSummary['inventory_items'])) <span class="badge bg-secondary">📦 {{ $lastExecutionSummary['inventory_items'] }} Inventory</span> @endif
                                @if(isset($lastExecutionSummary['duration'])) <span class="badge bg-dark">⏱️ {{ $lastExecutionSummary['duration'] }}</span> @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Purge Confirmation Modal with 3 Scopes -->
    @if($showPurgeModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6);" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white py-2">
                    <h6 class="modal-title fw-bold text-white"><span class="fas fa-exclamation-triangle me-1"></span> Purge / Delete Demo Data</h6>
                    <button type="button" class="btn-close btn-close-white" wire:click="$set('showPurgeModal', false)"></button>
                </div>
                <div class="modal-body p-4">
                    <label class="form-label fs-11 fw-bold text-800 mb-2">Select Purge Action Scope:</label>

                    <div class="list-group mb-3">
                        <label class="list-group-item list-group-item-action d-flex gap-3 py-3 {{ $purgeScope === 'data_only' ? 'active bg-primary-subtle border-primary text-primary-emphasis' : '' }}">
                            <input class="form-check-input flex-shrink-0" type="radio" name="purgeScope" value="data_only" wire:model.live="purgeScope">
                            <div>
                                <h6 class="mb-0 fw-bold fs-11">🧹 Clean Transactions & Records Only</h6>
                                <p class="mb-0 fs-11 text-muted">Resets bookings, payments, staff, CRM logs, and expenses to 0. <strong>Keeps the marquee and its branches intact.</strong></p>
                            </div>
                        </label>

                        @if($selectedMarqueeId && $selectedMarqueeId !== 1)
                        <label class="list-group-item list-group-item-action d-flex gap-3 py-3 {{ $purgeScope === 'delete_marquee' ? 'active bg-danger-subtle border-danger text-danger-emphasis' : '' }}">
                            <input class="form-check-input flex-shrink-0" type="radio" name="purgeScope" value="delete_marquee" wire:model.live="purgeScope">
                            <div>
                                <h6 class="mb-0 fw-bold fs-11 text-danger">🗑️ Delete This Demo Marquee Entirely</h6>
                                <p class="mb-0 fs-11 text-muted">Deletes <strong>{{ $selectedMarquee?->name }}</strong> along with all its branches, halls, and synthetic records.</p>
                            </div>
                        </label>
                        @endif

                        @if($marquees->where('id', '!=', 1)->count() > 0)
                        <label class="list-group-item list-group-item-action d-flex gap-3 py-3 {{ $purgeScope === 'purge_all_demo_marquees' ? 'active bg-danger-subtle border-danger text-danger-emphasis' : '' }}">
                            <input class="form-check-input flex-shrink-0" type="radio" name="purgeScope" value="purge_all_demo_marquees" wire:model.live="purgeScope">
                            <div>
                                <h6 class="mb-0 fw-bold fs-11 text-danger">🚨 Purge ALL Demo Marquees</h6>
                                <p class="mb-0 fs-11 text-muted">Deletes all {{ $marquees->where('id', '!=', 1)->count() }} synthetic demo marquees and resets back to primary default tenant.</p>
                            </div>
                        </label>
                        @endif
                    </div>

                    @if($purgeScope === 'data_only')
                    <div class="form-check fs-11">
                        <input class="form-check-input" type="checkbox" id="keepMaster" wire:model="keepMasterCatalogs">
                        <label class="form-check-label text-700" for="keepMaster">
                            Keep Master Item & Service Catalogs (Don't delete base inventory/service definitions)
                        </label>
                    </div>
                    @endif
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showPurgeModal', false)">Cancel</button>
                    <button type="button" class="btn btn-danger btn-sm fw-bold" wire:click="purgeData">
                        <span class="fas fa-trash-alt me-1"></span> Confirm & Execute Purge
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Create New Demo Marquee Modal -->
    @if($showNewMarqueeModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6);" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title fw-bold text-white"><span class="fas fa-building me-1"></span> Create New Demo Tenant Marquee</h6>
                    <button type="button" class="btn-close btn-close-white" wire:click="$set('showNewMarqueeModal', false)"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label class="form-label fs-11 fw-bold">Marquee / Banquet Name</label>
                        <input type="text" class="form-control form-control-sm" placeholder="e.g. Royal Palm Grand Marquee" wire:model="newMarqueeName">
                        @error('newMarqueeName') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label fs-11 fw-bold">City</label>
                            <select class="form-select form-select-sm" wire:model="newMarqueeCity">
                                <option value="Lahore">Lahore</option>
                                <option value="Karachi">Karachi</option>
                                <option value="Islamabad">Islamabad</option>
                                <option value="Faisalabad">Faisalabad</option>
                                <option value="Multan">Multan</option>
                                <option value="Rawalpindi">Rawalpindi</option>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label fs-11 fw-bold">Branches Count</label>
                            <input type="number" class="form-control form-control-sm" wire:model="newMarqueeBranchesCount" min="1" max="5">
                            @error('newMarqueeBranchesCount') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showNewMarqueeModal', false)">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm fw-bold" wire:click="createNewDemoMarquee">
                        <span class="fas fa-check me-1"></span> Create & Initialize
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
