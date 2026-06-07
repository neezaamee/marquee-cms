<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <span class="fas fa-glass-cheers me-2 text-primary"></span>
                Event Type Details: {{ $eventType->event_type_name }}
            </h5>
            <div class="d-flex gap-2">
                <a class="btn btn-falcon-default btn-sm" href="{{ route('event-types.index') }}">
                    <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
                </a>
                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('event-types.edit'))
                    <a class="btn btn-falcon-primary btn-sm" href="{{ route('event-types.edit', $eventType->id) }}">
                        <span class="fas fa-edit me-1"></span>Edit Event Type
                    </a>
                @endif
            </div>
        </div>

        <div class="card-body">
            <div class="row g-4">
                
                <!-- Core Profile Details -->
                <div class="col-md-7">
                    <h5 class="mb-3 text-800"><span class="fas fa-info-circle me-2 text-primary"></span>General Specifications</h5>
                    <table class="table table-sm table-borderless fs-10 mb-0">
                        <tbody>
                            <tr>
                                <td class="fw-semi-bold text-600" style="width:200px">Event Type Name:</td>
                                <td class="text-800 fw-semi-bold">{{ $eventType->event_type_name }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semi-bold text-600">Unique Identifier Code:</td>
                                <td class="text-800"><span class="badge badge-subtle-primary font-monospace fs-11">{{ $eventType->event_type_code }}</span></td>
                            </tr>
                            <tr>
                                <td class="fw-semi-bold text-600">Branch Availability:</td>
                                <td class="text-800">
                                    @if($eventType->branch_id)
                                        <span class="text-secondary"><span class="fas fa-store me-1"></span>{{ $eventType->branch->name }}</span>
                                    @else
                                        <span class="text-success fw-semi-bold"><span class="fas fa-globe me-1"></span>All Branches (Marquee Wide)</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semi-bold text-600">Base Price:</td>
                                <td class="text-800 font-monospace">
                                    {{ $eventType->base_price ? 'PKR ' . number_format($eventType->base_price, 2) : 'No default price set' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semi-bold text-600">Default Duration:</td>
                                <td class="text-800">
                                    {{ $eventType->default_duration_hours ? $eventType->default_duration_hours . ' Hours' : 'No default duration set' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semi-bold text-600">Slot Shift Preference:</td>
                                <td class="text-800">{{ $eventType->default_slot_preference ?: 'No preference' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semi-bold text-600">Sorting Order:</td>
                                <td class="text-800">{{ $eventType->sort_order }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semi-bold text-600">System Record Type:</td>
                                <td class="text-800">
                                    @if($eventType->is_system_default)
                                        <span class="badge badge-subtle-info rounded-pill"><span class="fas fa-shield-alt me-1"></span>System Default</span>
                                    @else
                                        <span class="badge badge-subtle-secondary rounded-pill">Custom Event Type</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semi-bold text-600">Status:</td>
                                <td class="text-800">
                                    <span class="badge badge-subtle-{{ $eventType->status === 'Active' ? 'success' : 'secondary' }} rounded-pill">{{ $eventType->status }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Future Bookings Integration Statistics -->
                <div class="col-md-5">
                    <div class="bg-light p-3 rounded border mb-3">
                        <h5 class="mb-3 text-800"><span class="fas fa-chart-line me-2 text-primary"></span>Historical Bookings</h5>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="bg-white p-2.5 rounded border text-center">
                                    <h4 class="mb-0 text-900">0</h4>
                                    <small class="text-500 fs-11 fw-semi-bold">Total Bookings</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-white p-2.5 rounded border text-center">
                                    <h4 class="mb-0 text-success">0</h4>
                                    <small class="text-500 fs-11 fw-semi-bold">Revenue Generated</small>
                                </div>
                            </div>
                        </div>

                        <div class="text-center py-4 text-muted border-top">
                            <span class="fas fa-calendar-check fa-2x mb-2 d-block text-300"></span>
                            Booking metrics and analytics dashboard integration will be active here once the Booking engine is built.
                        </div>
                    </div>

                    <div class="bg-light p-3 rounded border">
                        <h6 class="mb-2 text-800"><span class="fas fa-user-edit me-1"></span>Metadata</h6>
                        <div class="fs-10 text-700">
                            <div>Created By: {{ $eventType->creator->name ?? 'System Seeder' }}</div>
                            <div>Created At: {{ $eventType->created_at->format('d-M-Y H:i:s') }}</div>
                            <div>Last Modified: {{ $eventType->updated_at->format('d-M-Y H:i:s') }}</div>
                        </div>
                    </div>
                </div>

                <!-- Description / Terms -->
                <div class="col-12 border-top pt-4">
                    <h5 class="mb-3 text-800"><span class="fas fa-sticky-note me-2 text-primary"></span>Description & Custom Notes</h5>
                    <div class="bg-light p-3 rounded text-800 fs-10" style="min-height: 100px; white-space: pre-wrap;">{{ $eventType->description ?: 'No detailed descriptions or special guidelines provided for this event type.' }}</div>
                </div>

            </div>
        </div>
    </div>
</div>
