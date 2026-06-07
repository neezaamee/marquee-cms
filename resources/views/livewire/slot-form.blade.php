<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ $isEditMode ? 'Edit Shift Slot' : 'Add New Shift Slot' }}</h5>
            <a class="btn btn-falcon-default btn-sm" href="{{ route('slots.index') }}">
                <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
            </a>
        </div>
        
        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="row g-3">
                    <!-- Marquee selector for Super Admins -->
                    @if(auth()->user()->isSuperAdmin())
                        <div class="col-12">
                            <label class="form-label" for="marquee_id">Select Marquee Tenant *</label>
                            <select wire:model.live="marquee_id" class="form-select @error('marquee_id') is-invalid @enderror" id="marquee_id" required>
                                <option value="">Select a marquee tenant...</option>
                                @foreach($marquees as $marquee)
                                    <option value="{{ $marquee->id }}">{{ $marquee->name }} ({{ $marquee->city }})</option>
                                @endforeach
                            </select>
                            @error('marquee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <!-- Slot Name -->
                    <div class="col-md-12">
                        <label class="form-label" for="slot_name">Slot Name *</label>
                        <input wire:model="slot_name" class="form-control @error('slot_name') is-invalid @enderror" id="slot_name" type="text" required placeholder="e.g. Day Shift, Lunch Shift, Midnight Shift" />
                        @error('slot_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Start Time -->
                    <div class="col-md-6">
                        <label class="form-label" for="start_time">Start Time *</label>
                        <input wire:model="start_time" class="form-control @error('start_time') is-invalid @enderror" id="start_time" type="time" required />
                        @error('start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- End Time -->
                    <div class="col-md-6">
                        <label class="form-label" for="end_time">End Time *</label>
                        <input wire:model="end_time" class="form-control @error('end_time') is-invalid @enderror" id="end_time" type="time" required />
                        @error('end_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Status -->
                    <div class="col-md-12">
                        <label class="form-label" for="status">Status *</label>
                        <select wire:model="status" class="form-select @error('status') is-invalid @enderror" id="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <textarea wire:model="description" class="form-control @error('description') is-invalid @enderror" id="description" rows="3" placeholder="Provide shift duration details, event guidelines..."></textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a class="btn btn-falcon-default btn-sm" href="{{ route('slots.index') }}">Cancel</a>
                    <button class="btn btn-primary btn-sm" type="submit">
                        <span wire:loading class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        {{ $isEditMode ? 'Update Slot' : 'Save Slot' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
