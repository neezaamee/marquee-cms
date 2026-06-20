<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <span class="fas fa-list me-2 text-primary"></span>
                {{ $isEditMode ? 'Edit Menu Category' : 'Add New Menu Category' }}
            </h5>
            <a class="btn btn-falcon-default btn-sm" href="{{ route('menu-categories.index') }}">
                <span class="fas fa-chevron-left me-1"></span> Back
            </a>
        </div>

        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="row g-3">
                    <!-- Category Name -->
                    <div class="col-md-6">
                        <label class="form-label" for="category_name">Category Name *</label>
                        <input wire:model="category_name" class="form-control @error('category_name') is-invalid @enderror" id="category_name" type="text" required placeholder="e.g. Main Course, Desserts, BBQ" />
                        @error('category_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Category Code -->
                    <div class="col-md-6">
                        <label class="form-label" for="category_code">Category Code *</label>
                        <input wire:model="category_code" class="form-control @error('category_code') is-invalid @enderror" id="category_code" type="text" required placeholder="e.g. MC, DES, BBQ" />
                        @error('category_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text fs-11 text-500">Short unique code identifying the category.</div>
                    </div>

                    <!-- Sort Order -->
                    <div class="col-md-6">
                        <label class="form-label" for="sort_order">Sort Order</label>
                        <input wire:model="sort_order" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" type="number" min="0" placeholder="e.g. 0" />
                        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Status -->
                    <div class="col-md-6">
                        <label class="form-label fw-semi-bold d-block">Status *</label>
                        <div class="form-check form-check-inline">
                            <input wire:model="status" class="form-check-input" type="radio" name="status" id="status_active" value="Active">
                            <label class="form-check-label cursor-pointer text-success" for="status_active">Active</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input wire:model="status" class="form-check-input" type="radio" name="status" id="status_inactive" value="Inactive">
                            <label class="form-check-label cursor-pointer text-muted" for="status_inactive">Inactive</label>
                        </div>
                        @error('status') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <textarea wire:model="description" class="form-control @error('description') is-invalid @enderror" id="description" rows="4" placeholder="Brief description of the menu items included in this category..."></textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a class="btn btn-falcon-default btn-sm" href="{{ route('menu-categories.index') }}">Cancel</a>
                    <button class="btn btn-primary btn-sm" type="submit">
                        <span wire:loading class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        {{ $isEditMode ? 'Update Category' : 'Save Category' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
