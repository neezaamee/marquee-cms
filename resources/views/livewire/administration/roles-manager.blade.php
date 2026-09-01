<div>
    <!-- Page Header -->
    <div class="row align-items-center justify-content-between g-3 mb-4">
        <div class="col-12 col-md-auto">
            <h1 class="h3 mb-0 text-gray-800">Roles & Access Groups</h1>
            <p class="mb-0 text-muted fs-10">Manage the security groups and access policies across the CMS platform.</p>
        </div>
        @if(auth()->user()->isSuperAdmin())
            <div class="col-12 col-md-auto">
                <button wire:click="openCreateModal" class="btn btn-primary btn-sm rounded-pill shadow-sm px-3" type="button">
                    <span class="fas fa-plus me-2"></span>Add New Role
                </button>
            </div>
        @endif
    </div>

    <!-- Main List Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h5 class="mb-0 text-900">Security Roles</h5>
            </div>
            <div class="d-flex align-items-center gap-2 col-12 col-md-auto">
                <div class="input-group input-group-sm">
                    <input wire:model.live.debounce.300ms="search" class="form-control bg-light border-0 px-3 py-2 rounded-start" type="search" placeholder="Search roles..." />
                    <span class="input-group-text bg-light border-0"><span class="fas fa-search text-400"></span></span>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Messages -->
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm d-flex align-items-center m-3" role="alert">
                    <div class="bg-success text-white rounded-circle p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <span class="fas fa-check fs-10"></span>
                    </div>
                    <p class="mb-0 flex-1 fw-semi-bold">{{ session('success') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center m-3" role="alert">
                    <div class="bg-danger text-white rounded-circle p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <span class="fas fa-times fs-10"></span>
                    </div>
                    <p class="mb-0 flex-1 fw-semi-bold">{{ session('error') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-hover align-middle mb-0 fs-10">
                    <thead class="bg-light text-900">
                        <tr>
                            <th class="align-middle px-3 py-3" style="width: 25%;">Role Name / Key</th>
                            <th class="align-middle" style="width: 25%;">Label</th>
                            <th class="align-middle" style="width: 35%;">Description</th>
                            <th class="align-middle text-center" style="width: 10%;">Users Linked</th>
                            @if(auth()->user()->isSuperAdmin())
                                <th class="align-middle text-end px-3" style="width: 5%;">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                            <tr class="hover-actions-trigger">
                                <td class="align-middle px-3 py-3 font-monospace">
                                    <div class="d-flex align-items-center">
                                        <span class="badge badge-subtle-secondary rounded-pill px-3 py-2 fs-11">
                                            {{ $role->name }}
                                        </span>
                                        @if(in_array($role->name, $builtinRoles))
                                            <span class="badge bg-primary rounded-pill ms-2" style="font-size: 8px;">System Default</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="align-middle fw-bold text-800">{{ $role->label }}</td>
                                <td class="align-middle text-muted">{{ $role->description ?? 'No description provided.' }}</td>
                                <td class="align-middle text-center">
                                    <span class="badge badge-subtle-info rounded-pill px-2 py-1 fs-11">
                                        <span class="fas fa-user-friends me-1"></span>{{ $role->users_count }}
                                    </span>
                                </td>
                                @if(auth()->user()->isSuperAdmin())
                                    <td class="align-middle text-end px-3">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button wire:click="editRole({{ $role->id }})" class="btn btn-link p-0 text-primary" type="button" data-bs-toggle="tooltip" title="Edit Role">
                                                <span class="fas fa-edit"></span>
                                            </button>
                                            
                                            @if(!in_array($role->name, $builtinRoles))
                                                <button wire:click="confirmDeletion({{ $role->id }})" class="btn btn-link p-0 text-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" title="Delete Role">
                                                    <span class="fas fa-trash-alt"></span>
                                                </button>
                                            @else
                                                <span class="fas fa-lock text-300 fs-10" data-bs-toggle="tooltip" title="System roles cannot be deleted"></span>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <span class="fas fa-shield-alt fs-5 d-block mb-3 text-300"></span>
                                    No roles found matching your search.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($roles->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-white border-0 py-3">
                {{ $roles->links() }}
            </div>
        @endif
    </div>

    <!-- Edit/Create Role Modal -->
    <div wire:ignore.self class="modal fade @if($isModalOpen) show @endif" id="roleFormModal" tabindex="-1" style="@if($isModalOpen) display: block; background: rgba(0, 0, 0, 0.5); @else display: none; @endif" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h5 class="modal-title text-white" id="roleFormModalLabel">
                        <span class="fas @if($isEditMode) fa-edit @else fa-plus @endif me-2"></span>
                        {{ $isEditMode ? 'Modify Security Role' : 'Create New Security Role' }}
                    </h5>
                    <button wire:click="closeModal" type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                </div>
                <form wire:submit.prevent="saveRole">
                    <div class="modal-body text-start py-4">
                        
                        <!-- Name field -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-700 fs-10" for="roleName">Role Identifier / Key</label>
                            <input wire:model="name" type="text" id="roleName" class="form-control py-2 @error('name') is-invalid @enderror" placeholder="e.g. general_accountant" @if($isEditMode && in_array($name, $builtinRoles)) disabled @endif />
                            @if($isEditMode && in_array($name, $builtinRoles))
                                <div class="form-text text-warning fs-11 mt-1">
                                    <span class="fas fa-info-circle me-1"></span>System keys for default roles cannot be changed.
                                </div>
                            @else
                                <div class="form-text text-muted fs-11">Lowercase characters, numbers, and underscores only. Uniquely identifies the role.</div>
                            @endif
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Label field -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-700 fs-10" for="roleLabel">Display Name / Label</label>
                            <input wire:model="label" type="text" id="roleLabel" class="form-control py-2 @error('label') is-invalid @enderror" placeholder="e.g. General Accountant" />
                            @error('label')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description field -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-700 fs-10" for="roleDescription">Role Description</label>
                            <textarea wire:model="description" id="roleDescription" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="Briefly describe the functions and responsibilities associated with this role..."></textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                    <div class="modal-footer bg-light border-0 py-3">
                        <button wire:click="closeModal" type="button" class="btn btn-falcon-default btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <span class="fas fa-save me-1"></span>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div wire:ignore.self class="modal fade @if($confirmingDeletionId) show @endif" id="deleteConfirmModal" tabindex="-1" style="@if($confirmingDeletionId) display: block; background: rgba(0, 0, 0, 0.5); @else display: none; @endif" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white border-0 py-3">
                    <h5 class="modal-title text-white" id="deleteConfirmModalLabel">
                        <span class="fas fa-exclamation-triangle me-2"></span>Confirm Deletion
                    </h5>
                    <button wire:click="$set('confirmingDeletionId', null)" type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start py-4 text-900">
                    <p class="mb-0">Are you sure you want to permanently delete this security role? This action is irreversible and will revoke the access settings mapped to it.</p>
                </div>
                <div class="modal-footer bg-light border-0 py-3">
                    <button wire:click="$set('confirmingDeletionId', null)" type="button" class="btn btn-falcon-default btn-sm px-3">Cancel</button>
                    <button wire:click="deleteRole" type="button" class="btn btn-danger btn-sm px-4">
                        <span class="fas fa-trash-alt me-1"></span>Delete Role
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
