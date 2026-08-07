<div class="container-fluid p-0">
    <!-- Header -->
    <div class="row mb-3 align-items-center justify-content-between">
        <div class="col-auto">
            <h3 class="mb-0 text-secondary">
                <span class="fas fa-id-badge me-2 text-primary"></span>Department Staff Roster & Assignments
            </h3>
            <p class="text-600 fs-10 mb-0">Assign employees to organizational cost centers and reporting managers</p>
        </div>
        <div class="col-auto d-flex gap-2">
            @if(count($selectedEmployees) > 0)
                <button wire:click="openBulkModal" class="btn btn-primary btn-sm">
                    <span class="fas fa-exchange-alt me-1"></span>Bulk Assign ({{ count($selectedEmployees) }})
                </button>
            @endif
            <a href="{{ route('departments.index') }}" class="btn btn-outline-secondary btn-sm">
                <span class="fas fa-sitemap me-1"></span>Department Master
            </a>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-start border-primary border-4 shadow-sm">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-500 fs-11 text-uppercase fw-semi-bold">Total Staff</div>
                        <div class="fs-4 fw-bold text-dark font-monospace">{{ $totalStaff }}</div>
                    </div>
                    <div class="icon-item bg-primary-subtle text-primary rounded-circle">
                        <span class="fas fa-users"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-start border-success border-4 shadow-sm">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-500 fs-11 text-uppercase fw-semi-bold">Assigned to Departments</div>
                        <div class="fs-4 fw-bold text-success font-monospace">{{ $assignedStaff }}</div>
                    </div>
                    <div class="icon-item bg-success-subtle text-success rounded-circle">
                        <span class="fas fa-check-circle"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-start border-warning border-4 shadow-sm">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-500 fs-11 text-uppercase fw-semi-bold">Unassigned Staff</div>
                        <div class="fs-4 fw-bold text-warning font-monospace">{{ $unassignedStaff }}</div>
                    </div>
                    <div class="icon-item bg-warning-subtle text-warning rounded-circle">
                        <span class="fas fa-user-tag"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Table Card -->
    <div class="card border border-200">
        <div class="card-header bg-light">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" placeholder="Search staff by name, code, phone...">
                </div>
                <div class="col-md-4">
                    <select wire:model.live="filterDepartment" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        <option value="unassigned">-- Unassigned Staff Only --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->department_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <select wire:model.live="filterDesignation" class="form-select form-select-sm">
                        <option value="">All Designations</option>
                        @foreach($designations as $desig)
                            <option value="{{ $desig }}">{{ $desig }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0 align-middle">
                    <thead class="bg-200">
                        <tr>
                            <th class="ps-3" style="width: 30px;">
                                <input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.emp-chk').forEach(c => c.checked = this.checked); @this.set('selectedEmployees', Array.from(document.querySelectorAll('.emp-chk:checked')).map(c => c.value))">
                            </th>
                            <th>Emp ID</th>
                            <th>Employee Name</th>
                            <th>Designation</th>
                            <th>Department</th>
                            <th>Reporting Manager</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                            <tr>
                                <td class="ps-3">
                                    <input type="checkbox" wire:model.live="selectedEmployees" value="{{ $emp->id }}" class="form-check-input emp-chk">
                                </td>
                                <td class="font-monospace fw-bold">{{ $emp->employee_id }}</td>
                                <td>
                                    <div class="fw-semi-bold text-dark">{{ $emp->name }}</div>
                                    <div class="fs-11 text-muted">{{ $emp->mobile_number }}</div>
                                </td>
                                <td><span class="badge bg-200 text-800">{{ $emp->designation }}</span></td>
                                <td>
                                    @if($emp->department)
                                        <span class="badge bg-primary-subtle text-primary">{{ $emp->department->name }}</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">Unassigned</span>
                                    @endif
                                </td>
                                <td>{{ $emp->reportingManager->name ?? '—' }}</td>
                                <td>
                                    @if($emp->status === 'active')
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($emp->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <button wire:click="openAssignModal({{ $emp->id }})" class="btn btn-outline-primary btn-xs">
                                        <span class="fas fa-edit me-1"></span>Assign
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No employees found matching the filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($employees->hasPages())
            <div class="card-footer bg-light p-2">
                {{ $employees->links() }}
            </div>
        @endif
    </div>

    <!-- Edit Assignment Modal -->
    @if($isAssignModalOpen)
        <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title">Assign Department & Manager</h5>
                        <button type="button" class="btn-close" wire:click="$set('isAssignModalOpen', false)"></button>
                    </div>
                    <form wire:submit.prevent="saveAssignment">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Department Cost Center</label>
                                <select wire:model="department_id" class="form-select">
                                    <option value="">None (Unassigned)</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->department_code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reporting Manager</label>
                                <select wire:model="reporting_manager_id" class="form-select">
                                    <option value="">None</option>
                                    @foreach($allManagers as $mgr)
                                        @if($mgr->id !== $employeeId)
                                            <option value="{{ $mgr->id }}">{{ $mgr->name }} ({{ $mgr->designation }})</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('isAssignModalOpen', false)">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm">Save Assignment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Bulk Assignment Modal -->
    @if($isBulkModalOpen)
        <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title">Bulk Assign Department</h5>
                        <button type="button" class="btn-close" wire:click="$set('isBulkModalOpen', false)"></button>
                    </div>
                    <form wire:submit.prevent="processBulkTransfer">
                        <div class="modal-body">
                            <p class="fs-10 text-600 mb-2">Re-assigning {{ count($selectedEmployees) }} selected staff members to department:</p>
                            <div class="mb-3">
                                <label class="form-label">Target Department</label>
                                <select wire:model="bulk_department_id" class="form-select @error('bulk_department_id') is-invalid @enderror">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->department_code }})</option>
                                    @endforeach
                                </select>
                                @error('bulk_department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('isBulkModalOpen', false)">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm">Transfer All</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
