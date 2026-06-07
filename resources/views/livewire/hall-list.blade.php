<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Halls & Venues</h5>
            <div class="d-flex align-items-center gap-2">
                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('create_halls'))
                    <a class="btn btn-falcon-primary btn-sm" href="{{ route('halls.create') }}">
                        <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add New Hall
                    </a>
                @endif
            </div>
        </div>

        <div class="card-body bg-light border-bottom">
            <div class="row g-2">
                <!-- Search -->
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search by name, code, type..." />
                        <span class="input-group-text"><span class="fas fa-search"></span></span>
                    </div>
                </div>

                <!-- Branch Filter -->
                @if(!auth()->user()->branch_id)
                    <div class="col-md-3">
                        <select wire:model.live="filterBranch" class="form-select form-select-sm">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Status Filter -->
                <div class="col-md-3">
                    <select wire:model.live="filterStatus" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
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
                            <th class="align-middle px-3">Hall Name</th>
                            <th class="align-middle">Hall Code</th>
                            <th class="align-middle">Branch</th>
                            <th class="align-middle">Capacity</th>
                            <th class="align-middle">Type</th>
                            <th class="align-middle">Price</th>
                            <th class="align-middle text-center">Status</th>
                            <th class="align-middle text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($halls as $hall)
                            <tr>
                                <td class="align-middle px-3 fw-semi-bold">
                                    <a href="{{ route('halls.show', $hall->id) }}">{{ ucwords($hall->hall_name) }}</a>
                                </td>
                                <td class="align-middle">{{ strtoupper($hall->hall_code) }}</td>
                                <td class="align-middle">{{ $hall->branch->name ?? 'N/A' }}</td>
                                <td class="align-middle">{{ number_format($hall->capacity) }} guests</td>
                                <td class="align-middle">
                                    <span class="badge badge-subtle-primary">{{ ucfirst($hall->hall_type) }}</span>
                                </td>
                                <td class="align-middle">PKR {{ number_format($hall->default_booking_price, 2) }}</td>
                                <td class="align-middle text-center">
                                    <span class="badge badge-subtle-{{ $hall->status === 'active' ? 'success' : 'secondary' }} rounded-pill">
                                        {{ ucfirst($hall->status) }}
                                    </span>
                                </td>
                                <td class="align-middle text-end px-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-link p-0" href="{{ route('halls.show', $hall->id) }}" data-bs-toggle="tooltip" title="View Details">
                                            <span class="text-info fas fa-eye"></span>
                                        </a>
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_halls'))
                                            <a class="btn btn-link p-0" href="{{ route('halls.edit', $hall->id) }}" data-bs-toggle="tooltip" title="Edit Hall">
                                                <span class="text-primary fas fa-edit"></span>
                                            </a>
                                        @endif
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('delete_halls'))
                                            <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $hall->id }})" title="Delete Hall">
                                                <span class="text-danger fas fa-trash-alt"></span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No halls found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($halls->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $halls->links() }}
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
                    <p class="mb-0 text-900">Are you sure you want to delete this hall? This action cannot be undone and will permanently remove the record.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button wire:click="deleteRecord" type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        <span class="fas fa-trash-alt me-1"></span>Delete Permanently
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
