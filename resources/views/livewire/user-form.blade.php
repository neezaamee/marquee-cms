<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ $isEditMode ? 'Edit CMS User' : 'Add New CMS User' }}</h5>
            <a class="btn btn-falcon-default btn-sm" href="{{ route('users.index') }}">
                <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
            </a>
        </div>

        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="row g-3">
                    <!-- Marquee selector for Super Admins -->
                    @if(auth()->user()->isSuperAdmin())
                        <div class="col-12">
                            <label class="form-label" for="marquee_id">Select Marquee Tenant</label>
                            <select wire:model.live="marquee_id" class="form-select @error('marquee_id') is-invalid @enderror" id="marquee_id">
                                <option value="">Global SaaS Admin (No Marquee Tenant)</option>
                                @foreach($marquees as $marquee)
                                    <option value="{{ $marquee->id }}">{{ $marquee->name }} ({{ $marquee->city }})</option>
                                @endforeach
                            </select>
                            @error('marquee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <!-- Branch selector -->
                    <div class="col-md-6">
                        <label class="form-label" for="branch_id">Assign to Branch</label>
                        <select wire:model="branch_id" class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" {{ empty($branches) && !auth()->user()->isSuperAdmin() ? 'disabled' : '' }}>
                            <option value="">All Branches / Head Office</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->city }})</option>
                            @endforeach
                        </select>
                        @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Role selector -->
                    <div class="col-md-6">
                        <label class="form-label" for="role_id">System Role *</label>
                        <select wire:model="role_id" class="form-select @error('role_id') is-invalid @enderror" id="role_id" required>
                            <option value="">Select Role...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->label }}</option>
                            @endforeach
                        </select>
                        @error('role_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Name -->
                    <div class="col-md-6">
                        <label class="form-label" for="name">Full Name *</label>
                        <input wire:model="name" class="form-control @error('name') is-invalid @enderror" id="name" type="text" required placeholder="e.g. Asif Mehmood" />
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email Address *</label>
                        <input wire:model="email" class="form-control @error('email') is-invalid @enderror" id="email" type="email" required placeholder="e.g. manager.lh@royalmarquee.com" />
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Password -->
                    <div class="col-md-6">
                        <label class="form-label" for="password">Password {{ $isEditMode ? '(Leave blank to keep current)' : '*' }}</label>
                        <input wire:model="password" class="form-control @error('password') is-invalid @enderror" id="password" type="password" placeholder="Password min 6 chars" />
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Phone -->
                    <div class="col-md-3">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input wire:model="phone" class="form-control @error('phone') is-invalid @enderror" id="phone" type="text" placeholder="e.g. +923007654321" />
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Status -->
                    <div class="col-md-3">
                        <label class="form-label" for="status">Account Status *</label>
                        <select wire:model="status" class="form-select @error('status') is-invalid @enderror" id="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a class="btn btn-falcon-default btn-sm" href="{{ route('users.index') }}">Cancel</a>
                    <button class="btn btn-primary btn-sm" type="submit">
                        <span wire:loading class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        {{ $isEditMode ? 'Update User' : 'Save User' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
