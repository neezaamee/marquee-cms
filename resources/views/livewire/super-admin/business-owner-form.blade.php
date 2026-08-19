<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ $isEditMode ? 'Edit Business Owner' : 'Add New Business Owner' }}</h5>
            <a class="btn btn-falcon-default btn-sm" href="{{ route('super-admin.business-owners') }}">
                <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
            </a>
        </div>

        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="row g-3">
                    <!-- Name -->
                    <div class="col-md-6">
                        <label class="form-label" for="name">Full Name *</label>
                        <input wire:model="name" class="form-control @error('name') is-invalid @enderror" id="name" type="text" required placeholder="e.g. Ali Khan" />
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email Address *</label>
                        <input wire:model="email" class="form-control @error('email') is-invalid @enderror" id="email" type="email" required placeholder="e.g. owner@brand.com" />
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Username -->
                    <div class="col-md-6">
                        <label class="form-label" for="username">Username *</label>
                        <input wire:model="username" class="form-control @error('username') is-invalid @enderror" id="username" type="text" required placeholder="e.g. alikhan_owner" />
                        @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Password -->
                    <div class="col-md-6">
                        <label class="form-label" for="password">Password {{ $isEditMode ? '(Leave blank to keep current)' : '*' }}</label>
                        <input wire:model="password" class="form-control @error('password') is-invalid @enderror" id="password" type="password" placeholder="Password min 8 chars" />
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Phone -->
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input wire:model="phone" class="form-control @error('phone') is-invalid @enderror" id="phone" type="text" placeholder="e.g. +923001234567" />
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Status -->
                    <div class="col-md-6">
                        <label class="form-label" for="status">Account Status *</label>
                        <select wire:model="status" class="form-select @error('status') is-invalid @enderror" id="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12"><hr class="my-2"></div>
                    
                    <h5 class="mb-0 fs-9 text-primary">SaaS Subscription Details</h5>

                    <!-- Subscription Plan -->
                    <div class="col-md-6">
                        <label class="form-label" for="subscription_plan_id">Subscription Plan *</label>
                        <select wire:model="subscription_plan_id" class="form-select @error('subscription_plan_id') is-invalid @enderror" id="subscription_plan_id" required>
                            <option value="">Select Subscription Plan...</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} (PKR {{ number_format($plan->price, 0) }})</option>
                            @endforeach
                        </select>
                        @error('subscription_plan_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Subscription Ends At -->
                    <div class="col-md-6">
                        <label class="form-label" for="subscription_ends_at">Subscription Ends At *</label>
                        <input wire:model="subscription_ends_at" class="form-control @error('subscription_ends_at') is-invalid @enderror" id="subscription_ends_at" type="date" required />
                        @error('subscription_ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a class="btn btn-falcon-default btn-sm" href="{{ route('super-admin.business-owners') }}">Cancel</a>
                    <button class="btn btn-primary btn-sm" type="submit">
                        <span wire:loading class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        {{ $isEditMode ? 'Update Owner' : 'Create Owner' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
