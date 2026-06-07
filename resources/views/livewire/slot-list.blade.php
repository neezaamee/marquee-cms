<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Shift Slots</h5>
            <div class="d-flex align-items-center gap-2">
                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'))
                    <a class="btn btn-falcon-primary btn-sm" href="{{ route('slots.create') }}">
                        <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add New Slot
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
                <div class="alert alert-danger border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-1">{{ session('error') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="align-middle px-3">Slot Name</th>
                            <th class="align-middle">Start Time</th>
                            <th class="align-middle">End Time</th>
                            <th class="align-middle">Description</th>
                            <th class="align-middle text-center">Status</th>
                            <th class="align-middle text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shiftSlots as $slot)
                            <tr>
                                <td class="align-middle px-3 fw-semi-bold">
                                    {{ $slot->slot_name }}
                                </td>
                                <td class="align-middle">
                                    <span class="badge badge-subtle-info">
                                        <span class="fas fa-clock me-1"></span>
                                        {{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }}
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <span class="badge badge-subtle-info">
                                        <span class="fas fa-clock me-1"></span>
                                        {{ \Carbon\Carbon::parse($slot->end_time)->format('h:i A') }}
                                    </span>
                                </td>
                                <td class="align-middle text-muted">{{ $slot->description ?? 'No description' }}</td>
                                <td class="align-middle text-center">
                                    @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'))
                                        <button wire:click="toggleStatus({{ $slot->id }})" class="btn btn-sm p-0 border-0 bg-transparent" type="button" data-bs-toggle="tooltip" title="Click to toggle status">
                                            <span class="badge badge-subtle-{{ $slot->status === 'active' ? 'success' : 'secondary' }} rounded-pill">
                                                {{ ucfirst($slot->status) }}
                                            </span>
                                        </button>
                                    @else
                                        <span class="badge badge-subtle-{{ $slot->status === 'active' ? 'success' : 'secondary' }} rounded-pill">
                                            {{ ucfirst($slot->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle text-end px-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'))
                                            <a class="btn btn-link p-0" href="{{ route('slots.edit', $slot->id) }}" data-bs-toggle="tooltip" title="Edit Slot">
                                                <span class="text-primary fas fa-edit"></span>
                                            </a>
                                            <button wire:click="deleteSlot({{ $slot->id }})" wire:confirm="Are you sure you want to delete this slot? If assigned to halls, this might affect availability checking." class="btn btn-link p-0" type="button" data-bs-toggle="tooltip" title="Delete Slot">
                                                <span class="text-danger fas fa-trash-alt"></span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No slots found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($shiftSlots->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $shiftSlots->links() }}
            </div>
        @endif
    </div>
</div>
