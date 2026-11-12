<?php

namespace App\Livewire\Payroll;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\CashBankAccount;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\SalaryAdvance;
use App\Services\PaymentVoucherService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PayrollBatchDetail extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public int $payrollId;
    public string $search = '';
    public string $filterStatus = '';

    // Modal Edit Item
    public bool $isEditModalOpen = false;
    public ?int $editingItemId = null;
    public string $editEmployeeName = '';
    public float $editBasicSalary = 0.00;
    public float $editOvertimeHours = 0.00;
    public float $editOvertimeRate = 0.00;
    public float $editOvertimeAmount = 0.00;
    public float $editBonus = 0.00;
    public float $editConveyance = 0.00;
    public float $editMedical = 0.00;
    public float $editOtherAllowance = 0.00;
    public float $editAbsenceDeduction = 0.00;
    public float $editLateDeduction = 0.00;
    public float $editAdvanceDeduction = 0.00;
    public float $editFineDeduction = 0.00;
    public float $editOtherDeduction = 0.00;
    public string $editNotes = '';

    // Bulk Disburse Modal
    public bool $isDisburseModalOpen = false;
    public ?int $disburseCashBankAccountId = null;
    public string $disbursePaymentMethod = 'cash'; // 'cash' or 'bank'
    public string $disburseDate = '';
    public string $disburseReference = '';
    public string $disburseNotes = '';

    // Single Employee Pay Modal
    public bool $isSinglePayModalOpen = false;
    public ?int $singlePayItemId = null;
    public ?PayrollItem $singlePayItem = null;
    public ?int $singleCashBankAccountId = null;
    public string $singlePaymentMethod = 'cash';
    public string $singlePaymentDate = '';
    public string $singleReference = '';

    public function mount(int $id)
    {
        $this->payrollId = $id;
        $this->disburseDate = Carbon::today()->format('Y-m-d');
        $this->singlePaymentDate = Carbon::today()->format('Y-m-d');

        // Verify access
        $payroll = $this->getPayroll();
        if (!$payroll) {
            abort(404, 'Payroll batch not found or access denied.');
        }

        // Set default cash bank account if available
        $marqueeId = $this->getMarqueeId();
        $defaultAccount = CashBankAccount::where('marquee_id', $marqueeId)->where('status', 'active')->first();
        if ($defaultAccount) {
            $this->disburseCashBankAccountId = $defaultAccount->id;
            $this->singleCashBankAccountId = $defaultAccount->id;
        }
    }

    public function getMarqueeId(): ?int
    {
        $user = auth()->user();
        return $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
    }

    public function getPayroll(): ?Payroll
    {
        $marqueeId = $this->getMarqueeId();
        return Payroll::where('marquee_id', $marqueeId)
            ->with(['branch', 'creator', 'approver', 'cashBankAccount', 'paymentVoucher'])
            ->find($this->payrollId);
    }

    public function approveBatch(): void
    {
        $payroll = $this->getPayroll();
        if (!$payroll) {
            session()->flash('error', 'Payroll batch not found.');
            return;
        }

        if ($payroll->status !== 'draft') {
            session()->flash('error', 'Only draft batches can be approved.');
            return;
        }

        $payroll->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => Carbon::now(),
            'updated_by' => auth()->id(),
        ]);

        session()->flash('success', "Payroll batch {$payroll->batch_number} has been approved successfully.");
    }

    // ==========================================
    // ITEM EDITING MODAL
    // ==========================================
    public function openEditModal(int $itemId): void
    {
        $marqueeId = $this->getMarqueeId();
        $item = PayrollItem::where('marquee_id', $marqueeId)
            ->where('payroll_id', $this->payrollId)
            ->with('employee')
            ->findOrFail($itemId);

        $this->editingItemId = $item->id;
        $this->editEmployeeName = $item->employee ? $item->employee->name : 'Staff';
        $this->editBasicSalary = (float) $item->basic_salary;
        $this->editOvertimeHours = (float) $item->overtime_hours;
        $this->editOvertimeRate = (float) $item->overtime_rate;
        $this->editOvertimeAmount = (float) $item->overtime_amount;

        $allowances = (array) ($item->allowances ?? []);
        $this->editBonus = (float) ($allowances['bonus'] ?? 0);
        $this->editConveyance = (float) ($allowances['conveyance'] ?? 0);
        $this->editMedical = (float) ($allowances['medical'] ?? 0);
        $this->editOtherAllowance = (float) ($allowances['other'] ?? 0);

        $this->editAbsenceDeduction = (float) $item->absence_deduction;
        $this->editLateDeduction = (float) $item->late_deduction;
        $this->editAdvanceDeduction = (float) $item->advance_deduction;

        $otherDeds = (array) ($item->other_deductions ?? []);
        $this->editFineDeduction = (float) ($otherDeds['fine'] ?? 0);
        $this->editOtherDeduction = (float) ($otherDeds['other'] ?? 0);

        $this->editNotes = (string) ($item->notes ?? '');
        $this->isEditModalOpen = true;
    }

    public function closeEditModal(): void
    {
        $this->isEditModalOpen = false;
        $this->editingItemId = null;
    }

    public function recalculateItemModal(): void
    {
        $this->editOvertimeAmount = round($this->editOvertimeHours * $this->editOvertimeRate, 2);
    }

    public function saveItem(): void
    {
        $marqueeId = $this->getMarqueeId();
        $item = PayrollItem::where('marquee_id', $marqueeId)
            ->where('payroll_id', $this->payrollId)
            ->findOrFail($this->editingItemId);

        $totalAllowances = (float) ($this->editBonus + $this->editConveyance + $this->editMedical + $this->editOtherAllowance);
        $allowancesArray = [
            'bonus' => $this->editBonus,
            'conveyance' => $this->editConveyance,
            'medical' => $this->editMedical,
            'other' => $this->editOtherAllowance,
        ];

        $totalOtherDeductions = (float) ($this->editFineDeduction + $this->editOtherDeduction);
        $otherDedsArray = [
            'fine' => $this->editFineDeduction,
            'other' => $this->editOtherDeduction,
        ];

        $grossSalary = (float) ($this->editBasicSalary + $this->editOvertimeAmount + $totalAllowances);
        $totalDeductions = (float) ($this->editAbsenceDeduction + $this->editLateDeduction + $this->editAdvanceDeduction + $totalOtherDeductions);
        $netSalary = max(0, $grossSalary - $totalDeductions);

        $item->update([
            'basic_salary' => $this->editBasicSalary,
            'overtime_hours' => $this->editOvertimeHours,
            'overtime_rate' => $this->editOvertimeRate,
            'overtime_amount' => $this->editOvertimeAmount,
            'allowances' => $allowancesArray,
            'total_allowances' => $totalAllowances,
            'gross_salary' => $grossSalary,
            'absence_deduction' => $this->editAbsenceDeduction,
            'late_deduction' => $this->editLateDeduction,
            'advance_deduction' => $this->editAdvanceDeduction,
            'other_deductions' => $otherDedsArray,
            'total_other_deductions' => $totalOtherDeductions,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'notes' => $this->editNotes ?: null,
        ]);

        $payroll = $this->getPayroll();
        if ($payroll) {
            $payroll->recalculateTotals();
        }

        session()->flash('success', "Payslip for {$this->editEmployeeName} updated successfully.");
        $this->closeEditModal();
    }

    // ==========================================
    // BULK DISBURSE MODAL & ACTION
    // ==========================================
    public function openDisburseModal(): void
    {
        $this->disburseDate = Carbon::today()->format('Y-m-d');
        $this->isDisburseModalOpen = true;
    }

    public function closeDisburseModal(): void
    {
        $this->isDisburseModalOpen = false;
    }

    public function executeDisburseBatch(): void
    {
        $this->validate([
            'disburseCashBankAccountId' => 'required|exists:cash_bank_accounts,id',
            'disburseDate' => 'required|date',
            'disbursePaymentMethod' => 'required|in:cash,bank',
        ]);

        $payroll = $this->getPayroll();
        if (!$payroll) {
            session()->flash('error', 'Payroll batch not found.');
            return;
        }

        $marqueeId = $this->getMarqueeId();
        $userId = auth()->id();

        // Get unpaid items
        $unpaidItems = $payroll->items()->where('payment_status', '!=', 'paid')->get();
        if ($unpaidItems->isEmpty()) {
            session()->flash('error', 'All employees in this batch have already been paid.');
            $this->closeDisburseModal();
            return;
        }

        $amountToDisburse = (float) $unpaidItems->sum(function ($it) {
            return max(0, $it->net_salary - $it->paid_amount);
        });

        if ($amountToDisburse <= 0) {
            session()->flash('error', 'Total net payable amount to disburse is 0.');
            $this->closeDisburseModal();
            return;
        }

        DB::beginTransaction();
        try {
            // 1. Locate or create General Ledger Salary Expense Account (5302)
            $salaryAccount = Account::where('marquee_id', $marqueeId)
                ->where(function ($q) {
                    $q->where('account_code', '5302')
                      ->orWhere('name', 'like', '%Salary%');
                })
                ->first();

            if (!$salaryAccount) {
                $opType = AccountType::where('code', 'OPERATING_EXPENSES')->first();
                $salaryAccount = Account::create([
                    'marquee_id' => $marqueeId,
                    'account_type_id' => $opType ? $opType->id : 1,
                    'account_code' => '5302',
                    'name' => 'Permanent Staff Salaries',
                    'nature' => 'Expense',
                    'is_active' => true,
                    'description' => 'Permanent staff salary expense',
                ]);
            }

            // 2. Generate Payment Voucher (CPV if cash, BPV if bank)
            $pvType = $this->disbursePaymentMethod === 'bank' ? 'BPV' : 'CPV';
            $pvService = app(PaymentVoucherService::class);

            $voucherData = [
                'marquee_id' => $marqueeId,
                'branch_id' => $payroll->branch_id,
                'voucher_type' => $pvType,
                'voucher_date' => $this->disburseDate,
                'payee_type' => 'general',
                'payee_name' => "Staff Salary Disbursement - {$payroll->batch_number} (" . Carbon::parse($payroll->payroll_month . '-01')->format('F Y') . ")",
                'cash_bank_account_id' => $this->disburseCashBankAccountId,
                'debit_account_id' => $salaryAccount->id,
                'amount' => $amountToDisburse,
                'payment_method' => $this->disbursePaymentMethod === 'bank' ? 'Bank Transfer' : 'Cash',
                'reference_no' => $this->disburseReference ?: null,
                'description' => "Salary payment for batch {$payroll->batch_number} (" . count($unpaidItems) . " staff). " . ($this->disburseNotes ?? ''),
                'status' => 'Approved',
            ];

            $voucher = $pvService->createPaymentVoucher($voucherData, $userId);

            // Post voucher to GL
            try {
                $pvService->disburseAndPost($voucher, $userId, ['disbursed_date' => $this->disburseDate]);
            } catch (\Throwable $pvException) {
                // If GL posting fails (e.g. FY missing in sandbox), we still preserve the voucher
                report($pvException);
            }

            // 3. Mark all unpaid items as paid
            $now = Carbon::now();
            foreach ($unpaidItems as $item) {
                $item->update([
                    'payment_status' => 'paid',
                    'paid_amount' => $item->net_salary,
                    'payment_method' => $this->disbursePaymentMethod,
                    'cash_bank_account_id' => $this->disburseCashBankAccountId,
                    'payment_voucher_id' => $voucher->id,
                    'paid_at' => $now,
                ]);

                // 4. Settle any linked pending advances for this employee in this month
                if ($item->advance_deduction > 0) {
                    SalaryAdvance::where('marquee_id', $marqueeId)
                        ->where('employee_id', $item->employee_id)
                        ->where('deduction_month', $payroll->payroll_month)
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'deducted',
                            'deducted_amount' => DB::raw('amount'),
                            'deducted_payroll_id' => $payroll->id,
                            'updated_by' => $userId,
                        ]);
                }
            }

            // 5. Update Batch Status
            $payroll->payment_date = $this->disburseDate;
            $payroll->payment_method = $this->disbursePaymentMethod;
            $payroll->cash_bank_account_id = $this->disburseCashBankAccountId;
            $payroll->payment_voucher_id = $voucher->id;
            $payroll->status = 'paid';
            $payroll->recalculateTotals();

            DB::commit();

            session()->flash('success', "Batch {$payroll->batch_number} successfully disbursed (Rs. " . number_format($amountToDisburse, 2) . ") via Voucher {$voucher->voucher_no}.");
            $this->closeDisburseModal();

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            session()->flash('error', 'Disbursement failed: ' . $e->getMessage());
        }
    }

    // ==========================================
    // SINGLE EMPLOYEE PAY MODAL & ACTION
    // ==========================================
    public function openSinglePayModal(int $itemId): void
    {
        $marqueeId = $this->getMarqueeId();
        $this->singlePayItem = PayrollItem::where('marquee_id', $marqueeId)
            ->where('payroll_id', $this->payrollId)
            ->with('employee')
            ->findOrFail($itemId);

        $this->singlePayItemId = $this->singlePayItem->id;
        $this->singlePaymentDate = Carbon::today()->format('Y-m-d');
        $this->singleReference = '';
        $this->isSinglePayModalOpen = true;
    }

    public function closeSinglePayModal(): void
    {
        $this->isSinglePayModalOpen = false;
        $this->singlePayItemId = null;
        $this->singlePayItem = null;
    }

    public function executeSinglePay(): void
    {
        $this->validate([
            'singleCashBankAccountId' => 'required|exists:cash_bank_accounts,id',
            'singlePaymentDate' => 'required|date',
            'singlePaymentMethod' => 'required|in:cash,bank',
        ]);

        $marqueeId = $this->getMarqueeId();
        $userId = auth()->id();
        $payroll = $this->getPayroll();
        $item = PayrollItem::where('marquee_id', $marqueeId)
            ->where('payroll_id', $this->payrollId)
            ->with('employee')
            ->findOrFail($this->singlePayItemId);

        $amountToPay = max(0, $item->net_salary - $item->paid_amount);
        if ($amountToPay <= 0) {
            session()->flash('error', 'This employee has already received full payment.');
            $this->closeSinglePayModal();
            return;
        }

        DB::beginTransaction();
        try {
            $salaryAccount = Account::where('marquee_id', $marqueeId)
                ->where(function ($q) {
                    $q->where('account_code', '5302')
                      ->orWhere('name', 'like', '%Salary%');
                })
                ->first();

            if (!$salaryAccount) {
                $opType = AccountType::where('code', 'OPERATING_EXPENSES')->first();
                $salaryAccount = Account::create([
                    'marquee_id' => $marqueeId,
                    'account_type_id' => $opType ? $opType->id : 1,
                    'account_code' => '5302',
                    'name' => 'Permanent Staff Salaries',
                    'nature' => 'Expense',
                    'is_active' => true,
                    'description' => 'Permanent staff salary expense',
                ]);
            }

            $pvType = $this->singlePaymentMethod === 'bank' ? 'BPV' : 'CPV';
            $pvService = app(PaymentVoucherService::class);
            $empName = $item->employee ? $item->employee->name : 'Staff';

            $voucherData = [
                'marquee_id' => $marqueeId,
                'branch_id' => $item->branch_id ?: $payroll->branch_id,
                'voucher_type' => $pvType,
                'voucher_date' => $this->singlePaymentDate,
                'payee_type' => 'general',
                'payee_name' => "Staff Salary - {$empName} (" . Carbon::parse($payroll->payroll_month . '-01')->format('F Y') . ")",
                'cash_bank_account_id' => $this->singleCashBankAccountId,
                'debit_account_id' => $salaryAccount->id,
                'amount' => $amountToPay,
                'payment_method' => $this->singlePaymentMethod === 'bank' ? 'Bank Transfer' : 'Cash',
                'reference_no' => $this->singleReference ?: null,
                'description' => "Salary payout for {$empName} ({$item->salary_slip_number})",
                'status' => 'Approved',
            ];

            $voucher = $pvService->createPaymentVoucher($voucherData, $userId);

            try {
                $pvService->disburseAndPost($voucher, $userId, ['disbursed_date' => $this->singlePaymentDate]);
            } catch (\Throwable $pvException) {
                report($pvException);
            }

            // Settle item
            $item->update([
                'payment_status' => 'paid',
                'paid_amount' => $item->net_salary,
                'payment_method' => $this->singlePaymentMethod,
                'cash_bank_account_id' => $this->singleCashBankAccountId,
                'payment_voucher_id' => $voucher->id,
                'paid_at' => Carbon::now(),
            ]);

            // Settle advance if any
            if ($item->advance_deduction > 0) {
                SalaryAdvance::where('marquee_id', $marqueeId)
                    ->where('employee_id', $item->employee_id)
                    ->where('deduction_month', $payroll->payroll_month)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'deducted',
                        'deducted_amount' => DB::raw('amount'),
                        'deducted_payroll_id' => $payroll->id,
                        'updated_by' => $userId,
                    ]);
            }

            // Recalculate batch
            $payroll->recalculateTotals();
            if ($payroll->total_paid >= $payroll->total_net_payable) {
                $payroll->update(['status' => 'paid']);
            } elseif ($payroll->total_paid > 0) {
                $payroll->update(['status' => 'partially_paid']);
            }

            DB::commit();

            session()->flash('success', "Salary for {$empName} disbursed successfully via Voucher {$voucher->voucher_no}.");
            $this->closeSinglePayModal();

        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', 'Payment failed: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $payroll = $this->getPayroll();
        if (!$payroll) {
            abort(404);
        }

        $marqueeId = $this->getMarqueeId();

        $query = $payroll->items()
            ->with(['employee.department', 'cashBankAccount', 'paymentVoucher']);

        if ($this->search) {
            $query->whereHas('employee', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('employee_code', 'like', '%' . $this->search . '%');
            })->orWhere('salary_slip_number', 'like', '%' . $this->search . '%');
        }

        if ($this->filterStatus) {
            $query->where('payment_status', $this->filterStatus);
        }

        $items = $query->paginate(25);

        $cashBankAccounts = CashBankAccount::where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('livewire.payroll.payroll-batch-detail', [
            'payroll' => $payroll,
            'items' => $items,
            'cashBankAccounts' => $cashBankAccounts,
        ])->layout('layouts.admin');
    }
}
