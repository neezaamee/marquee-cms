<div>
    <div class="row g-3">
        <!-- Booking Selector -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0 text-primary"><i class="fas fa-calendar-day me-2"></i>Select Event / Booking</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush fs--1">
                        @forelse($bookings as $booking)
                            <button type="button" wire:click="$set('selectedBookingId', {{ $booking->id }})" 
                                    class="list-group-item list-group-item-action p-3 text-start border-bottom @if($selectedBookingId == $booking->id) active bg-light text-primary border-primary @endif">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-800">{{ $booking->customer ? ($booking->customer->first_name . ' ' . $booking->customer->last_name) : 'Walk-in Customer' }}</span>
                                    <span class="badge badge-subtle-primary">{{ $booking->booking_date->format('d M, Y') }}</span>
                                </div>
                                <div class="text-600 small mb-1">
                                    <i class="fas fa-hotel me-1 text-500"></i>{{ $booking->hall ? $booking->hall->hall_name : 'No Hall Assigned' }}
                                </div>
                                <div class="d-flex justify-content-between align-items-center small text-500">
                                    <span>Guests: {{ $booking->guest_count }}</span>
                                    <span class="badge badge-subtle-success">{{ $booking->status }}</span>
                                </div>
                            </button>
                        @empty
                            <div class="text-center py-4 text-muted">No booking records found.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Checklist view -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 text-primary"><i class="fas fa-tasks-alt me-2"></i>Event Day Operations Checklist</h5>
                        <p class="mb-0 text-muted small">Coordinate event stage prep, catering checks, sound systems, and general readiness.</p>
                    </div>
                </div>
                <div class="card-body">
                    @if($selectedBookingId)
                        @php
                            $totalCount = count($checklistItems);
                            $completedCount = $checklistItems->where('status', 'Completed')->count();
                            $percentage = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;
                        @endphp

                        <!-- Progress Bar -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1 small">
                                <span class="fw-semibold text-800">Event Readiness Progress</span>
                                <span class="fw-bold text-success">{{ $percentage }}% Complete</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success rounded" role="progressbar" style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>

                        <!-- Add Item Form -->
                        <form wire:submit.prevent="addChecklistItem" class="row g-2 mb-4 bg-light p-3 rounded">
                            <div class="col-md-5">
                                <input type="text" wire:model="newTaskName" placeholder="New task name..." class="form-control form-control-sm" required />
                            </div>
                            <div class="col-md-3">
                                <select wire:model="newTaskCategory" class="form-select form-select-sm">
                                    <option value="Sound System">Sound System</option>
                                    <option value="Catering">Catering</option>
                                    <option value="Decoration">Decoration</option>
                                    <option value="Stage Setup">Stage Setup</option>
                                    <option value="Security & Power">Security & Power</option>
                                    <option value="Cleaning">Cleaning</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select wire:model="newTaskAssigneeId" class="form-select form-select-sm">
                                    <option value="">-- Assign Staff --</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-1 text-end">
                                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-plus"></i></button>
                            </div>
                        </form>

                        <!-- Tasks List -->
                        <div class="list-group list-group-flush fs--1">
                            @forelse($checklistItems as $item)
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-bottom-0">
                                    <div class="d-flex align-items-center">
                                        <div class="form-check mb-0">
                                            <input type="checkbox" class="form-check-input cursor-pointer" id="chk-{{ $item->id }}" 
                                                   @if($item->status === 'Completed') checked @endif 
                                                   wire:click="toggleChecklistItem({{ $item->id }})" />
                                        </div>
                                        <label class="form-check-label ms-2 cursor-pointer @if($item->status === 'Completed') text-decoration-line-through text-400 @else fw-semibold text-800 @endif" for="chk-{{ $item->id }}">
                                            {{ $item->task_name }}
                                        </label>
                                        <span class="badge badge-subtle-secondary ms-2">{{ $item->category }}</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        @if($item->assignee)
                                            <span class="badge badge-subtle-primary me-2"><i class="fas fa-user-tag me-1"></i>{{ $item->assignee->name }}</span>
                                        @endif
                                        @if($item->status === 'Completed')
                                            <span class="badge bg-success rounded"><i class="fas fa-check me-1"></i>Done</span>
                                        @else
                                            <span class="badge bg-secondary rounded">Pending</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted">No checklist tasks mapped for this event. Try adding a task above!</div>
                            @endforelse
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">Please select an event from the left panel to manage checklists.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
