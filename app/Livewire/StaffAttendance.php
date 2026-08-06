<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\Attendance;
use Livewire\Component;
use Carbon\Carbon;

class StaffAttendance extends Component
{
    public $date;
    public $attendanceData = []; // employee_id => [status, check_in, check_out, notes]

    protected $rules = [
        'attendanceData.*.status' => 'required|in:Present,Absent,Late,Leave',
        'attendanceData.*.check_in' => 'nullable',
        'attendanceData.*.check_out' => 'nullable',
        'attendanceData.*.notes' => 'nullable|string',
    ];

    public function mount()
    {
        $this->date = Carbon::today()->format('Y-m-d');
        $this->loadAttendance();
    }

    public function updatedDate()
    {
        $this->loadAttendance();
    }

    public function loadAttendance()
    {
        $employees = Employee::where('status', 'Active')->get();
        $this->attendanceData = [];

        foreach ($employees as $employee) {
            $attendance = Attendance::where('employee_id', $employee->id)
                ->where('date', $this->date)
                ->first();

            $this->attendanceData[$employee->id] = [
                'status' => $attendance ? $attendance->status : 'Present',
                'check_in' => $attendance ? ($attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : '') : '09:00',
                'check_out' => $attendance ? ($attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i') : '') : '17:00',
                'notes' => $attendance ? $attendance->notes : '',
            ];
        }
    }

    public function saveAttendance()
    {
        $this->validate();

        foreach ($this->attendanceData as $employeeId => $data) {
            Attendance::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'date' => $this->date,
                ],
                [
                    'branch_id' => Employee::find($employeeId)->branch_id,
                    'status' => $data['status'],
                    'check_in' => $data['status'] === 'Absent' ? null : $data['check_in'],
                    'check_out' => $data['status'] === 'Absent' ? null : $data['check_out'],
                    'notes' => $data['notes'],
                ]
            );
        }

        session()->flash('success', 'Attendance records for ' . $this->date . ' saved successfully.');
    }

    public function render()
    {
        $employees = Employee::where('status', 'Active')->get();

        return view('livewire.staff-attendance', [
            'employees' => $employees,
        ])->layout('layouts.admin');
    }
}
