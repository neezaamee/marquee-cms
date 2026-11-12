<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use Carbon\Carbon;
use Livewire\Component;

class StaffAttendance extends Component
{
    public string $date;
    public ?int $selectedBranchId = null;
    public string $selectedDepartmentId = '';
    public string $selectedStatus = 'all';
    public string $search = '';

    // employee_id => [status, check_in, check_out, overtime_hours, notes]
    public array $attendanceData = [];

    protected $rules = [
        'attendanceData.*.status' => 'required|in:Present,Absent,Late,Half Day,Leave',
        'attendanceData.*.check_in' => 'nullable',
        'attendanceData.*.check_out' => 'nullable',
        'attendanceData.*.overtime_hours' => 'nullable|numeric|min:0|max:24',
        'attendanceData.*.notes' => 'nullable|string|max:500',
    ];

    public function mount()
    {
        $this->date = Carbon::today()->format('Y-m-d');
        $user = auth()->user();
        if ($user && $user->branch_id && !$user->isSuperAdmin() && !$user->isBusinessOwner()) {
            $this->selectedBranchId = $user->branch_id;
        }
        $this->loadAttendance();
    }

    public function updatedDate()
    {
        $this->loadAttendance();
    }

    public function updatedSelectedBranchId()
    {
        $this->loadAttendance();
    }

    public function updatedSelectedDepartmentId()
    {
        $this->loadAttendance();
    }

    public function getMarqueeId(): ?int
    {
        $user = auth()->user();
        return $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
    }

    public function loadAttendance(): void
    {
        $marqueeId = $this->getMarqueeId();

        $employeesQuery = Employee::where('marquee_id', $marqueeId)
            ->where('status', 'Active');

        if ($this->selectedBranchId) {
            $employeesQuery->where('branch_id', $this->selectedBranchId);
        }

        if ($this->selectedDepartmentId) {
            $employeesQuery->where('department_id', $this->selectedDepartmentId);
        }

        $employees = $employeesQuery->orderBy('name')->get();
        $this->attendanceData = [];

        $existingAttendances = Attendance::where('marquee_id', $marqueeId)
            ->whereDate('date', $this->date)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        foreach ($employees as $employee) {
            $att = $existingAttendances->get($employee->id);

            $this->attendanceData[$employee->id] = [
                'status' => $att ? $att->status : 'Present',
                'check_in' => $att && $att->check_in ? Carbon::parse($att->check_in)->format('H:i') : '09:00',
                'check_out' => $att && $att->check_out ? Carbon::parse($att->check_out)->format('H:i') : '17:00',
                'overtime_hours' => $att ? (float) $att->overtime_hours : 0.00,
                'notes' => $att ? $att->notes : '',
            ];
        }
    }

    public function markAllPresent(): void
    {
        foreach ($this->attendanceData as $empId => $data) {
            $this->attendanceData[$empId]['status'] = 'Present';
            if (empty($this->attendanceData[$empId]['check_in'])) {
                $this->attendanceData[$empId]['check_in'] = '09:00';
            }
            if (empty($this->attendanceData[$empId]['check_out'])) {
                $this->attendanceData[$empId]['check_out'] = '17:00';
            }
        }
        session()->flash('info', 'All employees marked as Present.');
    }

    public function markAllAbsent(): void
    {
        foreach ($this->attendanceData as $empId => $data) {
            $this->attendanceData[$empId]['status'] = 'Absent';
            $this->attendanceData[$empId]['check_in'] = null;
            $this->attendanceData[$empId]['check_out'] = null;
            $this->attendanceData[$empId]['overtime_hours'] = 0.00;
        }
        session()->flash('info', 'All employees marked as Absent.');
    }

    public function saveAttendance(): void
    {
        $this->validate();
        $marqueeId = $this->getMarqueeId();
        $userId = auth()->id();

        $formattedDate = Carbon::parse($this->date)->format('Y-m-d');
        foreach ($this->attendanceData as $employeeId => $data) {
            $employee = Employee::find($employeeId);
            if (!$employee) {
                continue;
            }

            $isAbsent = in_array($data['status'], ['Absent', 'Leave']);

            $existing = Attendance::where('marquee_id', $marqueeId)
                ->where('employee_id', $employeeId)
                ->whereDate('date', $this->date)
                ->first();

            $attributes = [
                'branch_id' => $employee->branch_id,
                'status' => $data['status'],
                'check_in' => $isAbsent ? null : ($data['check_in'] ?: null),
                'check_out' => $isAbsent ? null : ($data['check_out'] ?: null),
                'overtime_hours' => (float) ($data['overtime_hours'] ?? 0.00),
                'notes' => $data['notes'] ?: null,
                'updated_by' => $userId,
            ];

            if ($existing) {
                $existing->update($attributes);
            } else {
                $attributes['marquee_id'] = $marqueeId;
                $attributes['employee_id'] = $employeeId;
                $attributes['date'] = $formattedDate;
                $attributes['created_by'] = $userId;
                Attendance::create($attributes);
            }
        }

        session()->flash('success', 'Attendance records for ' . Carbon::parse($this->date)->format('d M Y') . ' saved successfully.');
    }

    public function render()
    {
        $marqueeId = $this->getMarqueeId();

        $employeesQuery = Employee::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->with(['branch', 'department']);

        if ($this->selectedBranchId) {
            $employeesQuery->where('branch_id', $this->selectedBranchId);
        }

        if ($this->selectedDepartmentId) {
            $employeesQuery->where('department_id', $this->selectedDepartmentId);
        }

        if ($this->search) {
            $employeesQuery->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('employee_id', 'like', '%' . $this->search . '%')
                    ->orWhere('designation', 'like', '%' . $this->search . '%');
            });
        }

        $employees = $employeesQuery->orderBy('name')->get();

        // Calculate KPI summary counts
        $totalEmployees = count($this->attendanceData);
        $presentCount = 0;
        $absentCount = 0;
        $lateCount = 0;
        $halfDayCount = 0;
        $leaveCount = 0;
        $totalOvertime = 0.0;

        foreach ($this->attendanceData as $row) {
            $st = $row['status'] ?? 'Present';
            if ($st === 'Present') $presentCount++;
            elseif ($st === 'Absent') $absentCount++;
            elseif ($st === 'Late') $lateCount++;
            elseif ($st === 'Half Day') $halfDayCount++;
            elseif ($st === 'Leave') $leaveCount++;

            $totalOvertime += (float) ($row['overtime_hours'] ?? 0.0);
        }

        $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('name')->get();
        $departments = Department::where('marquee_id', $marqueeId)->orderBy('name')->get();

        return view('livewire.staff-attendance', [
            'employees' => $employees,
            'branches' => $branches,
            'departments' => $departments,
            'totalEmployees' => $totalEmployees,
            'presentCount' => $presentCount,
            'absentCount' => $absentCount,
            'lateCount' => $lateCount,
            'halfDayCount' => $halfDayCount,
            'leaveCount' => $leaveCount,
            'totalOvertime' => $totalOvertime,
        ])->layout('layouts.admin');
    }
}
