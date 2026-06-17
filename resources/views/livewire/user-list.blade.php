<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">CMS Users</h5>
            <div class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm">
                    <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search users..." />
                    <span class="input-group-text"><span class="fas fa-search"></span></span>
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
                            <th class="align-middle px-3">Name</th>
                            <th class="align-middle">Username</th>
                            <th class="align-middle">Email</th>
                            <th class="align-middle">Role</th>
                            <th class="align-middle">Branch</th>
                            @if(auth()->user()->isSuperAdmin())
                                <th class="align-middle">Marquee Tenant</th>
                            @endif
                            <th class="align-middle">Phone</th>
                            <th class="align-middle text-center">Status</th>
                            <th class="align-middle text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $usr)
                            <tr>
                                <td class="align-middle px-3 fw-semi-bold">
                                    <a href="{{ route('users.show', $usr->id) }}">{{ $usr->name }}</a>
                                </td>
                                <td class="align-middle font-monospace">{{ $usr->username ?? '—' }}</td>
                                <td class="align-middle">{{ $usr->email }}</td>
                                <td class="align-middle">
                                    <span class="badge badge-subtle-primary">{{ $usr->role->label ?? 'None' }}</span>
                                </td>
                                <td class="align-middle">{{ $usr->branch->name ?? 'All Branches' }}</td>
                                @if(auth()->user()->isSuperAdmin())
                                    <td class="align-middle">{{ $usr->marquee->name ?? 'Global SaaS' }}</td>
                                @endif
                                <td class="align-middle">{{ $usr->phone ?? '—' }}</td>
                                <td class="align-middle text-center">
                                    <span class="badge badge-subtle-{{ $usr->status === 'active' ? 'success' : 'secondary' }} rounded-pill">
                                        {{ ucfirst($usr->status) }}
                                    </span>
                                </td>
                                <td class="align-middle text-end px-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-link p-0" href="{{ route('users.show', $usr->id) }}" data-bs-toggle="tooltip" title="View">
                                            <span class="text-info fas fa-eye"></span>
                                        </a>
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'))
                                            <a class="btn btn-link p-0" href="{{ route('users.edit', $usr->id) }}" data-bs-toggle="tooltip" title="Edit">
                                                <span class="text-primary fas fa-edit"></span>
                                            </a>
                                            @if($usr->id !== auth()->id())
                                                <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $usr->id }})" title="Delete User">
                                                    <span class="text-danger fas fa-trash-alt"></span>
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($users->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $users->links() }}
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
                    <p class="mb-0 text-900">Are you sure you want to delete this user? This will soft-delete the user's account and revoke their system access.</p>
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
