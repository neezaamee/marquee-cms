<div class="card theme-wizard mb-5" id="setupWizard">
    <!-- Header -->
    <div class="card-header bg-light pt-3 pb-2">
        <h4 class="mb-1 text-primary">Initial Business Configuration Setup</h4>
        <p class="mb-0 text-secondary fs-10">Please complete the following configurations to activate your banquet business ledger and unlock CMS modules.</p>
    </div>

    <!-- Step Indicators (Falcon Theme Style) -->
    <div class="card-header bg-body-tertiary pt-3 pb-2">
        <ul class="nav justify-content-between nav-wizard">
            <!-- Step 1 -->
            <li class="nav-item">
                <a class="nav-link {{ $currentStep == 1 ? 'active' : ($currentStep > 1 ? 'done' : '') }} fw-semi-bold" href="#" wire:click.prevent="goToStep(1)">
                    <span class="nav-item-circle-parent">
                        <span class="nav-item-circle">
                            @if($currentStep > 1)
                                <span class="fas fa-check"></span>
                            @else
                                <span class="fas fa-building"></span>
                            @endif
                        </span>
                    </span>
                    <span class="d-none d-md-block mt-1 fs-10">Marquee Info</span>
                </a>
            </li>
            <!-- Step 2 -->
            <li class="nav-item">
                <a class="nav-link {{ $currentStep == 2 ? 'active' : ($currentStep > 2 ? 'done' : '') }} fw-semi-bold" href="#" wire:click.prevent="goToStep(2)">
                    <span class="nav-item-circle-parent">
                        <span class="nav-item-circle">
                            @if($currentStep > 2)
                                <span class="fas fa-check"></span>
                            @else
                                <span class="fas fa-map-marker-alt"></span>
                            @endif
                        </span>
                    </span>
                    <span class="d-none d-md-block mt-1 fs-10">Branch Details</span>
                </a>
            </li>
            <!-- Step 3 -->
            <li class="nav-item">
                <a class="nav-link {{ $currentStep == 3 ? 'active' : ($currentStep > 3 ? 'done' : '') }} fw-semi-bold" href="#" wire:click.prevent="goToStep(3)">
                    <span class="nav-item-circle-parent">
                        <span class="nav-item-circle">
                            @if($currentStep > 3)
                                <span class="fas fa-check"></span>
                            @else
                                <span class="fas fa-hotel"></span>
                            @endif
                        </span>
                    </span>
                    <span class="d-none d-md-block mt-1 fs-10">Hall / Venue</span>
                </a>
            </li>
            <!-- Step 4 -->
            <li class="nav-item">
                <a class="nav-link {{ $currentStep == 4 ? 'active' : ($currentStep > 4 ? 'done' : '') }} fw-semi-bold" href="#" wire:click.prevent="goToStep(4)">
                    <span class="nav-item-circle-parent">
                        <span class="nav-item-circle">
                            @if($currentStep > 4)
                                <span class="fas fa-check"></span>
                            @else
                                <span class="fas fa-calculator"></span>
                            @endif
                        </span>
                    </span>
                    <span class="d-none d-md-block mt-1 fs-10">Financial Year</span>
                </a>
            </li>
            <!-- Step 5 -->
            <li class="nav-item">
                <a class="nav-link {{ $currentStep == 5 ? 'active' : '' }} fw-semi-bold" href="#" wire:click.prevent="goToStep(5)">
                    <span class="nav-item-circle-parent">
                        <span class="nav-item-circle">
                            <span class="fas fa-rocket"></span>
                        </span>
                    </span>
                    <span class="d-none d-md-block mt-1 fs-10">Defaults & Launch</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="card-body p-4 p-sm-5">
        <!-- Session & Validation Notifications -->
        @if ($errors->any())
            <div class="alert alert-warning border-2 d-flex align-items-center mb-4" role="alert">
                <span class="fas fa-exclamation-triangle me-2"></span>
                <div>Please resolve the highlighted validation errors before continuing.</div>
            </div>
        @endif

        <form wire:submit.prevent="finishSetup">
            <!-- STEP 1: MARQUEE INFO -->
            @if($currentStep == 1)
                <div>
                    <h5 class="mb-3 border-bottom pb-2"><span class="fas fa-building me-2 text-primary"></span>Step 1: Marquee Information</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="marquee_name">Marquee Business Name *</label>
                            <input wire:model="marquee_name" type="text" class="form-control @error('marquee_name') is-invalid @enderror" id="marquee_name" placeholder="e.g. Royal Banquet Hall & Marquee" required>
                            @error('marquee_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="business_type">Business Type *</label>
                            <select wire:model="business_type" class="form-select @error('business_type') is-invalid @enderror" id="business_type" required>
                                <option value="Single Marquee">Single Marquee</option>
                                <option value="Banquet Hall Chain">Banquet Hall Chain</option>
                                <option value="Lawn/Catering">Lawn / Catering</option>
                                <option value="Hotel Event Center">Hotel Event Center</option>
                            </select>
                            @error('business_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="email">Contact Email *</label>
                            <input wire:model="email" type="email" class="form-control @error('email') is-invalid @enderror" id="email" placeholder="e.g. info@royalmarquee.com" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="phone">Phone Number *</label>
                            <input wire:model="phone" type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" placeholder="e.g. +923001234567" required>
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="address">Corporate Address *</label>
                            <input wire:model="address" type="text" class="form-control @error('address') is-invalid @enderror" id="address" placeholder="e.g. Plot 45, Main Expressway" required>
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="province">Province / State *</label>
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

                        <div class="col-md-4">
                            <label class="form-label" for="city">City *</label>
                            <select wire:model="city" class="form-select @error('city') is-invalid @enderror" id="city" required {{ empty($province) ? 'disabled' : '' }}>
                                <option value="">Select City...</option>
                                @foreach($cities as $c)
                                    <option value="{{ $c }}">{{ $c }}</option>
                                @endforeach
                            </select>
                            @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="country">Country *</label>
                            <input wire:model="country" type="text" class="form-control @error('country') is-invalid @enderror" id="country" required>
                            @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="currency">Currency *</label>
                            <select wire:model="currency" class="form-select @error('currency') is-invalid @enderror" id="currency" required>
                                <option value="PKR">PKR (Rs.)</option>
                                <option value="USD">USD ($)</option>
                                <option value="AED">AED (Dh.)</option>
                            </select>
                            @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="timezone">Time Zone *</label>
                            <select wire:model="timezone" class="form-select @error('timezone') is-invalid @enderror" id="timezone" required>
                                <option value="Asia/Karachi">Asia/Karachi (GMT+5)</option>
                                <option value="UTC">UTC (GMT+0)</option>
                                <option value="Asia/Dubai">Asia/Dubai (GMT+4)</option>
                            </select>
                            @error('timezone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="tax_authority">Tax Authority *</label>
                            <select wire:model="tax_authority" class="form-select @error('tax_authority') is-invalid @enderror" id="tax_authority" required>
                                <option value="FBR">FBR (Federal)</option>
                                <option value="PRA">PRA (Punjab)</option>
                                <option value="SRB">SRB (Sindh)</option>
                                <option value="KPRA">KPRA (KPK)</option>
                                <option value="BRA">BRA (Balochistan)</option>
                            </select>
                            @error('tax_authority') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="ntn">NTN Number (Optional)</label>
                            <input wire:model="ntn" type="text" class="form-control @error('ntn') is-invalid @enderror" id="ntn" placeholder="e.g. 1234567-8">
                            @error('ntn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="strn">STRN (Sales Tax Reg. Number, Optional)</label>
                            <input wire:model="strn" type="text" class="form-control @error('strn') is-invalid @enderror" id="strn" placeholder="e.g. 9876543-2">
                            @error('strn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="logo">Company Logo (Optional)</label>
                            <input wire:model="logo" type="file" class="form-control @error('logo') is-invalid @enderror" id="logo" accept="image/*">
                            <div class="fs-10 text-muted mt-1">Accepts PNG, JPG (Max 2MB). Recommended square size.</div>
                            @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            @endif

            <!-- STEP 2: BRANCH CONFIG -->
            @if($currentStep == 2)
                <div>
                    <h5 class="mb-3 border-bottom pb-2"><span class="fas fa-map-marked-alt me-2 text-primary"></span>Step 2: Branch Configuration</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="branch_name">Branch Venue Name *</label>
                            <input wire:model="branch_name" type="text" class="form-control @error('branch_name') is-invalid @enderror" id="branch_name" required>
                            @error('branch_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="branch_phone">Branch Contact Phone *</label>
                            <input wire:model="branch_phone" type="text" class="form-control @error('branch_phone') is-invalid @enderror" id="branch_phone" placeholder="e.g. +92423123456" required>
                            @error('branch_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="branch_address">Branch Address *</label>
                            <input wire:model="branch_address" type="text" class="form-control @error('branch_address') is-invalid @enderror" id="branch_address" required>
                            @error('branch_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="branch_province">Province / State *</label>
                            <select wire:model.live="branch_province" class="form-select @error('branch_province') is-invalid @enderror" id="branch_province" required>
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
                            <label class="form-label" for="branch_city">City *</label>
                            <select wire:model="branch_city" class="form-select @error('branch_city') is-invalid @enderror" id="branch_city" required {{ empty($branch_province) ? 'disabled' : '' }}>
                                <option value="">Select City...</option>
                                @foreach($this->branchCities as $bc)
                                    <option value="{{ $bc }}">{{ $bc }}</option>
                                @endforeach
                            </select>
                            @error('branch_city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="branch_manager">Branch Manager Name (Optional)</label>
                            <input wire:model="branch_manager" type="text" class="form-control @error('branch_manager') is-invalid @enderror" id="branch_manager" placeholder="e.g. Asif Mehmood">
                            @error('branch_manager') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            @endif

            <!-- STEP 3: HALL CONFIG -->
            @if($currentStep == 3)
                <div>
                    <h5 class="mb-3 border-bottom pb-2"><span class="fas fa-hotel me-2 text-primary"></span>Step 3: Hall / Venue Configuration</h5>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" for="hall_name">Hall / Marquee Name *</label>
                            <input wire:model="hall_name" type="text" class="form-control @error('hall_name') is-invalid @enderror" id="hall_name" required>
                            @error('hall_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="hall_code">Unique Code *</label>
                            <input wire:model="hall_code" type="text" class="form-control @error('hall_code') is-invalid @enderror" id="hall_code" placeholder="e.g. ROYAL-HL" required>
                            @error('hall_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="capacity">Maximum Guest Capacity *</label>
                            <input wire:model="capacity" type="number" class="form-control @error('capacity') is-invalid @enderror" id="capacity" min="10" required>
                            @error('capacity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="hall_type">Hall Venue Type *</label>
                            <select wire:model="hall_type" class="form-select @error('hall_type') is-invalid @enderror" id="hall_type" required>
                                <option value="Marquee">Marquee (Outdoor Feeling)</option>
                                <option value="Banquet">Banquet Hall (Indoor)</option>
                                <option value="Lawn">Lawn (Garden / Open Air)</option>
                                <option value="Ballroom">Ballroom (Premium Class)</option>
                            </select>
                            @error('hall_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="default_booking_price">Default Rent / Booking Price (per Shift) *</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $currency }}</span>
                                <input wire:model="default_booking_price" type="number" class="form-control @error('default_booking_price') is-invalid @enderror" id="default_booking_price" min="0" required>
                            </div>
                            @error('default_booking_price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="hall_description">Short Description / Special features</label>
                            <textarea wire:model="hall_description" class="form-control @error('hall_description') is-invalid @enderror" id="hall_description" rows="3" placeholder="Describe the layout, central air conditioning, backup generator details..."></textarea>
                            @error('hall_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            @endif

            <!-- STEP 4: FINANCIAL YEAR -->
            @if($currentStep == 4)
                <div>
                    <h5 class="mb-3 border-bottom pb-2"><span class="fas fa-calendar-check me-2 text-primary"></span>Step 4: Active Financial Year</h5>
                    <p class="fs-10 text-muted">Double-entry accounting requires an open fiscal accounting period to process booking journals, vouchers, and cash flow reports. Let's create your first active financial year.</p>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="fy_name">Financial Year Name *</label>
                            <input wire:model="fy_name" type="text" class="form-control @error('fy_name') is-invalid @enderror" id="fy_name" required>
                            @error('fy_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="fy_start_date">Start Date *</label>
                            <input wire:model="fy_start_date" type="date" class="form-control @error('fy_start_date') is-invalid @enderror" id="fy_start_date" required>
                            @error('fy_start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="fy_end_date">End Date *</label>
                            <input wire:model="fy_end_date" type="date" class="form-control @error('fy_end_date') is-invalid @enderror" id="fy_end_date" required>
                            @error('fy_end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            @endif

            <!-- STEP 5: DEFAULTS & SAVE -->
            @if($currentStep == 5)
                <div>
                    <h5 class="mb-3 border-bottom pb-2"><span class="fas fa-sliders-h me-2 text-primary"></span>Step 5: Operational Settings & Launch</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="tax_rate">Default Provincial Sales Tax (%) *</label>
                            <div class="input-group">
                                <input wire:model="tax_rate" type="number" step="0.01" class="form-control @error('tax_rate') is-invalid @enderror" id="tax_rate" required>
                                <span class="input-group-text">%</span>
                            </div>
                            @error('tax_rate') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="default_payment_method">Primary Payment Method *</label>
                            <select wire:model="default_payment_method" class="form-select @error('default_payment_method') is-invalid @enderror" id="default_payment_method" required>
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Card">Credit / Debit Card</option>
                            </select>
                            @error('default_payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="booking_prefix">Booking Number Prefix</label>
                            <input wire:model="booking_prefix" type="text" class="form-control @error('booking_prefix') is-invalid @enderror" id="booking_prefix">
                            @error('booking_prefix') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="invoice_prefix">Invoice Prefix</label>
                            <input wire:model="invoice_prefix" type="text" class="form-control @error('invoice_prefix') is-invalid @enderror" id="invoice_prefix">
                            @error('invoice_prefix') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-light border rounded-3">
                        <h6 class="text-dark mb-2"><span class="fas fa-magic me-2 text-info"></span>Automated Setup Automation</h6>
                        <p class="fs-10 text-muted mb-0">Upon launching, the system will automatically pre-populate:
                            <br>• Standard Chart of Accounts (Cash, Bank, Accounts Receivable, Accounts Payable, etc.) for ledger postings.
                            <br>• Double shift timings ("Day Shift" and "Night Shift") linked to your new Hall.
                            <br>• Standard event type records (Wedding, Barat, Walima, Birthday, Seminar) to allow immediate booking entry.
                        </p>
                    </div>
                </div>
            @endif

            <!-- Navigation Buttons -->
            <div class="mt-5 d-flex justify-content-between border-top pt-4">
                <div>
                    @if($currentStep > 1)
                        <button wire:click.prevent="prevStep" class="btn btn-outline-secondary btn-sm" type="button">
                            <span class="fas fa-chevron-left me-1"></span> Back
                        </button>
                    @endif
                </div>
                
                <div>
                    <button wire:click.prevent="goToStep(1)" class="btn btn-link text-secondary btn-sm me-2" type="button">
                        Save and Exit Later
                    </button>
                    
                    @if($currentStep < $this->totalSteps)
                        <button wire:click.prevent="nextStep" class="btn btn-primary btn-sm" type="button">
                            Continue <span class="fas fa-chevron-right ms-1"></span>
                        </button>
                    @else
                        <button class="btn btn-success btn-sm" type="submit">
                            <span wire:loading class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Complete Setup & Launch <span class="fas fa-rocket ms-1"></span>
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
