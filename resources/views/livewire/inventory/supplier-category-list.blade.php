<div>
    <!-- Header Banner -->
    <div class="card mb-3 border border-200">
        <div class="card-body py-3 px-4 bg-light">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 text-primary fw-bold">
                        <span class="fas fa-tags me-2"></span>Supplier Categories Master
                    </h5>
                    <p class="fs-11 text-600 mb-0">Classify raw material, food, and procurement suppliers into multi-assignment supply categories.</p>
                </div>
                <div class="d-flex gap-2">
                    <button wire:click="seedDefaults" class="btn btn-falcon-default btn-sm">
                        <span class="fas fa-sync-alt me-1"></span>Sync Standard Defaults
                    </button>
                    @if(!$showForm)
                        <button wire:click="create" class="btn btn-falcon-primary btn-sm">
                            <span class="fas fa-plus me-1"></span>New Category
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Metric Summary Cards -->
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-start border-primary border-4 shadow-none">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase fs-11 fw-bold text-500">Total Categories</div>
                            <div class="fs-17 fw-bolder font-monospace text-primary">{{ $totalCount }}</div>
                            <div class="fs-11 text-500 mt-1">Master procurement types</div>
                        </div>
                        <div class="avatar avatar-l bg-subtle-primary rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-boxes text-primary fs-8"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-start border-success border-4 shadow-none">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase fs-11 fw-bold text-500">Active Categories</div>
                            <div class="fs-17 fw-bolder font-monospace text-success">{{ $activeCount }}</div>
                            <div class="fs-11 text-success mt-1">Available for assignment</div>
                        </div>
                        <div class="avatar avatar-l bg-subtle-success rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-check-circle text-success fs-8"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-start border-info border-4 shadow-none">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase fs-11 fw-bold text-500">Categories In Use</div>
                            <div class="fs-17 fw-bolder font-monospace text-info">{{ $assignedCount }}</div>
                            <div class="fs-11 text-500 mt-1">Assigned to suppliers</div>
                        </div>
                        <div class="avatar avatar-l bg-subtle-info rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-truck text-info fs-8"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-start border-secondary border-4 shadow-none">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase fs-11 fw-bold text-500">Inactive Categories</div>
                            <div class="fs-17 fw-bolder font-monospace text-secondary">{{ $inactiveCount }}</div>
                            <div class="fs-11 text-muted mt-1">Hidden from selection</div>
                        </div>
                        <div class="avatar avatar-l bg-subtle-secondary rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-pause-circle text-secondary fs-8"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success border-2 d-flex align-items-center mb-3" role="alert">
            <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
            <p class="mb-0 flex-1">{{ session('success') }}</p>
            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-2 d-flex align-items-center mb-3" role="alert">
            <div class="bg-danger me-3 icon-item"><span class="fas fa-exclamation-triangle text-white fs-8"></span></div>
            <p class="mb-0 flex-1">{{ session('error') }}</p>
            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3">
        <!-- Create / Edit Form Sidebar -->
        @if($showForm)
            <div class="col-md-4">
                <div class="card border border-200">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">{{ $editId ? 'Edit Supplier Category' : 'New Supplier Category' }}</h6>
                        <button wire:click="resetForm" class="btn-close btn-sm" type="button"></button>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="save">
                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold text-600" for="cat-name">Category Name *</label>
                                <input wire:model="name" class="form-control form-control-sm @error('name') is-invalid @enderror" id="cat-name" type="text" placeholder="e.g. Meat & Poultry, Grocery" />
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold text-600" for="cat-code">Category Code *</label>
                                <input wire:model="code" class="form-control form-control-sm font-monospace text-uppercase @error('code') is-invalid @enderror" id="cat-code" type="text" placeholder="e.g. SC-MEAT" />
                                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-500 fs-11">Unique identifier code for reports and grouping.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold text-600" for="cat-desc">Description</label>
                                <textarea wire:model="description" class="form-control form-control-sm @error('description') is-invalid @enderror" id="cat-desc" rows="3" placeholder="Category coverage and notes..."></textarea>
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fs-11 fw-bold text-600" for="cat-status">Status *</label>
                                    <select wire:model="status" class="form-select form-select-sm" id="cat-status">
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fs-11 fw-bold text-600" for="cat-sort">Sort Order</label>
                                    <input wire:model="sort_order" class="form-control form-control-sm" id="cat-sort" type="number" min="0" />
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" wire:click="resetForm" class="btn btn-falcon-default btn-sm">Cancel</button>
                                <button type="submit" class="btn btn-falcon-primary btn-sm">
                                    <span class="fas fa-save me-1"></span>Save Category
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Categories Data Table -->
        <div class="{{ $showForm ? 'col-md-8' : 'col-md-12' }}">
            <div class="card border border-200">
                <div class="card-header bg-light py-2">
                    <div class="row g-2 align-items-center justify-content-between">
                        <div class="col-auto">
                            <h6 class="mb-0 fw-bold"><span class="fas fa-list me-2 text-primary"></span>Category Directory</h6>
                        </div>
                        <div class="col-auto d-flex align-items-center gap-2">
                            <!-- Status Filter -->
                            <select wire:model.live="statusFilter" class="form-select form-select-sm" style="width: 140px;">
                                <option value="all">All Statuses</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>

                            <!-- Search -->
                            <div class="input-group input-group-sm" style="width: 220px;">
                                <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search categories..." />
                                <span class="input-group-text bg-white"><span class="fas fa-search text-400"></span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive scrollbar">
                        <table class="table table-sm table-striped fs-11 mb-0 align-middle">
                            <thead class="bg-200 text-900">
                                <tr>
                                    <th class="ps-3" style="width: 120px;">Code</th>
                                    <th>Category Name</th>
                                    <th>Description</th>
                                    <th class="text-center" style="width: 130px;">Suppliers Count</th>
                                    <th class="text-center" style="width: 80px;">Sort</th>
                                    <th class="text-center" style="width: 100px;">Status</th>
                                    <th class="text-end pe-3" style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $cat)
                                    <tr>
                                        <td class="ps-3 font-monospace fw-bold">
                                            <span class="badge badge-subtle-secondary fs-11">{{ $cat->code }}</span>
                                        </td>
                                        <td class="fw-semi-bold text-900">{{ $cat->name }}</td>
                                        <td class="text-600">{{ $cat->description ?: '—' }}</td>
                                        <td class="text-center">
                                            @if($cat->suppliers_count > 0)
                                                <span class="badge badge-subtle-primary rounded-pill px-2">
                                                    <span class="fas fa-truck me-1"></span>{{ $cat->suppliers_count }}
                                                </span>
                                            @else
                                                <span class="badge badge-subtle-secondary rounded-pill px-2">0</span>
                                            @endif
                                        </td>
                                        <td class="text-center text-muted font-monospace">{{ $cat->sort_order }}</td>
                                        <td class="text-center">
                                            <button wire:click="toggleStatus({{ $cat->id }})" class="btn btn-link p-0 text-decoration-none" title="Click to toggle status">
                                                <span class="badge badge-subtle-{{ $cat->status === 'Active' ? 'success' : 'danger' }} rounded-pill">
                                                    {{ $cat->status }}
                                                </span>
                                            </button>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="d-flex justify-content-end gap-2">
                                                <button wire:click="edit({{ $cat->id }})" class="btn btn-link p-0" title="Edit">
                                                    <span class="text-primary fas fa-edit"></span>
                                                </button>
                                                <button wire:click="confirmDeletion({{ $cat->id }})" class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#deleteCatModal" title="Delete">
                                                    <span class="text-danger fas fa-trash-alt"></span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <span class="fas fa-boxes me-1"></span>No supplier categories found. Click "Sync Standard Defaults" or "New Category" to begin.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($categories->hasPages())
                    <div class="card-footer d-flex align-items-center justify-content-center bg-light py-2">
                        {{ $categories->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="deleteCatModal" tabindex="-1" aria-labelledby="deleteCatModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header bg-danger text-white py-2">
                    <h6 class="modal-title text-white" id="deleteCatModalLabel">
                        <span class="fas fa-exclamation-triangle me-2"></span>Confirm Category Deletion
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start py-3">
                    <p class="mb-0 text-900">Are you sure you want to delete this supplier category? Categories assigned to active suppliers cannot be deleted.</p>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button wire:click="deleteRecord" type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        <span class="fas fa-trash-alt me-1"></span>Delete Category
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
