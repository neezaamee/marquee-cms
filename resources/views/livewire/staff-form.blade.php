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

                    <!-- CMS Login Account Details -->
                    <div class="row navbar-vertical-label-wrapper mt-4 mb-2">
                        <div class="col-auto navbar-vertical-label text-primary">CMS Login Account</div>
                        <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch mb-0">
                            <input wire:model.live="enable_login" class="form-check-input cursor-pointer" type="checkbox" id="enable_login" />
                            <label class="form-check-label mb-0 fw-semi-bold cursor-pointer" for="enable_login">Enable CMS login credentials for this staff member</label>
                        </div>
                    </div>

                    @if($enable_login)
                        <!-- CMS Login Email -->
                        <div class="col-md-4">
                            <label class="form-label" for="login_email">Login Email *</label>
                            <input wire:model="login_email" class="form-control @error('login_email') is-invalid @enderror" id="login_email" type="email" required placeholder="e.g. staff.ajmal@royalmarquee.com" />
                            @error('login_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- CMS Login Password -->
                        <div class="col-md-4">
                            <label class="form-label" for="login_password">Password {{ $linkedUserId ? '(Leave blank to keep current)' : '*' }}</label>
                            <input wire:model="login_password" class="form-control @error('login_password') is-invalid @enderror" id="login_password" type="password" placeholder="Password min 6 chars" />
                            @error('login_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- CMS Login Role -->
                        <div class="col-md-4">
                            <label class="form-label" for="login_role_id">Login Role *</label>
                            <select wire:model="login_role_id" class="form-select @error('login_role_id') is-invalid @enderror" id="login_role_id" required>
                                <option value="">Select Role...</option>
                                @foreach($roles as $r)
                                    <option value="{{ $r->id }}">{{ $r->label }}</option>
                                @endforeach
                            </select>
                            @error('login_role_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif
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
