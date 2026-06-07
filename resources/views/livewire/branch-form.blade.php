<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ $isEditMode ? 'Edit Branch' : 'Add New Branch' }}</h5>
            <a class="btn btn-falcon-default btn-sm" href="{{ route('branches.index') }}">
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

                    <!-- Name -->
                    <div class="col-md-6">
                        <label class="form-label" for="name">Branch Name *</label>
                        <input wire:model="name" class="form-control @error('name') is-invalid @enderror" id="name" type="text" required placeholder="e.g. Lahore Gulberg Branch" />
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Phone -->
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Phone Number *</label>
                        <input wire:model="phone" class="form-control @error('phone') is-invalid @enderror" id="phone" type="text" required placeholder="e.g. +923001234567" />
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Address -->
                    <div class="col-12">
                        <label class="form-label" for="address">Address *</label>
                        <input wire:model="address" class="form-control @error('address') is-invalid @enderror" id="address" type="text" required placeholder="e.g. 12-A, Main Boulevard, Gulberg III" />
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Province -->
                    <div class="col-md-4">
                        <label class="form-label" for="province">Province *</label>
                        <select wire:model.live="province" class="form-select @error('province') is-invalid @enderror" id="province" required>
                            <option value="">Select Province...</option>
                            <option value="Punjab">Punjab</option>
                            <option value="Sindh">Sindh</option>
                            <option value="Khyber Pakhtunkhwa">Khyber Pakhtunkhwa</option>
                            <option value="Balochistan">Balochistan</option>
                            <option value="Islamabad Capital Territory">Islamabad Capital Territory</option>
                        </select>
                        @error('province') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- City -->
                    <div class="col-md-4">
                        <label class="form-label" for="city">City *</label>
                        <select wire:model="city" class="form-select @error('city') is-invalid @enderror" id="city" required {{ empty($cities) ? 'disabled' : '' }}>
                            <option value="">Select City...</option>
                            @foreach($cities as $c)
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endforeach
                        </select>
                        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Status -->
                    <div class="col-md-4">
                        <label class="form-label" for="status">Status *</label>
                        <select wire:model="status" class="form-select @error('status') is-invalid @enderror" id="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row navbar-vertical-label-wrapper mt-4 mb-2">
                        <div class="col-auto navbar-vertical-label text-primary">FBR POS Integration Settings (Optional)</div>
                        <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
                    </div>

                    <!-- FBR POS ID -->
                    <div class="col-md-5">
                        <label class="form-label" for="fbr_pos_id">FBR POS Device ID</label>
                        <input wire:model="fbr_pos_id" class="form-control @error('fbr_pos_id') is-invalid @enderror" id="fbr_pos_id" type="text" placeholder="e.g. PRA-LHR-GUL-01" />
                        @error('fbr_pos_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- FBR POS Key -->
                    <div class="col-md-5">
                        <label class="form-label" for="fbr_pos_key">POS Authorization Key</label>
                        <input wire:model="fbr_pos_key" class="form-control @error('fbr_pos_key') is-invalid @enderror" id="fbr_pos_key" type="password" placeholder="Key / Token secret" />
                        @error('fbr_pos_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- FBR Sandbox Mode -->
                    <div class="col-md-2 d-flex align-items-center mt-md-4">
                        <div class="form-check mb-0">
                            <input wire:model="fbr_sandbox_mode" class="form-check-input" type="checkbox" id="fbr_sandbox_mode" />
                            <label class="form-check-label mb-0" for="fbr_sandbox_mode">Sandbox Mode</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a class="btn btn-falcon-default btn-sm" href="{{ route('branches.index') }}">Cancel</a>
                    <button class="btn btn-primary btn-sm" type="submit">
                        <span wire:loading class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        {{ $isEditMode ? 'Update Branch' : 'Save Branch' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
