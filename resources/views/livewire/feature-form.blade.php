<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ $isEditMode ? 'Edit Feature: ' . $feature_name : 'Define New Plan Feature' }}</h5>
            <a href="{{ route('plan-features.index') }}" class="btn btn-falcon-default btn-sm">
                <span class="fas fa-arrow-left me-1"></span> Cancel
            </a>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="feature_name">Feature Name <span class="text-danger">*</span></label>
                        <input wire:model.blur="feature_name" class="form-control @error('feature_name') is-invalid @enderror" id="feature_name" type="text" placeholder="e.g. FBR POS Integration" />
                        @error('feature_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="feature_key">Feature Key <span class="text-danger">*</span></label>
                        <input wire:model="feature_key" class="form-control @error('feature_key') is-invalid @enderror" id="feature_key" type="text" placeholder="e.g. fbr_pos_integration" />
                        <small class="text-muted">Unique identifier used for code-level capability checks.</small>
                        @error('feature_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <textarea wire:model="description" class="form-control @error('description') is-invalid @enderror" id="description" rows="3" placeholder="Describe what this feature provides..."></textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                        <select wire:model="status" class="form-select @error('status') is-invalid @enderror" id="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 mt-4">
                        <button class="btn btn-primary btn-sm px-4 me-2" type="submit">
                            <span class="fas fa-save me-1"></span> Save Feature
                        </button>
                        <a href="{{ route('plan-features.index') }}" class="btn btn-falcon-default btn-sm px-3">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
