<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ $isEditMode ? 'Edit Marquee Tenant' : 'Add New Marquee Tenant' }}</h5>
            <a class="btn btn-falcon-default btn-sm" href="{{ route('marquees.index') }}">
                <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
            </a>
        </div>

        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="row g-3">
                    <!-- Name -->
                    <div class="col-md-6">
                        <label class="form-label" for="name">Marquee/Company Name *</label>
                        <input wire:model="name" class="form-control @error('name') is-invalid @enderror" id="name" type="text" required placeholder="e.g. Royal Banquet Company" />
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label class="form-label" for="email">Business Email *</label>
                        <input wire:model="email" class="form-control @error('email') is-invalid @enderror" id="email" type="email" required placeholder="e.g. contact@royalmarquee.com" />
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Logo Upload -->
                    <div class="col-md-6">
                        <label class="form-label" for="logo">Company Logo</label>
                        <input wire:model="logo" class="form-control @error('logo') is-invalid @enderror" id="logo" type="file" accept="image/*" />
                        @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div wire:loading wire:target="logo" class="text-primary fs-11 mt-1">
                            <span class="spinner-border spinner-border-sm me-1" role="status"></span>Uploading image...
                        </div>
                    </div>

                    <!-- Preview Logo -->
                    <div class="col-md-6 d-flex align-items-center gap-3">
                        @if ($logo)
                            <div>
                                <small class="text-muted d-block mb-1">New Preview:</small>
                                <img src="{{ $logo->temporaryUrl() }}" class="rounded border" width="60" height="60" style="object-fit: contain;">
                            </div>
                        @elseif ($existingLogo)
                            <div>
                                <small class="text-muted d-block mb-1">Current Logo:</small>
                                <img src="{{ asset('storage/' . $existingLogo) }}" class="rounded border" width="60" height="60" style="object-fit: contain;">
                            </div>
                        @endif
                    </div>

                    <!-- Phone -->
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Phone Number *</label>
                        <input wire:model="phone" class="form-control @error('phone') is-invalid @enderror" id="phone" type="text" required />
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Address -->
                    <div class="col-md-6">
                        <label class="form-label" for="address">Address *</label>
                        <input wire:model="address" class="form-control @error('address') is-invalid @enderror" id="address" type="text" required />
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Province -->
                    <div class="col-md-4">
                        <label class="form-label" for="province">Province *</label>
                        <select wire:model="province" class="form-select @error('province') is-invalid @enderror" id="province" required>
                            <option value="">Choose province...</option>
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
                        <input wire:model="city" class="form-control @error('city') is-invalid @enderror" id="city" type="text" required placeholder="e.g. Lahore" />
                        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Status -->
                    <div class="col-md-4">
                        <label class="form-label" for="status">Status *</label>
                        <select wire:model="status" class="form-select @error('status') is-invalid @enderror" id="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row navbar-vertical-label-wrapper mt-4 mb-2">
                        <div class="col-auto navbar-vertical-label text-primary">Taxation Details</div>
                        <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
                    </div>

                    <!-- NTN -->
                    <div class="col-md-4">
                        <label class="form-label" for="ntn">NTN Number</label>
                        <input wire:model="ntn" class="form-control @error('ntn') is-invalid @enderror" id="ntn" type="text" placeholder="e.g. 1234567-8" />
                        @error('ntn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- STRN -->
                    <div class="col-md-4">
                        <label class="form-label" for="strn">STRN Number</label>
                        <input wire:model="strn" class="form-control @error('strn') is-invalid @enderror" id="strn" type="text" placeholder="e.g. 9876543-2" />
                        @error('strn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Tax Authority -->
                    <div class="col-md-4">
                        <label class="form-label" for="tax_authority">Tax Authority *</label>
                        <select wire:model="tax_authority" class="form-select @error('tax_authority') is-invalid @enderror" id="tax_authority" required>
                            <option value="FBR">FBR (Federal)</option>
                            <option value="PRA">PRA (Punjab)</option>
                            <option value="SRB">SRB (Sindh)</option>
                            <option value="KPRA">KPRA (Khyber Pakhtunkhwa)</option>
                            <option value="BRA">BRA (Balochistan)</option>
                        </select>
                        @error('tax_authority') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row navbar-vertical-label-wrapper mt-4 mb-2">
                        <div class="col-auto navbar-vertical-label text-primary">Business Owner Association</div>
                        <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
                    </div>

                    @if (!$isEditMode)
                        <!-- Inline Owner toggle -->
                        <div class="col-12 mb-2">
                            <div class="form-check form-switch">
                                <input wire:model.live="createOwnerInline" class="form-check-input" id="createOwnerInline" type="checkbox" />
                                <label class="form-check-label fw-bold" for="createOwnerInline">Create New Business Owner Inline (Shortcut)</label>
                            </div>
                        </div>
                    @endif

                    @if ($isEditMode || !$createOwnerInline)
                        <!-- Multi-select for Existing Owners -->
                        <div class="col-12">
                            <label class="form-label" for="selectedOwners">Associate Business Owner(s) *</label>
                            <select wire:model="selectedOwners" class="form-select @error('selectedOwners') is-invalid @enderror" id="selectedOwners" multiple style="height: 120px;">
                                @foreach($businessOwnersList as $owner)
                                    <option value="{{ $owner->id }}">{{ $owner->name }} ({{ $owner->email }})</option>
                                @endforeach
                            </select>
                            <div class="fs-11 text-muted mt-1">Hold Ctrl (Windows) or Cmd (Mac) to select multiple owners.</div>
                            @error('selectedOwners') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                        </div>
                    @else
                        <!-- Inline Owner Creation Form -->
                        <div class="col-md-6">
                            <label class="form-label" for="owner_name">Owner Full Name *</label>
                            <input wire:model="owner_name" class="form-control @error('owner_name') is-invalid @enderror" id="owner_name" type="text" placeholder="e.g. Mian Akbar" />
                            @error('owner_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="owner_username">Owner Username *</label>
                            <input wire:model="owner_username" class="form-control @error('owner_username') is-invalid @enderror" id="owner_username" type="text" placeholder="e.g. akbar_owner" />
                            @error('owner_username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="owner_email">Owner Email *</label>
                            <input wire:model="owner_email" class="form-control @error('owner_email') is-invalid @enderror" id="owner_email" type="email" placeholder="e.g. owner@example.com" />
                            @error('owner_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="owner_password">Owner Password *</label>
                            <input wire:model="owner_password" class="form-control @error('owner_password') is-invalid @enderror" id="owner_password" type="password" placeholder="At least 8 characters" />
                            @error('owner_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="owner_phone">Owner Phone</label>
                            <input wire:model="owner_phone" class="form-control @error('owner_phone') is-invalid @enderror" id="owner_phone" type="text" placeholder="e.g. +923007654321" />
                            @error('owner_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12"><hr class="my-2 text-300"></div>

                        <h5 class="fs-10 text-primary mb-0">SaaS Subscription Details (For New Owner)</h5>

                        <div class="col-md-6">
                            <label class="form-label" for="subscription_plan_id">Subscription Plan *</label>
                            <select wire:model="subscription_plan_id" class="form-select @error('subscription_plan_id') is-invalid @enderror" id="subscription_plan_id">
                                <option value="">Select plan...</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }} (PKR {{ number_format($plan->price) }})</option>
                                @endforeach
                            </select>
                            @error('subscription_plan_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="subscription_ends_at">Subscription Ends At *</label>
                            <input wire:model="subscription_ends_at" class="form-control @error('subscription_ends_at') is-invalid @enderror" id="subscription_ends_at" type="date" />
                            @error('subscription_ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a class="btn btn-falcon-default btn-sm" href="{{ route('marquees.index') }}">Cancel</a>
                    <button class="btn btn-primary btn-sm" type="submit">
                        <span wire:loading class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        {{ $isEditMode ? 'Update Marquee' : 'Save Marquee' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
