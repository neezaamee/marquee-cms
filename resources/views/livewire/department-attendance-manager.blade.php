<div class="container-fluid p-0">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0 text-secondary"><span class="fas fa-user-check me-2 text-primary"></span>Department Attendance Register</h4>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border border-200 mb-3">
        <div class="card-header bg-light">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <label class="form-label mb-1 fw-semi-bold">Target Department</label>
                    <select wire:model.live="selectedDepartmentId" class="form-select form-select-sm">
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->department_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1 fw-semi-bold">Attendance Date</label>
                    <input type="date" wire:model.live="selectedDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-4 text-md-end mt-4 d-flex justify-content-end gap-1">
                    <button type="button" wire:click="markAllPresent" class="btn btn-outline-success btn-sm">
                        <span class="fas fa-check me-1"></span>All Present
                    </button>
                    <button type="button" wire:click="markAllAbsent" class="btn btn-outline-danger btn-sm">
                        <span class="fas fa-times me-1"></span>All Absent
                    </button>
                    <button wire:click="save" class="btn btn-primary btn-sm px-3">
                        <span class="fas fa-save me-1"></span>Save
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            @if(empty($attendanceData))
                <div class="text-center py-5 text-muted">
                    <span class="fas fa-users-slash fa-2x mb-2 d-block"></span>
                    Please select a department with active employees to view logs.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0 align-middle">
                        <thead class="bg-200">
                            <tr>
                                <th class="px-3">Employee Name</th>
                                <th>Designation</th>
                                <th style="width: 150px;">Status</th>
                                <th style="width: 120px;">Check In</th>
                                <th style="width: 120px;">Check Out</th>
                                <th style="width: 120px;">Overtime (Min)</th>
                                <th>Notes / Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attendanceData as $employeeId => $data)
                                <tr>
                                    <td class="px-3 fw-semi-bold text-dark">{{ $data['employee_name'] }}</td>
                                    <td>{{ $data['designation'] }}</td>
                                    <td>
                                        <select wire:model="attendanceData.{{ $employeeId }}.status" class="form-select form-select-sm py-0">
                                            <option value="Present">Present</option>
                                            <option value="Absent">Absent</option>
                                            <option value="Late">Late</option>
                                            <option value="Leave">Leave</option>
                                            <option value="Holiday">Holiday</option>
                                            <option value="Weekly Off">Weekly Off</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="time" wire:model="attendanceData.{{ $employeeId }}.check_in" 
                                               class="form-control form-control-sm py-0" 
                                               @if(!in_array($attendanceData[$employeeId]['status'], ['Present', 'Late'])) disabled @endif>
                                    </td>
                                    <td>
                                        <input type="time" wire:model="attendanceData.{{ $employeeId }}.check_out" 
                                               class="form-control form-control-sm py-0" 
                                               @if(!in_array($attendanceData[$employeeId]['status'], ['Present', 'Late'])) disabled @endif>
                                    </td>
                                    <td>
                                        <input type="number" wire:model="attendanceData.{{ $employeeId }}.overtime_minutes" 
                                               class="form-control form-control-sm py-0" 
                                               @if(!in_array($attendanceData[$employeeId]['status'], ['Present', 'Late'])) disabled @endif>
                                    </td>
                                    <td>
                                        <input type="text" wire:model="attendanceData.{{ $employeeId }}.notes" class="form-control form-control-sm py-0" placeholder="notes...">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
