<div>
    <div class="row g-3">
        <!-- LEFT COLUMN: PACKAGE INFORMATION & METRICS -->
        <div class="col-lg-4">
            <!-- Details Card -->
            <div class="card mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><span class="fas fa-cubes me-2 text-primary"></span>Package Profile</h6>
                    @if($package->seasonal_package)
                        <span class="badge badge-subtle-{{ $package->isSeasonalActive() ? 'success' : 'warning' }} rounded-pill"><span class="fas fa-snowflake me-1"></span>Seasonal</span>
                    @else
                        <span class="badge badge-subtle-secondary rounded-pill">Standard</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="fs-12 text-500 d-block">Package Name</span>
                        <h4 class="text-primary fw-bold">{{ $package->package_name }}</h4>
                    </div>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <span class="fs-12 text-500 d-block">Code</span>
                            <span class="badge badge-subtle-primary font-monospace fs-10">{{ $package->package_code }}</span>
                        </div>
                        <div class="col-6">
                            <span class="fs-12 text-500 d-block">Tier Level</span>
                            <span class="fw-semi-bold text-800">{{ $package->package_type }}</span>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <span class="fs-12 text-500 d-block">Min Guests</span>
                            <span class="text-800 fw-semi-bold">{{ $package->minimum_guests }}</span>
                        </div>
                        <div class="col-6">
                            <span class="fs-12 text-500 d-block">Max Guests</span>
                            <span class="text-800 fw-semi-bold">{{ $package->maximum_guests ?: 'No Limit' }}</span>
                        </div>
                    </div>

                    @if($package->seasonal_package)
                        <div class="mb-3 border-top pt-2">
                            <span class="fs-12 text-500 d-block">Seasonal Range</span>
                            <span class="text-800 font-monospace fs-11">
                                <span class="fas fa-calendar-alt me-1 text-primary"></span>
                                {{ $package->season_start_date->format('M d, Y') }} - {{ $package->season_end_date->format('M d, Y') }}
                            </span>
                        </div>
                    @endif

                    <div class="border-top pt-2">
                        <span class="fs-12 text-500 d-block">Description</span>
                        <p class="text-800 mb-0 fs-11">{{ $package->description ?: 'No description provided.' }}</p>
                    </div>
                </div>
            </div>

            <!-- Simulation Controls Card -->
            <div class="card">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0"><span class="fas fa-calculator me-2 text-primary"></span>Pricing Estimator</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label font-sans-serif fw-bold text-700" for="previewGuests">Headcount Simulation (Guests)</label>
                        <div class="input-group input-group-sm">
                            <input wire:model.live.debounce.250ms="previewGuests" type="number" min="1" class="form-control font-monospace" id="previewGuests">
                            <span class="input-group-text"><span class="fas fa-users"></span></span>
                        </div>
                    </div>

                    <div class="border rounded bg-light p-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-600 fs-11">Base Setup Cost:</span>
                            <span class="font-monospace fw-semi-bold">PKR {{ number_format($quoteDetails['package_base_price'], 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-600 fs-11">Per Plate Price:</span>
                            <span class="font-monospace fw-semi-bold text-success">PKR {{ number_format($quoteDetails['per_plate_price'], 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2 mb-2">
                            <span class="text-900 fw-bold fs-11">Total Revenue:</span>
                            <span class="font-monospace fw-bold text-primary">PKR {{ number_format($quoteDetails['total_selling_price'], 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-600 fs-11">Total Item Cost:</span>
                            <span class="font-monospace text-secondary">PKR {{ number_format($quoteDetails['estimated_total_base_cost'], 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2">
                            <span class="text-900 fw-bold fs-11">Estimated Profit:</span>
                            <span class="font-monospace fw-bold text-success">PKR {{ number_format($quoteDetails['estimated_profit'], 2) }}</span>
                        </div>
                        <div class="text-center mt-2">
                            <span class="badge badge-subtle-{{ $quoteDetails['profit_margin_percent'] >= 30 ? 'success' : 'warning' }} rounded-pill font-monospace fs-11">
                                {{ $quoteDetails['profit_margin_percent'] }}% Margin
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: ASSIGNED MENU ITEMS AND DETAILS -->
        <div class="col-lg-8">
            <div class="card h-100 d-flex flex-column">
                <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0"><span class="fas fa-list-ol me-2 text-primary"></span>Package Menu Items ({{ count($packageItems) }} Items)</h6>
                    <div class="d-flex gap-2">
                        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_packages'))
                            <a class="btn btn-falcon-success btn-xs" href="{{ route('packages.builder', $package->id) }}">
                                <span class="fas fa-tools me-1"></span> Open Builder
                            </a>
                            <a class="btn btn-falcon-primary btn-xs" href="{{ route('packages.edit', $package->id) }}">
                                <span class="fas fa-edit me-1"></span> Edit Settings
                            </a>
                        @endif
                        <a class="btn btn-falcon-default btn-xs" href="{{ route('packages.index') }}">
                            <span class="fas fa-chevron-left me-1"></span> Back
                        </a>
                    </div>
                </div>
                <div class="card-body p-0 flex-grow-1 overflow-auto scrollbar">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped fs-11 mb-0 align-middle">
                            <thead class="bg-200 text-900">
                                <tr>
                                    <th class="px-3" style="width: 80px;">Order</th>
                                    <th>Item Code</th>
                                    <th>Menu Item</th>
                                    <th>Category</th>
                                    <th>Qty/Plate</th>
                                    <th>Selling Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($packageItems as $item)
                                    <tr>
                                        <td class="px-3">
                                            <span class="badge badge-subtle-secondary rounded-pill font-monospace fs-11">{{ $item->pivot->display_order }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-subtle-primary font-monospace fs-11">{{ $item->item_code }}</span>
                                        </td>
                                        <td class="fw-semi-bold">
                                            {{ $item->item_name }}
                                        </td>
                                        <td>
                                            {{ $item->category->category_name ?? 'Uncategorized' }}
                                        </td>
                                        <td>
                                            {{ number_format($item->pivot->quantity, 2) }} {{ $item->unit }}
                                        </td>
                                        <td class="font-monospace text-success fw-semi-bold">
                                            PKR {{ number_format($item->selling_price, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <span class="fas fa-utensils fa-2x mb-2 d-block"></span>
                                            No menu items have been added to this package yet. Use the builder screen to add items.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
