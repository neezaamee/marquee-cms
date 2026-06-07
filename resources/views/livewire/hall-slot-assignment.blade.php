<div>
    <div class="card mb-3">
        <div class="card-header bg-light">
            <h5 class="mb-0">Hall Slot Assignments</h5>
            <p class="mb-0 text-muted fs-10">Map shift slots to individual halls. Note that bookings will only be allowed on dates and slots that are assigned to a hall.</p>
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
                <!-- Tenant filter for Super Admins -->
                @if(auth()->user()->isSuperAdmin())
                    <div class="col-md-4">
                        <label class="form-label" for="marquee_id">Select Marquee Tenant</label>
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
                    <label class="form-label" for="branch_id">Select Branch</label>
                    <select wire:model.live="branch_id" class="form-select form-select-sm" id="branch_id" {{ (auth()->user()->branch_id && !auth()->user()->isSuperAdmin()) ? 'disabled' : '' }}>
                        <option value="">Choose branch...</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->city }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Hall Selector -->
                <div class="col-md-4">
                    <label class="form-label" for="selectedHallId">Select Hall / Venue</label>
                    <select wire:model.live="selectedHallId" class="form-select form-select-sm" id="selectedHallId" {{ empty($halls) ? 'disabled' : '' }}>
                        <option value="">Choose hall...</option>
                        @foreach($halls as $hall)
                            <option value="{{ $hall->id }}">{{ $hall->hall_name }} ({{ strtoupper($hall->hall_code) }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Assignments Selection Section -->
            <div class="mt-4">
                @if($selectedHallId)
                    <h6 class="mb-3 border-bottom pb-2">Shift Slots for selected Hall:</h6>
                    
                    @if(count($activeSlots) > 0)
                        <div class="row g-3">
                            @foreach($activeSlots as $slot)
                                @php
                                    $isAssigned = in_array((string)$slot->id, $assignedSlotIds);
                                @endphp
                                <div class="col-md-6">
                                    <div class="border rounded p-3 bg-white d-flex align-items-center justify-content-between {{ $isAssigned ? 'border-primary' : '' }}">
                                        <div>
                                            <h6 class="mb-1 fw-bold">{{ $slot->slot_name }}</h6>
                                            <span class="badge badge-subtle-info me-2">
                                                <span class="fas fa-clock me-1"></span>
                                                {{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('h:i A') }}
                                            </span>
                                            <div class="text-muted fs-11 mt-1">{{ $slot->description ?? 'No description' }}</div>
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
                        <div class="alert alert-warning">
                            No active shift slots found. Please create active shift slots first under "Shift Slots".
                        </div>
                    @endif
                @else
                    <div class="text-center py-5 border rounded bg-white mt-3 text-muted">
                        <span class="fas fa-tasks fs-4 mb-2 d-block"></span>
                        <p class="mb-0">Please select a branch and a hall to manage shift slot assignments.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
