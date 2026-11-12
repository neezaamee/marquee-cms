<div>
    <!-- Page Header & Mode Switcher -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-xl bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center shadow-sm">
                        <span class="fas fa-calendar-check fa-lg"></span>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold text-900 d-flex align-items-center gap-2">
                            Daily Staff Attendance Register
                            <span class="badge bg-primary-subtle text-primary rounded-pill fs-11">Live Log</span>
                        </h4>
                        <p class="text-600 fs-11 mb-0">Record and monitor daily staff check-ins, absenteeism, and overtime duty hours.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('attendance.monthly') }}" class="btn btn-outline-primary btn-sm fw-bold">
                        <span class="fas fa-th me-1"></span> Monthly Attendance Sheet
                    </a>
                    <a href="{{ route('payroll.index') }}" class="btn btn-falcon-default btn-sm fw-bold">
                        <span class="fas fa-money-check-alt me-1 text-success"></span> Monthly Payroll
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Summary KPI Bar -->
    <div class="row g-2 mb-3">
        <div class="col-4 col-sm-2">
            <div class="card bg-body-tertiary border-0 shadow-sm p-2 text-center h-100">
                <span class="fs-11 text-muted text-uppercase fw-bold">Total Staff</span>
                <h4 class="mb-0 fw-bolder text-900">{{ $totalEmployees }}</h4>
            </div>
        </div>
        <div class="col-4 col-sm-2">
            <div class="card bg-success-subtle border-0 shadow-sm p-2 text-center h-100">
                <span class="fs-11 text-success text-uppercase fw-bold">Present</span>
                <h4 class="mb-0 fw-bolder text-success">{{ $presentCount }}</h4>
            </div>
        </div>
        <div class="col-4 col-sm-2">
            <div class="card bg-danger-subtle border-0 shadow-sm p-2 text-center h-100">
                <span class="fs-11 text-danger text-uppercase fw-bold">Absent</span>
                <h4 class="mb-0 fw-bolder text-danger">{{ $absentCount }}</h4>
            </div>
        </div>
        <div class="col-4 col-sm-2">
            <div class="card bg-warning-subtle border-0 shadow-sm p-2 text-center h-100">
                <span class="fs-11 text-warning text-uppercase fw-bold">Late</span>
                <h4 class="mb-0 fw-bolder text-warning">{{ $lateCount }}</h4>
            </div>
        </div>
        <div class="col-4 col-sm-2">
            <div class="card bg-info-subtle border-0 shadow-sm p-2 text-center h-100">
                <span class="fs-11 text-info text-uppercase fw-bold">Half Day / Leave</span>
                <h4 class="mb-0 fw-bolder text-info">{{ $halfDayCount + $leaveCount }}</h4>
            </div>
        </div>
        <div class="col-4 col-sm-2">
            <div class="card bg-primary-subtle border-0 shadow-sm p-2 text-center h-100">
                <span class="fs-11 text-primary text-uppercase fw-bold">Overtime</span>
                <h4 class="mb-0 fw-bolder text-primary">{{ number_format($totalOvertime, 1) }} <small class="fs-11 text-muted">hrs</small></h4>
            </div>
        </div>
    </div>

    <!-- Main Attendance Register Card -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-header bg-body-tertiary py-3">
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-12 col-md-auto d-flex align-items-center gap-2 flex-wrap">
                    <div>
                        <label class="form-label fs-11 text-muted mb-0 fw-bold">Attendance Date</label>
                        <input type="date" wire:model.live="date" class="form-control form-control-sm" />
                    </div>

                    @if($branches->count() > 1)
                        <div>
                            <label class="form-label fs-11 text-muted mb-0 fw-bold">Branch</label>
                            <select wire:model.live="selectedBranchId" class="form-select form-select-sm">
                                <option value="">All Branches</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($departments->count() > 0)
                        <div>
                            <label class="form-label fs-11 text-muted mb-0 fw-bold">Department</label>
                            <select wire:model.live="selectedDepartmentId" class="form-select form-select-sm">
                                <option value="">All Departments</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label class="form-label fs-11 text-muted mb-0 fw-bold">Search Staff</label>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name, ID..." class="form-control form-control-sm" style="min-width: 160px;" />
                    </div>
                </div>

                <div class="col-12 col-md-auto d-flex gap-2 justify-content-end align-items-end pt-2 pt-md-0">
                    <button type="button" wire:click="markAllPresent" class="btn btn-outline-success btn-sm">
                        <span class="fas fa-check-double me-1"></span> All Present
                    </button>
                    <button type="button" wire:click="markAllAbsent" class="btn btn-outline-danger btn-sm">
                        <span class="fas fa-times me-1"></span> All Absent
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            @if(session()->has('success'))
                <div class="alert alert-success border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-grow-1 text-success-800">{{ session('success') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session()->has('info'))
                <div class="alert alert-info border-2 d-flex align-items-center m-3" role="alert">
                    <span class="fas fa-info-circle me-2"></span>
                    <p class="mb-0 flex-grow-1">{{ session('info') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form wire:submit.prevent="saveAttendance">
                <div class="table-responsive scrollbar">
                    <table class="table table-sm table-striped align-middle mb-0 fs-10 table-hover">
                        <thead class="bg-200 text-900">
                            <tr>
                                <th class="ps-3 py-2" style="width: 250px;">Employee & Designation</th>
                                <th class="text-center py-2" style="width: 140px;">Attendance Status</th>
                                <th class="text-center py-2" style="width: 110px;">Check In</th>
                                <th class="text-center py-2" style="width: 110px;">Check Out</th>
                                <th class="text-center py-2" style="width: 100px;">Overtime (Hrs)</th>
                                <th class="py-2 pe-3">Notes / Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employees as $employee)
                                @php
                                    $st = $attendanceData[$employee->id]['status'] ?? 'Present';
                                    $isAbsent = in_array($st, ['Absent', 'Leave']);
                                @endphp
                                <tr>
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            @if($employee->photo)
                                                <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->name }}" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                            @else
                                                <div class="avatar avatar-m bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 11px;">
                                                    {{ substr($employee->name, 0, 2) }}
                                                </div>
                                            @endif
                                            <div>
                                                <a href="{{ route('staff.show', $employee->id) }}" class="fw-bold text-900 text-decoration-none" target="_blank">
                                                    {{ $employee->name }}
                                                </a>
                                                <div class="text-muted fs-11">
                                                    <span class="badge bg-secondary-subtle text-secondary me-1" style="font-size: 9px;">{{ $employee->employee_id }}</span>
                                                    {{ $employee->designation }} &bull; {{ $employee->department->name ?? 'Staff' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <select wire:model.live="attendanceData.{{ $employee->id }}.status" 
                                                class="form-select form-select-sm fw-bold fs-11 text-center 
                                                    {{ $st === 'Present' ? 'border-success text-success' : '' }}
                                                    {{ $st === 'Absent' ? 'border-danger text-danger bg-danger-subtle' : '' }}
                                                    {{ $st === 'Late' ? 'border-warning text-warning' : '' }}
                                                    {{ $st === 'Half Day' ? 'border-info text-info' : '' }}
                                                    {{ $st === 'Leave' ? 'border-secondary text-secondary' : '' }}">
                                            <option value="Present">Present</option>
                                            <option value="Absent">Absent</option>
                                            <option value="Late">Late</option>
                                            <option value="Half Day">Half Day</option>
                                            <option value="Leave">Leave</option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <input type="time" wire:model="attendanceData.{{ $employee->id }}.check_in" 
                                               class="form-control form-control-sm text-center"
                                               @if($isAbsent) disabled @endif />
                                    </td>
                                    <td class="text-center">
                                        <input type="time" wire:model="attendanceData.{{ $employee->id }}.check_out" 
                                               class="form-control form-control-sm text-center"
                                               @if($isAbsent) disabled @endif />
                                    </td>
                                    <td class="text-center">
                                        <input type="number" step="0.5" min="0" max="24" wire:model="attendanceData.{{ $employee->id }}.overtime_hours" 
                                               placeholder="0.0" class="form-control form-control-sm text-center font-monospace"
                                               @if($isAbsent) disabled @endif />
                                    </td>
                                    <td class="pe-3">
                                        <input type="text" wire:model="attendanceData.{{ $employee->id }}.notes" 
                                               placeholder="Duty notes, event shift..." class="form-control form-control-sm" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <span class="fas fa-users-slash fa-2x mb-2 d-block text-400"></span>
                                        No active employees found matching the selected criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($employees->count() > 0)
                    <div class="card-footer bg-body-tertiary d-flex align-items-center justify-content-between py-2">
                        <span class="fs-11 text-muted">Review all staff lines and click Save Attendance to update database records.</span>
                        <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm fw-bold">
                            <span class="fas fa-save me-1"></span> Save Attendance Records
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
