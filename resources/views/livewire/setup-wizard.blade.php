<div class="row g-3">
    <!-- Left Sidebar: Progress Tracker -->
    <div class="col-lg-3">
        <div class="card h-100 border border-translucent">
            <div class="card-header bg-light pt-3 pb-2 text-center border-bottom border-translucent">
                <h5 class="mb-1 text-primary"><span class="fas fa-magic me-2"></span>Onboarding Status</h5>
                <div class="progress mt-2" style="height: 10px;">
                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" style="width: {{ $progressPercent }}%;" aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="fs-10 text-muted mt-1 fw-semi-bold">{{ $progressPercent }}% Configured</div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="onboardingStepsList">
                    <!-- Step 1: Business Profile -->
                    <button type="button" wire:click="goToStep(1)" class="list-group-item list-group-item-action border-0 px-3 py-2.5 d-flex align-items-center justify-content-between {{ $currentStep == 1 ? 'bg-subtle-primary fw-bold text-primary border-start border-3 border-primary' : '' }}">
                        <span class="fs-10"><i class="fas fa-building me-2 text-secondary"></i>1. Business Profile</span>
                        @if($checklist['marquee_info'])
                            <span class="badge bg-subtle-success text-success rounded-pill fs-11"><i class="fas fa-check"></i></span>
                        @else
                            <span class="badge bg-subtle-secondary text-secondary rounded-pill fs-11"><i class="fas fa-circle-notch fa-spin"></i></span>
                        @endif
                    </button>

                    <!-- Step 2: Main Branch -->
                    <button type="button" wire:click="goToStep(2)" class="list-group-item list-group-item-action border-0 px-3 py-2.5 d-flex align-items-center justify-content-between {{ $currentStep == 2 ? 'bg-subtle-primary fw-bold text-primary border-start border-3 border-primary' : '' }}">
                        <span class="fs-10"><i class="fas fa-map-marker-alt me-2 text-secondary"></i>2. Main Branch</span>
                        @if($checklist['branch'])
                            <span class="badge bg-subtle-success text-success rounded-pill fs-11"><i class="fas fa-check"></i></span>
                        @else
                            <span class="badge bg-subtle-secondary text-secondary rounded-pill fs-11"><i class="fas fa-circle-notch fa-spin"></i></span>
                        @endif
                    </button>

                    <!-- Step 3: Branch Config -->
                    <button type="button" wire:click="goToStep(3)" class="list-group-item list-group-item-action border-0 px-3 py-2.5 d-flex align-items-center justify-content-between {{ $currentStep == 3 ? 'bg-subtle-primary fw-bold text-primary border-start border-3 border-primary' : '' }}">
                        <span class="fs-10"><i class="fas fa-cog me-2 text-secondary"></i>3. Branch Config</span>
                        @if($checklist['branch_config'])
                            <span class="badge bg-subtle-success text-success rounded-pill fs-11"><i class="fas fa-check"></i></span>
                        @else
                            <span class="badge bg-subtle-secondary text-secondary rounded-pill fs-11"><i class="fas fa-circle-notch fa-spin"></i></span>
                        @endif
                    </button>

                    <!-- Step 4: Halls Setup -->
                    <button type="button" wire:click="goToStep(4)" class="list-group-item list-group-item-action border-0 px-3 py-2.5 d-flex align-items-center justify-content-between {{ $currentStep == 4 ? 'bg-subtle-primary fw-bold text-primary border-start border-3 border-primary' : '' }}">
                        <span class="fs-10"><i class="fas fa-hotel me-2 text-secondary"></i>4. Halls Setup</span>
                        @if($checklist['halls'])
                            <span class="badge bg-subtle-success text-success rounded-pill fs-11"><i class="fas fa-check"></i></span>
                        @else
                            <span class="badge bg-subtle-secondary text-secondary rounded-pill fs-11"><i class="fas fa-circle-notch fa-spin"></i></span>
                        @endif
                    </button>

                    <!-- Step 5: Departments -->
                    <button type="button" wire:click="goToStep(5)" class="list-group-item list-group-item-action border-0 px-3 py-2.5 d-flex align-items-center justify-content-between {{ $currentStep == 5 ? 'bg-subtle-primary fw-bold text-primary border-start border-3 border-primary' : '' }}">
                        <span class="fs-10"><i class="fas fa-sitemap me-2 text-secondary"></i>5. Departments</span>
                        @if($checklist['departments'])
                            <span class="badge bg-subtle-success text-success rounded-pill fs-11"><i class="fas fa-check"></i></span>
                        @else
                            <span class="badge bg-subtle-secondary text-secondary rounded-pill fs-11"><i class="fas fa-circle-notch fa-spin"></i></span>
                        @endif
                    </button>

                    <!-- Step 6: Booking Masters -->
                    <button type="button" wire:click="goToStep(6)" class="list-group-item list-group-item-action border-0 px-3 py-2.5 d-flex align-items-center justify-content-between {{ $currentStep == 6 ? 'bg-subtle-primary fw-bold text-primary border-start border-3 border-primary' : '' }}">
                        <span class="fs-10"><i class="fas fa-ticket-alt me-2 text-secondary"></i>6. Booking Masters</span>
                        @if($checklist['booking_masters'])
                            <span class="badge bg-subtle-success text-success rounded-pill fs-11"><i class="fas fa-check"></i></span>
                        @else
                            <span class="badge bg-subtle-secondary text-secondary rounded-pill fs-11"><i class="fas fa-circle-notch fa-spin"></i></span>
                        @endif
                    </button>

                    <!-- Step 7: Menu & Packages -->
                    <button type="button" wire:click="goToStep(7)" class="list-group-item list-group-item-action border-0 px-3 py-2.5 d-flex align-items-center justify-content-between {{ $currentStep == 7 ? 'bg-subtle-primary fw-bold text-primary border-start border-3 border-primary' : '' }}">
                        <span class="fs-10"><i class="fas fa-utensils me-2 text-secondary"></i>7. Menu & Packages</span>
                        @if($checklist['menu_packages'])
                            <span class="badge bg-subtle-success text-success rounded-pill fs-11"><i class="fas fa-check"></i></span>
                        @else
                            <span class="badge bg-subtle-secondary text-secondary rounded-pill fs-11"><i class="fas fa-circle-notch fa-spin"></i></span>
                        @endif
                    </button>

                    <!-- Step 8: Inventory -->
                    <button type="button" wire:click="goToStep(8)" class="list-group-item list-group-item-action border-0 px-3 py-2.5 d-flex align-items-center justify-content-between {{ $currentStep == 8 ? 'bg-subtle-primary fw-bold text-primary border-start border-3 border-primary' : '' }}">
                        <span class="fs-10"><i class="fas fa-boxes me-2 text-secondary"></i>8. Inventory</span>
                        @if($checklist['inventory'])
                            <span class="badge bg-subtle-success text-success rounded-pill fs-11"><i class="fas fa-check"></i></span>
                        @else
                            <span class="badge bg-subtle-secondary text-secondary rounded-pill fs-11"><i class="fas fa-circle-notch fa-spin"></i></span>
                        @endif
                    </button>

                    <!-- Step 9: Finance Config -->
                    <button type="button" wire:click="goToStep(9)" class="list-group-item list-group-item-action border-0 px-3 py-2.5 d-flex align-items-center justify-content-between {{ $currentStep == 9 ? 'bg-subtle-primary fw-bold text-primary border-start border-3 border-primary' : '' }}">
                        <span class="fs-10"><i class="fas fa-dollar-sign me-2 text-secondary"></i>9. Finance Config</span>
                        @if($checklist['finance'])
                            <span class="badge bg-subtle-success text-success rounded-pill fs-11"><i class="fas fa-check"></i></span>
                        @else
                            <span class="badge bg-subtle-secondary text-secondary rounded-pill fs-11"><i class="fas fa-circle-notch fa-spin"></i></span>
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side: Active Wizard Content Card -->
    <div class="col-lg-9">
        <div class="card border border-translucent">
            <div class="card-header bg-light pt-3 pb-2 border-bottom border-translucent">
                <div class="row align-items-center justify-content-between">
                    <div class="col-auto">
                        <h4 class="mb-1 text-primary">Configuration Setup</h4>
                        <p class="mb-0 text-secondary fs-10">Step {{ $currentStep }} of {{ $totalSteps }} — Operational Setup Wizard</p>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <!-- Errors banner -->
                @if ($errors->any())
                    <div class="alert alert-warning border-2 d-flex align-items-center mb-4" role="alert">
                        <span class="fas fa-exclamation-triangle me-2"></span>
                        <div class="fs-10 fw-semi-bold">Validation Failed. Please review and fix the input fields below.</div>
                    </div>
                @endif

                <!-- STEP 1: BUSINESS PROFILE (READ-ONLY PRE-CONFIGURED) -->
                @if($currentStep == 1)
                    <div>
                        <h5 class="mb-3 border-bottom pb-2 text-primary"><span class="fas fa-building me-2"></span>Step 1: Business Profile Details</h5>
                        <div class="alert alert-info border-1 mb-4 d-flex align-items-center" role="alert">
                            <span class="fas fa-info-circle me-2"></span>
                            <div class="fs-10">
                                <strong>Super Admin Provisioned Profile:</strong> Tenant settings, Legal NTN/STRN details, and subscriptions are managed exclusively by Super Administration. You may update operational contacts and upload the brand logo below.
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Marquee Name</label>
                                <input type="text" class="form-control bg-200" value="{{ $marquee_name }}" readonly disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Business Type</label>
                                <input type="text" class="form-control bg-200" value="{{ $business_type }}" readonly disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Email Address *</label>
                                <input wire:model="email" type="email" class="form-control @error('email') is-invalid @enderror" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Phone Number *</label>
                                <input wire:model="phone" type="text" class="form-control @error('phone') is-invalid @enderror" required x-data x-init="IMask($el, { mask: '0000-0000000' })">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Corporate Address *</label>
                                <input wire:model="address" type="text" class="form-control @error('address') is-invalid @enderror" required>
                                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Province *</label>
                                <select wire:model.live="province" class="form-select @error('province') is-invalid @enderror" required>
                                    <option value="">Select Province...</option>
                                    <option value="Punjab">Punjab</option>
                                    <option value="Sindh">Sindh</option>
                                    <option value="Khyber Pakhtunkhwa">Khyber Pakhtunkhwa</option>
                                    <option value="Balochistan">Balochistan</option>
                                    <option value="Islamabad Capital Territory">Islamabad Capital Territory</option>
                                </select>
                                @error('province') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City *</label>
                                <select wire:model="city" class="form-select @error('city') is-invalid @enderror" required {{ empty($province) ? 'disabled' : '' }}>
                                    <option value="">Select City...</option>
                                    @foreach($cities as $c)
                                        <option value="{{ $c }}">{{ $c }}</option>
                                    @endforeach
                                </select>
                                @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Country</label>
                                <input type="text" class="form-control bg-200" value="{{ $country }}" readonly disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tax Authority</label>
                                <input type="text" class="form-control bg-200" value="{{ $tax_authority }}" readonly disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">NTN Number</label>
                                <input type="text" class="form-control bg-200" value="{{ $ntn ?: 'Not Configured' }}" readonly disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">STRN Number</label>
                                <input type="text" class="form-control bg-200" value="{{ $strn ?: 'Not Configured' }}" readonly disabled>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Upload Company Logo</label>
                                @if ($logo)
                                    <div class="d-block mb-2">
                                        <small class="text-muted d-block mb-1">Temporary Preview:</small>
                                        <img src="{{ $logo->temporaryUrl() }}" class="rounded border" width="60" height="60" style="object-fit: contain;">
                                    </div>
                                @elseif (auth()->user()->getActiveMarqueeId() && \App\Models\Marquee::find(auth()->user()->getActiveMarqueeId())->logo)
                                    @php
                                        $mLogo = \App\Models\Marquee::find(auth()->user()->getActiveMarqueeId())->logo;
                                        $mLogoUrl = Str::startsWith($mLogo, ['http://', 'https://']) 
                                            ? $mLogo 
                                            : (Str::startsWith($mLogo, 'storage/') ? asset($mLogo) : asset('storage/' . $mLogo));
                                    @endphp
                                    <div class="d-block mb-2">
                                        <small class="text-muted d-block mb-1">Current Logo:</small>
                                        <img src="{{ $mLogoUrl }}" class="rounded border" width="60" height="60" style="object-fit: contain;">
                                    </div>
                                @endif
                                <input wire:model="logo" type="file" class="form-control @error('logo') is-invalid @enderror" accept="image/*">
                                <div class="fs-10 text-muted mt-1">PNG, JPG (Max 2MB). Optimal sizing: Square under 250kb.</div>
                                @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                @endif
 
                <!-- STEP 2: CREATE MAIN BRANCH -->
                @if($currentStep == 2)
                    <div>
                        <h5 class="mb-3 border-bottom pb-2 text-primary"><span class="fas fa-map-marker-alt me-2"></span>Step 2: Create Main Branch Location</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Branch Venue Name *</label>
                                <input wire:model="branch_name" type="text" class="form-control @error('branch_name') is-invalid @enderror" required placeholder="e.g. Main Branch / Downtown Marquee">
                                @error('branch_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Branch Contact Phone *</label>
                                <input wire:model="branch_phone" type="text" class="form-control @error('branch_phone') is-invalid @enderror" required placeholder="e.g. 0321-8662726" x-data x-init="IMask($el, { mask: '0000-0000000' })">
                                @error('branch_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Branch Address *</label>
                                <input wire:model="branch_address" type="text" class="form-control @error('branch_address') is-invalid @enderror" required placeholder="e.g. Canal Road near Toyota Motors">
                                @error('branch_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Branch Province *</label>
                                <select wire:model.live="branch_province" class="form-select @error('branch_province') is-invalid @enderror" required>
                                    <option value="">Select Province...</option>
                                    <option value="Punjab">Punjab</option>
                                    <option value="Sindh">Sindh</option>
                                    <option value="Khyber Pakhtunkhwa">Khyber Pakhtunkhwa</option>
                                    <option value="Balochistan">Balochistan</option>
                                    <option value="Islamabad Capital Territory">Islamabad Capital Territory</option>
                                </select>
                                @error('branch_province') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Branch City *</label>
                                <select wire:model="branch_city" class="form-select @error('branch_city') is-invalid @enderror" required {{ empty($branch_province) ? 'disabled' : '' }}>
                                    <option value="">Select City...</option>
                                    @foreach($this->branchCities as $bc)
                                        <option value="{{ $bc }}">{{ $bc }}</option>
                                    @endforeach
                                </select>
                                @error('branch_city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                @endif

                <!-- STEP 3: BRANCH CONFIGURATION -->
                @if($currentStep == 3)
                    <div>
                        <h5 class="mb-3 border-bottom pb-2 text-primary"><span class="fas fa-cog me-2"></span>Step 3: Branch Operations Configuration</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Default Tax Rate (%) *</label>
                                <input wire:model="tax_rate" type="number" step="0.01" class="form-control @error('tax_rate') is-invalid @enderror" required>
                                @error('tax_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Invoice Prefix</label>
                                <input wire:model="invoice_prefix" type="text" class="form-control @error('invoice_prefix') is-invalid @enderror">
                                @error('invoice_prefix') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Booking Prefix</label>
                                <input wire:model="booking_prefix" type="text" class="form-control @error('booking_prefix') is-invalid @enderror">
                                @error('booking_prefix') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Default Payment Method *</label>
                                <select wire:model="default_payment_method" class="form-select @error('default_payment_method') is-invalid @enderror" required>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Card">Credit/Debit Card</option>
                                </select>
                                @error('default_payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 mt-4 border-top pt-3">
                                <div class="form-check form-switch">
                                    <input wire:model.live="enable_fbr" class="form-check-input" type="checkbox" id="enableFbrSwitch">
                                    <label class="form-check-label fw-bold" for="enableFbrSwitch">Enable FBR POS / Fiscal Integration</label>
                                </div>
                            </div>

                            @if($enable_fbr)
                                <div class="col-md-6">
                                    <label class="form-label">FBR POS Device ID *</label>
                                    <input wire:model="fbr_pos_id" type="text" class="form-control @error('fbr_pos_id') is-invalid @enderror" required placeholder="e.g. PRA-LHR-GUL-01">
                                    @error('fbr_pos_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">FBR POS Secret Key / Auth Key *</label>
                                    <input wire:model="fbr_pos_key" type="password" class="form-control @error('fbr_pos_key') is-invalid @enderror" required placeholder="FBR registered device key">
                                    @error('fbr_pos_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input wire:model="fbr_sandbox_mode" class="form-check-input" type="checkbox" id="sandboxModeCheckbox">
                                        <label class="form-check-label" for="sandboxModeCheckbox">FBR Sandbox (Testing/Training Mode)</label>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- STEP 4: CREATE HALLS -->
                @if($currentStep == 4)
                    <div>
                        <h5 class="mb-3 border-bottom pb-2 text-primary"><span class="fas fa-hotel me-2"></span>Step 4: Hall Venue Configuration</h5>
                        
                        <!-- Inline Form to Add Hall -->
                        <div class="card bg-body-tertiary mb-4 border border-translucent">
                            <div class="card-body p-3">
                                <h6 class="mb-2 text-primary fw-semi-bold">Add New Hall / Lawn Venue</h6>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <input wire:model="new_hall_name" type="text" class="form-control form-control-sm @error('new_hall_name') is-invalid @enderror" placeholder="Hall Name (e.g. Shalimar Hall)">
                                        @error('new_hall_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <input wire:model="new_hall_code" type="text" class="form-control form-control-sm @error('new_hall_code') is-invalid @enderror" placeholder="Code (e.g. SH-01)">
                                        @error('new_hall_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <input wire:model="new_capacity" type="number" class="form-control form-control-sm @error('new_capacity') is-invalid @enderror" placeholder="Capacity (e.g. 500)">
                                        @error('new_capacity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <select wire:model="new_hall_type" class="form-select form-select-sm">
                                            <option value="Marquee">Marquee</option>
                                            <option value="Banquet">Banquet</option>
                                            <option value="Lawn">Lawn</option>
                                            <option value="Ballroom">Ballroom</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input wire:model="new_default_booking_price" type="number" class="form-control form-control-sm @error('new_default_booking_price') is-invalid @enderror" placeholder="Rent Price">
                                        @error('new_default_booking_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-12 mt-2">
                                        <input wire:model="new_hall_description" type="text" class="form-control form-control-sm" placeholder="Description / Amenities (optional)">
                                    </div>
                                    <div class="col-12 text-end mt-2">
                                        <button type="button" wire:click="addHall" class="btn btn-primary btn-sm px-3"><span class="fas fa-plus me-1"></span>Add Hall</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Current Halls Table -->
                        <h6 class="mb-2 fw-semi-bold">Configured Halls:</h6>
                        <div class="table-responsive border border-translucent rounded-3 bg-body">
                            <table class="table table-sm table-striped fs-10 mb-0">
                                <thead class="bg-200">
                                    <tr>
                                        <th class="px-3 py-2">Name</th>
                                        <th class="py-2">Code</th>
                                        <th class="py-2">Type</th>
                                        <th class="py-2 text-end">Capacity</th>
                                        <th class="py-2 text-end">Base Price</th>
                                        <th class="py-2 text-end px-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($halls as $hall)
                                        <tr>
                                            <td class="px-3 py-2 fw-bold text-primary">{{ $hall->hall_name }}</td>
                                            <td><span class="badge badge-subtle-secondary">{{ $hall->hall_code }}</span></td>
                                            <td>{{ $hall->hall_type }}</td>
                                            <td class="text-end">{{ number_format($hall->capacity) }} Guests</td>
                                            <td class="text-end">Rs. {{ number_format($hall->default_booking_price, 0) }}</td>
                                            <td class="text-end px-3">
                                                <button type="button" wire:click="deleteHall({{ $hall->id }})" class="btn btn-link btn-sm text-danger p-0"><i class="fas fa-trash-alt"></i></button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">No halls added yet. Add at least one hall to continue.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- STEP 5: CREATE DEPARTMENTS -->
                @if($currentStep == 5)
                    <div>
                        <h5 class="mb-3 border-bottom pb-2 text-primary"><span class="fas fa-sitemap me-2"></span>Step 5: Branch Departments</h5>
                        <div class="alert alert-info border-1 mb-4 d-flex align-items-center" role="alert">
                            <span class="fas fa-info-circle me-2"></span>
                            <div class="fs-10">
                                <strong>Default Seeding:</strong> We have seeded standard departments (`BBQ`, `Kitchen`, `Store`, `Accounts`, etc.) automatically. You can review, add new custom ones, or delete unwanted ones.
                            </div>
                        </div>

                        <!-- Add department inline form -->
                        <div class="card bg-body-tertiary mb-4 border border-translucent">
                            <div class="card-body p-3">
                                <h6 class="mb-2 text-primary fw-semi-bold">Add Custom Department</h6>
                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <input wire:model="new_dept_name" type="text" class="form-control form-control-sm @error('new_dept_name') is-invalid @enderror" placeholder="Name (e.g. Chinese Station)">
                                        @error('new_dept_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <input wire:model="new_dept_code" type="text" class="form-control form-control-sm @error('new_dept_code') is-invalid @enderror" placeholder="Code (e.g. CHIN)">
                                        @error('new_dept_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <select wire:model="new_dept_type" class="form-select form-select-sm">
                                            <option value="Kitchen Production">Kitchen Production</option>
                                            <option value="Operations">Operations</option>
                                            <option value="Administration">Administration</option>
                                        </select>
                                    </div>
                                    <div class="col-12 mt-2">
                                        <input wire:model="new_dept_description" type="text" class="form-control form-control-sm" placeholder="Brief Description (optional)">
                                    </div>
                                    <div class="col-12 text-end mt-2">
                                        <button type="button" wire:click="addDepartment" class="btn btn-primary btn-sm px-3"><span class="fas fa-plus me-1"></span>Add Department</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Departments List -->
                        <h6 class="mb-2 fw-semi-bold">Available Departments:</h6>
                        <div class="table-responsive border border-translucent rounded-3 bg-body">
                            <table class="table table-sm table-striped fs-10 mb-0">
                                <thead class="bg-200">
                                    <tr>
                                        <th class="px-3 py-2">Name</th>
                                        <th class="py-2">Code</th>
                                        <th class="py-2">Type</th>
                                        <th class="py-2 text-end px-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($departments as $dept)
                                        <tr>
                                            <td class="px-3 py-2 fw-bold text-primary">{{ $dept->name }}</td>
                                            <td><span class="badge badge-subtle-secondary">{{ $dept->department_code }}</span></td>
                                            <td>{{ $dept->department_type }}</td>
                                            <td class="text-end px-3">
                                                <button type="button" wire:click="deleteDepartment({{ $dept->id }})" class="btn btn-link btn-sm text-danger p-0"><i class="fas fa-trash-alt"></i></button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No departments configured. Add at least one to proceed.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif



                <!-- STEP 6: CONFIGURE BOOKING MASTERS -->
                @if($currentStep == 6)
                    <div>
                        <h5 class="mb-3 border-bottom pb-2 text-primary"><span class="fas fa-ticket-alt me-2"></span>Step 6: Booking Masters Setup</h5>
                        
                        <div class="row g-3 mb-4">
                            <!-- Event Types Side -->
                            <div class="col-md-6 border-end border-translucent">
                                <h6 class="mb-2 fw-semi-bold text-primary">Event Types</h6>
                                
                                <div class="bg-body-tertiary p-2 rounded mb-3 border border-translucent">
                                    <div class="row g-1">
                                        <div class="col-md-5">
                                            <input wire:model="new_et_name" type="text" class="form-control form-control-sm @error('new_et_name') is-invalid @enderror" placeholder="Name (e.g. Walima)">
                                            @error('new_et_name') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <input wire:model="new_et_code" type="text" class="form-control form-control-sm @error('new_et_code') is-invalid @enderror" placeholder="Code">
                                            @error('new_et_code') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <input wire:model="new_et_price" type="number" class="form-control form-control-sm @error('new_et_price') is-invalid @enderror" placeholder="Price">
                                            @error('new_et_price') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-12 mt-1">
                                            <input wire:model="new_et_description" type="text" class="form-control form-control-sm @error('new_et_description') is-invalid @enderror" placeholder="Description">
                                            @error('new_et_description') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-12 text-end mt-2">
                                            <button type="button" wire:click="addEventType" class="btn btn-primary btn-xs px-2"><i class="fas fa-plus"></i> Add Event</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive border border-translucent rounded bg-body" style="max-height: 250px; overflow-y: auto;">
                                    <table class="table table-sm table-striped fs-11 mb-0">
                                        <tbody>
                                            @foreach($eventTypes as $et)
                                                <tr>
                                                    <td class="px-2 py-1fw-bold">{{ $et->event_type_name }}</td>
                                                    <td><code>{{ $et->event_type_code }}</code></td>
                                                    <td class="text-end">Rs. {{ number_format($et->base_price, 0) }}</td>
                                                    <td class="text-end px-2">
                                                        <button type="button" wire:click="deleteEventType({{ $et->id }})" class="btn btn-link btn-sm text-danger p-0"><i class="fas fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Shifts/Slots Side -->
                            <div class="col-md-6">
                                <h6 class="mb-2 fw-semi-bold text-primary">Operational Shifts / Slots</h6>

                                <div class="bg-body-tertiary p-2 rounded mb-3 border border-translucent">
                                    <div class="row g-1">
                                        <div class="col-md-4">
                                            <input wire:model="new_slot_name" type="text" class="form-control form-control-sm @error('new_slot_name') is-invalid @enderror" placeholder="Shift (e.g. Day)">
                                            @error('new_slot_name') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <input wire:model="new_slot_start" type="time" class="form-control form-control-sm @error('new_slot_start') is-invalid @enderror">
                                            @error('new_slot_start') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <input wire:model="new_slot_end" type="time" class="form-control form-control-sm @error('new_slot_end') is-invalid @enderror">
                                            @error('new_slot_end') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-12 text-end mt-2">
                                            <button type="button" wire:click="addSlot" class="btn btn-primary btn-xs px-2"><i class="fas fa-plus"></i> Add Slot</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive border border-translucent rounded bg-body" style="max-height: 250px; overflow-y: auto;">
                                    <table class="table table-sm table-striped fs-11 mb-0">
                                        <tbody>
                                            @foreach($slots as $slot)
                                                <tr>
                                                    <td class="px-2 py-1 fw-bold">{{ $slot->slot_name }}</td>
                                                    <td>{{ date('h:i A', strtotime($slot->start_time)) }} - {{ date('h:i A', strtotime($slot->end_time)) }}</td>
                                                    <td class="text-end px-2">
                                                        <button type="button" wire:click="deleteSlot({{ $slot->id }})" class="btn btn-link btn-sm text-danger p-0"><i class="fas fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- STEP 7: CONFIGURE MENU & PACKAGES -->
                @if($currentStep == 7)
                    <div>
                        <h5 class="mb-3 border-bottom pb-2 text-primary"><span class="fas fa-utensils me-2"></span>Step 7: Catering Menu & Plate Packages</h5>
                        
                        <div class="row g-3">
                            <!-- Categories & Items -->
                            <div class="col-md-6 border-end border-translucent">
                                <h6 class="mb-2 fw-semi-bold text-primary">Menu Categories & Items</h6>
                                
                                <!-- Add item inline -->
                                <div class="bg-body-tertiary p-2 rounded mb-3 border border-translucent">
                                    <div class="row g-1">
                                        <div class="col-md-6">
                                            <input wire:model="new_item_name" type="text" class="form-control form-control-sm @error('new_item_name') is-invalid @enderror" placeholder="Item Name (e.g. Mutton Karahi)">
                                            @error('new_item_name') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <input wire:model="new_item_code" type="text" class="form-control form-control-sm @error('new_item_code') is-invalid @enderror" placeholder="Code">
                                            @error('new_item_code') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <select wire:model="new_item_category_id" class="form-select form-select-sm @error('new_item_category_id') is-invalid @enderror">
                                                <option value="">Category...</option>
                                                @foreach($menuCategories as $mc)
                                                    <option value="{{ $mc->id }}">{{ $mc->category_name }}</option>
                                                @endforeach
                                            </select>
                                            @error('new_item_category_id') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-6 mt-1">
                                            <input wire:model="new_item_cost" type="number" class="form-control form-control-sm @error('new_item_cost') is-invalid @enderror" placeholder="Base Cost">
                                            @error('new_item_cost') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-6 mt-1">
                                            <input wire:model="new_item_price" type="number" class="form-control form-control-sm @error('new_item_price') is-invalid @enderror" placeholder="Selling Price">
                                            @error('new_item_price') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-12 text-end mt-2">
                                            <button type="button" wire:click="addMenuItem" class="btn btn-primary btn-xs px-2"><i class="fas fa-plus"></i> Add Item</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <input type="text" wire:model.live.debounce.300ms="search_menu_item" class="form-control form-control-sm" placeholder="Search menu items by name/code...">
                                </div>

                                <div class="table-responsive border border-translucent rounded bg-body" style="max-height: 250px; overflow-y: auto;">
                                    <table class="table table-sm table-striped fs-11 mb-0">
                                        <tbody>
                                            @forelse($menuItems as $item)
                                                <tr>
                                                    <td class="px-2 py-1 fw-bold text-primary">{{ $item->item_name }}</td>
                                                    <td><span class="badge badge-subtle-secondary">{{ $item->category->category_name ?? 'Raw' }}</span></td>
                                                    <td class="text-end">Rs. {{ number_format($item->selling_price, 0) }}</td>
                                                    <td class="text-end px-2">
                                                        <button type="button" wire:click="deleteMenuItem({{ $item->id }})" class="btn btn-link btn-sm text-danger p-0"><i class="fas fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center py-3 text-muted">No menu items found.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Plate Packages -->
                            <div class="col-md-6">
                                <h6 class="mb-2 fw-semi-bold text-primary">Plate / Seating Packages</h6>
                                
                                <div class="bg-body-tertiary p-2 rounded mb-3 border border-translucent">
                                    <div class="row g-1">
                                        <div class="col-md-6">
                                            <input wire:model="new_pkg_name" type="text" class="form-control form-control-sm @error('new_pkg_name') is-invalid @enderror" placeholder="Package (e.g. VIP Mutton)">
                                            @error('new_pkg_name') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <input wire:model="new_pkg_code" type="text" class="form-control form-control-sm @error('new_pkg_code') is-invalid @enderror" placeholder="Code">
                                            @error('new_pkg_code') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <input wire:model="new_pkg_price" type="number" class="form-control form-control-sm @error('new_pkg_price') is-invalid @enderror" placeholder="Price/Plate">
                                            @error('new_pkg_price') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-12 mt-1">
                                            <input wire:model="new_pkg_description" type="text" class="form-control form-control-sm @error('new_pkg_description') is-invalid @enderror" placeholder="Description">
                                            @error('new_pkg_description') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-12 mt-2">
                                            <label class="form-label fs-11 fw-bold text-secondary mb-1">Select Package Dishes *</label>
                                            <div class="border rounded p-2 bg-white" style="max-height: 120px; overflow-y: auto;">
                                                @foreach($menuItems as $item)
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" value="{{ $item->id }}" id="pkg_item_{{ $item->id }}" wire:model="new_pkg_items">
                                                        <label class="form-check-label fs-11" for="pkg_item_{{ $item->id }}">
                                                            {{ $item->item_name }} (Rs. {{ number_format($item->selling_price, 0) }})
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                            @error('new_pkg_items') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-12 text-end mt-2">
                                            <button type="button" wire:click="addPackage" class="btn btn-primary btn-xs px-2"><i class="fas fa-plus"></i> Add Package</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <input type="text" wire:model.live.debounce.300ms="search_package" class="form-control form-control-sm" placeholder="Search packages by name/code...">
                                </div>

                                <div class="table-responsive border border-translucent rounded bg-body" style="max-height: 250px; overflow-y: auto;">
                                    <table class="table table-sm table-striped fs-11 mb-0">
                                        <tbody>
                                            @forelse($packages as $pkg)
                                                <tr>
                                                    <td class="px-2 py-1 fw-bold text-primary">{{ $pkg->package_name }}</td>
                                                    <td><code>{{ $pkg->package_code }}</code></td>
                                                    <td class="text-end">Rs. {{ number_format($pkg->per_plate_price, 0) }}/plate</td>
                                                    <td class="text-end px-2">
                                                        <button type="button" wire:click="deletePackage({{ $pkg->id }})" class="btn btn-link btn-sm text-danger p-0"><i class="fas fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center py-3 text-muted">No packages found.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- STEP 8: CONFIGURE INVENTORY -->
                @if($currentStep == 8)
                    <div>
                        <h5 class="mb-3 border-bottom pb-2 text-primary"><span class="fas fa-boxes me-2"></span>Step 8: Inventory Defaults</h5>
                        
                        <div class="row g-3">
                            <!-- Suppliers List -->
                            <div class="col-md-6 border-end border-translucent">
                                <h6 class="mb-2 fw-semi-bold text-primary">Suppliers / Vendors Directory</h6>

                                <div class="bg-body-tertiary p-2 rounded mb-3 border border-translucent">
                                    <div class="row g-1">
                                        <div class="col-12">
                                            <input wire:model="new_supplier_name" type="text" class="form-control form-control-sm @error('new_supplier_name') is-invalid @enderror" placeholder="Supplier Company (e.g. Metro Food)">
                                            @error('new_supplier_name') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-4 mt-1">
                                            <input wire:model="new_supplier_code" type="text" class="form-control form-control-sm @error('new_supplier_code') is-invalid @enderror" placeholder="Code">
                                            @error('new_supplier_code') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-4 mt-1">
                                            <input wire:model="new_supplier_phone" type="text" class="form-control form-control-sm @error('new_supplier_phone') is-invalid @enderror" placeholder="Phone" x-data x-init="IMask($el, { mask: '0000-0000000' })">
                                            @error('new_supplier_phone') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-4 mt-1">
                                            <input wire:model="new_supplier_city" type="text" class="form-control form-control-sm @error('new_supplier_city') is-invalid @enderror" placeholder="City">
                                            @error('new_supplier_city') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-12 text-end mt-2">
                                            <button type="button" wire:click="addSupplier" class="btn btn-primary btn-xs px-2"><i class="fas fa-plus"></i> Add Supplier</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive border border-translucent rounded bg-body" style="max-height: 250px; overflow-y: auto;">
                                    <table class="table table-sm table-striped fs-11 mb-0">
                                        <tbody>
                                            @foreach($suppliers as $supplier)
                                                <tr>
                                                    <td class="px-2 py-1 fw-bold text-primary">{{ $supplier->name }}</td>
                                                    <td>{{ $this->formatPhoneForUi($supplier->mobile_number) }}</td>
                                                    <td class="text-end px-2">
                                                        <button type="button" wire:click="deleteSupplier({{ $supplier->id }})" class="btn btn-link btn-sm text-danger p-0"><i class="fas fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Units and Categories -->
                            <div class="col-md-6">
                                <h6 class="mb-2 fw-semi-bold text-primary">Units of Measure (UOM)</h6>
                                
                                <div class="bg-body-tertiary p-2 rounded mb-3 border border-translucent">
                                    <div class="row g-1">
                                        <div class="col-md-5">
                                            <input wire:model="new_unit_name" type="text" class="form-control form-control-sm @error('new_unit_name') is-invalid @enderror" placeholder="Name (e.g. Gram)">
                                            @error('new_unit_name') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <input wire:model="new_unit_code" type="text" class="form-control form-control-sm @error('new_unit_code') is-invalid @enderror" placeholder="Code">
                                            @error('new_unit_code') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <button type="button" wire:click="addInventoryUnit" class="btn btn-primary btn-xs w-100" style="height: 31px;"><i class="fas fa-plus"></i> Add Unit</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive border border-translucent rounded bg-body mb-3" style="max-height: 150px; overflow-y: auto;">
                                    <table class="table table-sm table-striped fs-11 mb-0">
                                        <tbody>
                                            @foreach($units as $unit)
                                                <tr>
                                                    <td class="px-2 py-1 fw-bold">{{ $unit->name }}</td>
                                                    <td><code>{{ $unit->short_code }}</code></td>
                                                    <td class="text-end px-2">
                                                        <button type="button" wire:click="deleteInventoryUnit({{ $unit->id }})" class="btn btn-link btn-sm text-danger p-0"><i class="fas fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- STEP 9: CONFIGURE FINANCE -->
                @if($currentStep == 9)
                    <div>
                        <h5 class="mb-3 border-bottom pb-2 text-primary"><span class="fas fa-dollar-sign me-2"></span>Step 9: Finance Setup & Cash Drawers</h5>
                        
                        <div class="row g-3 mb-4">
                            <!-- Cash Drawers -->
                            <div class="col-md-6 border-end border-translucent">
                                <h6 class="mb-2 fw-semi-bold text-primary">Petty Cash Drawers / Tills</h6>

                                <div class="bg-body-tertiary p-2 rounded mb-3 border border-translucent">
                                    <div class="row g-1">
                                        <div class="col-md-8">
                                            <input wire:model="new_cash_name" type="text" class="form-control form-control-sm @error('new_cash_name') is-invalid @enderror" placeholder="Drawer Name (e.g. Accounts Drawer)">
                                            @error('new_cash_name') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <input wire:model="new_cash_limit" type="number" class="form-control form-control-sm @error('new_cash_limit') is-invalid @enderror" placeholder="Limit Amount">
                                            @error('new_cash_limit') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-12 text-end mt-2">
                                            <button type="button" wire:click="addCashAccount" class="btn btn-primary btn-xs px-2"><i class="fas fa-plus"></i> Add Cash Drawer</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive border border-translucent rounded bg-body" style="max-height: 180px; overflow-y: auto;">
                                    <table class="table table-sm table-striped fs-11 mb-0">
                                        <tbody>
                                            @foreach($cashAccounts as $cash)
                                                <tr>
                                                    <td class="px-2 py-1 fw-bold text-primary">{{ $cash->account_name }}</td>
                                                    <td class="text-end">Limit: Rs. {{ number_format($cash->limit_amount, 0) }}</td>
                                                    <td class="text-end px-2">
                                                        <button type="button" wire:click="deleteCashAccount({{ $cash->id }})" class="btn btn-link btn-sm text-danger p-0"><i class="fas fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Bank Accounts -->
                            <div class="col-md-6">
                                <h6 class="mb-2 fw-semi-bold text-primary">Bank Accounts Directory</h6>

                                <div class="bg-body-tertiary p-2 rounded mb-3 border border-translucent">
                                    <div class="row g-1">
                                        <div class="col-md-6">
                                            <input wire:model="new_bank_name" type="text" class="form-control form-control-sm @error('new_bank_name') is-invalid @enderror" placeholder="Bank Name (e.g. HBL Bank)">
                                            @error('new_bank_name') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <input wire:model="new_bank_account_number" type="text" class="form-control form-control-sm @error('new_bank_account_number') is-invalid @enderror" placeholder="Account Number">
                                            @error('new_bank_account_number') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-12 mt-1">
                                            <input wire:model="new_bank_branch_name" type="text" class="form-control form-control-sm @error('new_bank_branch_name') is-invalid @enderror" placeholder="Branch Name/Code">
                                            @error('new_bank_branch_name') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-12 text-end mt-2">
                                            <button type="button" wire:click="addBankAccount" class="btn btn-primary btn-xs px-2"><i class="fas fa-plus"></i> Register Bank</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive border border-translucent rounded bg-body" style="max-height: 180px; overflow-y: auto;">
                                    <table class="table table-sm table-striped fs-11 mb-0">
                                        <tbody>
                                            @foreach($bankAccounts as $bank)
                                                <tr>
                                                    <td class="px-2 py-1 fw-bold text-primary">{{ $bank->account_name }}</td>
                                                    <td>{{ $bank->account_number }}</td>
                                                    <td class="text-end px-2">
                                                        <button type="button" wire:click="deleteBankAccount({{ $bank->id }})" class="btn btn-link btn-sm text-danger p-0"><i class="fas fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Root Chart of Accounts seed notification -->
                        <div class="alert alert-success border-1 mb-4 d-flex align-items-center" role="alert">
                            <span class="fas fa-check-circle me-2"></span>
                            <div class="fs-10">
                                <strong>Ledger Integration:</strong> System root ledger controls (Assets, Liabilities, Equity, Revenues, Expenses) have been generated automatically for this Marquee environment.
                            </div>
                        </div>

                        <!-- Financial Year Settings Form -->
                        <h6 class="mb-2 fw-semi-bold text-primary">Financial Year Range</h6>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label fs-11">Financial Year Name *</label>
                                <input wire:model="fy_name" type="text" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-11">Fiscal Start Date *</label>
                                <input wire:model="fy_start_date" type="date" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-11">Fiscal End Date *</label>
                                <input wire:model="fy_end_date" type="date" class="form-control form-control-sm" required>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Footer: Back and Forward Actions -->
            <div class="card-footer bg-light d-flex align-items-center justify-content-between py-3 border-top border-translucent">
                @if($currentStep > 1)
                    <button type="button" wire:click="prevStep" class="btn btn-link btn-sm text-secondary px-0"><span class="fas fa-chevron-left me-1"></span>Back</button>
                @else
                    <div></div>
                @endif

                @if($currentStep < $totalSteps)
                    <button type="button" wire:click="nextStep" class="btn btn-primary px-4 btn-sm">Save & Continue <span class="fas fa-chevron-right ms-1"></span></button>
                @else
                    <button type="button" wire:click="finishSetup" class="btn btn-success px-4 btn-sm">Finish Onboarding & Launch <span class="fas fa-rocket ms-1"></span></button>
                @endif
            </div>
        </div>
    </div>
</div>
