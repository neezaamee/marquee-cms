<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    use HasFactory, BelongsToTenant, BelongsToBranch;

    protected $fillable = [
        'payroll_id',
        'marquee_id',
        'branch_id',
        'employee_id',
        'salary_slip_number',
        'basic_salary',
        'total_working_days',
        'present_days',
        'absent_days',
        'late_days',
        'half_days',
        'paid_leaves',
        'overtime_hours',
        'overtime_rate',
        'overtime_amount',
        'allowances',
        'total_allowances',
        'gross_salary',
        'absence_deduction',
        'late_deduction',
        'advance_deduction',
        'other_deductions',
        'total_other_deductions',
        'total_deductions',
        'net_salary',
        'paid_amount',
        'payment_status',
        'payment_method',
        'cash_bank_account_id',
        'payment_voucher_id',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'allowances' => 'array',
        'total_allowances' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'absence_deduction' => 'decimal:2',
        'late_deduction' => 'decimal:2',
        'advance_deduction' => 'decimal:2',
        'other_deductions' => 'array',
        'total_other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function cashBankAccount(): BelongsTo
    {
        return $this->belongsTo(CashBankAccount::class, 'cash_bank_account_id');
    }

    public function paymentVoucher(): BelongsTo
    {
        return $this->belongsTo(PaymentVoucher::class, 'payment_voucher_id');
    }

    /**
     * Helper to compute net salary from parts.
     */
    public function computeNet(): void
    {
        $this->gross_salary = $this->basic_salary + $this->overtime_amount + $this->total_allowances;
        $this->total_deductions = $this->absence_deduction + $this->late_deduction + $this->advance_deduction + $this->total_other_deductions;
        $this->net_salary = max(0, $this->gross_salary - $this->total_deductions);
    }
}
