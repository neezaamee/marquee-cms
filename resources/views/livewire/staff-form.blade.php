<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><span class="fas fa-user-plus me-2 text-primary"></span>{{ $isEditMode ? 'Edit Employee Profile' : 'Add New Employee' }}</h5>
            <a class="btn btn-falcon-default btn-sm" href="{{ route('staff.index') }}">
                <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
            </a>
        </div>

        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="row g-3">
                    <!-- General Details Section -->
                    <div class="col-md-6">
                        <label class="form-label" for="name">Full Name *</label>
                        <input wire:model="name" class="form-control @error('name') is-invalid @enderror" id="name" type="text" required placeholder="e.g. Ajmal Khan" />
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="cnic">CNIC / ID Number *</label>
                        <input wire:model="cnic" class="form-control @error('cnic') is-invalid @enderror" id="cnic" type="text" required placeholder="e.g. 35201-1234567-1" />
                        @error('cnic') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="mobile_number">Mobile Number *</label>
                        <input wire:model="mobile_number" class="form-control @error('mobile_number') is-invalid @enderror" id="mobile_number" type="text" required placeholder="e.g. +923001234567" />
                        @error('mobile_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="designation">Designation *</label>
                        <select wire:model="designation" class="form-select @error('designation') is-invalid @enderror" id="designation" required>
                            <option value="">Select Designation...</option>
                            @foreach($designations as $d)
                                <option value="{{ $d }}">{{ $d }}</option>
                            @endforeach
                        </select>
                        @error('designation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="joining_date">Joining Date *</label>
                        <input wire:model="joining_date" class="form-control @error('joining_date') is-invalid @enderror" id="joining_date" type="date" required />
                        @error('joining_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="salary">Monthly Salary (PKR) *</label>
                        <div class="input-group">
                            <span class="input-group-text">PKR</span>
                            <input wire:model="salary" class="form-control @error('salary') is-invalid @enderror" id="salary" type="number" required placeholder="e.g. 45000" />
                            @error('salary') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="employment_type">Employment Type *</label>
                        <select wire:model="employment_type" class="form-select @error('employment_type') is-invalid @enderror" id="employment_type" required>
                            @foreach($employmentTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                        @error('employment_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="branch_id">Assign to Branch *</label>
                        <select wire:model="branch_id" class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" required {{ count($branches) === 1 ? 'disabled' : '' }}>
                            <option value="">Select Branch...</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="status">Employment Status *</label>
                        <select wire:model="status" class="form-select @error('status') is-invalid @enderror" id="status" required>
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Photo Upload -->
                    <div class="col-md-6">
                        <label class="form-label" for="photo">Staff Photo</label>
                        <input wire:model="photo" class="form-control @error('photo') is-invalid @enderror" id="photo" type="file" accept="image/*" />
                        @error('photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div wire:loading wire:target="photo" class="text-primary fs-11 mt-1">
                            <span class="spinner-border spinner-border-sm me-1" role="status"></span>Uploading image...
                        </div>
                    </div>

                    <!-- Preview Photo -->
                    <div class="col-md-6 d-flex align-items-center">
                        @if ($photo)
                            <div>
                                <small class="text-muted d-block mb-1">New Preview:</small>
                                <img src="{{ $photo->temporaryUrl() }}" class="rounded border" width="60" height="60" style="object-fit: cover;">
                            </div>
                        @elseif ($existingPhoto)
                            <div>
                                <small class="text-muted d-block mb-1">Current Photo:</small>
                                <img src="{{ asset('storage/' . $existingPhoto) }}" class="rounded border" width="60" height="60" style="object-fit: cover;">
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a class="btn btn-falcon-default btn-sm" href="{{ route('staff.index') }}">Cancel</a>
                    <button class="btn btn-primary btn-sm" type="submit">
                        <span wire:loading class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        {{ $isEditMode ? 'Update Employee' : 'Save Employee' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
