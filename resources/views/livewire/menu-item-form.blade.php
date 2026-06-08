<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <span class="fas fa-utensils me-2 text-primary"></span>
                {{ $isEditMode ? 'Edit Menu Item' : 'Add New Menu Item' }}
            </h5>
            <a class="btn btn-falcon-default btn-sm" href="{{ route('menu-items.index') }}">
                <span class="fas fa-chevron-left me-1"></span> Back
            </a>
        </div>

        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="row g-3">
                    
                    <!-- Item Name -->
                    <div class="col-md-6">
                        <label class="form-label" for="item_name">Menu Item Name *</label>
                        <input wire:model="item_name" class="form-control @error('item_name') is-invalid @enderror" id="item_name" type="text" required placeholder="e.g. Chicken Biryani, Seekh Kabab, Shahi Kheer" />
                        @error('item_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Item Code -->
                    <div class="col-md-6">
                        <label class="form-label" for="item_code">Menu Item Code *</label>
                        <input wire:model="item_code" class="form-control @error('item_code') is-invalid @enderror" id="item_code" type="text" required placeholder="e.g. BIRY-CH, KBB-SK, KHR-SH" />
                        @error('item_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text fs-11 text-500">Short unique code identifying this item.</div>
                    </div>

                    <!-- Category -->
                    <div class="col-md-6">
                        <label class="form-label" for="category_id">Category *</label>
                        <select wire:model="category_id" class="form-select @error('category_id') is-invalid @enderror" id="category_id" required>
                            <option value="">Select Category...</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Unit of Measure -->
                    <div class="col-md-6">
                        <label class="form-label" for="unit">Serving Unit *</label>
                        <select wire:model="unit" class="form-select @error('unit') is-invalid @enderror" id="unit" required>
                            <option value="Per Plate">Per Plate</option>
                            <option value="Per Head">Per Head</option>
                            <option value="Per Serving">Per Serving</option>
                            <option value="KG">KG</option>
                            <option value="Liter">Liter</option>
                            <option value="Dozen">Dozen</option>
                        </select>
                        @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Base Cost (Est Ingredient Cost) -->
                    <div class="col-md-6">
                        <label class="form-label" for="base_cost">Estimated Base Cost (PKR)</label>
                        <div class="input-group">
                            <span class="input-group-text">PKR</span>
                            <input wire:model="base_cost" class="form-control @error('base_cost') is-invalid @enderror" id="base_cost" type="number" step="0.01" placeholder="e.g. 150" />
                            @error('base_cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text fs-11 text-500">Estimate of ingredient/procurement cost before markups.</div>
                    </div>

                    <!-- Selling Price -->
                    <div class="col-md-6">
                        <label class="form-label" for="selling_price">Selling Price / Plate Addition (PKR) *</label>
                        <div class="input-group">
                            <span class="input-group-text">PKR</span>
                            <input wire:model="selling_price" class="form-control @error('selling_price') is-invalid @enderror" id="selling_price" type="number" step="0.01" required placeholder="e.g. 300" />
                            @error('selling_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <!-- Image Upload -->
                    <div class="col-md-6">
                        <label class="form-label" for="image">Menu Item Photo</label>
                        <input wire:model="image" class="form-control @error('image') is-invalid @enderror" id="image" type="file" accept="image/*" />
                        @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text fs-11 text-500">Supported formats: JPG, PNG, WebP. Max size: 2MB.</div>

                        <!-- Upload Live Preview -->
                        <div class="mt-3">
                            @if($image)
                                <span class="d-block text-secondary fs-11 mb-1">New Image Preview:</span>
                                <img src="{{ $image->temporaryUrl() }}" class="img-thumbnail rounded" style="max-height: 100px;">
                            @elseif($existingImage)
                                <span class="d-block text-secondary fs-11 mb-1">Current Image:</span>
                                <img src="{{ \Storage::url($existingImage) }}" class="img-thumbnail rounded" style="max-height: 100px;">
                            @endif
                        </div>
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
                        <label class="form-label" for="description">Description / Serving Detail</label>
                        <textarea wire:model="description" class="form-control @error('description') is-invalid @enderror" id="description" rows="3" placeholder="Specify serving style, garnishes, or any allergy/dietary notes..."></textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a class="btn btn-falcon-default btn-sm" href="{{ route('menu-items.index') }}">Cancel</a>
                    <button class="btn btn-primary btn-sm" type="submit">
                        <span wire:loading class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        {{ $isEditMode ? 'Update Menu Item' : 'Save Menu Item' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
