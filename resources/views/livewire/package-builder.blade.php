<div>
    <!-- Header Summary Card -->
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0"><span class="fas fa-tools me-2 text-primary"></span>Package Builder: <span class="text-primary">{{ $package->package_name }}</span></h5>
                <p class="mb-0 fs-11 text-600">Code: <strong>{{ $package->package_code }}</strong> | Tier: <strong>{{ $package->package_type }}</strong> | Min Guests: <strong>{{ $package->minimum_guests }}</strong></p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-falcon-default btn-sm" href="{{ route('packages.preview', $package->id) }}">
                    <span class="fas fa-eye me-1"></span> Preview Package
                </a>
                <a class="btn btn-falcon-primary btn-sm" href="{{ route('packages.index') }}">
                    <span class="fas fa-check me-1"></span> Finish
                </a>
            </div>
        </div>
    </div>

    @if(session('builder_success'))
        <div class="alert alert-success border-2 d-flex align-items-center mb-3" role="alert">
            <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
            <p class="mb-0 flex-1">{{ session('builder_success') }}</p>
            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3">
        <!-- LEFT COLUMN: AVAILABLE MENU ITEMS -->
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Available Menu Items</h6>
                </div>
                <div class="card-body d-flex flex-column p-3">
                    <!-- Search available -->
                    <div class="input-group input-group-sm mb-3">
                        <input wire:model.live.debounce.250ms="searchQuery" class="form-control" type="search" placeholder="Search menu items..." />
                        <span class="input-group-text"><span class="fas fa-search"></span></span>
                    </div>

                    <!-- Category Tabs (Pills) -->
                    <div class="mb-3 scrollbar" style="overflow-x: auto; white-space: nowrap; max-height: 50px;">
                        <button wire:click="$set('selectedCategory', '')" class="btn btn-xs rounded-pill me-1 {{ $selectedCategory === '' ? 'btn-primary' : 'btn-outline-primary' }}">
                            All
                        </button>
                        @foreach($categories as $cat)
                            <button wire:click="$set('selectedCategory', {{ $cat->id }})" class="btn btn-xs rounded-pill me-1 {{ $selectedCategory == $cat->id ? 'btn-primary' : 'btn-outline-primary' }}">
                                {{ $cat->category_name }}
                            </button>
                        @endforeach
                    </div>

                    <!-- Available items list -->
                    <div class="flex-grow-1 overflow-auto scrollbar" style="max-height: 400px; min-height: 300px;">
                        <div class="list-group list-group-flush fs-11">
                            @forelse($availableItems as $item)
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-bottom">
                                    <div class="pe-2">
                                        <h6 class="mb-0 text-900 fw-semi-bold">{{ $item->item_name }}</h6>
                                        <p class="mb-0 text-500 fs-12">{{ $item->category->category_name }} | {{ $item->unit }}</p>
                                    </div>
                                    <div class="text-end">
                                        <span class="d-block fw-bold text-success mb-1">PKR {{ number_format($item->selling_price) }}</span>
                                        <button wire:click="addItem({{ $item->id }})" class="btn btn-xs btn-falcon-success px-2 py-0">
                                            <span class="fas fa-plus me-1"></span>Add
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5 text-muted">
                                    <span class="fas fa-hamburger fa-2x mb-2 d-block"></span>
                                    No items available to add.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: SELECTED PACKAGE MENU ITEMS & CALCULATIONS -->
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Selected Package Contents ({{ count($selectedItems) }} Items)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive scrollbar" style="max-height: 420px; min-height: 300px;">
                        <table class="table table-sm table-striped fs-11 mb-0 align-middle">
                            <thead class="bg-200 text-900">
                                <tr>
                                    <th class="px-3" style="width: 80px;">Order</th>
                                    <th>Item Name</th>
                                    <th style="width: 100px;">Qty/Serving</th>
                                    <th>Unit Cost</th>
                                    <th>Item Price</th>
                                    <th class="text-end px-3" style="width: 60px;">Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($selectedItems as $item)
                                    <tr wire:key="selected-{{ $item['id'] }}">
                                        <td class="px-3">
                                            <div class="btn-group btn-group-xs">
                                                <button wire:click="moveUp({{ $item['id'] }})" class="btn btn-falcon-default p-1" title="Move Up"><span class="fas fa-chevron-up"></span></button>
                                                <button wire:click="moveDown({{ $item['id'] }})" class="btn btn-falcon-default p-1" title="Move Down"><span class="fas fa-chevron-down"></span></button>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-semi-bold d-block text-900">{{ $item['item_name'] }}</span>
                                            <span class="text-500 fs-12">{{ $item['category_name'] }}</span>
                                        </td>
                                        <td>
                                            <input wire:change="updateQuantity({{ $item['id'] }}, $event.target.value)" type="number" step="0.1" class="form-control form-control-xs py-0 text-center font-monospace" value="{{ $item['quantity'] }}" style="max-width: 70px;">
                                        </td>
                                        <td class="font-monospace text-secondary">
                                            PKR {{ number_format($item['base_cost']) }}
                                        </td>
                                        <td class="font-monospace text-success fw-semi-bold">
                                            PKR {{ number_format($item['selling_price']) }}
                                        </td>
                                        <td class="text-end px-3">
                                            <button wire:click="removeItem({{ $item['id'] }})" class="btn btn-link btn-xs p-0 text-danger" title="Remove Item">
                                                <span class="fas fa-times-circle"></span>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <span class="fas fa-tools fa-2x mb-2 d-block"></span>
                                            Builder is empty. Select menu items from the left to include them in the package.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PRICING SIMULATION BOARD -->
            <div class="card">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0"><span class="fas fa-calculator me-2"></span>Pricing & Profit Margins Engine</h6>
                </div>
                <div class="card-body">
                    <div class="row align-items-center g-3">
                        <div class="col-md-5">
                            <label class="form-label mb-1 font-sans-serif fw-bold text-700" for="previewGuests">Headcount Simulation (Guests)</label>
                            <div class="input-group input-group-sm">
                                <input wire:model.live.debounce.300ms="previewGuests" type="number" min="1" class="form-control font-monospace" id="previewGuests">
                                <span class="input-group-text"><span class="fas fa-users"></span></span>
                            </div>
                            <div class="form-text fs-12 mt-1">Simulate margins dynamically based on headcounts.</div>
                        </div>
                        <div class="col-md-7">
                            <div class="border rounded bg-light p-3">
                                <div class="row text-center g-2">
                                    <div class="col-6 border-end">
                                        <span class="d-block fs-11 text-600">Total Selling Price</span>
                                        <span class="fs-9 text-success fw-bold font-monospace">PKR {{ number_format($quoteDetails['total_selling_price'], 2) }}</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="d-block fs-11 text-600">Estimated Total cost</span>
                                        <span class="fs-9 text-secondary fw-bold font-monospace">PKR {{ number_format($quoteDetails['estimated_total_base_cost'], 2) }}</span>
                                    </div>
                                    <div class="col-12 mt-2 pt-2 border-top">
                                        <span class="fs-11 text-700">Estimated profit: <strong>PKR {{ number_format($quoteDetails['estimated_profit'], 2) }}</strong></span>
                                        <span class="badge badge-subtle-{{ $quoteDetails['profit_margin_percent'] >= 30 ? 'success' : 'warning' }} rounded-pill ms-2 font-monospace">{{ $quoteDetails['profit_margin_percent'] }}% Margin</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
