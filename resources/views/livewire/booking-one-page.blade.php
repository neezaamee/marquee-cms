<div>
    <!-- Top Action bar with Title -->
    <div class="card mb-3">
        <div class="card-body p-3">
            <div class="row justify-content-between align-items-center">
                <div class="col-auto">
                    <h5 class="mb-0"><span class="fas fa-file-alt me-2 text-primary"></span>New One-Page Booking Form</h5>
                </div>
                <div class="col-auto">
                    <span class="badge badge-subtle-primary fs-11 font-monospace">Single Step Booking</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert / Validation / Status Warnings -->
    @if(session('success'))
        <div class="alert alert-success border-2 d-flex align-items-center mb-3" role="alert">
            <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
            <p class="mb-0 flex-grow-1 text-success-800">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning border-2 d-flex align-items-center mb-3" role="alert">
            <div class="bg-warning me-2 icon-item"><span class="fas fa-exclamation-triangle text-white fs-11"></span></div>
            <span class="mb-0 flex-grow-1 text-warning-800 fs-11 fw-semi-bold">{{ session('warning') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-2 d-flex align-items-start mb-3" role="alert">
            <div class="bg-danger me-3 icon-item mt-1"><span class="fas fa-times-circle text-white fs-8"></span></div>
            <div class="flex-grow-1 text-danger-800">
                <h6 class="alert-heading text-danger mb-1 fw-bold">Please correct the following errors:</h6>
                <ul class="mb-0 fs-11 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="row g-3">
        <!-- Left Column: inputs -->
        <div class="col-lg-8">
            
            <!-- PANEL 1: CUSTOMER SELECTION -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold"><span class="fas fa-user-circle me-2 text-primary"></span>Customer Profile</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label font-sans-serif fw-bold text-700">Search Existing Customer *</label>
                            <div class="position-relative">
                                <input wire:model.live.debounce.250ms="customerSearch" class="form-control" type="text" placeholder="Type first name, last name, phone, or customer code..." />
                                @if(!empty($customerSearch) && empty($selectedCustomerId))
                                    <div class="position-absolute bg-white border rounded shadow w-100 z-3 mt-1 overflow-hidden">
                                        @forelse($customersList as $cust)
                                            <button wire:click="selectCustomer({{ $cust->id }})" class="btn btn-link w-100 text-start text-decoration-none text-900 py-2 px-3 hover-bg-light border-bottom border-translucent" type="button">
                                                <span class="fw-bold">{{ $cust->full_name }}</span> 
                                                <span class="badge badge-subtle-secondary ms-1">{{ $cust->customer_code }}</span> 
                                                <span class="text-secondary fs-11 float-end">{{ $cust->phone_number }}</span>
                                            </button>
                                        @empty
                                            <div class="text-center py-2 fs-11 text-muted">No active profiles found.</div>
                                        @endforelse
                                    </div>
                                @endif
                            </div>
                            <div class="d-flex align-items-center justify-content-between mt-2">
                                <span class="text-muted fs-11">Search will automatically list active profiles matching the details.</span>
                                @if($showQuickCustomerModal)
                                    <button wire:click="$set('showQuickCustomerModal', false)" class="btn btn-link text-danger fs-11 p-0 text-decoration-none" type="button">
                                        <span class="fas fa-times me-1"></span> Cancel New Profile
                                    </button>
                                @else
                                    <button wire:click="$set('showQuickCustomerModal', true)" class="btn btn-link text-success fs-11 p-0 text-decoration-none" type="button">
                                        <span class="fas fa-user-plus me-1"></span> Quick Create Customer
                                    </button>
                                @endif
                            </div>
                            @error('selectedCustomerId') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <!-- QUICK CUSTOMER CREATION BLOCK -->
                    @if($showQuickCustomerModal)
                        <div class="card border border-success border-2 mt-3 bg-success-subtle bg-opacity-10">
                            <div class="card-header bg-success-subtle py-2">
                                <h6 class="mb-0 text-success-800 fw-bold fs-11">
                                    <span class="fas fa-user-plus me-2"></span>Quick Add New Customer Profile
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Customer Type *</label>
                                        <div class="form-check form-check-inline">
                                            <input wire:model.live="newCustomerType" class="form-check-input" type="radio" id="new_type_ind" value="Individual">
                                            <label class="form-check-label cursor-pointer" for="new_type_ind">Individual</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input wire:model.live="newCustomerType" class="form-check-input" type="radio" id="new_type_corp" value="Corporate">
                                            <label class="form-check-label cursor-pointer" for="new_type_corp">Corporate</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="newFirstName">First Name *</label>
                                        <input wire:model="newFirstName" class="form-control form-control-sm" id="newFirstName" type="text" placeholder="e.g. Ajmal" />
                                        @error('newFirstName') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="newLastName">Last Name *</label>
                                        <input wire:model="newLastName" class="form-control form-control-sm" id="newLastName" type="text" placeholder="e.g. Khan" />
                                        @error('newLastName') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    @if($newCustomerType === 'Corporate')
                                        <div class="col-12">
                                            <label class="form-label" for="newCompanyName">Company Name *</label>
                                            <input wire:model="newCompanyName" class="form-control form-control-sm" id="newCompanyName" type="text" placeholder="e.g. Acme Corporation" />
                                            @error('newCompanyName') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    @endif

                                    <div class="col-md-6">
                                        <label class="form-label" for="newPhone">Phone Number *</label>
                                        <input type="text" id="newPhone" class="form-control form-control-sm" placeholder="e.g. +923001234567" x-data x-init="IMask($el, { mask: '+920000000000' })" wire:model.blur="newPhone" />
                                        @error('newPhone') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="newCNIC">CNIC Number</label>
                                        <input type="text" id="newCNIC" class="form-control form-control-sm" placeholder="e.g. 35201-1234567-1" x-data x-init="IMask($el, { mask: '00000-0000000-0' })" wire:model.blur="newCNIC" />
                                        @error('newCNIC') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="newEmail">Email Address</label>
                                        <input wire:model="newEmail" class="form-control form-control-sm" id="newEmail" type="email" placeholder="e.g. customer@example.com" />
                                        @error('newEmail') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="newNTN">NTN Number</label>
                                        <input wire:model="newNTN" class="form-control form-control-sm" id="newNTN" type="text" placeholder="e.g. 1234567-8" />
                                        @error('newNTN') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-md-6">
                                         <label class="form-label" for="newReferralName">Referral Name</label>
                                         <input wire:model="newReferralName" class="form-control form-control-sm" id="newReferralName" type="text" placeholder="e.g. Saleem Akhter" />
                                         @error('newReferralName') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                     </div>

                                     <div class="col-md-6">
                                         <label class="form-label" for="newReferralContact">Referral Contact</label>
                                         <input type="text" id="newReferralContact" class="form-control form-control-sm" placeholder="e.g. 0322-1234567" x-data x-init="IMask($el, { mask: '0000-0000000' })" wire:model.blur="newReferralContact" />
                                         @error('newReferralContact') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                     </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="newGender">Gender</label>
                                        <select wire:model="newGender" class="form-select form-select-sm" id="newGender">
                                            <option value="">Choose...</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="newCity">City *</label>
                                        <select wire:model="newCity" class="form-select form-select-sm" id="newCity">
                                            @foreach($cities as $c)
                                                <option value="{{ $c }}">{{ $c }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12 mt-3 text-end">
                                        <button wire:click="createCustomer" class="btn btn-success btn-sm px-4" type="button">
                                            Save & Select Customer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- SELECTED CUSTOMER DETAIL CARD -->
                    @if($selectedCustomerId)
                        @php $customer = \App\Models\Customer::find($selectedCustomerId); @endphp
                        @if($customer)
                            <div class="card border border-primary mt-3 bg-light">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-xl me-3 bg-200" style="border-radius:50%;width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
                                            <span class="fas fa-user text-primary"></span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-primary">Selected: {{ $customer->full_name }}</h6>
                                            <p class="mb-0 fs-11 text-600">
                                                Code: <strong>{{ $customer->customer_code }}</strong> | 
                                                Phone: <strong>{{ $customer->phone_number }}</strong> | 
                                                Email: <strong>{{ $customer->email ?? '—' }}</strong> | 
                                                NTN: <strong>{{ $customer->ntn_number ?? '—' }}</strong> | 
                                                Referral: <strong>{{ $customer->referred_by_name ?? '—' }}</strong> | 
                                                City: <strong>{{ $customer->city }}</strong>
                                            </p>
                                        </div>
                                        <div>
                                            <button wire:click="$set('selectedCustomerId', ''); $set('customerSearch', '')" class="btn btn-link text-danger p-0 text-decoration-none" type="button" title="Clear selection">
                                                <span class="fas fa-trash-alt me-1"></span>Clear
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <!-- PANEL 2: EVENT DETAILS & LOCATION -->
            <div class="card mb-3 text-start">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><span class="fas fa-map-marker-alt me-2 text-primary"></span>Booking Location & Event Schedule</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Branch Selection Section -->
                        <div class="col-12">
                            <label class="form-label font-sans-serif fw-bold text-700" for="selectedBranchId">Branch / Facility Location *</label>
                            @if($isMultiBranchUser)
                                <select wire:model.live="selectedBranchId" class="form-select @error('selectedBranchId') is-invalid @enderror" id="selectedBranchId">
                                    <option value="">Choose Branch...</option>
                                    @foreach($branchesList as $b)
                                        <option value="{{ $b->id }}">
                                            {{ $b->name }} @if($b->is_head_office) (Head Office) @endif — {{ $b->city }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('selectedBranchId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text fs-12 text-600 mt-1">
                                    <span class="fas fa-info-circle me-1 text-primary"></span>Halls and pricing are dynamically filtered by the selected physical branch location.
                                </div>
                            @else
                                <div class="p-2 border rounded bg-light d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-l me-2 bg-primary-subtle rounded-circle p-2 d-flex align-items-center justify-content-center">
                                            <span class="fas fa-building text-primary"></span>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-800 fs-12">
                                                {{ $autoSelectedBranch?->name ?? ($branchesList->first()?->name ?? 'Main Branch') }}
                                                @if($autoSelectedBranch?->is_head_office)
                                                    <span class="badge badge-subtle-primary ms-1 fs-12">Head Office</span>
                                                @endif
                                            </div>
                                            <div class="fs-11 text-600">
                                                <span class="fas fa-map-marker-alt me-1"></span>{{ $autoSelectedBranch?->address ?? '' }}, {{ $autoSelectedBranch?->city ?? '' }}
                                                @if($autoSelectedBranch?->phone) | <span class="fas fa-phone me-1"></span>{{ $autoSelectedBranch?->phone }} @endif
                                            </div>
                                        </div>
                                    </div>
                                    <span class="badge badge-subtle-success fs-12"><span class="fas fa-lock me-1"></span>Active Branch</span>
                                </div>
                            @endif
                        </div>

                        <!-- Event Type Dropdown -->
                        <div class="col-md-6">
                            <label class="form-label font-sans-serif fw-bold text-700" for="selectedEventTypeId">Event Type *</label>
                            <select wire:model.live="selectedEventTypeId" class="form-select @error('selectedEventTypeId') is-invalid @enderror" id="selectedEventTypeId">
                                <option value="">Choose Event Type...</option>
                                @foreach($eventTypesList as $ev)
                                    <option value="{{ $ev->id }}">{{ $ev->event_type_name }}</option>
                                @endforeach
                            </select>
                            @error('selectedEventTypeId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Date -->
                        <div class="col-md-6">
                            <label class="form-label font-sans-serif fw-bold text-700" for="selectedDate">Event Date *</label>
                            <input wire:model.live="selectedDate" class="form-control font-monospace @error('selectedDate') is-invalid @enderror" type="date" id="selectedDate" />
                            @error('selectedDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Select Multiple Halls -->
                        <div class="col-12">
                            <label class="form-label font-sans-serif fw-bold text-700">Select Venue Hall(s) *</label>
                            @if(empty($selectedBranchId))
                                <div class="alert alert-subtle-warning fs-12 py-2 mb-0" role="alert">
                                    <span class="fas fa-exclamation-triangle me-1"></span>Please select a Branch above to view and assign halls.
                                </div>
                            @elseif($hallsList->isEmpty())
                                <div class="alert alert-subtle-info fs-12 py-2 mb-0" role="alert">
                                    <span class="fas fa-info-circle me-1"></span>No active halls configured for this branch. Please create a hall in Branch Settings.
                                </div>
                            @else
                                <div class="row g-2">
                                    @foreach($hallsList as $hall)
                                        <div class="col-md-6 col-xxl-4">
                                            <div class="card border h-100 cursor-pointer transition-base hover-shadow {{ in_array((string)$hall->id, $selectedHallIds) ? 'border-primary bg-primary-subtle bg-opacity-25' : '' }}" wire:click="toggleHall({{ $hall->id }})">
                                                <div class="card-body p-3">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div>
                                                            <h6 class="mb-1 {{ in_array((string)$hall->id, $selectedHallIds) ? 'text-primary fw-bold' : 'text-900' }}">{{ $hall->hall_name }}</h6>
                                                            <span class="fs-11 text-600">Capacity: {{ $hall->capacity }} guests</span>
                                                            @if($hall->default_booking_price > 0)
                                                                <div class="fs-11 text-primary fw-semi-bold">Rent: Rs. {{ number_format($hall->default_booking_price) }}</div>
                                                            @endif
                                                        </div>
                                                        <div>
                                                            @if(in_array((string)$hall->id, $selectedHallIds))
                                                                <span class="fas fa-check-circle text-primary fs-8"></span>
                                                            @else
                                                                <span class="far fa-circle text-300 fs-8"></span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            @error('selectedHallIds') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- PANEL 3: SCHEDULING & SLOT CHECKER -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold"><span class="fas fa-clock me-2 text-primary"></span>Scheduling & Shifts</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label font-sans-serif fw-bold text-700">Checking Method</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input wire:model.live="checkType" class="form-check-input" type="radio" name="checkType" id="onepage_check_slot" value="slot">
                                    <label class="form-check-label cursor-pointer text-800" for="onepage_check_slot">Predefined Shift Slot</label>
                                </div>
                                <div class="form-check">
                                    <input wire:model.live="checkType" class="form-check-input" type="radio" name="checkType" id="onepage_check_custom" value="custom">
                                    <label class="form-check-label cursor-pointer text-800" for="onepage_check_custom">Custom Time Range</label>
                                </div>
                            </div>
                        </div>

                        @if($checkType === 'slot')
                            <div class="col-12">
                                <label class="form-label font-sans-serif fw-bold text-700" for="selectedSlotId">Predefined Shift Slot *</label>
                                <select wire:model.live="selectedSlotId" class="form-select" id="selectedSlotId">
                                    <option value="">Choose a slot...</option>
                                    @foreach($availableSlotsList as $sl)
                                        <option value="{{ $sl['id'] }}" {{ !$sl['is_available'] ? 'disabled' : '' }}>
                                            {{ $sl['name'] }} ({{ $sl['start'] }} - {{ $sl['end'] }}) {{ !$sl['is_available'] ? '[BOOKED]' : '[FREE]' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('selectedSlotId') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                            </div>
                        @else
                            <div class="col-md-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="customStart">Start Time *</label>
                                <input wire:model.live="customStart" class="form-control font-monospace" type="time" id="customStart" />
                                @error('customStart') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="customEnd">End Time *</label>
                                <input wire:model.live="customEnd" class="form-control font-monospace" type="time" id="customEnd" />
                                @error('customEnd') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                            </div>
                        @endif
                    </div>

                    <!-- Availability Alerts -->
                    @if($availabilityChecked)
                        <div class="border-top pt-3 mt-4">
                            @if($isAvailable)
                                <div class="alert alert-success border-2 d-flex align-items-center mb-0" role="alert">
                                    <div class="bg-success me-3 icon-item">
                                        <span class="fas fa-check-circle text-white fs-8"></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="alert-heading text-success mb-0 fw-bold">SCHEDULE AVAILABLE</h6>
                                        <span class="fs-11 text-success-800">The selected slots/timings are free and ready for booking.</span>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-danger border-2 d-flex align-items-center mb-3" role="alert">
                                    <div class="bg-danger me-3 icon-item">
                                        <span class="fas fa-times-circle text-white fs-8"></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="alert-heading text-danger mb-0 fw-bold">SCHEDULE CONFLICT DETECTED</h6>
                                        <span class="fs-11 text-danger-800">Timings conflict with another booking. Please check details below.</span>
                                    </div>
                                </div>

                                @if($conflictDetails)
                                    <div class="card border border-2 border-danger shadow-sm mt-2">
                                        <div class="card-header bg-danger-subtle py-1">
                                            <span class="text-danger-800 fw-bold fs-11"><span class="fas fa-exclamation-triangle me-1"></span>Conflict Details</span>
                                        </div>
                                        <div class="card-body py-2 px-3 fs-11">
                                            <div>Booking Code: <strong>{{ $conflictDetails->booking_number }}</strong></div>
                                            <div>Status: <span class="badge badge-subtle-danger rounded-pill">{{ $conflictDetails->booking_status }}</span></div>
                                            <div>Conflicting Hall: <strong>{{ $conflictDetails->hall->hall_name ?? 'N/A' }}</strong></div>
                                            <div>Conflict Bounds: <span class="font-monospace fw-bold">{{ $conflictDetails->start_time->format('h:i A') }} → {{ $conflictDetails->end_time->format('h:i A') }}</span></div>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif
                    @error('availability') <div class="text-danger fs-11 mt-2">{{ $message }}</div> @enderror
                </div>
            </div>

            <!-- Predefined Shift Availability timeline -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold"><span class="fas fa-calendar-check me-2 text-primary"></span>Shift Availability Timeline</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped fs-11 mb-0 align-middle">
                            <thead class="bg-200">
                                <tr>
                                    <th class="px-3">Shift Name</th>
                                    <th>Schedule Times</th>
                                    <th class="text-center" style="width: 100px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($availableSlotsList as $sl)
                                    <tr>
                                        <td class="px-3 fw-semi-bold text-900">{{ $sl['name'] }}</td>
                                        <td class="font-monospace text-secondary">{{ $sl['start'] }} - {{ $sl['end'] }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-subtle-{{ $sl['is_available'] ? 'success' : 'danger' }} rounded-pill">
                                                {{ $sl['is_available'] ? 'Available' : 'Booked' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-3 text-muted">No predefined shifts setup for the selected Hall(s).</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PANEL 4: CATERING & MATH -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold"><span class="fas fa-utensils me-2 text-primary"></span>Catering & Package Plan</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Sitting Plan Switch -->
                        <div class="col-12">
                            <div class="form-check form-switch bg-light p-3 border rounded">
                                <input class="form-check-input ms-0" type="checkbox" id="onepage_noFood" wire:model.live="noFood" />
                                <label class="form-check-label fw-bold cursor-pointer ms-2 text-primary" for="onepage_noFood">
                                    Sitting Plan Only (No Food Catering)
                                </label>
                            </div>
                        </div>

                        <!-- Package Select -->
                        @if(!$noFood)
                            <div class="col-12">
                                <label class="form-label font-sans-serif fw-bold text-700" for="selectedPackageId">Select Package *</label>
                                <select wire:model.live="selectedPackageId" class="form-select @error('selectedPackageId') is-invalid @enderror" id="selectedPackageId">
                                    <option value="">Choose Package...</option>
                                    @foreach($packagesList as $pkg)
                                        <option value="{{ $pkg->id }}">{{ $pkg->package_name }} (Rs. {{ number_format($pkg->per_plate_price) }}/plate)</option>
                                    @endforeach
                                </select>
                                @error('selectedPackageId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endif

                        <!-- Guest Count -->
                        <div class="col-md-6">
                            <label class="form-label font-sans-serif fw-bold text-700" for="guestCount">Guest Count *</label>
                            <input wire:model.live="guestCount" class="form-control" type="number" id="guestCount" min="1" />
                            @error('guestCount') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <!-- Per Plate Price -->
                        @if(!$noFood)
                            <div class="col-md-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="perPlatePrice">Per Plate Rate *</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rs.</span>
                                    <input wire:model.live="perPlatePrice" class="form-control font-monospace" type="number" id="perPlatePrice" step="0.01" />
                                </div>
                                @error('perPlatePrice') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                            </div>
                        @endif

                        <!-- Hall Rent Charges -->
                        <div class="col-md-6">
                            <label class="form-label font-sans-serif fw-bold text-700" for="hallCharges">Hall Rent Charges</label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input wire:model.live="hallCharges" class="form-control font-monospace" type="number" id="hallCharges" step="0.01" />
                            </div>
                            @error('hallCharges') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <!-- Refundable Security Deposit -->
                        <div class="col-md-6">
                            <label class="form-label font-sans-serif fw-bold text-700" for="securityDeposit">Refundable Security Deposit</label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input wire:model.live="securityDeposit" class="form-control font-monospace" type="number" id="securityDeposit" step="0.01" />
                            </div>
                            <span class="text-muted fs-11">Security deposit is tracked separately, NOT counted as direct event revenue.</span>
                            @error('securityDeposit') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <!-- Discount -->
                        <div class="col-md-6">
                            <label class="form-label font-sans-serif fw-bold text-700" for="discountAmount">Discount Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input wire:model.live="discountAmount" class="form-control font-monospace" type="number" id="discountAmount" step="0.01" />
                            </div>
                            @error('discountAmount') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <!-- Tax Rate -->
                        <div class="col-md-6">
                            <label class="form-label font-sans-serif fw-bold text-700" for="taxRate">Tax Rate (%)</label>
                            <div class="input-group">
                                <input wire:model.live="taxRate" class="form-control font-monospace" type="number" id="taxRate" step="0.01" />
                                <span class="input-group-text">%</span>
                            </div>
                            @error('taxRate') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <!-- Privacy / Partition -->
                        <div class="col-12 mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="onepage_privacyRequired" wire:model.live="privacyRequired" />
                                <label class="form-check-label fw-bold cursor-pointer" for="onepage_privacyRequired">Privacy / Partition Required?</label>
                            </div>
                        </div>

                        @if($privacyRequired)
                            <div class="col-md-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="privacyLadiesPercentage">Ladies Percentage (%) *</label>
                                <div class="input-group">
                                    <input wire:model.live="privacyLadiesPercentage" class="form-control @error('privacyLadiesPercentage') is-invalid @enderror" type="number" id="privacyLadiesPercentage" min="0" max="100" />
                                    <span class="input-group-text">%</span>
                                </div>
                                @error('privacyLadiesPercentage') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="privacyGentsPercentage">Gents Percentage (%) *</label>
                                <div class="input-group">
                                    <input wire:model.live="privacyGentsPercentage" class="form-control @error('privacyGentsPercentage') is-invalid @enderror" type="number" id="privacyGentsPercentage" min="0" max="100" />
                                    <span class="input-group-text">%</span>
                                </div>
                                @error('privacyGentsPercentage') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- PANEL 5: CUSTOM MENU CUSTOMIZATION -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold"><span class="fas fa-list-ul me-2 text-primary"></span>Customize Event Menu Items</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2 align-items-center mb-3">
                        <div class="col-12">
                            <label class="form-label font-sans-serif fw-bold text-700">Search and Add Dish</label>
                            <div class="position-relative" x-data="{ open: false }" @click.outside="open = false">
                                <input wire:model.live.debounce.250ms="menuItemSearch" class="form-control form-control-sm" type="text" placeholder="Type dish name to search..." @focus="open = true" @click="open = true" />
                                <div x-show="open" class="position-absolute bg-white border rounded shadow w-100 z-3 mt-1 overflow-hidden" style="max-height: 200px; overflow-y: auto; display: none;">
                                    @forelse($menuItemsAutocomplete as $mi)
                                        <button wire:click="selectMenuItem({{ $mi->id }}); open = false;" class="btn btn-link w-100 text-start text-decoration-none text-900 py-1.5 px-3 hover-bg-light border-bottom border-translucent" type="button">
                                            <span class="fw-bold">{{ $mi->item_name }}</span>
                                            @if($mi->urdu_name)
                                                <span class="text-muted fs-11 ms-1">({{ $mi->urdu_name }})</span>
                                            @endif
                                            <span class="badge badge-subtle-secondary ms-1 fs-12">{{ $mi->category->category_name ?? 'N/A' }}</span>
                                        </button>
                                    @empty
                                        <div class="text-center py-2 fs-11 text-muted">No matching dishes.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(count($bookingMenuItems) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm bg-white border fs-11 mb-0">
                                <thead>
                                    <tr class="bg-200">
                                        <th>Dish Name</th>
                                        <th>Custom Instructions (e.g. extra spicy)</th>
                                        <th class="text-center" style="width: 150px;">Managed by Host</th>
                                        <th class="text-center" style="width: 120px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bookingMenuItems as $idx => $item)
                                        <tr>
                                            <td class="align-middle fw-bold text-700">
                                                {{ $item['item_name'] }}
                                                @if(!empty($item['urdu_name']))
                                                    <span class="text-muted fs-11 d-block">{{ $item['urdu_name'] }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" placeholder="No instructions" wire:model="bookingMenuItems.{{ $idx }}.custom_note" />
                                            </td>
                                            <td class="text-center align-middle">
                                                <input class="form-check-input" type="checkbox" wire:model.live="bookingMenuItems.{{ $idx }}.managed_by_host" id="managed_by_host_{{ $idx }}" {{ $noFood ? 'disabled checked' : '' }} />
                                            </td>
                                            <td class="align-middle text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <button type="button" class="btn btn-sm btn-link text-secondary p-0" wire:click="moveMenuItemUp({{ $idx }})" {{ $idx === 0 ? 'disabled' : '' }} title="Move Up">
                                                        <span class="fas fa-arrow-up fs-11"></span>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-link text-secondary p-0" wire:click="moveMenuItemDown({{ $idx }})" {{ $idx === count($bookingMenuItems) - 1 ? 'disabled' : '' }} title="Move Down">
                                                        <span class="fas fa-arrow-down fs-11"></span>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-link text-danger p-0" wire:click="removeMenuItem({{ $idx }})" title="Remove">
                                                        <span class="fas fa-trash-alt fs-11"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-3 bg-light border rounded">
                            <span class="fas fa-info-circle me-1"></span>No dishes currently selected. Add custom dishes or choose a package.
                        </div>
                    @endif
                </div>
            </div>

            <!-- PANEL 6: EXTRA SERVICES & ADD-ONS -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold"><span class="fas fa-cubes me-2 text-primary"></span>Select Extra Add-ons & Services</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped border fs-11 mb-0">
                            <thead>
                                <tr class="bg-light">
                                    <th class="text-center" style="width: 50px;">Select</th>
                                    <th>Service Name</th>
                                    <th style="width: 150px;">Price (PKR)</th>
                                    <th style="width: 100px;">Qty</th>
                                    <th style="width: 120px;" class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($addonsList as $addon)
                                    <tr>
                                        <td class="align-middle text-center">
                                            <input class="form-check-input" type="checkbox" wire:model.live="selectedAddons.{{ $addon->id }}.selected" />
                                        </td>
                                        <td class="align-middle fw-semi-bold">{{ $addon->service_name }}</td>
                                        <td class="align-middle">
                                            <input class="form-control form-control-sm text-end" type="number" step="0.01" wire:model.live="selectedAddons.{{ $addon->id }}.price" {{ empty($selectedAddons[$addon->id]['selected']) ? 'disabled' : '' }} />
                                        </td>
                                        <td class="align-middle">
                                            <input class="form-control form-control-sm text-center" type="number" min="1" wire:model.live="selectedAddons.{{ $addon->id }}.quantity" {{ empty($selectedAddons[$addon->id]['selected']) ? 'disabled' : '' }} />
                                        </td>
                                        <td class="align-middle text-end font-monospace fw-bold text-700">
                                            Rs. {{ number_format(floatval($selectedAddons[$addon->id]['price'] ?? 0) * intval($selectedAddons[$addon->id]['quantity'] ?? 1), 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: review panel & details -->
        <div class="col-lg-4">
            <div class="position-sticky" style="top: 85px;">
                <!-- PRICING BREAKDOWN CARD -->
                <div class="card border border-primary mb-3">
                    <div class="card-header bg-primary py-2 text-white d-flex align-items-center justify-content-between">
                        <h6 class="mb-0 text-white"><span class="fas fa-file-invoice-dollar me-2"></span>Invoice Summary</h6>
                        <span class="badge badge-subtle-light text-primary font-monospace fs-11">Live Preview</span>
                    </div>
                    <div class="card-body">
                        @if(!$noFood)
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-600">Base Package Amount:</span>
                                <span class="font-monospace text-900">Rs. {{ number_format($packageAmount, 2) }}</span>
                            </div>
                        @else
                            <div class="d-flex justify-content-between mb-2 text-secondary fw-bold">
                                <span>Catering Plan:</span>
                                <span>Sitting Plan Only</span>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-600">Hall Rent:</span>
                            <span class="font-monospace text-900">Rs. {{ number_format($hallCharges, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-600">Extra Services Fee:</span>
                            <span class="font-monospace text-900">Rs. {{ number_format($extraCharges, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between text-danger mb-3 border-bottom pb-2">
                            <span>Discount Allowed:</span>
                            <span class="font-monospace">- Rs. {{ number_format($discountAmount, 2) }}</span>
                        </div>
                        
                        <div class="d-flex justify-content-between fw-bold mb-2">
                            <span>Subtotal (Before Tax):</span>
                            <span class="font-monospace text-900">Rs. {{ number_format($subtotal, 2) }}</span>
                        </div>
                        
                        <div class="d-flex justify-content-between text-secondary mb-2 border-bottom pb-2">
                            <span>Tax ({{ $taxRate }}%):</span>
                            <span class="font-monospace">Rs. {{ number_format($taxAmount, 2) }}</span>
                        </div>

                        <div class="d-flex justify-content-between text-info mb-3 border-bottom pb-2">
                            <span>Refundable Deposit:</span>
                            <span class="font-monospace">Rs. {{ number_format($securityDeposit, 2) }}</span>
                        </div>

                        <div class="d-flex justify-content-between fw-black fs-8 text-primary mt-3 mb-0">
                            <span>Grand Total:</span>
                            <span class="font-monospace fs-7">Rs. {{ number_format($grandTotal, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- REVIEW DETAILS CARD -->
                <div class="card mb-3">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0 fw-bold"><span class="fas fa-clipboard-list me-2 text-primary"></span>Review & Status</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Special Instructions -->
                            <div class="col-12">
                                <label class="form-label font-sans-serif fw-bold text-700" for="specialInstructions">Special Instructions / Notes</label>
                                <textarea wire:model="specialInstructions" class="form-control form-control-sm" id="specialInstructions" rows="3" placeholder="Enter custom setup details..."></textarea>
                                @error('specialInstructions') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                            </div>

                            <!-- Booking Status Selection -->
                            <div class="col-12">
                                <label class="form-label font-sans-serif fw-bold text-700" for="bookingStatus">Initial Booking Status *</label>
                                <select wire:model="bookingStatus" class="form-select form-select-sm" id="bookingStatus">
                                    <option value="Draft">Draft (No lock)</option>
                                    <option value="Reserved">Reserved (Blocks slot)</option>
                                    <option value="Confirmed">Confirmed (Blocks slot)</option>
                                </select>
                                <p class="text-muted fs-11 mt-1 mb-0">Select 'Reserved' to hold slot availability temporarily, or 'Confirmed' for fully authorized events.</p>
                                @error('bookingStatus') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACTION SUBMIT CARD -->
                <div class="card bg-light border-2">
                    <div class="card-body p-3">
                        <button wire:click="submitBooking" wire:loading.attr="disabled" class="btn btn-primary w-100 py-2 fs-10 fw-bold" type="button">
                            <span wire:loading class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            <span wire:loading.remove class="fas fa-save me-2"></span>Create Booking & Generate Invoice
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
