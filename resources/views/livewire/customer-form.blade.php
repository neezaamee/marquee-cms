<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <span class="fas fa-user-plus me-2 text-primary"></span>
                {{ $isEditMode ? 'Edit Customer Profile' : 'Add New Customer' }}
            </h5>
            <a class="btn btn-falcon-default btn-sm" href="{{ route('customers.index') }}">
                <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
            </a>
        </div>

        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="row g-3">
                    
                    <!-- Customer Type Selection -->
                    <div class="col-12">
                        <label class="form-label fw-semi-bold d-block">Customer Type *</label>
                        <div class="form-check form-check-inline">
                            <input wire:model.live="customer_type" class="form-check-input" type="radio" name="customer_type" id="type_individual" value="Individual">
                            <label class="form-check-label cursor-pointer" for="type_individual">Individual</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input wire:model.live="customer_type" class="form-check-input" type="radio" name="customer_type" id="type_corporate" value="Corporate">
                            <label class="form-check-label cursor-pointer" for="type_corporate">Corporate</label>
                        </div>
                        @error('customer_type') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <!-- Personal / Corporate Identity -->
                    <div class="row navbar-vertical-label-wrapper mt-4 mb-2">
                        <div class="col-auto navbar-vertical-label text-primary">Identity Details</div>
                        <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="first_name">First Name *</label>
                        <input wire:model="first_name" class="form-control @error('first_name') is-invalid @enderror" id="first_name" type="text" required placeholder="e.g. Ajmal" />
                        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="last_name">Last Name *</label>
                        <input wire:model="last_name" class="form-control @error('last_name') is-invalid @enderror" id="last_name" type="text" required placeholder="e.g. Khan" />
                        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if($customer_type === 'Corporate')
                        <div class="col-md-12">
                            <label class="form-label" for="company_name">Company Name *</label>
                            <input wire:model="company_name" class="form-control @error('company_name') is-invalid @enderror" id="company_name" type="text" required placeholder="e.g. Elite Events Private Ltd." />
                            @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="col-md-4">
                        <label class="form-label" for="gender">Gender</label>
                        <select wire:model="gender" class="form-select @error('gender') is-invalid @enderror" id="gender">
                            <option value="">Select Gender...</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                        @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="date_of_birth">Date of Birth</label>
                        <input wire:model="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" id="date_of_birth" type="date" />
                        @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="cnic_national_id">CNIC (Pakistan Format)</label>
                        <input type="text" id="cnic_national_id" class="form-control @error('cnic_national_id') is-invalid @enderror" placeholder="e.g. 35202-1234567-1" x-data x-init="IMask($el, { mask: '00000-0000000-0' })" wire:model.blur="cnic_national_id" />
                        @error('cnic_national_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="ntn_number">NTN (National Tax Number)</label>
                        <input wire:model="ntn_number" class="form-control @error('ntn_number') is-invalid @enderror" id="ntn_number" type="text" placeholder="e.g. 1234567-8" />
                        @error('ntn_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="status">Profile Status *</label>
                        <select wire:model="status" class="form-select @error('status') is-invalid @enderror" id="status" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Blocked">Blocked</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Contact Details -->
                    <div class="row navbar-vertical-label-wrapper mt-4 mb-2">
                        <div class="col-auto navbar-vertical-label text-primary">Contact & Address</div>
                        <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="email">Email Address</label>
                        <input wire:model="email" class="form-control @error('email') is-invalid @enderror" id="email" type="email" placeholder="e.g. client@email.com" />
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="phone_number">Phone Number *</label>
                        <input type="text" id="phone_number" class="form-control @error('phone_number') is-invalid @enderror" required placeholder="e.g. 0300-1234567" x-data x-init="IMask($el, { mask: '0000-0000000' })" wire:model.blur="phone_number" />
                        @error('phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="alternate_phone">Alternate Phone</label>
                        <input type="text" id="alternate_phone" class="form-control @error('alternate_phone') is-invalid @enderror" placeholder="e.g. 0321-1234567" x-data x-init="IMask($el, { mask: '0000-0000000' })" wire:model.blur="alternate_phone" />
                        @error('alternate_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label" for="address">Postal Address</label>
                        <input wire:model="address" class="form-control @error('address') is-invalid @enderror" id="address" type="text" placeholder="e.g. House 45-B, Sector Z, DHA Phase 3" />
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="city">City</label>
                        <div wire:ignore x-data x-init="
                            const choices = new Choices($refs.citySelect, {
                                searchEnabled: true,
                                itemSelectText: '',
                                shouldSort: false
                            });
                            $refs.citySelect.addEventListener('change', (e) => {
                                $wire.set('city', e.target.value);
                            });
                        ">
                            <select x-ref="citySelect" id="city" class="form-select @error('city') is-invalid @enderror">
                                <option value="">Select City...</option>
                                @foreach($cities as $c)
                                    <option value="{{ $c }}" @selected($city == $c)>{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('city') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="province">Province / State</label>
                        <div wire:ignore x-data x-init="
                            const choices = new Choices($refs.provinceSelect, {
                                searchEnabled: true,
                                itemSelectText: '',
                                shouldSort: false
                            });
                            $refs.provinceSelect.addEventListener('change', (e) => {
                                $wire.set('province', e.target.value);
                            });
                        ">
                            <select x-ref="provinceSelect" id="province" class="form-select @error('province') is-invalid @enderror">
                                <option value="">Select Province...</option>
                                @foreach($provinces as $p)
                                    <option value="{{ $p }}" @selected($province == $p)>{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('province') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="postal_code">Postal Code</label>
                        <input wire:model="postal_code" class="form-control @error('postal_code') is-invalid @enderror" id="postal_code" type="text" placeholder="e.g. 54000" />
                        @error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Referral Details -->
                    <div class="row navbar-vertical-label-wrapper mt-4 mb-2">
                        <div class="col-auto navbar-vertical-label text-primary">Referral Source</div>
                        <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="referred_by_type">Referral Type</label>
                        <select wire:model="referred_by_type" class="form-select @error('referred_by_type') is-invalid @enderror" id="referred_by_type">
                            @foreach($referralTypes as $refType)
                                <option value="{{ $refType }}">{{ $refType }}</option>
                            @endforeach
                        </select>
                        @error('referred_by_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="referred_by_name">Referrer Name</label>
                        <input wire:model="referred_by_name" class="form-control @error('referred_by_name') is-invalid @enderror" id="referred_by_name" type="text" placeholder="e.g. Bilal Asif or Google Search" />
                        @error('referred_by_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="referred_by_contact">Referrer Contact Info</label>
                        <input type="text" id="referred_by_contact" class="form-control @error('referred_by_contact') is-invalid @enderror" placeholder="e.g. 0322-1234567" x-data x-init="IMask($el, { mask: '0000-0000000' })" wire:model.blur="referred_by_contact" />
                        @error('referred_by_contact') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Profile Photo & Notes -->
                    <div class="row navbar-vertical-label-wrapper mt-4 mb-2">
                        <div class="col-auto navbar-vertical-label text-primary">Photo & Notes</div>
                        <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="photo">Profile Photo</label>
                        <input wire:model="photo" class="form-control @error('photo') is-invalid @enderror" id="photo" type="file" accept="image/*" />
                        @error('photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div wire:loading wire:target="photo" class="text-primary fs-11 mt-1">
                            <span class="spinner-border spinner-border-sm me-1" role="status"></span>Uploading image...
                        </div>
                    </div>

                    <div class="col-md-6 d-flex align-items-center">
                        @if ($photo)
                            <div>
                                <small class="text-muted d-block mb-1">New Photo Preview:</small>
                                <img src="{{ $photo->temporaryUrl() }}" class="rounded border" width="60" height="60" style="object-fit: cover;">
                            </div>
                        @elseif ($existingPhoto)
                            <div>
                                <small class="text-muted d-block mb-1">Current Photo:</small>
                                <img src="{{ asset('storage/' . $existingPhoto) }}" class="rounded border" width="60" height="60" style="object-fit: cover;">
                            </div>
                        @endif
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="notes">Notes / Special Instructions</label>
                        <textarea wire:model="notes" class="form-control @error('notes') is-invalid @enderror" id="notes" rows="3" placeholder="Add any background info or corporate details..."></textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a class="btn btn-falcon-default btn-sm" href="{{ route('customers.index') }}">Cancel</a>
                    <button class="btn btn-primary btn-sm" type="submit">
                        <span wire:loading class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        {{ $isEditMode ? 'Update Profile' : 'Save Customer' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
