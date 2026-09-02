<div>
    <!-- Top Header Bar -->
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
            <div>
                <h5 class="mb-0 text-primary fw-bold">
                    <span class="fas fa-calendar-alt me-2"></span>Booking Management Operational Dashboard
                </h5>
                <div class="text-muted fs-11 mt-1">
                    Manage bookings, guest confirmations, event schedules, and financial balance tracking.
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button wire:click="$refresh" class="btn btn-falcon-default btn-sm" type="button" data-bs-toggle="tooltip" title="Refresh Dashboard">
                    <span class="fas fa-sync-alt me-1"></span>Refresh
                </button>
                <button wire:click="exportExcel" class="btn btn-falcon-default btn-sm text-nowrap" type="button">
                    <span class="fas fa-file-excel me-1 text-success"></span>Export CSV
                </button>
                <a href="{{ route('bookings.report', [
                    'search' => $search,
                    'filterStatus' => $filterStatus,
                    'filterPaymentStatus' => $filterPaymentStatus,
                    'filterHall' => $filterHall,
                    'filterDateStart' => $filterDateStart,
                    'filterDateEnd' => $filterDateEnd
                ]) }}" target="_blank" class="btn btn-falcon-default btn-sm text-nowrap">
                    <span class="fas fa-print me-1 text-primary"></span>Print / PDF
                </a>
                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('create_bookings'))
                    <a class="btn btn-falcon-primary btn-sm text-nowrap" href="{{ route('bookings.create') }}">
                        <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add Booking
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Interactive Operational Metrics Cards (Grid of 8 Real DB Metrics) -->
    <div class="row g-2 mb-3">
        <!-- 1. Total Bookings -->
        <div class="col-6 col-sm-4 col-md-3 col-lg-3 col-xl">
            <div class="card card-span h-100 cursor-pointer border {{ $filterQuickShortcut === 'all' || empty($filterQuickShortcut) ? 'border-primary shadow-sm bg-primary-subtle' : 'border-200' }}" wire:click="applyShortcutFilter('all')">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-500 fw-bold text-uppercase fs-12">Total Bookings</div>
                        <h4 class="mb-0 font-monospace text-primary fw-bold">{{ number_format($totalBookingsCount) }}</h4>
                    </div>
                    <div class="icon-item bg-primary-subtle text-primary rounded-3"><span class="fas fa-calendar-alt fs-9"></span></div>
                </div>
            </div>
        </div>

        <!-- 2. Confirmed Bookings -->
        <div class="col-6 col-sm-4 col-md-3 col-lg-3 col-xl">
            <div class="card card-span h-100 cursor-pointer border {{ $filterStatus === 'Confirmed' || $filterQuickShortcut === 'confirmed' ? 'border-success shadow-sm bg-success-subtle' : 'border-200' }}" wire:click="applyShortcutFilter('confirmed')">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-500 fw-bold text-uppercase fs-12">Confirmed</div>
                        <h4 class="mb-0 font-monospace text-success fw-bold">{{ number_format($confirmedBookingsCount) }}</h4>
                    </div>
                    <div class="icon-item bg-success-subtle text-success rounded-3"><span class="fas fa-check-circle fs-9"></span></div>
                </div>
            </div>
        </div>

        <!-- 3. Tentative Bookings -->
        <div class="col-6 col-sm-4 col-md-3 col-lg-3 col-xl">
            <div class="card card-span h-100 cursor-pointer border {{ $filterQuickShortcut === 'tentative' || $filterStatus === 'Reserved' ? 'border-warning shadow-sm bg-warning-subtle' : 'border-200' }}" wire:click="applyShortcutFilter('tentative')">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-500 fw-bold text-uppercase fs-12">Tentative</div>
                        <h4 class="mb-0 font-monospace text-warning fw-bold">{{ number_format($tentativeBookingsCount) }}</h4>
                    </div>
                    <div class="icon-item bg-warning-subtle text-warning rounded-3"><span class="fas fa-clock fs-9"></span></div>
                </div>
            </div>
        </div>

        <!-- 4. Today's Events -->
        <div class="col-6 col-sm-4 col-md-3 col-lg-3 col-xl">
            <div class="card card-span h-100 cursor-pointer border {{ $filterQuickShortcut === 'today' ? 'border-danger shadow-sm bg-danger-subtle' : ($todaysEventsCount > 0 ? 'border-danger-subtle' : 'border-200') }}" wire:click="applyShortcutFilter('today')">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-500 fw-bold text-uppercase fs-12">Today's Events</div>
                        <div class="d-flex align-items-center gap-1">
                            <h4 class="mb-0 font-monospace text-danger fw-bold">{{ number_format($todaysEventsCount) }}</h4>
                            @if($todaysEventsCount > 0)
                                <span class="badge bg-danger rounded-pill fs-12 ms-1">Today</span>
                            @endif
                        </div>
                    </div>
                    <div class="icon-item bg-danger-subtle text-danger rounded-3"><span class="fas fa-glass-cheers fs-9"></span></div>
                </div>
            </div>
        </div>

        <!-- 5. Upcoming Events -->
        <div class="col-6 col-sm-4 col-md-3 col-lg-3 col-xl">
            <div class="card card-span h-100 cursor-pointer border {{ $filterQuickShortcut === 'upcoming' ? 'border-info shadow-sm bg-info-subtle' : 'border-200' }}" wire:click="applyShortcutFilter('upcoming')">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-500 fw-bold text-uppercase fs-12">Upcoming Events</div>
                        <h4 class="mb-0 font-monospace text-info fw-bold">{{ number_format($upcomingEventsCount) }}</h4>
                    </div>
                    <div class="icon-item bg-info-subtle text-info rounded-3"><span class="fas fa-calendar-day fs-9"></span></div>
                </div>
            </div>
        </div>

        <!-- 6. This Month -->
        <div class="col-6 col-sm-4 col-md-3 col-lg-3 col-xl">
            <div class="card card-span h-100 cursor-pointer border {{ $filterQuickShortcut === 'this_month' ? 'border-primary shadow-sm bg-primary-subtle' : 'border-200' }}" wire:click="applyShortcutFilter('this_month')">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-500 fw-bold text-uppercase fs-12">This Month</div>
                        <h4 class="mb-0 font-monospace text-secondary fw-bold">{{ number_format($thisMonthCount) }}</h4>
                    </div>
                    <div class="icon-item bg-secondary-subtle text-secondary rounded-3"><span class="fas fa-calendar-week fs-9"></span></div>
                </div>
            </div>
        </div>

        <!-- 7. Pending Approvals -->
        <div class="col-6 col-sm-4 col-md-3 col-lg-3 col-xl">
            <div class="card card-span h-100 cursor-pointer border {{ $filterQuickShortcut === 'pending' || $filterStatus === 'Draft' ? 'border-secondary shadow-sm bg-secondary-subtle' : 'border-200' }}" wire:click="applyShortcutFilter('pending')">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-500 fw-bold text-uppercase fs-12">Pending Approval</div>
                        <h4 class="mb-0 font-monospace text-dark fw-bold">{{ number_format($pendingApprovalsCount) }}</h4>
                    </div>
                    <div class="icon-item bg-secondary-subtle text-dark rounded-3"><span class="fas fa-user-clock fs-9"></span></div>
                </div>
            </div>
        </div>

        <!-- 8. Payment Outstanding -->
        <div class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-3">
            <div class="card card-span h-100 cursor-pointer border {{ $filterQuickShortcut === 'outstanding' || $filterBalanceStatus === 'outstanding' ? 'border-danger shadow-sm bg-danger-subtle' : 'border-200' }}" wire:click="applyShortcutFilter('outstanding')">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-500 fw-bold text-uppercase fs-12">Payment Outstanding</div>
                        <div class="d-flex align-items-baseline gap-2">
                            <h4 class="mb-0 font-monospace text-danger fw-bold">{{ number_format($outstandingPaymentsCount) }}</h4>
                            <span class="text-muted fs-12 font-monospace">Rs. {{ number_format($outstandingAmountSum, 0) }}</span>
                        </div>
                    </div>
                    <div class="icon-item bg-danger-subtle text-danger rounded-3"><span class="fas fa-exclamation-triangle fs-9"></span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Shortcut Navigation Pills & Filter Toolbar -->
    <div class="card mb-3">
        <div class="card-body bg-light border-top border-bottom py-2">
            <!-- Shortcut Pills Row -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div class="nav nav-pills flex-wrap gap-1 fs-11">
                    <button wire:click="applyShortcutFilter('all')" class="nav-link px-2 py-1 {{ empty($filterQuickShortcut) ? 'active' : '' }}" type="button">All Bookings</button>
                    <button wire:click="applyShortcutFilter('today')" class="nav-link px-2 py-1 {{ $filterQuickShortcut === 'today' ? 'active bg-danger text-white' : '' }}" type="button">Today's Events</button>
                    <button wire:click="applyShortcutFilter('upcoming')" class="nav-link px-2 py-1 {{ $filterQuickShortcut === 'upcoming' ? 'active' : '' }}" type="button">Upcoming Events</button>
                    <button wire:click="applyShortcutFilter('next_7_days')" class="nav-link px-2 py-1 {{ $filterQuickShortcut === 'next_7_days' ? 'active' : '' }}" type="button">Next 7 Days</button>
                    <button wire:click="applyShortcutFilter('this_month')" class="nav-link px-2 py-1 {{ $filterQuickShortcut === 'this_month' ? 'active' : '' }}" type="button">This Month</button>
                    <button wire:click="applyShortcutFilter('pending')" class="nav-link px-2 py-1 {{ $filterQuickShortcut === 'pending' ? 'active bg-secondary text-white' : '' }}" type="button">Pending Approvals</button>
                    <button wire:click="applyShortcutFilter('outstanding')" class="nav-link px-2 py-1 {{ $filterQuickShortcut === 'outstanding' ? 'active bg-warning text-dark' : '' }}" type="button">Outstanding Balance</button>
                </div>
                <div>
                    <button wire:click="toggleAdvancedFilters" class="btn btn-falcon-default btn-xs" type="button">
                        <span class="fas fa-sliders-h me-1"></span>{{ $showAdvancedFilters ? 'Hide Advanced Filters' : 'Advanced Filters' }}
                    </button>
                </div>
            </div>

            <!-- Primary Filters Row -->
            <div class="row g-2">
                <!-- Search Box -->
                <div class="col-lg-3 col-md-4 col-12">
                    <div class="input-group input-group-sm">
                        <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search Booking #, Customer, Phone..." />
                        <span class="input-group-text"><span class="fas fa-search"></span></span>
                    </div>
                </div>

                <!-- Hall Filter -->
                <div class="col-lg-2 col-md-4 col-6">
                    <select wire:model.live="filterHall" class="form-select form-select-sm">
                        <option value="">All Halls</option>
                        @foreach($halls as $hall)
                            <option value="{{ $hall->id }}">{{ $hall->hall_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Booking Status Filter -->
                <div class="col-lg-2 col-md-4 col-6">
                    <select wire:model.live="filterStatus" class="form-select form-select-sm">
                        <option value="">All Booking Statuses</option>
                        <option value="Draft">Draft (Pending Approval)</option>
                        <option value="Reserved">Reserved (Tentative)</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>

                <!-- Payment Status Filter -->
                <div class="col-lg-2 col-md-4 col-6">
                    <select wire:model.live="filterPaymentStatus" class="form-select form-select-sm">
                        <option value="">All Payment Statuses</option>
                        <option value="Unpaid">Unpaid</option>
                        <option value="Partially Paid">Partially Paid</option>
                        <option value="Paid">Paid</option>
                        <option value="Refunded">Refunded</option>
                    </select>
                </div>

                <!-- Start Date Filter -->
                <div class="col-lg-1.5 col-md-4 col-6">
                    <input wire:model.live="filterDateStart" type="date" class="form-control form-control-sm font-monospace" placeholder="From Date" title="From Date" />
                </div>

                <!-- End Date Filter -->
                <div class="col-lg-1.5 col-md-4 col-6">
                    <input wire:model.live="filterDateEnd" type="date" class="form-control form-control-sm font-monospace" placeholder="To Date" title="To Date" />
                </div>
            </div>

            <!-- Advanced Collapsible Filter Drawer -->
            @if($showAdvancedFilters)
                <div class="row g-2 mt-2 border-top pt-2">
                    <!-- Branch Filter -->
                    <div class="col-md-3 col-6">
                        <label class="form-label fs-11 text-700 mb-1">Branch</label>
                        <select wire:model.live="filterBranch" class="form-select form-select-sm">
                            <option value="">All Branches</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Event Type Filter -->
                    <div class="col-md-3 col-6">
                        <label class="form-label fs-11 text-700 mb-1">Event Type</label>
                        <select wire:model.live="filterEventType" class="form-select form-select-sm">
                            <option value="">All Event Types</option>
                            @foreach($eventTypes as $et)
                                <option value="{{ $et->id }}">{{ $et->event_type_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Guest Confirmation Filter -->
                    <div class="col-md-2 col-6">
                        <label class="form-label fs-11 text-700 mb-1">Guest Status</label>
                        <select wire:model.live="filterGuestStatus" class="form-select form-select-sm">
                            <option value="">All Guest Statuses</option>
                            <option value="Tentative">Tentative Headcount</option>
                            <option value="Confirmed">Confirmed Headcount</option>
                        </select>
                    </div>

                    <!-- Balance Status Filter -->
                    <div class="col-md-2 col-6">
                        <label class="form-label fs-11 text-700 mb-1">Balance Status</label>
                        <select wire:model.live="filterBalanceStatus" class="form-select form-select-sm">
                            <option value="">All Balances</option>
                            <option value="outstanding">Outstanding Balance (> 0)</option>
                            <option value="fully_paid">Fully Settled (0)</option>
                        </select>
                    </div>

                    <!-- Created By Filter -->
                    <div class="col-md-2 col-6">
                        <label class="form-label fs-11 text-700 mb-1">Created By</label>
                        <select wire:model.live="filterCreatedBy" class="form-select form-select-sm">
                            <option value="">All Operators</option>
                            @foreach($operators as $op)
                                <option value="{{ $op->id }}">{{ $op->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <button wire:click="resetFilters" class="btn btn-falcon-default btn-xs" type="button">
                            <span class="fas fa-undo me-1"></span>Reset All Filters
                        </button>
                    </div>
                </div>
            @endif
        </div>

        <!-- Main Bookings Table -->
        <div class="card-body p-0">
            @if(session('success'))
                <div class="alert alert-success border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-grow-1 text-success-800">{{ session('success') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-grow-1 text-danger-800">{{ session('error') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Loading Spinner Indicator -->
            <div wire:loading.flex class="justify-content-center align-items-center py-4">
                <div class="spinner-border text-primary me-2" role="status"></div>
                <span class="text-muted fs-11 fw-bold">Updating booking records...</span>
            </div>

            <div wire:loading.remove class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle table-hover">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3">Booking #</th>
                            <th>Customer Profile</th>
                            <th>Venue / Branch & Hall</th>
                            <th>Event Details</th>
                            <th>Headcount & Confirmation</th>
                            <th>Booking Status</th>
                            <th>Payment & Balance</th>
                            <th class="text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            @php
                                $received = $booking->paid_amount ?? 0.00;
                                $balance = max(0.00, $booking->grand_total - $received);
                                $isToday = $booking->booking_date->isToday();
                            @endphp
                            <tr class="{{ $isToday ? 'table-warning' : '' }}">
                                <!-- Booking # -->
                                <td class="px-3">
                                    <a href="{{ route('bookings.show', $booking->id) }}" class="badge badge-subtle-primary fs-11 font-monospace text-decoration-none">
                                        {{ $booking->booking_number }}
                                    </a>
                                    @if($isToday)
                                        <span class="d-block badge bg-danger text-white fs-12 mt-1">TODAY</span>
                                    @endif
                                </td>

                                <!-- Customer Profile -->
                                <td class="fw-semi-bold">
                                    @if($booking->customer)
                                        <a href="{{ route('customers.show', $booking->customer->id) }}" class="text-900 fw-bold">{{ $booking->customer->full_name }}</a>
                                        <div class="text-muted fs-11"><span class="fas fa-phone me-1"></span>{{ $booking->customer->phone_number }}</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <!-- Venue / Branch & Hall -->
                                <td>
                                    <div class="fw-bold text-800">
                                        @if($booking->halls->isNotEmpty())
                                            {{ $booking->halls->pluck('hall_name')->implode(', ') }}
                                        @else
                                            {{ $booking->hall->hall_name ?? '—' }}
                                        @endif
                                    </div>
                                    <div class="text-muted fs-12">
                                        <span class="fas fa-building me-1"></span>{{ $booking->hall->branch->name ?? 'Main Branch' }}
                                    </div>
                                </td>

                                <!-- Event Details -->
                                <td>
                                    <div class="fw-bold text-primary">{{ $booking->eventType->event_type_name ?? '—' }}</div>
                                    <div><span class="fas fa-calendar-alt me-1 text-500"></span>{{ $booking->booking_date->format('M d, Y') }}</div>
                                    @if($booking->slot)
                                        <div class="mt-1">
                                            <span class="badge badge-subtle-info rounded-pill fs-12">
                                                <span class="fas fa-clock me-1"></span>{{ $booking->slot->slot_name }}
                                            </span>
                                        </div>
                                    @endif
                                    <div class="text-muted fs-11 font-monospace mt-1">
                                        {{ $booking->start_time->format('h:i A') }} - {{ $booking->end_time->format('h:i A') }}
                                    </div>
                                </td>

                                <!-- Headcount & Confirmation -->
                                <td>
                                    <div class="fw-bold font-monospace fs-10 text-800">
                                        {{ number_format($booking->effective_guest_count) }} Guests
                                    </div>
                                    <div class="mt-1">
                                        @if($booking->is_guest_confirmed)
                                            <span class="badge bg-success-subtle text-success fs-12"><span class="fas fa-check-circle me-1"></span>Confirmed</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning fs-12"><span class="fas fa-clock me-1"></span>Tentative</span>
                                        @endif
                                    </div>
                                    <div class="text-muted fs-12 mt-1">
                                        Est: {{ number_format($booking->tentative_guests ?? $booking->guest_count) }}
                                        @if($booking->confirmed_guests)
                                            | Conf: {{ number_format($booking->confirmed_guests) }}
                                        @endif
                                    </div>
                                </td>

                                <!-- Booking Status -->
                                <td>
                                    @php
                                        $statusColors = [
                                            'Draft' => 'secondary',
                                            'Reserved' => 'warning',
                                            'Confirmed' => 'success',
                                            'Completed' => 'info',
                                            'Cancelled' => 'danger',
                                            'Rejected' => 'dark'
                                        ];
                                        $sc = $statusColors[$booking->booking_status] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-subtle-{{ $sc }} rounded-pill px-2 py-1 fs-11">
                                        {{ $booking->booking_status }}
                                    </span>
                                    @if($booking->trashed())
                                        <span class="badge bg-danger rounded-pill px-2 py-1 fs-11 ms-1" data-bs-toggle="tooltip" title="Soft Deleted">Deleted</span>
                                    @endif

                                    <!-- Quick Pending Action for Drafts -->
                                    @if($booking->booking_status === 'Draft' && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_bookings') || (auth()->user()->role && in_array(auth()->user()->role->name, ['owner', 'super_admin']))))
                                        <div class="mt-1 d-flex gap-1">
                                            <button wire:click="approveBooking({{ $booking->id }})" class="btn btn-falcon-success btn-xs" type="button" data-bs-toggle="tooltip" title="Approve Booking">
                                                <span class="fas fa-check"></span> Approve
                                            </button>
                                            <button wire:click="rejectBooking({{ $booking->id }})" class="btn btn-falcon-danger btn-xs" type="button" data-bs-toggle="tooltip" title="Reject Booking">
                                                <span class="fas fa-times"></span>
                                            </button>
                                        </div>
                                    @endif
                                </td>

                                <!-- Payment & Balance -->
                                <td>
                                    <div class="font-monospace fw-bold text-800">Rs. {{ number_format($booking->grand_total, 0) }}</div>
                                    <div class="fs-11 font-monospace text-success">Paid: Rs. {{ number_format($received, 0) }}</div>
                                    <div class="fs-11 font-monospace fw-bold text-{{ $balance > 0 ? 'danger' : 'success' }}">
                                        Bal: Rs. {{ number_format($balance, 0) }}
                                    </div>
                                    <div class="mt-1 d-flex flex-wrap gap-1 align-items-center">
                                        @php
                                            $paymentColors = [
                                                'Unpaid' => 'danger',
                                                'Partially Paid' => 'warning',
                                                'Paid' => 'success',
                                                'Refunded' => 'secondary'
                                            ];
                                            $pc = $paymentColors[$booking->payment_status] ?? 'secondary';
                                            
                                            $finColors = [
                                                'Pending' => 'secondary',
                                                'Partially Paid' => 'warning',
                                                'Fully Paid' => 'info',
                                                'Settled' => 'success',
                                                'Refunded' => 'dark',
                                                'Cancelled' => 'danger'
                                            ];
                                            $fc = $finColors[$booking->financial_status] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-subtle-{{ $pc }} fs-12">{{ $booking->payment_status }}</span>
                                        @if($booking->financial_status && $booking->financial_status !== 'Pending')
                                            <span class="badge badge-subtle-{{ $fc }} fs-12">{{ $booking->financial_status }}</span>
                                        @endif
                                    </div>

                                    @if(!$booking->trashed() && $balance > 0)
                                        <div class="mt-1">
                                            <button wire:click="openPaymentModal({{ $booking->id }})" class="btn btn-falcon-success btn-xs" type="button" title="Post or Acknowledge Payment Entry to Cash / Bank">
                                                <span class="fas fa-check-circle me-1"></span>Post Payment
                                            </button>
                                        </div>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="align-middle text-end px-3">
                                    <div class="d-inline-flex align-items-center">
                                        <div class="dropdown font-sans-serif d-inline-block">
                                            <button class="btn btn-link text-600 dropdown-toggle dropdown-caret-none transition-none btn-sm" type="button" id="booking-actions-{{ $booking->id }}" data-bs-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false">
                                                <span class="fas fa-ellipsis-h fs-10"></span>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end border py-0" aria-labelledby="booking-actions-{{ $booking->id }}">
                                                <div class="bg-white dark__bg-1000 py-2 text-start">
                                                    @if(!$booking->trashed())
                                                        <button wire:click="openPaymentModal({{ $booking->id }})" class="dropdown-item text-success fw-semi-bold" type="button">
                                                            <span class="fas fa-check-double me-2 text-success"></span>Post / Acknowledge Payment
                                                        </button>
                                                        <div class="dropdown-divider"></div>
                                                    @endif

                                                    <a class="dropdown-item" href="{{ route('bookings.show', $booking->id) }}">
                                                        <span class="text-info fas fa-eye me-2"></span>View Details
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('bookings.slip', $booking->id) }}" target="_blank">
                                                        <span class="text-success fas fa-print me-2"></span>Print Slip
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('bookings.kitchen-slip', ['booking' => $booking->id, 'lang' => 'bilingual']) }}" target="_blank">
                                                        <span class="text-warning fas fa-utensils me-2"></span>Kitchen Slip
                                                    </a>

                                                    @if(!$booking->trashed() && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_bookings')))
                                                        @if($booking->booking_status !== 'Completed' || (auth()->user()->role && in_array(auth()->user()->role->name, ['owner', 'super_admin'])))
                                                            <a class="dropdown-item" href="{{ route('bookings.edit', $booking->id) }}">
                                                                <span class="text-primary fas fa-edit me-2"></span>Edit Booking
                                                            </a>
                                                        @endif
                                                    @endif

                                                    @if(!$booking->trashed() && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('cancel_bookings')))
                                                        @if($booking->booking_status !== 'Completed' || (auth()->user()->role && in_array(auth()->user()->role->name, ['owner', 'super_admin'])))
                                                            <div class="dropdown-divider"></div>
                                                            <button class="dropdown-item text-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $booking->id }})">
                                                                <span class="fas fa-ban me-2"></span>Cancel & Delete
                                                            </button>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <span class="fas fa-calendar-times fa-3x mb-2 d-block text-400"></span>
                                    <h6 class="fw-bold text-700">No bookings match the selected criteria.</h6>
                                    <p class="fs-11 mb-2">Try clearing search terms or resetting operational filters.</p>
                                    <button wire:click="resetFilters" class="btn btn-falcon-primary btn-sm" type="button">
                                        <span class="fas fa-undo me-1"></span>Reset All Filters
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($bookings->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>

    <!-- Quick Payment / Collection Modal -->
    @if($showPaymentModal && $paymentBooking)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.55); z-index: 1055;" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white py-3">
                        <div class="d-flex align-items-center">
                            <span class="fas fa-hand-holding-usd fs-2 me-2"></span>
                            <div>
                                <h5 class="modal-title mb-0 text-white fw-bold">Record Payment / Collection</h5>
                                <span class="fs-11 text-white-50">Booking #{{ $paymentBooking->booking_number }} &bull; {{ $paymentBooking->customer->full_name ?? 'Walk-in' }}</span>
                            </div>
                        </div>
                        <button wire:click="closePaymentModal" class="btn-close btn-close-white" type="button" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        @if($errors->has('paymentSubmission'))
                            <div class="alert alert-danger border-2 d-flex align-items-center mb-3" role="alert">
                                <span class="fas fa-exclamation-circle me-2 fs-6"></span>
                                <div class="flex-1">{{ $errors->first('paymentSubmission') }}</div>
                            </div>
                        @endif

                        <!-- Financial Overview Cards -->
                        <div class="row g-2 mb-3">
                            <div class="col-6 col-md-3">
                                <div class="bg-light p-2.5 rounded border text-center">
                                    <span class="fs-12 text-600 d-block text-uppercase fw-semi-bold">Total Bill</span>
                                    <span class="fs-9 fw-bold text-dark font-monospace">Rs. {{ number_format($paymentBooking->effective_invoice_amount, 2) }}</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="bg-subtle-primary p-2.5 rounded border border-primary-subtle text-center">
                                    <span class="fs-12 text-primary d-block text-uppercase fw-semi-bold">Advance Held</span>
                                    <span class="fs-9 fw-bold text-primary font-monospace">Rs. {{ number_format($paymentBooking->advance_received, 2) }}</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="bg-subtle-success p-2.5 rounded border border-success-subtle text-center">
                                    <span class="fs-12 text-success d-block text-uppercase fw-semi-bold">Total Paid</span>
                                    <span class="fs-9 fw-bold text-success font-monospace">Rs. {{ number_format($paymentBooking->total_paid, 2) }}</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                @php
                                    $rem = max(0.00, (float)$paymentBooking->effective_invoice_amount - (float)$paymentBooking->total_paid);
                                @endphp
                                <div class="bg-subtle-danger p-2.5 rounded border border-danger-subtle text-center">
                                    <span class="fs-12 text-danger d-block text-uppercase fw-semi-bold">Outstanding</span>
                                    <span class="fs-9 fw-bold text-danger font-monospace">Rs. {{ number_format($rem, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Form -->
                        <form wire:submit.prevent="postPayment">
                            <div class="row g-3">
                                <!-- Payment Type Selection -->
                                <div class="col-12">
                                    <label class="form-label fs-11 fw-bold text-700 mb-1">Transaction Nature / Type <span class="text-danger">*</span></label>
                                    <div class="btn-group w-100" role="group">
                                        @if(!$paymentBooking->is_revenue_recognized)
                                            <button wire:click="setPaymentType('advance')" type="button" class="btn btn-sm {{ $paymentType === 'advance' ? 'btn-primary fw-bold' : 'btn-outline-primary' }}">
                                                <i class="fas fa-shield-alt me-1"></i>Advance Payment (Contract Liability)
                                            </button>
                                        @else
                                            <button wire:click="setPaymentType('receivable_payment')" type="button" class="btn btn-sm {{ $paymentType === 'receivable_payment' ? 'btn-primary fw-bold' : 'btn-outline-primary' }}">
                                                <i class="fas fa-file-invoice-dollar me-1"></i>Receivable Settlement
                                            </button>
                                        @endif
                                        <button wire:click="setPaymentType('refund')" type="button" class="btn btn-sm {{ $paymentType === 'refund' ? 'btn-danger fw-bold' : 'btn-outline-danger' }}">
                                            <i class="fas fa-undo me-1"></i>Refund Disbursement
                                        </button>
                                    </div>
                                    <div class="fs-12 text-500 mt-1">
                                        @if($paymentType === 'advance')
                                            <span class="text-primary"><i class="fas fa-info-circle me-1"></i>Credited to <strong>2003: Customer Advances (Liability)</strong>. Not recognized as income until event completion.</span>
                                        @elseif($paymentType === 'receivable_payment')
                                            <span class="text-success"><i class="fas fa-info-circle me-1"></i>Credited to <strong>1003: Accounts Receivable</strong> to clear outstanding balance on completed event.</span>
                                        @else
                                            <span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Debited to <strong>2003: Customer Advances</strong> & disbursed from selected Cash/Bank account.</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Payment Amount -->
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label fs-11 fw-bold text-700 mb-0">Amount (PKR) <span class="text-danger">*</span></label>
                                        <button wire:click="fillFullRemaining" type="button" class="btn btn-link btn-xs p-0 text-primary fs-12 text-decoration-none">
                                            Fill Balance (Rs. {{ number_format($paymentType === 'refund' ? $paymentBooking->advance_received : $rem, 0) }})
                                        </button>
                                    </div>
                                    <div class="input-group">
                                        <span class="input-group-text font-monospace fw-bold fs-11">Rs.</span>
                                        <input wire:model="paymentAmount" type="number" step="0.01" min="1" class="form-control font-monospace fw-bold fs-10 @error('paymentAmount') is-invalid @enderror" placeholder="0.00" />
                                    </div>
                                    @error('paymentAmount') <span class="text-danger fs-11 d-block mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- Payment Account (Cash / Bank) -->
                                <div class="col-md-6">
                                    <label class="form-label fs-11 fw-bold text-700 mb-1">Deposit / Disbursement Account <span class="text-danger">*</span></label>
                                    <select wire:model="paymentAccountId" class="form-select @error('paymentAccountId') is-invalid @enderror">
                                        <option value="">-- Default Cash in Hand (1001) --</option>
                                        @foreach($cashBankAccounts as $cb)
                                            <option value="{{ $cb->account_id }}">
                                                [{{ strtoupper($cb->type) }}] {{ $cb->account->name ?? ($cb->bank_name . ' - ' . $cb->account_number) }} (COA: {{ $cb->account->account_code ?? 'Asset' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('paymentAccountId') <span class="text-danger fs-11 d-block mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- Payment Date -->
                                <div class="col-md-4">
                                    <label class="form-label fs-11 fw-bold text-700 mb-1">Payment Date <span class="text-danger">*</span></label>
                                    <input wire:model="paymentDate" type="date" class="form-control @error('paymentDate') is-invalid @enderror" />
                                    @error('paymentDate') <span class="text-danger fs-11 d-block mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- Payment Method -->
                                <div class="col-md-4">
                                    <label class="form-label fs-11 fw-bold text-700 mb-1">Payment Method <span class="text-danger">*</span></label>
                                    <select wire:model="paymentMethod" class="form-select @error('paymentMethod') is-invalid @enderror">
                                        <option value="Cash">Cash in Hand</option>
                                        <option value="Bank Transfer">Bank Transfer / Raast</option>
                                        <option value="Cheque">Cheque</option>
                                        <option value="Online / Card">Credit / Debit Card</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    @error('paymentMethod') <span class="text-danger fs-11 d-block mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- Reference / Transaction No -->
                                <div class="col-md-4">
                                    <label class="form-label fs-11 fw-bold text-700 mb-1">Transaction Ref # / Cheque #</label>
                                    <input wire:model="transactionReference" type="text" class="form-control" placeholder="e.g. TXN-98421" />
                                    @error('transactionReference') <span class="text-danger fs-11 d-block mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- Remarks / Notes -->
                                <div class="col-12">
                                    <label class="form-label fs-11 fw-bold text-700 mb-1">Payment Remarks / Ledger Narration</label>
                                    <input wire:model="paymentNotes" type="text" class="form-control" placeholder="e.g. Booking advance installment collected via Cash receipt" />
                                    @error('paymentNotes') <span class="text-danger fs-11 d-block mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="modal-footer px-0 pb-0 pt-3 mt-3 border-top d-flex justify-content-between">
                                <button wire:click="closePaymentModal" type="button" class="btn btn-falcon-default btn-sm">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </button>
                                <button type="submit" class="btn {{ $paymentType === 'refund' ? 'btn-danger' : 'btn-success' }} btn-sm" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="postPayment">
                                        <i class="fas fa-check-circle me-1"></i>{{ $paymentType === 'refund' ? 'Post Refund Voucher' : 'Post Payment & Generate Voucher' }}
                                    </span>
                                    <span wire:loading wire:target="postPayment">
                                        <span class="spinner-border spinner-border-sm me-1" role="status"></span>Posting Transaction...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Cancel Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white" id="deleteConfirmModalLabel">
                        <span class="fas fa-exclamation-triangle me-2"></span>Confirm Booking Cancellation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <p class="mb-0 text-900">Are you sure you want to cancel and delete this booking? This will change its status to <strong>Cancelled</strong>, log the event in history, and soft-delete the record.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Go Back</button>
                    <button wire:click="deleteRecord" type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        <span class="fas fa-trash-alt me-1"></span>Cancel & Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
