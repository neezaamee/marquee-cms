<?php

namespace App\Livewire\Payroll;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\SalaryAdvance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PayrollBatchManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $filterYear = '';
    public ?int $selectedBranchId = null;

    // Generate Modal state
    public bool $isGenerateModalOpen = false;
    public string $genMonth = ''; // 'YYYY-MM'
    public ?int $genBranchId = null;
    public int $genWorkingDays = 30; // standard 30 or actual month days

    public function mount()
    {
        $this->filterYear = Carbon::today()->format('Y');
        $this->genMonth = Carbon::today()->format('Y-m');
        $user = auth()->user();
        if ($user && $user->branch_id && !$user->isSuperAdmin() && !$user->isBusinessOwner()) {
            $this->selectedBranchId = $user->branch_id;
            $this->genBranchId = $user->branch_id;
        }
    }

    public function getMarqueeId(): ?int
    {
        $user = auth()->user();
        return $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
    }

    public function openGenerateModal(): void
    {
        $this->genMonth = Carbon::today()->format('Y-m');
        $this->isGenerateModalOpen = true;
    }

    public function closeGenerateModal(): void
    {
        $this->isGenerateModalOpen = false;
    }

    public function generatePayroll(): void
    {
        $marqueeId = $this->getMarqueeId();
        $userId = auth()->id();

        // Check if payroll already exists for this marquee & branch & month
        $existing = Payroll::where('marquee_id', $marqueeId)
            ->where('payroll_month', $this->genMonth)
            ->when($this->genBranchId, function ($q) {
                $q->where('branch_id', $this->genBranchId);
            })
            ->first();

        if ($existing) {
            session()->flash('error', "A payroll batch ({$existing->batch_number}) already exists for " . Carbon::parse($this->genMonth . '-01')->format('F Y') . ".");
            $this->closeGenerateModal();
            return;
        }

        $startOfMonth = Carbon::parse($this->genMonth . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($this->genMonth . '-01')->endOfMonth();
        $daysInMonth = $this->genWorkingDays ?: $startOfMonth->daysInMonth;

        $employeesQuery = Employee::where('marquee_id', $marqueeId)
            ->where('status', 'Active');

        if ($this->genBranchId) {
            $employeesQuery->where('branch_id', $this->genBranchId);
        }

        $employees = $employeesQuery->get();

        if ($employees->isEmpty()) {
            session()->flash('error', 'No active employees found to generate payroll.');
            $this->closeGenerateModal();
            return;
        }

        DB::beginTransaction();
        try {
            // Generate batch number: PAY-YYYY-MM-XXXX
            $countBatches = Payroll::withoutGlobalScopes()->where('marquee_id', $marqueeId)->count() + 1;
            $batchNumber = 'PAY-' . str_replace('-', '', $this->genMonth) . '-' . str_pad($countBatches, 3, '0', STR_PAD_LEFT);

            $payroll = Payroll::create([
                'marquee_id' => $marqueeId,
                'branch_id' => $this->genBranchId,
                'payroll_month' => $this->genMonth,
                'batch_number' => $batchNumber,
                'total_employees' => $employees->count(),
                'status' => 'draft',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Query attendances for the month
            $attendances = Attendance::where('marquee_id', $marqueeId)
                ->whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->whereIn('employee_id', $employees->pluck('id'))
                ->get()
                ->groupBy('employee_id');

            // Query pending salary advances for this deduction month
            $advances = SalaryAdvance::where('marquee_id', $marqueeId)
                ->where('deduction_month', $this->genMonth)
                ->where('status', 'pending')
                ->whereIn('employee_id', $employees->pluck('id'))
                ->get()
                ->groupBy('employee_id');

            $slipSeq = 1;
            foreach ($employees as $employee) {
                $empAtts = $attendances->get($employee->id, collect());
                
                $presentDays = $empAtts->where('status', 'Present')->count();
                $absentDays = $empAtts->where('status', 'Absent')->count();
                $lateDays = $empAtts->where('status', 'Late')->count();
                $halfDays = $empAtts->where('status', 'Half Day')->count();
                $leaves = $empAtts->where('status', 'Leave')->count();
                $overtimeHours = (float) $empAtts->sum('overtime_hours');

                $basicSalary = (float) $employee->salary;
                $perDayRate = $daysInMonth > 0 ? ($basicSalary / $daysInMonth) : 0;
                $hourlyRate = $perDayRate > 0 ? ($perDayRate / 8) : 0;

                // Absence deduction: full day absent + 0.5 per half day
                $absenceDeduction = round($perDayRate * ($absentDays + ($halfDays * 0.5)), 2);

                // Overtime pay
                $overtimeAmount = round($overtimeHours * $hourlyRate, 2);

                // Pending advance deduction
                $empAdvances = $advances->get($employee->id, collect());
                $advanceDeduction = (float) $empAdvances->sum('amount');

                // Gross & Net
                $grossSalary = $basicSalary + $overtimeAmount;
                $totalDeductions = $absenceDeduction + $advanceDeduction;
                $netSalary = max(0, $grossSalary - $totalDeductions);

                $slipNumber = 'SLIP-' . str_replace('-', '', $this->genMonth) . '-' . str_pad($slipSeq++, 4, '0', STR_PAD_LEFT);

                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'marquee_id' => $marqueeId,
                    'branch_id' => $employee->branch_id,
                    'employee_id' => $employee->id,
                    'salary_slip_number' => $slipNumber,
                    'basic_salary' => $basicSalary,
                    'total_working_days' => $daysInMonth,
                    'present_days' => $presentDays,
                    'absent_days' => $absentDays,
                    'late_days' => $lateDays,
                    'half_days' => $halfDays,
                    'paid_leaves' => $leaves,
                    'overtime_hours' => $overtimeHours,
                    'overtime_rate' => round($hourlyRate, 2),
                    'overtime_amount' => $overtimeAmount,
                    'allowances' => [],
                    'total_allowances' => 0.00,
                    'gross_salary' => $grossSalary,
                    'absence_deduction' => $absenceDeduction,
                    'late_deduction' => 0.00,
                    'advance_deduction' => $advanceDeduction,
                    'other_deductions' => [],
                    'total_other_deductions' => 0.00,
                    'total_deductions' => $totalDeductions,
                    'net_salary' => $netSalary,
                    'paid_amount' => 0.00,
                    'payment_status' => 'unpaid',
                    'payment_method' => 'cash',
                ]);
            }

            $payroll->recalculateTotals();

            DB::commit();

            session()->flash('success', "Payroll batch {$batchNumber} generated successfully for " . Carbon::parse($this->genMonth . '-01')->format('F Y') . " with {$employees->count()} employees.");
            $this->closeGenerateModal();

        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to generate payroll: ' . $e->getMessage());
        }
    }

    public function deleteBatch(int $id): void
    {
        $marqueeId = $this->getMarqueeId();
        $payroll = Payroll::where('marquee_id', $marqueeId)->findOrFail($id);

        if ($payroll->status === 'paid' || $payroll->status === 'partially_paid') {
            session()->flash('error', 'Cannot delete a payroll batch that has already been disbursed/paid.');
            return;
        }

        $payroll->items()->delete();
        $payroll->delete();

        session()->flash('success', 'Payroll batch removed successfully.');
    }

    public function render()
    {
        $marqueeId = $this->getMarqueeId();

        $query = Payroll::where('marquee_id', $marqueeId)
            ->with(['branch', 'creator', 'approver']);

        if ($this->filterYear) {
            $query->where('payroll_month', 'like', $this->filterYear . '-%');
        }

        if ($this->selectedBranchId) {
            $query->where('branch_id', $this->selectedBranchId);
        }

        $payrolls = $query->orderBy('payroll_month', 'desc')->paginate(12);

        // Overall statistics
        $totalPayrollDisbursed = (float) Payroll::where('marquee_id', $marqueeId)
            ->whereIn('status', ['paid', 'partially_paid'])
            ->sum('total_paid');

        $pendingApprovalBatches = Payroll::where('marquee_id', $marqueeId)
            ->where('status', 'draft')
            ->count();

        $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('name')->get();

        return view('livewire.payroll.payroll-batch-manager', [
            'payrolls' => $payrolls,
            'branches' => $branches,
            'totalPayrollDisbursed' => $totalPayrollDisbursed,
            'pendingApprovalBatches' => $pendingApprovalBatches,
        ])->layout('layouts.admin');
    }
}
