<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">SaaS Business Owners</h5>
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group btn-group-sm" role="group">
                    <button wire:click="$set('filter', '')" class="btn btn-{{ $filter === '' ? 'primary' : 'falcon-default' }}" type="button">All</button>
                    <button wire:click="$set('filter', 'active')" class="btn btn-{{ $filter === 'active' ? 'primary' : 'falcon-default' }}" type="button">Active</button>
                    <button wire:click="$set('filter', 'expired')" class="btn btn-{{ $filter === 'expired' ? 'primary' : 'falcon-default' }}" type="button">Expired Plan</button>
                </div>
                <div class="input-group input-group-sm">
                    <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search owners..." />
                    <span class="input-group-text"><span class="fas fa-search"></span></span>
                </div>
                @if(auth()->user()->isSuperAdmin())
                    <a class="btn btn-falcon-primary btn-sm" href="{{ route('super-admin.business-owners.create') }}">
                        <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add New Owner
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

            <div class="table-responsive scrollbar" style="overflow: visible;">
                <table class="table table-sm table-striped fs-10 mb-0">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="align-middle px-3" style="width: 50px;">No.</th>
                            <th class="align-middle px-3">Name</th>
                            <th class="align-middle">Contact Info</th>
                            <th class="align-middle">Subscription Plan</th>
                            <th class="align-middle">Expire At</th>
                            <th class="align-middle text-center">Status</th>
                            <th class="align-middle text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($businessOwners as $owner)
                            <tr>
                                <td class="align-middle px-3">
                                    {{ ($businessOwners->currentPage() - 1) * $businessOwners->perPage() + $loop->iteration }}
                                </td>
                                <td class="align-middle px-3 fw-semibold text-nowrap">
                                    <a href="{{ route('super-admin.business-owners.show', $owner->id) }}" class="text-decoration-none fw-bold">
                                        {{ $owner->name }}
                                    </a>
                                </td>
                                <td class="align-middle">
                                    <div><span class="fas fa-envelope me-1 text-500"></span>{{ $owner->email }}</div>
                                    @if($owner->phone)
                                        <div><span class="fas fa-phone me-1 text-500"></span>{{ $owner->phone }}</div>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    {{ $owner->subscriptionPlan->name ?? 'None' }}
                                </td>
                                <td class="align-middle">
                                    {{ $owner->subscription_ends_at ? $owner->subscription_ends_at->format('M d, Y') : 'N/A' }}
                                </td>
                                <td class="align-middle text-center">
                                    @if($owner->status === 'active')
                                        @if($owner->subscription_ends_at && $owner->subscription_ends_at->isPast())
                                            <span class="badge badge-subtle-warning rounded-pill" data-bs-toggle="tooltip" title="Subscription has expired">Expired</span>
                                        @else
                                            <span class="badge badge-subtle-success rounded-pill">Active</span>
                                        @endif
                                    @elseif($owner->status === 'inactive')
                                        <span class="badge badge-subtle-secondary rounded-pill">Inactive</span>
                                    @else
                                        <span class="badge badge-subtle-danger rounded-pill">Suspended</span>
                                    @endif
                                </td>
                                <td class="align-middle text-end px-3">
                                    <div class="dropdown font-sans-serif d-inline-block">
                                        <button class="btn btn-link text-600 dropdown-toggle dropdown-caret-none transition-none btn-sm" type="button" id="owner-actions-{{ $owner->id }}" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-haspopup="true" aria-expanded="false">
                                            <span class="fas fa-ellipsis-h fs-10"></span>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end border py-0" aria-labelledby="owner-actions-{{ $owner->id }}">
                                            <div class="bg-white dark__bg-1000 py-2 text-start">
                                                <a class="dropdown-item" href="{{ route('super-admin.business-owners.show', $owner->id) }}">
                                                    <span class="text-info fas fa-eye me-2"></span>View Details
                                                </a>
                                                <a class="dropdown-item" href="{{ route('super-admin.business-owners.edit', $owner->id) }}">
                                                    <span class="text-primary fas fa-edit me-2"></span>Edit Owner
                                                </a>
                                                @if(auth()->user()->isSuperAdmin())
                                                    <div class="dropdown-divider"></div>
                                                    <button class="dropdown-item text-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $owner->id }})">
                                                        <span class="text-danger fas fa-trash-alt me-2"></span>Delete Owner
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No business owners found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($businessOwners->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $businessOwners->links() }}
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
                    <p class="mb-0 text-900">Are you sure you want to delete this business owner? This action will remove their account and unlink them from all of their businesses/marquees.</p>
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
