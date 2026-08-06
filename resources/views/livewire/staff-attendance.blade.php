<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 text-primary"><i class="fas fa-user-check me-2"></i>Staff Attendance Register</h5>
                <p class="mb-0 text-muted small">Log and track daily employee check-ins and attendance states.</p>
            </div>
            <div>
                <input type="date" wire:model.live="date" class="form-control form-control-sm" />
            </div>
        </div>
        <div class="card-body">
            @if (session()->has('success'))
                <div class="alert alert-success border-2 d-flex align-items-center" role="alert">
                    <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-3"></span></div>
                    <p class="mb-0 flex-1">{{ session('success') }}</p>
                </div>
            @endif

            <form wire:submit.prevent="saveAttendance">
                <div class="table-responsive scrollbar">
                    <table class="table table-hover table-striped overflow-hidden fs--1 mb-0">
                        <thead class="bg-200 text-900">
                            <tr>
                                <th class="sort pe-1">Employee Name</th>
                                <th class="sort pe-1">Designation</th>
                                <th class="sort pe-1 text-center" style="width: 150px;">Status</th>
                                <th class="sort pe-1" style="width: 120px;">Check In</th>
                                <th class="sort pe-1" style="width: 120px;">Check Out</th>
                                <th class="sort pe-1">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="list">
                            @forelse ($employees as $employee)
                                <tr class="align-middle">
                                    <td class="text-nowrap">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-l me-2">
                                                <div class="avatar-name rounded-circle"><span>{{ substr($employee->name, 0, 2) }}</span></div>
                                            </div>
                                            <div class="ms-1">
                                                <h6 class="mb-0 text-900">{{ $employee->name }}</h6>
                                                <div class="text-500 text-nowrap small">{{ $employee->employment_type }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $employee->designation }}</td>
                                    <td class="text-center">
                                        <select wire:model="attendanceData.{{ $employee->id }}.status" class="form-select form-select-sm">
                                            <option value="Present">Present</option>
                                            <option value="Absent">Absent</option>
                                            <option value="Late">Late</option>
                                            <option value="Leave">Leave</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="time" wire:model="attendanceData.{{ $employee->id }}.check_in" class="form-control form-control-sm" 
                                               @if($attendanceData[$employee->id]['status'] === 'Absent') disabled @endif />
                                    </td>
                                    <td>
                                        <input type="time" wire:model="attendanceData.{{ $employee->id }}.check_out" class="form-control form-control-sm" 
                                               @if($attendanceData[$employee->id]['status'] === 'Absent') disabled @endif />
                                    </td>
                                    <td>
                                        <input type="text" wire:model="attendanceData.{{ $employee->id }}.notes" placeholder="Notes..." class="form-control form-control-sm" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No active employees found to register attendance.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($employees->count() > 0)
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="fas fa-save me-2"></i>Save Attendance Records
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
