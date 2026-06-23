<div>
    <div class="row g-3">
        <!-- Sidebar Form to Create/Edit Category -->
        @if($showForm)
            <div class="col-md-4">
                <div class="card border border-200">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">{{ $editId ? 'Edit Category' : 'Create Category' }}</h5>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="save">
                            <div class="mb-3">
                                <label class="form-label" for="category-name">Category Name *</label>
                                <input wire:model="name" class="form-control @error('name') is-invalid @enderror" id="category-name" type="text" placeholder="e.g. Food Items" />
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="parent-id">Parent Category</label>
                                <select wire:model="parent_id" class="form-select @error('parent_id') is-invalid @enderror" id="parent-id">
                                    <option value="">None (Top Level)</option>
                                    @foreach($parentCategories as $parent)
                                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                                    @endforeach
                                </select>
                                @error('parent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="category-desc">Description</label>
                                <textarea wire:model="description" class="form-control" id="category-desc" rows="3" placeholder="Category notes..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="category-status">Status *</label>
                                <select wire:model="status" class="form-select" id="category-status">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" wire:click="resetForm" class="btn btn-falcon-default btn-sm">Cancel</button>
                                <button type="submit" class="btn btn-falcon-primary btn-sm">
                                    <span class="fas fa-save me-1"></span>Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Categories Listing Table -->
        <div class="{{ $showForm ? 'col-md-8' : 'col-md-12' }}">
            <div class="card border border-200">
                <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><span class="fas fa-sitemap me-2 text-primary"></span>Inventory Categories</h5>
                    <div class="d-flex align-items-center gap-2">
                        <!-- Search -->
                        <div class="input-group input-group-sm">
                            <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search categories..." />
                            <span class="input-group-text"><span class="fas fa-search"></span></span>
                        </div>
                        @if(!$showForm && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory')))
                            <button wire:click="create" class="btn btn-falcon-primary btn-sm text-nowrap">
                                <span class="fas fa-plus me-1"></span>Add Category
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
                                    <th class="px-3" style="width: 250px;">Category Name</th>
                                    <th>Parent Category</th>
                                    <th>Description</th>
                                    <th class="text-center" style="width: 100px;">Status</th>
                                    <th class="text-end px-3" style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $cat)
                                    <tr>
                                        <td class="px-3 fw-semi-bold">{{ $cat->name }}</td>
                                        <td>{{ $cat->parent->name ?? '—' }}</td>
                                        <td class="text-muted">{{ $cat->description ?: '—' }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-subtle-{{ $cat->status === 'Active' ? 'success' : 'secondary' }} rounded-pill">
                                                {{ $cat->status }}
                                            </span>
                                        </td>
                                        <td class="text-end px-3">
                                            <div class="d-flex justify-content-end gap-2">
                                                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory'))
                                                    <button wire:click="edit({{ $cat->id }})" class="btn btn-link p-0" title="Edit">
                                                        <span class="text-primary fas fa-edit"></span>
                                                    </button>
                                                    <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $cat->id }})" title="Delete">
                                                        <span class="text-danger fas fa-trash-alt"></span>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No categories found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($categories->hasPages())
                    <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                        {{ $categories->links() }}
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
                    <p class="mb-0 text-900">Are you sure you want to remove this category? Associated items will remain but their category reference will be modified.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button wire:click="deleteRecord" type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        <span class="fas fa-trash-alt me-1"></span>Delete Category
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
