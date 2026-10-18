<div>
    <!-- Stock Summary Stats Row -->
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-md-2">
            <div class="card overflow-hidden h-100">
                <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-1.png') }});"></div>
                <div class="card-body position-relative p-3">
                    <h6 class="text-secondary mb-1 fs-11">Total Items</h6>
                    <div class="display-4 fs-4 mb-1 fw-bold font-sans-serif text-info">{{ $stats['total'] }}</div>
                    <span class="badge badge-subtle-info rounded-pill fs-11">Total Cataloged</span>
                </div>
            </div>
        </div>

        @if(!empty($filterDateFrom))
            <!-- Opening Valuation Card (Active when Date Range is used) -->
            <div class="col-sm-6 col-md-3">
                <div class="card overflow-hidden h-100 bg-warning bg-opacity-10 border border-warning border-opacity-25">
                    <div class="card-body position-relative p-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <h6 class="text-warning-emphasis mb-0 fs-11 fw-bold text-uppercase">Opening Stock Value</h6>
                            <span class="fas fa-history text-warning"></span>
                        </div>
                        <div class="fs-4 mb-1 fw-bold font-monospace text-warning-emphasis">PKR {{ number_format($stats['total_opening_value'] ?? 0, 2) }}</div>
                        <span class="text-muted fs-11">As of start of <strong>{{ \Carbon\Carbon::parse($filterDateFrom)->format('d M, Y') }}</strong> ({{ number_format($stats['total_opening_qty'] ?? 0, 2) }} units)</span>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-sm-6 {{ !empty($filterDateFrom) ? 'col-md-3' : 'col-md-3' }}">
            <div class="card overflow-hidden h-100 bg-primary bg-opacity-10 border border-primary border-opacity-25">
                <div class="card-body position-relative p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h6 class="text-primary mb-0 fs-11 fw-bold text-uppercase">{{ !empty($filterDateTo) ? 'Closing Stock Value' : 'Total Stock Value' }}</h6>
                        <span class="fas fa-coins text-primary"></span>
                    </div>
                    <div class="fs-4 mb-1 fw-bold font-monospace text-primary">PKR {{ number_format($stats['total_stock_value'] ?? 0, 2) }}</div>
                    <span class="text-muted fs-11">{{ !empty($filterDateTo) ? 'As of ' . \Carbon\Carbon::parse($filterDateTo)->format('d M, Y') . ':' : 'Live balance:' }} <strong>{{ number_format($stats['total_stock_qty'] ?? 0, 2) }}</strong> units</span>
                </div>
            </div>
        </div>

        <div class="col-sm-6 {{ !empty($filterDateFrom) ? 'col-md-2' : 'col-md-2' }}">
            <div class="card overflow-hidden h-100">
                <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-2.png') }});"></div>
                <div class="card-body position-relative p-3">
                    <h6 class="text-success mb-1 fs-11">Good Stock</h6>
                    <div class="display-4 fs-4 mb-1 fw-bold font-sans-serif text-success">{{ $stats['good'] }}</div>
                    <span class="badge badge-subtle-success rounded-pill fs-11">Healthy Levels</span>
                </div>
            </div>
        </div>

        <div class="col-sm-6 {{ !empty($filterDateFrom) ? 'col-md-2' : 'col-md-2' }}">
            <div class="card overflow-hidden h-100">
                <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-3.png') }});"></div>
                <div class="card-body position-relative p-3">
                    <h6 class="text-warning mb-1 fs-11">Reorder Required</h6>
                    <div class="display-4 fs-4 mb-1 fw-bold font-sans-serif text-warning">{{ $stats['reorder'] }}</div>
                    <span class="badge badge-subtle-warning rounded-pill fs-11">Below Reorder Level</span>
                </div>
            </div>
        </div>

        @if(empty($filterDateFrom))
            <div class="col-sm-6 col-md-3">
                <div class="card overflow-hidden h-100">
                    <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-4.png') }});"></div>
                    <div class="card-body position-relative p-3">
                        <h6 class="text-danger mb-1 fs-11">Critical & Out</h6>
                        <div class="display-4 fs-4 mb-1 fw-bold font-sans-serif text-danger">{{ $stats['low'] + $stats['out'] }}</div>
                        <span class="badge badge-subtle-danger rounded-pill fs-11">
                            {{ $stats['out'] }} Out / {{ $stats['low'] }} Critical
                        </span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Filter & Table Card -->
    <div class="card border border-200">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0">
                        <span class="fas fa-boxes me-2 text-primary"></span>
                        {{ (!empty($filterDateFrom) || !empty($filterDateTo)) ? 'Periodic Stock Statement' : 'Current Stock Levels' }}
                    </h5>
                    <span class="badge badge-subtle-primary fs-11">{{ $items->total() }} items</span>
                    @if(!empty($filterDateFrom) || !empty($filterDateTo))
                        <span class="badge badge-subtle-info fs-11">
                            <span class="fas fa-calendar-alt me-1"></span>
                            {{ $filterDateFrom ? \Carbon\Carbon::parse($filterDateFrom)->format('d M, Y') : 'Start' }}
                            &rarr;
                            {{ $filterDateTo ? \Carbon\Carbon::parse($filterDateTo)->format('d M, Y') : 'Today' }}
                        </span>
                    @endif
                </div>

                <!-- Export & Print Actions -->
                <div class="btn-group btn-group-sm">
                    <button wire:click="exportExcel" class="btn btn-outline-success" title="Export Excel (CSV)">
                        <span class="fas fa-file-excel me-1"></span>Excel
                    </button>
                    <a href="{{ route('inventory.stock.pdf', ['filterBranch' => $filterBranch, 'filterCategory' => $filterCategory, 'filterStatus' => $filterStatus, 'filterDateFrom' => $filterDateFrom, 'filterDateTo' => $filterDateTo, 'search' => $search]) }}" target="_blank" class="btn btn-outline-danger" title="Export PDF">
                        <span class="fas fa-file-pdf me-1"></span>PDF
                    </a>
                    <a href="{{ route('inventory.stock.print', ['filterBranch' => $filterBranch, 'filterCategory' => $filterCategory, 'filterStatus' => $filterStatus, 'filterDateFrom' => $filterDateFrom, 'filterDateTo' => $filterDateTo, 'search' => $search]) }}" target="_blank" class="btn btn-outline-secondary" title="Print View">
                        <span class="fas fa-print me-1"></span>Print
                    </a>
                </div>
            </div>

            <!-- Filters Row -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Search -->
                <div class="input-group input-group-sm" style="max-width: 170px;">
                    <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search items..." />
                    <span class="input-group-text"><span class="fas fa-search"></span></span>
                </div>

                <!-- Category Filter -->
                <select wire:model.live="filterCategory" class="form-select form-select-sm" style="max-width: 135px;">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>

                <!-- Branch Filter -->
                @if(auth()->user()->branch_id && !auth()->user()->isSuperAdmin())
                    <select class="form-select form-select-sm" style="max-width: 135px;" disabled>
                        <option value="">{{ auth()->user()->branch->name ?? 'Assigned Branch' }}</option>
                    </select>
                @else
                    <select wire:model.live="filterBranch" class="form-select form-select-sm" style="max-width: 135px;">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                @endif

                <!-- Status Filter -->
                <select wire:model.live="filterStatus" class="form-select form-select-sm" style="max-width: 125px;">
                    <option value="">All Statuses</option>
                    <option value="good">Good Stock</option>
                    <option value="reorder_required">Reorder Alert</option>
                    <option value="low_stock">Critical Low</option>
                    <option value="out_of_stock">Out of Stock</option>
                </select>

                <!-- Date Range Filters -->
                <div class="d-flex align-items-center gap-1 bg-white border rounded px-2 py-1">
                    <span class="text-500 fs-11 fw-bold text-uppercase me-1"><span class="fas fa-calendar-alt text-primary me-1"></span>From:</span>
                    <input type="date" wire:model.live="filterDateFrom" class="form-control form-control-sm border-0 p-0 shadow-none" style="width: 125px;" title="Period From Date" />
                </div>

                <div class="d-flex align-items-center gap-1 bg-white border rounded px-2 py-1">
                    <span class="text-500 fs-11 fw-bold text-uppercase me-1"><span class="fas fa-calendar-check text-primary me-1"></span>To:</span>
                    <input type="date" wire:model.live="filterDateTo" class="form-control form-control-sm border-0 p-0 shadow-none" style="width: 125px;" title="Period To Date" />
                </div>

                @if(!empty($filterDateFrom) || !empty($filterDateTo))
                    <button wire:click="clearDateFilter" class="btn btn-outline-danger btn-sm py-1" title="Clear Date Filter">
                        <span class="fas fa-times me-1"></span>Reset Dates
                    </button>
                @endif
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3" style="width: 95px;">Code</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th>Brand</th>
                            <th class="text-end" style="width: 90px;">Min / Reorder</th>
                            <th class="text-end" style="width: 85px;">
                                {{ !empty($filterDateFrom) ? 'Op. Stock' : 'Opening' }}
                                @if(!empty($filterDateFrom))
                                    <div class="text-muted fw-normal" style="font-size: 7.5px;">{{ \Carbon\Carbon::parse($filterDateFrom)->format('d-M') }}</div>
                                @endif
                            </th>
                            <th class="text-end" style="width: 80px;">Purchased</th>
                            <th class="text-end" style="width: 80px;">Returned</th>
                            <th class="text-end" style="width: 80px;">Net Issued</th>
                            <th class="text-end" style="width: 80px;">Adjusted</th>
                            <th class="text-end" style="width: 95px;">
                                {{ !empty($filterDateTo) ? 'Closing Stock' : 'Current Stock' }}
                                @if(!empty($filterDateTo))
                                    <div class="text-muted fw-normal" style="font-size: 7.5px;">{{ \Carbon\Carbon::parse($filterDateTo)->format('d-M') }}</div>
                                @endif
                            </th>
                            <th class="text-end" style="width: 85px;">Unit Cost</th>
                            <th class="text-end" style="width: 95px;">Stock Value</th>
                            <th class="text-center" style="width: 110px;">Stock Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $pageTotalOpening = 0;
                            $pageTotalOpeningValue = 0;
                            $pageTotalStock = 0;
                            $pageTotalValue = 0;
                        @endphp
                        @forelse($items as $item)
                            @php
                                $opening = (float) ($item->total_opening ?? 0);
                                $received = (float) ($item->total_received ?? 0);
                                $returned = (float) ($item->total_returned ?? 0);
                                $netIssued = (float) (($item->total_issued ?? 0) - ($item->total_dept_returned ?? 0));
                                $adjusted = (float) ($item->total_adjusted ?? 0);
                                $stock = $opening + $received - $returned - $netIssued + $adjusted;

                                $unitCost = (float)($item->average_cost > 0 ? $item->average_cost : ($item->last_purchase_cost > 0 ? $item->last_purchase_cost : $item->default_purchase_rate));
                                $itemValue = max(0, $stock) * $unitCost;
                                $openingValue = max(0, $opening) * $unitCost;

                                $pageTotalOpening += $opening;
                                $pageTotalOpeningValue += $openingValue;
                                $pageTotalStock += $stock;
                                $pageTotalValue += $itemValue;
                                
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
                                    $statusLabel = 'Healthy';
                                }
                            @endphp
                            <tr>
                                <td class="px-3 font-monospace text-primary fw-semi-bold">{{ $item->item_code }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $item->name }}</div>
                                    @if($item->description)
                                        <div class="fs-11 text-muted text-truncate" style="max-width: 200px;">{{ $item->description }}</div>
                                    @endif
                                </td>
                                <td>{{ $item->category->name ?? '—' }}</td>
                                <td>{{ $item->unit->short_code ?? 'Pcs' }}</td>
                                <td>{{ $item->brand->name ?? 'Generic' }}</td>
                                <td class="text-end font-monospace">
                                    <span class="text-danger" title="Min Stock">{{ number_format($item->minimum_stock_level, 0) }}</span> / 
                                    <span class="text-warning" title="Reorder Level">{{ number_format($item->reorder_level, 0) }}</span>
                                </td>
                                <td class="text-end font-monospace text-muted">
                                    <div>{{ number_format($opening, 2) }}</div>
                                    @if(!empty($filterDateFrom) && $opening > 0)
                                        <div class="text-500" style="font-size: 8px;" title="Opening Stock Valuation">
                                            PKR {{ number_format($openingValue, 0) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end font-monospace text-success">
                                    {{ $received > 0 ? '+' . number_format($received, 2) : '0.00' }}
                                </td>
                                <td class="text-end font-monospace text-warning">
                                    {{ $returned > 0 ? '-' . number_format($returned, 2) : '0.00' }}
                                </td>
                                <td class="text-end font-monospace text-danger">
                                    {{ $netIssued > 0 ? '-' . number_format($netIssued, 2) : '0.00' }}
                                </td>
                                <td class="text-end font-monospace text-info">
                                    {{ $adjusted != 0 ? ($adjusted > 0 ? '+' : '') . number_format($adjusted, 2) : '0.00' }}
                                </td>
                                <td class="text-end font-monospace fw-bold {{ $stock <= $item->minimum_stock_level ? 'text-danger' : 'text-primary' }}">
                                    {{ number_format($stock, 2) }}
                                </td>
                                <td class="text-end font-monospace text-muted fs-11">
                                    PKR {{ number_format($unitCost, 2) }}
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark">
                                    PKR {{ number_format($itemValue, 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-subtle-{{ $badgeColor }} rounded-pill" style="min-width: 95px;">
                                        <span class="fas {{ $badgeColor === 'success' ? 'fa-check' : ($badgeColor === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle') }} me-1"></span>
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="text-center py-5 text-muted">
                                    <span class="fas fa-boxes fa-2x mb-2 d-block"></span>
                                    No stock records match filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($items->count() > 0)
                        <tfoot class="bg-200">
                            <tr>
                                <td colspan="6" class="text-end fw-bold">Page Subtotal:</td>
                                <td class="text-end font-monospace fw-bold text-secondary">
                                    <div>{{ number_format($pageTotalOpening, 2) }}</div>
                                    @if(!empty($filterDateFrom) && $pageTotalOpeningValue > 0)
                                        <div style="font-size: 8px;" class="text-muted">PKR {{ number_format($pageTotalOpeningValue, 0) }}</div>
                                    @endif
                                </td>
                                <td colspan="4"></td>
                                <td class="text-end font-monospace fw-bold text-primary">{{ number_format($pageTotalStock, 2) }}</td>
                                <td></td>
                                <td class="text-end font-monospace fw-bold text-primary">PKR {{ number_format($pageTotalValue, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
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
