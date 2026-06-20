<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Marquee Tenants</h5>
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group btn-group-sm" role="group">
                    <button wire:click="$set('filter', '')" class="btn btn-{{ $filter === '' ? 'primary' : 'falcon-default' }}" type="button">All</button>
                    <button wire:click="$set('filter', 'active')" class="btn btn-{{ $filter === 'active' ? 'primary' : 'falcon-default' }}" type="button">Active</button>
                    <button wire:click="$set('filter', 'suspended')" class="btn btn-{{ $filter === 'suspended' ? 'primary' : 'falcon-default' }}" type="button">Suspended</button>
                </div>
                <div class="input-group input-group-sm">
                    <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search marquees..." />
                    <span class="input-group-text"><span class="fas fa-search"></span></span>
                </div>
                @if(auth()->user()->isSuperAdmin())
                    <a class="btn btn-falcon-primary btn-sm" href="{{ route('marquees.create') }}">
                        <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add New
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
                            <th class="align-middle px-3">Name</th>
                            <th class="align-middle">City</th>
                            <th class="align-middle">Phone</th>
                            <th class="align-middle">Subscription Plan</th>
                            <th class="align-middle">Expires At</th>
                            <th class="align-middle text-center">Status</th>
                            <th class="align-middle text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($marquees as $marquee)
                            <tr>
                                <td class="align-middle px-3 fw-semi-bold">
                                    <a href="{{ route('marquees.show', $marquee->id) }}">{{ $marquee->name }}</a>
                                </td>
                                <td class="align-middle">{{ $marquee->city }}</td>
                                <td class="align-middle">{{ $marquee->phone }}</td>
                                <td class="align-middle">{{ $marquee->subscriptionPlan->name ?? 'None' }}</td>
                                <td class="align-middle">
                                    {{ $marquee->subscription_ends_at ? $marquee->subscription_ends_at->format('M d, Y') : 'N/A' }}
                                </td>
                                <td class="align-middle text-center">
                                    @if($marquee->status === 'active')
                                        @if($marquee->subscription_ends_at && $marquee->subscription_ends_at->isPast())
                                            <span class="badge badge-subtle-warning rounded-pill" data-bs-toggle="tooltip" title="Subscription has expired">Expired</span>
                                        @else
                                            <span class="badge badge-subtle-success rounded-pill">Active</span>
                                        @endif
                                    @elseif($marquee->status === 'inactive')
                                        <span class="badge badge-subtle-secondary rounded-pill">Inactive</span>
                                    @else
                                        <span class="badge badge-subtle-danger rounded-pill">Suspended</span>
                                    @endif
                                </td>
                                <td class="align-middle text-end px-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-link p-0" href="{{ route('marquees.show', $marquee->id) }}" data-bs-toggle="tooltip" title="View">
                                            <span class="text-info fas fa-eye"></span>
                                        </a>
                                        <a class="btn btn-link p-0" href="{{ route('marquees.edit', $marquee->id) }}" data-bs-toggle="tooltip" title="Edit">
                                            <span class="text-primary fas fa-edit"></span>
                                        </a>
                                        @if(auth()->user()->isSuperAdmin())
                                            <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $marquee->id }})" title="Delete Marquee">
                                                <span class="text-danger fas fa-trash-alt"></span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No marquees found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($marquees->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $marquees->links() }}
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
                    <p class="mb-0 text-900">Are you sure you want to delete this marquee tenant? This action will soft-delete the marquee and all related branches, users, and data.</p>
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
