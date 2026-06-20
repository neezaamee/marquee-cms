<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-id-badge me-2 text-primary"></span>Staff Management</h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Search -->
                <div class="input-group input-group-sm" style="max-width: 200px;">
                    <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search staff..." />
                    <span class="input-group-text"><span class="fas fa-search"></span></span>
                </div>

                <!-- Status Filter -->
                <select wire:model.live="filterStatus" class="form-select form-select-sm" style="min-width:130px">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>

                <!-- Designation Filter -->
                <select wire:model.live="filterDesignation" class="form-select form-select-sm" style="min-width:160px">
                    <option value="">All Designations</option>
                    @foreach($designations as $d)
                        <option value="{{ $d }}">{{ $d }}</option>
                    @endforeach
                </select>

                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'))
                    <a class="btn btn-falcon-primary btn-sm" href="{{ route('staff.create') }}">
                        <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add Employee
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
                <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3" style="width:50px">Photo</th>
                            <th>Emp. ID</th>
                            <th>Name</th>
                            <th>Designation</th>
                            <th>Branch</th>
                            <th>Mobile</th>
                            <th>Type</th>
                            <th>Salary</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">CMS Login</th>
                            <th class="text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                            <tr>
                                <td class="px-3">
                                    @if($employee->photo)
                                        <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->name }}" class="rounded-circle" width="36" height="36" style="object-fit:cover;">
                                    @else
                                        <div class="avatar avatar-xl" style="width:36px;height:36px;background:var(--falcon-200);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                                            <span class="fas fa-user text-500"></span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-subtle-secondary fs-11 font-monospace">{{ $employee->employee_id }}</span>
                                </td>
                                <td class="fw-semi-bold">
                                    <a href="{{ route('staff.show', $employee->id) }}">{{ $employee->name }}</a>
                                </td>
                                <td>{{ $employee->designation }}</td>
                                <td>{{ $employee->branch->name ?? '—' }}</td>
                                <td>{{ $employee->mobile_number }}</td>
                                <td>
                                    @php
                                        $typeColors = ['Permanent' => 'success', 'Contract' => 'info', 'Daily Wages' => 'warning', 'Part-Time' => 'secondary'];
                                        $color = $typeColors[$employee->employment_type] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-subtle-{{ $color }}">{{ $employee->employment_type }}</span>
                                </td>
                                <td>PKR {{ number_format($employee->salary, 0) }}</td>
                                <td class="text-center">
                                    @php
                                        $statusColors = ['active' => 'success', 'inactive' => 'secondary', 'resigned' => 'warning', 'terminated' => 'danger'];
                                        $sc = $statusColors[$employee->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-subtle-{{ $sc }} rounded-pill">{{ ucfirst($employee->status) }}</span>
                                </td>
                                <td class="text-center">
                                    @if($employee->users_count > 0)
                                        <a href="{{ route('staff.logins', $employee->id) }}" class="badge badge-subtle-primary rounded-pill">
                                            <span class="fas fa-key me-1"></span>{{ $employee->users_count }} Login(s)
                                        </a>
                                    @else
                                        <a href="{{ route('staff.logins', $employee->id) }}" class="badge badge-subtle-secondary rounded-pill">
                                            <span class="fas fa-plus me-1"></span>Setup
                                        </a>
                                    @endif
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-link p-0" href="{{ route('staff.show', $employee->id) }}" data-bs-toggle="tooltip" title="View Profile">
                                            <span class="text-info fas fa-eye"></span>
                                        </a>
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'))
                                            <a class="btn btn-link p-0" href="{{ route('staff.logins', $employee->id) }}" data-bs-toggle="tooltip" title="Manage Logins">
                                                <span class="text-success fas fa-key"></span>
                                            </a>
                                            <a class="btn btn-link p-0" href="{{ route('staff.edit', $employee->id) }}" data-bs-toggle="tooltip" title="Edit">
                                                <span class="text-primary fas fa-edit"></span>
                                            </a>
                                            <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $employee->id }})" title="Delete Employee">
                                                <span class="text-danger fas fa-trash-alt"></span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <span class="fas fa-users fa-2x mb-2 d-block"></span>
                                    No staff members found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($employees->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $employees->links() }}
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
                    <p class="mb-0 text-900">Are you sure you want to remove this employee? This will also permanently disable their CMS login if they have one.</p>
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
