<div>
    <!-- Stock Summary Stats Row -->
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden" style="min-width: 12rem">
                <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-1.png') }});"></div>
                <div class="card-body position-relative">
                    <h6 class="text-secondary mb-1">Total Items</h6>
                    <div class="display-4 fs-4 mb-2 fw-normal font-sans-serif text-info">{{ $stats['total'] }}</div>
                    <span class="badge badge-subtle-info rounded-pill fs-11">Total Cataloged</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden" style="min-width: 12rem">
                <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-2.png') }});"></div>
                <div class="card-body position-relative">
                    <h6 class="text-success mb-1">Good Stock</h6>
                    <div class="display-4 fs-4 mb-2 fw-normal font-sans-serif text-success">{{ $stats['good'] }}</div>
                    <span class="badge badge-subtle-success rounded-pill fs-11">Stock Level Healthy</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden" style="min-width: 12rem">
                <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-3.png') }});"></div>
                <div class="card-body position-relative">
                    <h6 class="text-warning mb-1">Reorder Required</h6>
                    <div class="display-4 fs-4 mb-2 fw-normal font-sans-serif text-warning">{{ $stats['reorder'] }}</div>
                    <span class="badge badge-subtle-warning rounded-pill fs-11">Below Reorder Level</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden" style="min-width: 12rem">
                <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-4.png') }});"></div>
                <div class="card-body position-relative">
                    <h6 class="text-danger mb-1">Critical & Out</h6>
                    <div class="display-4 fs-4 mb-2 fw-normal font-sans-serif text-danger">{{ $stats['low'] + $stats['out'] }}</div>
                    <span class="badge badge-subtle-danger rounded-pill fs-11">
                        {{ $stats['out'] }} Out of Stock / {{ $stats['low'] }} Critical
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Table Card -->
    <div class="card border border-200">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-boxes me-2 text-primary"></span>Current Stock Levels</h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Search -->
                <div class="input-group input-group-sm" style="max-width: 200px;">
                    <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search items..." />
                    <span class="input-group-text"><span class="fas fa-search"></span></span>
                </div>

                <!-- Category Filter -->
                <select wire:model.live="filterCategory" class="form-select form-select-sm" style="max-width: 150px;">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>

                <!-- Branch Filter -->
                @if(auth()->user()->branch_id && !auth()->user()->isSuperAdmin())
                    <select class="form-select form-select-sm" style="max-width: 150px;" disabled>
                        <option value="">{{ auth()->user()->branch->name ?? 'Assigned Branch' }}</option>
                    </select>
                @else
                    <select wire:model.live="filterBranch" class="form-select form-select-sm" style="max-width: 150px;">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                @endif

                <!-- Status Filter -->
                <select wire:model.live="filterStatus" class="form-select form-select-sm" style="max-width: 150px;">
                    <option value="">All Statuses</option>
                    <option value="good">Good Stock</option>
                    <option value="reorder_required">Reorder Alert</option>
                    <option value="low_stock">Critical Low</option>
                    <option value="out_of_stock">Out of Stock</option>
                </select>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3" style="width: 110px;">Code</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th>Brand</th>
                            <th class="text-end" style="width: 110px;">Min / Reorder</th>
                            <th class="text-end" style="width: 100px;">Total Received</th>
                            <th class="text-end" style="width: 100px;">Total Returned</th>
                            <th class="text-end" style="width: 100px;">Issued to Dept</th>
                            <th class="text-end" style="width: 100px;">Current Stock</th>
                            <th class="text-center" style="width: 130px;">Stock Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            @php
                                $stock = $item->total_received - $item->total_returned - ($item->total_issued ?? 0) + ($item->total_dept_returned ?? 0);
                                
                                if ($stock <= 0) {
                                    $badgeColor = 'danger';
                                    $statusLabel = 'Out of Stock';
                                } elseif ($stock <= $item->minimum_stock_level) {
                                    $badgeColor = 'danger';
                                    $statusLabel = 'Critical Low';
                                } elseif ($stock <= $item->reorder_level) {
                                    $badgeColor = 'warning';
                                    $statusLabel = 'Reorder Req.';
                                } else {
                                    $badgeColor = 'success';
                                    $statusLabel = 'Good';
                                }
                            @endphp
                            <tr>
                                <td class="px-3 font-monospace fw-bold">
                                    <span class="badge badge-subtle-secondary fs-11">{{ $item->item_code }}</span>
                                </td>
                                <td class="fw-semi-bold">{{ $item->name }}</td>
                                <td>{{ $item->category->name ?? '—' }}</td>
                                <td><span class="badge bg-light text-dark">{{ $item->unit->short_code ?? 'Pcs' }}</span></td>
                                <td>{{ $item->brand->name ?? 'Generic' }}</td>
                                <td class="text-end font-monospace text-muted fs-11">
                                    {{ number_format($item->minimum_stock_level, 2) }} / {{ number_format($item->reorder_level, 2) }}
                                </td>
                                <td class="text-end font-monospace text-success">{{ number_format($item->total_received, 2) }}</td>
                                <td class="text-end font-monospace text-warning">{{ number_format($item->total_returned, 2) }}</td>
                                <td class="text-end font-monospace text-danger">{{ number_format(($item->total_issued ?? 0) - ($item->total_dept_returned ?? 0), 2) }}</td>
                                <td class="text-end font-monospace fw-bold {{ $stock <= $item->minimum_stock_level ? 'text-danger' : 'text-primary' }}">
                                    {{ number_format($stock, 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-subtle-{{ $badgeColor }} rounded-pill" style="min-width: 100px;">
                                        <span class="fas {{ $badgeColor === 'success' ? 'fa-check' : ($badgeColor === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle') }} me-1"></span>
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <span class="fas fa-boxes fa-2x mb-2 d-block"></span>
                                    No stock records match filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($items->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</div>
