<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use Carbon\Carbon;
use Livewire\Component;

class MonthlyAttendanceSheet extends Component
{
    public string $selectedMonth; // 'YYYY-MM'
    public ?int $selectedBranchId = null;
    public string $selectedDepartmentId = '';
    public string $search = '';

    public function mount()
    {
        $this->selectedMonth = Carbon::today()->format('Y-m');
        $user = auth()->user();
        if ($user && $user->branch_id && !$user->isSuperAdmin() && !$user->isBusinessOwner()) {
            $this->selectedBranchId = $user->branch_id;
        }
    }

    public function getMarqueeId(): ?int
    {
        $user = auth()->user();
        return $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
    }

    public function render()
    {
        $marqueeId = $this->getMarqueeId();

        $startOfMonth = Carbon::parse($this->selectedMonth . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($this->selectedMonth . '-01')->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        // Days list: [1 => ['day' => 1, 'name' => 'Sun', 'is_weekend' => true]]
        $daysList = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::createFromDate($startOfMonth->year, $startOfMonth->month, $day);
            $daysList[$day] = [
                'day' => $day,
                'date_str' => $date->format('Y-m-d'),
                'short_day' => $date->format('D'),
                'is_sunday' => $date->isSunday(),
            ];
        }

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

        // Fetch attendances for all active employees for this whole month
        $attendances = Attendance::where('marquee_id', $marqueeId)
            ->whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get();

        // Group attendances by employee_id and day
        $attendanceMatrix = [];
        $employeeStats = [];

        foreach ($employees as $emp) {
            $attendanceMatrix[$emp->id] = [];
            $employeeStats[$emp->id] = [
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'half_day' => 0,
                'leave' => 0,
                'overtime' => 0.0,
            ];
        }

        foreach ($attendances as $att) {
            $empId = $att->employee_id;
            $dayNum = (int) Carbon::parse($att->date)->format('j');

            $st = $att->status;
            $attendanceMatrix[$empId][$dayNum] = [
                'status' => $st,
                'overtime' => (float) $att->overtime_hours,
                'notes' => $att->notes,
            ];

            if (isset($employeeStats[$empId])) {
                if ($st === 'Present') $employeeStats[$empId]['present']++;
                elseif ($st === 'Absent') $employeeStats[$empId]['absent']++;
                elseif ($st === 'Late') $employeeStats[$empId]['late']++;
                elseif ($st === 'Half Day') $employeeStats[$empId]['half_day']++;
                elseif ($st === 'Leave') $employeeStats[$empId]['leave']++;

                $employeeStats[$empId]['overtime'] += (float) $att->overtime_hours;
            }
        }

        $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('name')->get();
        $departments = Department::where('marquee_id', $marqueeId)->orderBy('name')->get();

        return view('livewire.monthly-attendance-sheet', [
            'employees' => $employees,
            'daysList' => $daysList,
            'daysInMonth' => $daysInMonth,
            'attendanceMatrix' => $attendanceMatrix,
            'employeeStats' => $employeeStats,
            'branches' => $branches,
            'departments' => $departments,
            'formattedMonth' => $startOfMonth->format('F Y'),
        ])->layout('layouts.admin');
    }
}
