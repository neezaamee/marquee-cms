<div>
    <div class="row g-3">
        <!-- Sidebar Form to Create/Edit Item -->
        @if($showForm)
            <div class="col-md-4">
                <div class="card border border-200">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">{{ $editId ? 'Edit Item' : 'Catalog New Item' }}</h5>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="save">
                            <div class="mb-3">
                                <label class="form-label" for="item-name">Item Name *</label>
                                <input wire:model="name" class="form-control @error('name') is-invalid @enderror" id="item-name" type="text" placeholder="e.g. Plastic Chair" />
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="category-id">Category *</label>
                                <select wire:model="category_id" class="form-select @error('category_id') is-invalid @enderror" id="category-id">
                                    <option value="">Select Category</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="unit-id">Unit of Measure *</label>
                                <select wire:model="unit_id" class="form-select @error('unit_id') is-invalid @enderror" id="unit-id">
                                    <option value="">Select Unit</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->short_code }})</option>
                                    @endforeach
                                </select>
                                @error('unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="brand-id">Brand</label>
                                <select wire:model="brand_id" class="form-select @error('brand_id') is-invalid @enderror" id="brand-id">
                                    <option value="">None (Generic)</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                                @error('brand_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="min-stock">Min Stock Level *</label>
                                <input wire:model="minimum_stock_level" type="number" step="0.01" class="form-control @error('minimum_stock_level') is-invalid @enderror" id="min-stock" />
                                @error('minimum_stock_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="reorder-level">Reorder Level *</label>
                                <input wire:model="reorder_level" type="number" step="0.01" class="form-control @error('reorder_level') is-invalid @enderror" id="reorder-level" />
                                @error('reorder_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="purchase-rate">Default Purchase Rate *</label>
                                <input wire:model="default_purchase_rate" type="number" step="0.01" class="form-control @error('default_purchase_rate') is-invalid @enderror" id="purchase-rate" />
                                @error('default_purchase_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="item-desc">Description</label>
                                <textarea wire:model="description" class="form-control" id="item-desc" rows="2" placeholder="Item details..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="item-status">Status *</label>
                                <select wire:model="status" class="form-select" id="item-status">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" wire:click="resetForm" class="btn btn-falcon-default btn-sm">Cancel</button>
                                <button type="submit" class="btn btn-falcon-primary btn-sm">
                                    <span class="fas fa-save me-1"></span>Save Item
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Items Listing Table -->
        <div class="{{ $showForm ? 'col-md-8' : 'col-md-12' }}">
            <div class="card border border-200">
                <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><span class="fas fa-boxes me-2 text-primary"></span>Inventory Catalog</h5>
                    <div class="d-flex align-items-center gap-2">
                        <!-- Category Filter -->
                        <select wire:model.live="filterCategory" class="form-select form-select-sm" style="max-width: 150px;">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>

                        <!-- Search -->
                        <div class="input-group input-group-sm">
                            <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search items..." />
                            <span class="input-group-text"><span class="fas fa-search"></span></span>
                        </div>
                        @if(!$showForm && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory')))
                            <button wire:click="create" class="btn btn-falcon-primary btn-sm text-nowrap">
                                <span class="fas fa-plus me-1"></span>Catalog Item
                            </button>
                        @endif
                    </div>
                </div>

                <div class="card-body p-0">
                    @if(session('success'))
                        <div class="alert alert-success border-2 d-flex align-items-center m-3" role="alert">
                            <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                            <p class="mb-0 flex-1">{{ session('success') }}</p>
                            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="table-responsive scrollbar">
                        <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                            <thead class="bg-200 text-900">
                                <tr>
                                    <th class="px-3" style="width: 120px;">Code</th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Unit</th>
                                    <th>Brand</th>
                                    <th class="text-end" style="width: 120px;">Default Rate</th>
                                    <th class="text-center" style="width: 100px;">Status</th>
                                    <th class="text-end px-3" style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                    <tr>
                                        <td class="px-3 font-monospace fw-bold"><span class="badge badge-subtle-secondary fs-11">{{ $item->item_code }}</span></td>
                                        <td class="fw-semi-bold">{{ $item->name }}</td>
                                        <td>{{ $item->category->name ?? '—' }}</td>
                                        <td><span class="badge bg-light text-dark">{{ $item->unit->short_code ?? 'Pcs' }}</span></td>
                                        <td>{{ $item->brand->name ?? 'Generic' }}</td>
                                        <td class="text-end font-monospace">Rs. {{ number_format($item->default_purchase_rate, 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-subtle-{{ $item->status === 'Active' ? 'success' : 'secondary' }} rounded-pill">
                                                {{ $item->status }}
                                            </span>
                                        </td>
                                        <td class="text-end px-3">
                                            <div class="d-flex justify-content-end gap-2">
                                                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory'))
                                                    <button wire:click="edit({{ $item->id }})" class="btn btn-link p-0" title="Edit">
                                                        <span class="text-primary fas fa-edit"></span>
                                                    </button>
                                                    <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $item->id }})" title="Delete">
                                                        <span class="text-danger fas fa-trash-alt"></span>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No items cataloged.</td>
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
    </div>

    <!-- Delete Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white" id="deleteConfirmModalLabel">
                        <span class="fas fa-exclamation-triangle me-2"></span>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <p class="mb-0 text-900">Are you sure you want to delete this item? This action will soft-delete the item catalog record.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button wire:click="deleteRecord" type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        <span class="fas fa-trash-alt me-1"></span>Delete Item
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
