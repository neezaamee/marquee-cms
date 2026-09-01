<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-glass-cheers me-2 text-primary"></span>Event Types Management</h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Search -->
                <div class="input-group input-group-sm" style="max-width: 200px;">
                    <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search event types..." />
                    <span class="input-group-text"><span class="fas fa-search"></span></span>
                </div>

                <!-- Branch Filter (Only show if not restricted to single branch) -->
                @if(!auth()->user()->branch_id)
                    <select wire:model.live="filterBranch" class="form-select form-select-sm" style="min-width:140px">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                @endif

                <!-- Status Filter -->
                <select wire:model.live="filterStatus" class="form-select form-select-sm" style="min-width:130px">
                    <option value="">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>

                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('event-types.create'))
                    <a class="btn btn-falcon-primary btn-sm text-nowrap" href="{{ route('event-types.create') }}">
                        <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add Event Type
                    </a>
                @endif
            </div>
        </div>

        <div class="card-body p-0">
            @if(session('success'))
                <div class="alert alert-success border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-1">{{ session('success') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger border-2 d-flex align-items-center m-3 animate__animated animate__fadeIn" role="alert">
                    <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-1">{{ session('error') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-1">{{ session('error') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3" style="width: 80px;">Sort Order</th>
                            <th>Code</th>
                            <th>Event Name</th>
                            <th>Branch Scope</th>
                            <th>Base Price</th>
                            <th>Duration</th>
                            <th>Slot Preference</th>
                            <th class="text-center">System Default</th>
                            <th class="text-center">Status</th>
                            <th class="text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($eventTypes as $item)
                            <tr>
                                <td class="px-3 text-center">
                                    <span class="badge badge-subtle-secondary fs-11 font-monospace">{{ $item->sort_order }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-subtle-primary fs-11 font-monospace">{{ $item->event_type_code }}</span>
                                </td>
                                <td class="fw-semi-bold">
                                    <a href="{{ route('event-types.show', $item->id) }}">{{ $item->event_type_name }}</a>
                                </td>
                                <td>
                                    @if($item->branch_id)
                                        <span class="text-secondary"><span class="fas fa-store me-1"></span>{{ $item->branch->name }}</span>
                                    @else
                                        <span class="text-success fw-semi-bold"><span class="fas fa-globe me-1"></span>All Branches</span>
                                    @endif
                                </td>
                                <td>{{ $item->base_price ? 'PKR ' . number_format($item->base_price, 2) : '—' }}</td>
                                <td>{{ $item->default_duration_hours ? $item->default_duration_hours . ' Hours' : '—' }}</td>
                                <td>{{ $item->default_slot_preference ?: '—' }}</td>
                                <td class="text-center">
                                    @if($item->is_system_default)
                                        <span class="badge badge-subtle-info rounded-pill"><span class="fas fa-shield-alt me-1"></span>System</span>
                                    @else
                                        <span class="badge badge-subtle-secondary rounded-pill">Custom</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-subtle-{{ $item->status === 'Active' ? 'success' : 'secondary' }} rounded-pill">{{ $item->status }}</span>
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-link p-0" href="{{ route('event-types.show', $item->id) }}" data-bs-toggle="tooltip" title="View Details">
                                            <span class="text-info fas fa-eye"></span>
                                        </a>
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('event-types.edit'))
                                            <a class="btn btn-link p-0" href="{{ route('event-types.edit', $item->id) }}" data-bs-toggle="tooltip" title="Edit">
                                                <span class="text-primary fas fa-edit"></span>
                                            </a>
                                        @endif
                                        @if(!$item->is_system_default && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('event-types.delete')))
                                            <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $item->id }})" title="Delete Event Type">
                                                <span class="text-danger fas fa-trash-alt"></span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <span class="fas fa-glass-cheers fa-2x mb-2 d-block"></span>
                                    No event types found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($eventTypes->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $eventTypes->links() }}
            </div>
        @endif
    </div>

    <!-- Delete Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white" id="deleteConfirmModalLabel">
                        <span class="fas fa-exclamation-triangle me-2"></span>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <p class="mb-0 text-900">Are you sure you want to delete this event type? This action will soft-delete the record and is reversible, but it will prevent it from appearing in any new bookings.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button wire:click="deleteRecord" type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        <span class="fas fa-trash-alt me-1"></span>Delete Event Type
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
