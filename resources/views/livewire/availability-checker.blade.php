<div>
    <div class="row g-3">
        <!-- LEFT COLUMN: AVAILABILITY ENGINE INPUTS -->
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><span class="fas fa-search me-2 text-primary"></span>Availability Search</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if($isMultiBranch)
                            <div class="col-12">
                                <label class="form-label font-sans-serif fw-bold text-700" for="checkerBranchId">Select Branch *</label>
                                <select wire:model.live="selectedBranchId" class="form-select" id="checkerBranchId">
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->city }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <!-- Select Hall -->
                        <div class="col-12">
                            <label class="form-label font-sans-serif fw-bold text-700" for="selectedHallId">Select Hall *</label>
                            <select wire:model.live="selectedHallId" class="form-select" id="selectedHallId">
                                @forelse($halls as $hall)
                                    <option value="{{ $hall->id }}">{{ $hall->hall_name }} (Capacity: {{ $hall->seating_capacity ?? $hall->capacity }})</option>
                                @empty
                                    <option value="">No halls available for this branch</option>
                                @endforelse
                            </select>
                        </div>

                        <!-- Select Date -->
                        <div class="col-12">
                            <label class="form-label font-sans-serif fw-bold text-700" for="selectedDate">Booking Date (DD-MM-YYYY) <span class="text-danger">*</span></label>
                            <div wire:ignore x-data x-init="
                                flatpickr($refs.datePicker, {
                                    dateFormat: 'd-m-Y',
                                    allowInput: true,
                                    defaultDate: '{{ $selectedDate }}',
                                    onChange: function(selectedDates, dateStr) {
                                        $wire.set('selectedDate', dateStr);
                                    }
                                });
                            ">
                                <input x-ref="datePicker" wire:model.live="selectedDate" class="form-control font-monospace" type="text" id="selectedDate" placeholder="DD-MM-YYYY" />
                            </div>
                        </div>

                        <!-- Check Type Toggle -->
                        <div class="col-12">
                            <label class="form-label font-sans-serif fw-bold text-700">Checking Method</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input wire:model.live="checkType" class="form-check-input" type="radio" name="checkType" id="check_slot" value="slot">
                                    <label class="form-check-label cursor-pointer text-800" for="check_slot">Predefined Shift Slot</label>
                                </div>
                                <div class="form-check">
                                    <input wire:model.live="checkType" class="form-check-input" type="radio" name="checkType" id="check_custom" value="custom">
                                    <label class="form-check-label cursor-pointer text-800" for="check_custom">Custom Time Range</label>
                                </div>
                            </div>
                        </div>

                        <!-- Predefined Slot Selector -->
                        @if($checkType === 'slot')
                            <div class="col-12">
                                <label class="form-label font-sans-serif fw-bold text-700" for="selectedSlotId">Select Predefined Shift *</label>
                                <select wire:model.live="selectedSlotId" class="form-select" id="selectedSlotId">
                                    <option value="">Choose a slot...</option>
                                    @foreach($slotOptions as $s)
                                        <option value="{{ $s->id }}">{{ $s->slot_name }} ({{ \Carbon\Carbon::parse($s->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($s->end_time)->format('h:i A') }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <!-- Custom Time Range -->
                            <div class="col-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="customStart">Start Time *</label>
                                <input wire:model.live="customStart" class="form-control font-monospace" type="time" id="customStart" />
                            </div>
                            <div class="col-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="customEnd">End Time *</label>
                                <input wire:model.live="customEnd" class="form-control font-monospace" type="time" id="customEnd" />
                            </div>
                        @endif
                    </div>

                    <!-- CHECK RESULTS DISPLAY -->
                    @if($availabilityChecked)
                        <div class="border-top pt-4 mt-4">
                            @if($isAvailable)
                                <!-- SUCCESS: AVAILABLE -->
                                <div class="alert alert-success border-2 d-flex align-items-center mb-0" role="alert">
                                    <div class="bg-success me-3 icon-item">
                                        <span class="fas fa-check-circle text-white fs-8"></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="alert-heading text-success mb-1">AVAILABLE</h6>
                                        <p class="mb-0 fs-11 text-success-800">
                                            The hall is fully open during the requested time range. You can proceed with booking.
                                        </p>
                                    </div>
                                </div>
                            @else
                                <!-- CONFLICT WARNING -->
                                <div class="alert alert-danger border-2 d-flex align-items-center mb-3" role="alert">
                                    <div class="bg-danger me-3 icon-item">
                                        <span class="fas fa-times-circle text-white fs-8"></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="alert-heading text-danger mb-1">NOT AVAILABLE</h6>
                                        <p class="mb-0 fs-11 text-danger-800">
                                            A conflicting booking was detected during the selected range.
                                        </p>
                                    </div>
                                </div>

                                <!-- CONFLICT DETAILS CARD -->
                                @if($conflictDetails)
                                    <div class="card border border-2 border-danger shadow-sm">
                                        <div class="card-header bg-danger-subtle py-2">
                                            <h6 class="mb-0 text-danger-800 fw-bold fs-11">
                                                <span class="fas fa-exclamation-triangle me-1"></span>Conflict Details
                                            </h6>
                                        </div>
                                        <div class="card-body py-2 px-3">
                                            <div class="row g-2 fs-11">
                                                <div class="col-6">
                                                    <span class="text-500 d-block">Booking Reference</span>
                                                    <strong>Booking #{{ $conflictDetails->id }}</strong>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-500 d-block">Current Status</span>
                                                    <span class="badge badge-subtle-{{ $conflictDetails->booking_status === 'Reserved' ? 'warning' : 'danger' }} rounded-pill font-monospace">
                                                        {{ $conflictDetails->booking_status }}
                                                    </span>
                                                </div>
                                                <div class="col-12 border-top pt-2">
                                                    <span class="text-500 d-block">Time Bounds</span>
                                                    <span class="text-800 font-monospace fw-semi-bold">
                                                        {{ $conflictDetails->start_time->format('h:i A') }} → {{ $conflictDetails->end_time->format('h:i A') }}
                                                    </span>
                                                </div>
                                                <div class="col-12 border-top pt-2">
                                                    <span class="text-500 d-block">Managed By</span>
                                                    <span class="text-800">{{ $conflictDetails->creator?->name ?? 'System' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: DAILY SHIFT SCHEDULE -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><span class="fas fa-calendar-alt me-2 text-primary"></span>Daily Timeline & Predefined Slots</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive scrollbar">
                        <table class="table table-sm table-striped fs-11 mb-0 align-middle">
                            <thead class="bg-200 text-900">
                                <tr>
                                    <th class="px-3">Shift Name</th>
                                    <th>Schedule Times</th>
                                    <th>Overlap Conflict Range</th>
                                    <th class="text-center" style="width: 100px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($slotStatusList as $slot)
                                    <tr>
                                        <td class="px-3 fw-semi-bold text-900">
                                            {{ $slot['name'] }}
                                        </td>
                                        <td class="font-monospace text-secondary">
                                            {{ $slot['start'] }} - {{ $slot['end'] }}
                                        </td>
                                        <td>
                                            @if($slot['conflict'])
                                                <span class="text-danger font-monospace fs-12 fw-semi-bold">
                                                    <span class="fas fa-exclamation-circle me-1"></span>
                                                    {{ $slot['conflict']['start'] }} - {{ $slot['conflict']['end'] }}
                                                </span>
                                            @else
                                                <span class="text-muted fs-12">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $badgeClass = 'success';
                                                if ($slot['status'] === 'Booked') $badgeClass = 'danger';
                                                elseif ($slot['status'] === 'Reserved') $badgeClass = 'warning';
                                            @endphp
                                            <span class="badge badge-subtle-{{ $badgeClass }} rounded-pill font-sans-serif fs-11">
                                                {{ $slot['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <span class="fas fa-calendar-times fa-2x mb-2 d-block"></span>
                                            No active shift slots assigned to this Hall. Setup shift slots and assignments first.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
