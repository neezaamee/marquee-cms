<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <span class="fas fa-puzzle-piece me-2 text-primary"></span>
                {{ $isEditMode ? 'Edit Add-on' : 'Add New Add-on' }}
            </h5>
            <a class="btn btn-falcon-default btn-sm" href="{{ route('extra-services.index') }}">
                <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
            </a>
        </div>

        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="row g-3">
                    
                    <!-- Service Name -->
                    <div class="col-md-6">
                        <label class="form-label" for="service_name">Add-on / Service Name *</label>
                        <input wire:model="service_name" class="form-control @error('service_name') is-invalid @enderror" id="service_name" type="text" required placeholder="e.g. DJ Sound System, Extra AC / Backup Generator, Premium Stage Decor" />
                        @error('service_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Default Price -->
                    <div class="col-md-6">
                        <label class="form-label" for="default_price">Default Price (PKR) *</label>
                        <div class="input-group">
                            <span class="input-group-text">PKR</span>
                            <input wire:model="default_price" class="form-control @error('default_price') is-invalid @enderror" id="default_price" type="number" step="0.01" required placeholder="e.g. 25000" />
                            @error('default_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="col-md-12">
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

                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a class="btn btn-falcon-default btn-sm" href="{{ route('extra-services.index') }}">Cancel</a>
                    <button class="btn btn-primary btn-sm" type="submit">
                        <span wire:loading class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        {{ $isEditMode ? 'Update Add-on' : 'Save Add-on' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
