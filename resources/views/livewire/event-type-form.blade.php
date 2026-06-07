<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <span class="fas fa-glass-cheers me-2 text-primary"></span>
                {{ $isEditMode ? 'Edit Event Type' : 'Add New Event Type' }}
            </h5>
            <a class="btn btn-falcon-default btn-sm" href="{{ route('event-types.index') }}">
                <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
            </a>
        </div>

        <div class="card-body">
            @if($is_system_default)
                <div class="alert alert-info border-2 d-flex align-items-center mb-4" role="alert">
                    <div class="bg-info me-3 icon-item"><span class="fas fa-info-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-1">
                        <strong>System Default Alert:</strong> You are editing a system default event type. For data integrity, the unique identifier code is locked and cannot be changed.
                    </p>
                </div>
            @endif

            <form wire:submit.prevent="save">
                <div class="row g-3">
                    
                    <!-- General Details -->
                    <div class="col-md-6">
                        <label class="form-label" for="event_type_name">Event Type Name *</label>
                        <input wire:model="event_type_name" class="form-control @error('event_type_name') is-invalid @enderror" id="event_type_name" type="text" required placeholder="e.g. Mehndi, Walima Reception, Corporate Dinner" />
                        @error('event_type_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="event_type_code">Event Type Code *</label>
                        <input wire:model="event_type_code" class="form-control @error('event_type_code') is-invalid @enderror" id="event_type_code" type="text" required placeholder="e.g. MEHN, WALI, DINR" {{ $is_system_default ? 'disabled' : '' }} />
                        @error('event_type_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if($is_system_default)
                            <div class="form-text fs-11 text-500">System default codes cannot be modified.</div>
                        @endif
                    </div>

                    <!-- Branch Availability -->
                    <div class="col-md-6">
                        <label class="form-label" for="branch_id">Branch Availability</label>
                        <select wire:model="branch_id" class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" {{ $is_system_default ? 'disabled' : '' }}>
                            <option value="">All Branches (Marquee Wide)</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if($is_system_default)
                            <div class="form-text fs-11 text-500">System defaults are available marquee-wide across all branches.</div>
                        @endif
                    </div>

                    <!-- Base Price -->
                    <div class="col-md-6">
                        <label class="form-label" for="base_price">Base Booking Price (PKR)</label>
                        <div class="input-group">
                            <span class="input-group-text">PKR</span>
                            <input wire:model="base_price" class="form-control @error('base_price') is-invalid @enderror" id="base_price" type="number" step="0.01" placeholder="e.g. 75000" />
                            @error('base_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <!-- Duration in Hours -->
                    <div class="col-md-4">
                        <label class="form-label" for="default_duration_hours">Default Duration (Hours)</label>
                        <input wire:model="default_duration_hours" class="form-control @error('default_duration_hours') is-invalid @enderror" id="default_duration_hours" type="number" step="0.5" min="0.5" max="24" placeholder="e.g. 4 or 4.5" />
                        @error('default_duration_hours') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Shift Slot Preference -->
                    <div class="col-md-4">
                        <label class="form-label" for="default_slot_preference">Default Slot Preference</label>
                        <select wire:model="default_slot_preference" class="form-select @error('default_slot_preference') is-invalid @enderror" id="default_slot_preference">
                            <option value="">No Preference...</option>
                            @foreach($slotShifts as $slot)
                                <option value="{{ $slot->name }}">{{ $slot->name }} ({{ $slot->start_time }} - {{ $slot->end_time }})</option>
                            @endforeach
                            <!-- Standard fallbacks -->
                            <option value="Day Shift">Day Shift</option>
                            <option value="Night Shift">Night Shift</option>
                        </select>
                        @error('default_slot_preference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Sort Order -->
                    <div class="col-md-4">
                        <label class="form-label" for="sort_order">Sort Order</label>
                        <input wire:model="sort_order" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" type="number" min="0" placeholder="e.g. 0" />
                        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Status -->
                    <div class="col-md-12">
                        <label class="form-label fw-semi-bold d-block">Status *</label>
                        <div class="form-check form-check-inline">
                            <input wire:model="status" class="form-check-input" type="radio" name="status" id="status_active" value="Active">
                            <label class="form-check-label cursor-pointer text-success" for="status_active">Active</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input wire:model="status" class="form-check-input" type="radio" name="status" id="status_inactive" value="Inactive">
                            <label class="form-check-label cursor-pointer text-muted" for="status_inactive">Inactive</label>
                        </div>
                        @error('status') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <label class="form-label" for="description">Description / Special Terms</label>
                        <textarea wire:model="description" class="form-control @error('description') is-invalid @enderror" id="description" rows="4" placeholder="Detail any conditions, package defaults, or guidelines for this event type..."></textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a class="btn btn-falcon-default btn-sm" href="{{ route('event-types.index') }}">Cancel</a>
                    <button class="btn btn-primary btn-sm" type="submit">
                        <span wire:loading class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        {{ $isEditMode ? 'Update Event Type' : 'Save Event Type' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
