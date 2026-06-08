<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <span class="fas fa-cubes me-2 text-primary"></span>
                {{ $isEditMode ? 'Edit Package Details' : 'Add New Event Package' }}
            </h5>
            <a class="btn btn-falcon-default btn-sm" href="{{ route('packages.index') }}">
                <span class="fas fa-chevron-left me-1"></span> Back
            </a>
        </div>

        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="row g-3">
                    
                    <!-- Package Name -->
                    <div class="col-md-6">
                        <label class="form-label" for="package_name">Package Name *</label>
                        <input wire:model="package_name" class="form-control @error('package_name') is-invalid @enderror" id="package_name" type="text" required placeholder="e.g. Gold Wedding Package, Executive Corporate Dinner" />
                        @error('package_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Package Code -->
                    <div class="col-md-6">
                        <label class="form-label" for="package_code">Package Code *</label>
                        <input wire:model="package_code" class="form-control @error('package_code') is-invalid @enderror" id="package_code" type="text" required placeholder="e.g. PKG-GLD, PKG-CORP" />
                        @error('package_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text fs-11 text-500">Short unique code identifying this package.</div>
                    </div>

                    <!-- Package Type / Tier -->
                    <div class="col-md-4">
                        <label class="form-label" for="package_type">Tier Level *</label>
                        <select wire:model="package_type" class="form-select @error('package_type') is-invalid @enderror" id="package_type" required>
                            <option value="Silver">Silver</option>
                            <option value="Gold">Gold</option>
                            <option value="Platinum">Platinum</option>
                            <option value="VIP">VIP</option>
                            <option value="Custom">Custom</option>
                        </select>
                        @error('package_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Min Guests -->
                    <div class="col-md-4">
                        <label class="form-label" for="minimum_guests">Minimum Guests Requirement *</label>
                        <input wire:model="minimum_guests" class="form-control @error('minimum_guests') is-invalid @enderror" id="minimum_guests" type="number" min="0" required placeholder="e.g. 150" />
                        @error('minimum_guests') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Max Guests -->
                    <div class="col-md-4">
                        <label class="form-label" for="maximum_guests">Maximum Guests (Optional)</label>
                        <input wire:model="maximum_guests" class="form-control @error('maximum_guests') is-invalid @enderror" id="maximum_guests" type="number" min="0" placeholder="e.g. 500" />
                        @error('maximum_guests') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Base Price (Flat Addition) -->
                    <div class="col-md-6">
                        <label class="form-label" for="base_price">Flat Base setup Cost (PKR)</label>
                        <div class="input-group">
                            <span class="input-group-text">PKR</span>
                            <input wire:model="base_price" class="form-control @error('base_price') is-invalid @enderror" id="base_price" type="number" step="0.01" placeholder="e.g. 50000" />
                            @error('base_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text fs-11 text-500">Flat/fixed fee added to total booking cost (optional).</div>
                    </div>

                    <!-- Per Plate Price -->
                    <div class="col-md-6">
                        <label class="form-label" for="per_plate_price">Per Plate Price (PKR) *</label>
                        <div class="input-group">
                            <span class="input-group-text">PKR</span>
                            <input wire:model="per_plate_price" class="form-control @error('per_plate_price') is-invalid @enderror" id="per_plate_price" type="number" step="0.01" required placeholder="e.g. 1950" />
                            @error('per_plate_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text fs-11 text-500">Variable plate cost multiplied by guest count.</div>
                    </div>

                    <!-- Seasonal Checkbox -->
                    <div class="col-12 mt-4">
                        <div class="form-check form-switch">
                            <input wire:model.live="seasonal_package" class="form-check-input" type="checkbox" id="seasonal_package">
                            <label class="form-check-label fw-semi-bold" for="seasonal_package">This is a Seasonal Pricing Package</label>
                        </div>
                        <div class="form-text fs-11 text-500">Seasonal packages automatically expire outside their defined date range.</div>
                    </div>

                    <!-- Seasonal Dates -->
                    @if($seasonal_package)
                        <div class="col-md-6">
                            <label class="form-label" for="season_start_date">Season Start Date *</label>
                            <input wire:model="season_start_date" class="form-control @error('season_start_date') is-invalid @enderror" id="season_start_date" type="date" required />
                            @error('season_start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="season_end_date">Season End Date *</label>
                            <input wire:model="season_end_date" class="form-control @error('season_end_date') is-invalid @enderror" id="season_end_date" type="date" required />
                            @error('season_end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <!-- Status -->
                    <div class="col-md-12">
                        <label class="form-label fw-semi-bold d-block">Status *</label>
                        <div class="form-check form-check-inline">
                            <input wire:model="status" class="form-check-input" type="radio" name="status" id="status_draft" value="Draft">
                            <label class="form-check-label cursor-pointer text-info" for="status_draft">Draft</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input wire:model="status" class="form-check-input" type="radio" name="status" id="status_active" value="Active">
                            <label class="form-check-label cursor-pointer text-success" for="status_active">Active</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input wire:model="status" class="form-check-input" type="radio" name="status" id="status_inactive" value="Inactive">
                            <label class="form-check-label cursor-pointer text-secondary" for="status_inactive">Inactive</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input wire:model="status" class="form-check-input" type="radio" name="status" id="status_archived" value="Archived">
                            <label class="form-check-label cursor-pointer text-warning" for="status_archived">Archived</label>
                        </div>
                        @error('status') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <label class="form-label" for="description">Description / Event Terms</label>
                        <textarea wire:model="description" class="form-control @error('description') is-invalid @enderror" id="description" rows="4" placeholder="Detail package terms, inclusions (decoration, sound system, waiter service), or fine print rules..."></textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a class="btn btn-falcon-default btn-sm" href="{{ route('packages.index') }}">Cancel</a>
                    <button class="btn btn-primary btn-sm" type="submit">
                        <span wire:loading class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        {{ $isEditMode ? 'Update Package' : 'Save & Continue to Builder' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
