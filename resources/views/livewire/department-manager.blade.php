<div class="container-fluid p-0">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0 text-secondary"><span class="fas fa-sitemap me-2 text-primary"></span>Department Master</h4>
            <button wire:click="openCreateForm" class="btn btn-primary btn-sm">
                <span class="fas fa-plus me-1"></span>Create Department
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-start border-primary border-4 shadow-sm">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-500 fs-11 text-uppercase fw-semi-bold">Total Departments</div>
                        <div class="fs-4 fw-bold text-dark font-monospace">{{ $totalDepts }}</div>
                    </div>
                    <div class="icon-item bg-primary-subtle text-primary rounded-circle">
                        <span class="fas fa-sitemap"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-success border-4 shadow-sm">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-500 fs-11 text-uppercase fw-semi-bold">Active Status</div>
                        <div class="fs-4 fw-bold text-success font-monospace">{{ $activeDepts }}</div>
                    </div>
                    <div class="icon-item bg-success-subtle text-success rounded-circle">
                        <span class="fas fa-check-circle"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-danger border-4 shadow-sm">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-500 fs-11 text-uppercase fw-semi-bold">Kitchen Production</div>
                        <div class="fs-4 fw-bold text-danger font-monospace">{{ $kitchenDepts }}</div>
                    </div>
                    <div class="icon-item bg-danger-subtle text-danger rounded-circle">
                        <span class="fas fa-fire"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-info border-4 shadow-sm">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-500 fs-11 text-uppercase fw-semi-bold">Staff Assigned</div>
                        <div class="fs-4 fw-bold text-info font-monospace">{{ $totalAssignedStaff }}</div>
                    </div>
                    <div class="icon-item bg-info-subtle text-info rounded-circle">
                        <span class="fas fa-users"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Form Column (Conditional) -->
        @if($isFormOpen)
            <div class="col-md-4">
                <div class="card border border-200">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">{{ $departmentId ? 'Edit' : 'Create' }} Department</h5>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="save">
                            <div class="mb-3">
                                <label class="form-label font-monospace">Code (Auto)</label>
                                <input type="text" wire:model="department_code" class="form-control form-control-sm bg-200" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Department Name</label>
                                <input type="text" wire:model="name" class="form-control form-control-sm @error('name') is-invalid @enderror" placeholder="e.g. BBQ Kitchen">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select wire:model="department_type" class="form-select form-select-sm">
                                    <option value="Kitchen Production">Kitchen Production</option>
                                    <option value="Operations">Operations</option>
                                    <option value="Administration">Administration</option>
                                </select>
                                @error('department_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Manager / Head</label>
                                <select wire:model="manager_id" class="form-select form-select-sm">
                                    <option value="">Select Manager</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                                @error('manager_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Display Order</label>
                                <input type="number" wire:model="display_order" class="form-control form-control-sm @error('display_order') is-invalid @enderror">
                                @error('display_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select wire:model="status" class="form-select form-select-sm">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea wire:model="description" class="form-control form-control-sm" rows="3" placeholder="Enter remarks..."></textarea>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" wire:click="$set('isFormOpen', false)" class="btn btn-secondary btn-sm">Cancel</button>
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- List Column -->
        <div class="{{ $isFormOpen ? 'col-md-8' : 'col-12' }}">
            <div class="card border border-200">
                <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">Departments Register</h5>
                    <div class="d-flex gap-2">
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" placeholder="Search departments..." style="max-width: 180px;">
                        <select wire:model.live="filterType" class="form-select form-select-sm" style="max-width: 150px;">
                            <option value="">All Types</option>
                            <option value="Kitchen Production">Kitchen Production</option>
                            <option value="Operations">Operations</option>
                            <option value="Administration">Administration</option>
                        </select>
                        <select wire:model.live="filterStatus" class="form-select form-select-sm" style="max-width: 120px;">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 align-middle">
                            <thead class="bg-200">
                                <tr>
                                    <th class="px-3">Code</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Manager</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th class="text-end px-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($departments as $dept)
                                    <tr>
                                        <td class="px-3 font-monospace fw-bold">{{ $dept->department_code }}</td>
                                        <td class="fw-semi-bold">{{ $dept->name }}</td>
                                        <td><span class="badge bg-light text-dark">{{ $dept->department_type }}</span></td>
                                        <td>{{ $dept->manager->name ?? '—' }}</td>
                                        <td>{{ $dept->display_order }}</td>
                                        <td>
                                            <span class="badge badge-subtle-{{ $dept->status === 'Active' ? 'success' : 'danger' }}">
                                                {{ $dept->status }}
                                            </span>
                                        </td>
                                        <td class="text-end px-3">
                                            <button wire:click="edit({{ $dept->id }})" class="btn btn-link btn-sm p-0 me-2 text-warning">
                                                <span class="fas fa-edit"></span>
                                            </button>
                                            <button onclick="confirm('Are you sure you want to delete this department?') || event.stopImmediatePropagation()" wire:click="delete({{ $dept->id }})" class="btn btn-link btn-sm p-0 text-danger">
                                                <span class="fas fa-trash"></span>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No departments found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($departments->hasPages())
                    <div class="card-footer bg-light p-2">
                        {{ $departments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
