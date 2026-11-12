<div>
    <!-- Top Action & Navigation Bar -->
    <div class="card mb-3 border-0 shadow-sm d-print-none">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-xl bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center shadow-sm">
                        <span class="fas fa-th fa-lg"></span>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold text-900 d-flex align-items-center gap-2">
                            Monthly Attendance Sheet Matrix
                            <span class="badge bg-primary-subtle text-primary rounded-pill fs-11">{{ $formattedMonth }}</span>
                        </h4>
                        <p class="text-600 fs-11 mb-0">Consolidated monthly staff attendance, absenteeism deductions, and overtime summary grid.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                        <span class="fas fa-print me-1"></span> Print Sheet
                    </button>
                    <a href="{{ route('staff.attendance') }}" class="btn btn-outline-primary btn-sm fw-bold">
                        <span class="fas fa-calendar-day me-1"></span> Daily Register
                    </a>
                    <a href="{{ route('payroll.index') }}" class="btn btn-falcon-default btn-sm fw-bold">
                        <span class="fas fa-money-check-alt me-1 text-success"></span> Monthly Payroll
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Legend Bar -->
    <div class="card mb-3 border-0 shadow-sm d-print-none">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-12 col-md-auto d-flex align-items-center gap-2 flex-wrap">
                    <div>
                        <label class="form-label fs-11 text-muted mb-0 fw-bold">Select Month</label>
                        <input type="month" wire:model.live="selectedMonth" class="form-control form-control-sm" />
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
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Filter employee..." class="form-control form-control-sm" style="min-width: 150px;" />
                    </div>
                </div>

                <div class="col-12 col-md-auto d-flex align-items-center gap-2 flex-wrap fs-11">
                    <span class="fw-bold text-muted">Legend:</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2">P = Present</span>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2">A = Absent</span>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2">L = Late</span>
                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2">H = Half Day</span>
                    <span class="badge bg-secondary-subtle text-secondary border px-2">LV = Leave</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Header Only for Paper Output -->
    <div class="d-none d-print-block mb-3 text-center">
        <h4 class="mb-0 fw-bold">Monthly Staff Attendance Register</h4>
        <h6 class="text-muted">{{ $formattedMonth }}</h6>
    </div>

    <!-- Main Attendance Matrix Table -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive scrollbar" style="max-height: 75vh;">
                <table class="table table-bordered table-sm align-middle mb-0 fs-11 text-center" style="white-space: nowrap;">
                    <thead class="bg-200 text-900 sticky-top" style="z-index: 10;">
                        <tr>
                            <th class="ps-3 text-start bg-200 sticky-col" style="min-width: 180px; position: sticky; left: 0; z-index: 11;">
                                Staff Member
                            </th>
                            @foreach($daysList as $d)
                                <th class="p-1 {{ $d['is_sunday'] ? 'bg-danger-subtle text-danger' : '' }}" style="width: 28px; min-width: 28px;">
                                    <div class="fw-bold">{{ $d['day'] }}</div>
                                    <div style="font-size: 8px;">{{ substr($d['short_day'], 0, 1) }}</div>
                                </th>
                            @endforeach
                            <th class="bg-success-subtle text-success py-1" style="min-width: 40px;" title="Total Present Days">Pres</th>
                            <th class="bg-danger-subtle text-danger py-1" style="min-width: 40px;" title="Total Absent Days">Abs</th>
                            <th class="bg-info-subtle text-info py-1" style="min-width: 40px;" title="Total Half Days">Half</th>
                            <th class="bg-warning-subtle text-warning py-1" style="min-width: 40px;" title="Total Late Days">Late</th>
                            <th class="bg-secondary-subtle text-secondary py-1" style="min-width: 40px;" title="Total Leaves">Lvs</th>
                            <th class="bg-primary-subtle text-primary py-1" style="min-width: 50px;" title="Total Overtime Hours">OT(h)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                            @php
                                $stats = $employeeStats[$emp->id] ?? ['present' => 0, 'absent' => 0, 'half_day' => 0, 'late' => 0, 'leave' => 0, 'overtime' => 0];
                            @endphp
                            <tr>
                                <td class="ps-3 text-start bg-white sticky-col fw-semibold" style="position: sticky; left: 0; z-index: 5;">
                                    <a href="{{ route('staff.show', $emp->id) }}" class="text-900 text-decoration-none">
                                        {{ $emp->name }}
                                    </a>
                                    <div class="text-muted" style="font-size: 9px;">
                                        {{ $emp->designation }} &bull; {{ $emp->employee_id }}
                                    </div>
                                </td>
                                @foreach($daysList as $d)
                                    @php
                                        $dayNum = $d['day'];
                                        $record = $attendanceMatrix[$emp->id][$dayNum] ?? null;
                                        $st = $record['status'] ?? null;
                                        $ot = (float) ($record['overtime'] ?? 0);
                                    @endphp
                                    <td class="p-0 {{ $d['is_sunday'] ? 'bg-light' : '' }}" style="height: 34px;">
                                        @if($st === 'Present')
                                            <span class="badge bg-success-subtle text-success p-1" style="font-size: 10px;" title="Present {{ $ot > 0 ? "($ot hrs OT)" : '' }}">
                                                P{{ $ot > 0 ? "+$ot" : '' }}
                                            </span>
                                        @elseif($st === 'Absent')
                                            <span class="badge bg-danger-subtle text-danger p-1" style="font-size: 10px;" title="Absent">A</span>
                                        @elseif($st === 'Late')
                                            <span class="badge bg-warning-subtle text-warning p-1" style="font-size: 10px;" title="Late">L</span>
                                        @elseif($st === 'Half Day')
                                            <span class="badge bg-info-subtle text-info p-1" style="font-size: 10px;" title="Half Day">H</span>
                                        @elseif($st === 'Leave')
                                            <span class="badge bg-secondary-subtle text-secondary p-1" style="font-size: 10px;" title="Leave">LV</span>
                                        @else
                                            <span class="text-300" style="font-size: 9px;">&mdash;</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="font-monospace fw-bold text-success">{{ $stats['present'] }}</td>
                                <td class="font-monospace fw-bold text-danger">{{ $stats['absent'] }}</td>
                                <td class="font-monospace fw-bold text-info">{{ $stats['half_day'] }}</td>
                                <td class="font-monospace fw-bold text-warning">{{ $stats['late'] }}</td>
                                <td class="font-monospace fw-bold text-secondary">{{ $stats['leave'] }}</td>
                                <td class="font-monospace fw-bold text-primary">{{ number_format($stats['overtime'], 1) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($daysList) + 7 }}" class="text-center py-5 text-muted">
                                    No active staff members found for the selected criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-light py-2 d-flex align-items-center justify-content-between d-print-none">
            <span class="fs-11 text-muted">Showing {{ $employees->count() }} active staff attendance records for {{ $formattedMonth }}.</span>
            <a href="{{ route('payroll.index') }}" class="btn btn-primary btn-sm">
                <span class="fas fa-calculator me-1"></span> Generate Payroll from this Attendance
            </a>
        </div>
    </div>
</div>
