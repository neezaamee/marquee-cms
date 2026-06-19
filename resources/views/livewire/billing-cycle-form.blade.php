<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ $isEditMode ? 'Edit Billing Cycle: ' . $cycle_name : 'Define New Billing Cycle' }}</h5>
            <a href="{{ route('billing-cycles.index') }}" class="btn btn-falcon-default btn-sm">
                <span class="fas fa-arrow-left me-1"></span> Cancel
            </a>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="cycle_name">Cycle Name <span class="text-danger">*</span></label>
                        <input wire:model="cycle_name" class="form-control @error('cycle_name') is-invalid @enderror" id="cycle_name" type="text" placeholder="e.g. Monthly, Annually" />
                        @error('cycle_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="duration_in_months">Duration (In Months) <span class="text-danger">*</span></label>
                        <input wire:model="duration_in_months" class="form-control @error('duration_in_months') is-invalid @enderror" id="duration_in_months" type="number" min="1" placeholder="e.g. 1, 12" />
                        @error('duration_in_months') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="discount_percentage">Discount Percentage (%) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input wire:model="discount_percentage" class="form-control @error('discount_percentage') is-invalid @enderror" id="discount_percentage" type="number" step="0.01" min="0" max="100" />
                            <span class="input-group-text">%</span>
                        </div>
                        @error('discount_percentage') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
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
                            <span class="fas fa-save me-1"></span> Save Cycle
                        </button>
                        <a href="{{ route('billing-cycles.index') }}" class="btn btn-falcon-default btn-sm px-3">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
