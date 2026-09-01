<div>
    <!-- Header -->
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
            <div>
                <h5 class="mb-0 text-primary fw-bold"><span class="fas fa-hand-holding-usd me-2"></span>Customer Advance Liability Report</h5>
                <p class="fs-12 text-600 mb-0">Unearned customer booking advances held as Contract Liabilities prior to event completion.</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-falcon-default btn-sm"><i class="fas fa-print me-1"></i>Print Report</button>
            </div>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100 border border-primary-subtle">
                <div class="card-body">
                    <h6 class="text-uppercase text-600 fw-bold fs-11 text-primary">Total Advance Liability Held</h6>
                    <div class="display-4 fs-4 mb-1 fw-black text-primary font-monospace">Rs. {{ number_format($totalLiabilitySum, 2) }}</div>
                    <p class="fs-12 text-500 mb-0">Held under COA Account <strong>2003: Customer Advances</strong></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border border-info-subtle">
                <div class="card-body">
                    <h6 class="text-uppercase text-600 fw-bold fs-11 text-info-emphasis">Total Contract Value</h6>
                    <div class="display-4 fs-4 mb-1 fw-black text-info-emphasis font-monospace">Rs. {{ number_format($totalBookingValueSum, 2) }}</div>
                    <p class="fs-12 text-500 mb-0">Total commercial invoice value of these bookings</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border border-secondary-subtle">
                <div class="card-body">
                    <h6 class="text-uppercase text-600 fw-bold fs-11 text-secondary">Active Advance Bookings</h6>
                    <div class="display-4 fs-4 mb-1 fw-black text-dark font-monospace">{{ $activeAdvancesCount }}</div>
                    <p class="fs-12 text-500 mb-0">Upcoming events with advances on deposit</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Controls -->
    <div class="card mb-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input wire:model.live.debounce.300ms="search" type="text" class="form-control form-control-sm" placeholder="Search by booking #, client name, phone..." />
                </div>
                <div class="col-md-3">
                    <select wire:model.live="branchId" class="form-select form-select-sm">
                        <option value="all">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input wire:model.live="dateFrom" type="date" class="form-control form-control-sm" title="Event Date From" />
                </div>
                <div class="col-md-2">
                    <input wire:model.live="dateTo" type="date" class="form-control form-control-sm" title="Event Date To" />
                </div>
                <div class="col-md-1 d-grid">
                    <button wire:click="$set('search', ''); $set('branchId', 'all'); $set('dateFrom', ''); $set('dateTo', '');" class="btn btn-falcon-default btn-sm" title="Reset Filters"><i class="fas fa-undo"></i></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Advances Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped fs-11 mb-0 align-middle">
                    <thead class="bg-200 text-800">
                        <tr>
                            <th class="px-3">Booking #</th>
                            <th>Customer</th>
                            <th>Branch / Hall</th>
                            <th>Event Date</th>
                            <th class="text-end">Total Contract</th>
                            <th class="text-end">Advance Held (Liability)</th>
                            <th class="text-end">Remaining Unpaid</th>
                            <th class="text-center">Status</th>
                            <th class="text-center px-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $bk)
                            @php
                                $remaining = max(0.00, (float)$bk->grand_total - (float)$bk->advance_received);
                            @endphp
                            <tr>
                                <td class="px-3 font-monospace fw-bold">
                                    <a href="{{ route('bookings.show', $bk->id) }}">#{{ $bk->booking_number }}</a>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $bk->customer->full_name ?? 'Walk-in' }}</div>
                                    <div class="text-muted fs-10">{{ $bk->customer->phone ?? '' }}</div>
                                </td>
                                <td>
                                    <div>{{ $bk->branch->name ?? 'Default Branch' }}</div>
                                    <div class="text-muted fs-10">{{ $bk->hall->hall_name ?? '' }}</div>
                                </td>
                                <td class="fw-semi-bold">{{ $bk->booking_date->format('d-M-Y') }}</td>
                                <td class="text-end font-monospace text-dark">Rs. {{ number_format($bk->grand_total, 2) }}</td>
                                <td class="text-end font-monospace fw-bold text-primary">Rs. {{ number_format($bk->advance_received, 2) }}</td>
                                <td class="text-end font-monospace text-danger">Rs. {{ number_format($remaining, 2) }}</td>
                                <td class="text-center">
                                    <span class="badge badge-subtle-warning">{{ $bk->booking_status }}</span>
                                </td>
                                <td class="text-center px-3">
                                    <a href="{{ route('bookings.show', $bk->id) }}" class="btn btn-falcon-default btn-xs">
                                        <i class="fas fa-eye me-1"></i>View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fas fa-folder-open fa-2x mb-2 d-block text-400"></i>
                                    No active customer advance liabilities found matching the filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($bookings->hasPages())
                <div class="p-3 border-top">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
