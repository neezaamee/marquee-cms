<div>
    <div class="card mb-3">
        <div class="card-header bg-body-tertiary">
            <div class="row flex-between-center">
                <div class="col-4 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">Hall Slot Assignments</h5>
                </div>
                <div class="col-8 col-sm-auto text-end ps-2">
                    <a class="btn btn-falcon-default btn-sm" href="{{ route('slots.index') }}">
                        <span class="fas fa-clock me-1"></span>Manage Shift Slots
                    </a>
                    <a class="btn btn-falcon-default btn-sm" href="{{ route('halls.index') }}">
                        <span class="fas fa-door-open me-1"></span>View Halls
                    </a>
                </div>
            </div>
            <p class="mb-0 text-muted fs-10 mt-1">Map active shift slots to individual banquet halls. Bookings will only be accepted on dates and slots configured for each hall.</p>
        </div>
        
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success border-2 d-flex align-items-center mb-3" role="alert">
                    <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-1">{{ session('success') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row g-3">
                <!-- Tenant filter for Super Admins / Multi-tenant Owners -->
                @if(auth()->user()->isSuperAdmin() || (auth()->user()->isBusinessOwner() && count($marquees) > 1))
                    <div class="col-md-4">
                        <label class="form-label fw-semi-bold" for="marquee_id">Select Marquee Tenant</label>
                        <select wire:model.live="marquee_id" class="form-select form-select-sm" id="marquee_id">
                            <option value="">Choose tenant...</option>
                            @foreach($marquees as $marquee)
                                <option value="{{ $marquee->id }}">{{ $marquee->name }} ({{ $marquee->city }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Branch selector -->
                <div class="col-md-4">
                    <label class="form-label fw-semi-bold" for="branch_id">Select Branch</label>
                    <select wire:model.live="branch_id" class="form-select form-select-sm" id="branch_id" {{ (auth()->user()->branch_id && !auth()->user()->isSuperAdmin() && !auth()->user()->isBusinessOwner() && !auth()->user()->isAreaManager()) ? 'disabled' : '' }}>
                        <option value="">Choose branch...</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->city }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Hall Selector -->
                <div class="col-md-4">
                    <label class="form-label fw-semi-bold" for="selectedHallId">Select Hall / Venue</label>
                    <select wire:model.live="selectedHallId" class="form-select form-select-sm" id="selectedHallId" {{ empty($halls) ? 'disabled' : '' }}>
                        <option value="">{{ empty($halls) ? 'Select a branch first...' : 'Choose hall...' }}</option>
                        @foreach($halls as $hall)
                            <option value="{{ $hall->id }}">{{ $hall->hall_name }} ({{ strtoupper($hall->hall_code) }}) - {{ number_format($hall->capacity) }} Pax</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Assignments Selection Section -->
            <div class="mt-4">
                @if($selectedHallId)
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h6 class="mb-0 fw-bold text-800">
                            <span class="fas fa-sliders-h text-primary me-2"></span>Available Shift Slots for Selected Hall
                        </h6>
                        <span class="badge bg-primary-subtle text-primary rounded-pill">
                            {{ count($assignedSlotIds) }} of {{ count($activeSlots) }} Assigned
                        </span>
                    </div>
                    
                    @if(count($activeSlots) > 0)
                        <div class="row g-3">
                            @foreach($activeSlots as $slot)
                                @php
                                    $isAssigned = in_array((string)$slot->id, $assignedSlotIds);
                                @endphp
                                <div class="col-md-6 col-xxl-4">
                                    <div class="border rounded p-3 bg-white shadow-sm d-flex align-items-center justify-content-between {{ $isAssigned ? 'border-primary bg-primary-subtle bg-opacity-10' : '' }}">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <h6 class="mb-0 fw-bold text-900">{{ $slot->slot_name }}</h6>
                                                @if($isAssigned)
                                                    <span class="badge bg-success-subtle text-success fs-11">Assigned</span>
                                                @endif
                                            </div>
                                            <span class="badge badge-subtle-info me-2">
                                                <span class="fas fa-clock me-1"></span>
                                                {{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('h:i A') }}
                                            </span>
                                            <div class="text-muted fs-11 mt-1">{{ $slot->description ?: 'No additional notes' }}</div>
                                        </div>
                                        <div>
                                            <div class="form-check form-switch mb-0">
                                                <input wire:click="toggleSlotAssignment({{ $slot->id }})" class="form-check-input cursor-pointer" type="checkbox" id="slotSwitch{{ $slot->id }}" {{ $isAssigned ? 'checked' : '' }} />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-warning border-0 d-flex align-items-center">
                            <span class="fas fa-exclamation-triangle fa-2x me-3 text-warning"></span>
                            <div>
                                <h6 class="alert-heading mb-1">No Active Shift Slots Found</h6>
                                <p class="mb-0 fs-10">Please configure active shift slots first (e.g., Morning, Evening, Night) under <a href="{{ route('slots.index') }}" class="alert-link">Shift Slots</a>.</p>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-5 border rounded bg-light mt-3 text-muted">
                        <span class="fas fa-tasks fs-3 mb-2 d-block text-400"></span>
                        <h6 class="fw-bold text-700">No Hall Selected</h6>
                        <p class="mb-0 fs-10">Please select a branch and a hall from the dropdowns above to view and configure shift slot assignments.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
