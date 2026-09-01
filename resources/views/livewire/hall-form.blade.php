<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ $isEditMode ? 'Edit Hall' : 'Add New Hall' }}</h5>
            <a class="btn btn-falcon-default btn-sm" href="{{ route('halls.index') }}">
                <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
            </a>
        </div>
        
        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="row g-3">
                    <!-- Marquee selector for Super Admins -->
                    @if(auth()->user()->isSuperAdmin())
                        <div class="col-12">
                            <label class="form-label" for="marquee_id">Select Marquee Tenant *</label>
                            <select wire:model.live="marquee_id" class="form-select @error('marquee_id') is-invalid @enderror" id="marquee_id" required>
                                <option value="">Select a marquee tenant...</option>
                                @foreach($marquees as $marquee)
                                    <option value="{{ $marquee->id }}">{{ $marquee->name }} ({{ $marquee->city }})</option>
                                @endforeach
                            </select>
                            @error('marquee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <!-- Branch Selector -->
                    <div class="col-md-6">
                        <label class="form-label" for="branch_id">Select Branch *</label>
                        <select wire:model.live="branch_id" class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" required {{ (auth()->user()->branch_id && !auth()->user()->isBusinessOwner() && !auth()->user()->isSuperAdmin()) ? 'disabled' : '' }}>
                            <option value="">Select a branch...</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->city }})</option>
                            @endforeach
                        </select>
                        @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if(auth()->user()->branch_id && !auth()->user()->isBusinessOwner() && !auth()->user()->isSuperAdmin())
                            <small class="text-muted">Locked to your assigned branch.</small>
                        @endif
                    </div>

                    <!-- Hall Name -->
                    <div class="col-md-6">
                        <label class="form-label" for="hall_name">Hall Name *</label>
                        <input wire:model="hall_name" class="form-control @error('hall_name') is-invalid @enderror" id="hall_name" type="text" required placeholder="e.g. Royal Banquet Hall A" />
                        @error('hall_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Hall Code -->
                    <div class="col-md-4">
                        <label class="form-label" for="hall_code">Hall Code *</label>
                        <input wire:model="hall_code" class="form-control @error('hall_code') is-invalid @enderror" id="hall_code" type="text" required placeholder="e.g. HALL-A" />
                        @error('hall_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="text-muted">Must be unique within the selected branch.</small>
                    </div>

                    <!-- Capacity -->
                    <div class="col-md-4">
                        <label class="form-label" for="capacity">Guest Capacity *</label>
                        <input wire:model="capacity" class="form-control @error('capacity') is-invalid @enderror" id="capacity" type="number" required placeholder="e.g. 500" />
                        @error('capacity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Hall Type -->
                    <div class="col-md-4">
                        <label class="form-label" for="hall_type">Hall Type *</label>
                        <select wire:model="hall_type" class="form-select @error('hall_type') is-invalid @enderror" id="hall_type" required>
                            <option value="">Select type...</option>
                            <option value="Marquee">Marquee</option>
                            <option value="Banquet Hall">Banquet Hall</option>
                            <option value="Open Lawn">Open Lawn</option>
                            <option value="Seminar Room">Seminar Room</option>
                            <option value="Other">Other</option>
                        </select>
                        @error('hall_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Default Booking Price -->
                    <div class="col-md-6">
                        <label class="form-label" for="default_booking_price">Default Booking Price (PKR) *</label>
                        <div class="input-group">
                            <span class="input-group-text">PKR</span>
                            <input wire:model="default_booking_price" class="form-control @error('default_booking_price') is-invalid @enderror" id="default_booking_price" type="number" step="0.01" required placeholder="e.g. 150000.00" />
                            @error('default_booking_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="col-md-6">
                        <label class="form-label" for="status">Status *</label>
                        <select wire:model="status" class="form-select @error('status') is-invalid @enderror" id="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <textarea wire:model="description" class="form-control @error('description') is-invalid @enderror" id="description" rows="3" placeholder="Provide hall details, dimensions, special features..."></textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a class="btn btn-falcon-default btn-sm" href="{{ route('halls.index') }}">Cancel</a>
                    <button class="btn btn-primary btn-sm" type="submit">
                        <span wire:loading class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        {{ $isEditMode ? 'Update Hall' : 'Save Hall' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
