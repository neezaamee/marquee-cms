<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\Employee;
use App\Models\DepartmentAttendance;
use Livewire\Component;

class DepartmentAttendanceManager extends Component
{
    public $selectedDepartmentId = '';
    public $selectedDate = '';

    // Attendance form states: [employee_id => ['status' => x, 'check_in' => y, 'check_out' => z, 'overtime' => a, 'notes' => b]]
    public $attendanceData = [];

    public function mount()
    {
        $this->selectedDate = now()->format('Y-m-d');
        
        // Auto-select first department if exists
        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;
        $firstDept = Department::where('marquee_id', $marqueeId);
        if ($branchId) {
            $firstDept->where('branch_id', $branchId);
        }
        $firstDept = $firstDept->orderBy('display_order', 'asc')->first();

        if ($firstDept) {
            $this->selectedDepartmentId = $firstDept->id;
            $this->loadAttendance();
        }
    }

    public function updatedSelectedDepartmentId()
    {
        $this->loadAttendance();
    }

    public function updatedSelectedDate()
    {
        $this->loadAttendance();
    }

    public function loadAttendance()
    {
        $this->attendanceData = [];

        if (!$this->selectedDepartmentId || !$this->selectedDate) {
            return;
        }

        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;

        // 1. Fetch employees in department
        $employees = Employee::where('marquee_id', $marqueeId)
            ->where('department_id', $this->selectedDepartmentId);
        if ($branchId) {
            $employees->where('branch_id', $branchId);
        }
        $employees = $employees->orderBy('name', 'asc')->get();

        // 2. Fetch existing attendance logs
        $existingRecords = DepartmentAttendance::where('department_id', $this->selectedDepartmentId)
            ->where('date', $this->selectedDate)
            ->get()
            ->keyBy('employee_id');

        foreach ($employees as $employee) {
            $record = $existingRecords->get($employee->id);

            $this->attendanceData[$employee->id] = [
                'employee_name' => $employee->name,
                'designation' => $employee->designation ?? 'Staff',
                'status' => $record ? $record->status : 'Present',
                'check_in' => $record ? $record->check_in : '09:00',
                'check_out' => $record ? $record->check_out : '18:00',
                'overtime_minutes' => $record ? $record->overtime_minutes : 0,
                'notes' => $record ? $record->notes : '',
            ];
        }
    }

    public function markAllPresent()
    {
        foreach ($this->attendanceData as $empId => $data) {
            $this->attendanceData[$empId]['status'] = 'Present';
        }
    }

    public function markAllAbsent()
    {
        foreach ($this->attendanceData as $empId => $data) {
            $this->attendanceData[$empId]['status'] = 'Absent';
        }
    }

    public function save()
    {
        if (!$this->selectedDepartmentId || !$this->selectedDate) {
            return;
        }

        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;

        foreach ($this->attendanceData as $employeeId => $data) {
            DepartmentAttendance::updateOrCreate(
                [
                    'marquee_id' => $marqueeId,
                    'branch_id' => $branchId,
                    'department_id' => $this->selectedDepartmentId,
                    'employee_id' => $employeeId,
                    'date' => $this->selectedDate,
                ],
                [
                    'status' => $data['status'],
                    'check_in' => in_array($data['status'], ['Present', 'Late']) ? $data['check_in'] : null,
                    'check_out' => in_array($data['status'], ['Present', 'Late']) ? $data['check_out'] : null,
                    'overtime_minutes' => in_array($data['status'], ['Present', 'Late']) ? (int) $data['overtime_minutes'] : 0,
                    'notes' => $data['notes'],
                    'created_by' => auth()->id(),
                ]
            );
        }

        session()->flash('message', 'Attendance records updated successfully.');
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;

        $departments = Department::where('marquee_id', $marqueeId);
        if ($branchId) {
            $departments->where('branch_id', $branchId);
        }
        $departments = $departments->orderBy('display_order', 'asc')->get();

        return view('livewire.department-attendance-manager', [
            'departments' => $departments,
        ])->layout('layouts.admin');
    }
}
