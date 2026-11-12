<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, BelongsToBranch;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'payroll_month',
        'batch_number',
        'total_employees',
        'total_basic_salary',
        'total_allowances',
        'total_overtime_amount',
        'total_gross_salary',
        'total_advance_deductions',
        'total_absence_deductions',
        'total_other_deductions',
        'total_net_payable',
        'total_paid',
        'status',
        'approved_by',
        'approved_at',
        'payment_date',
        'payment_method',
        'cash_bank_account_id',
        'payment_voucher_id',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'approved_at' => 'datetime',
        'total_basic_salary' => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'total_overtime_amount' => 'decimal:2',
        'total_gross_salary' => 'decimal:2',
        'total_advance_deductions' => 'decimal:2',
        'total_absence_deductions' => 'decimal:2',
        'total_other_deductions' => 'decimal:2',
        'total_net_payable' => 'decimal:2',
        'total_paid' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getTotalGrossAttribute(): float
    {
        return (float) ($this->total_gross_salary ?? 0);
    }

    public function getTotalNetAttribute(): float
    {
        return (float) ($this->total_net_payable ?? 0);
    }

    public function getTotalDeductionsAttribute(): float
    {
        return (float) (($this->total_advance_deductions ?? 0) + ($this->total_absence_deductions ?? 0) + ($this->total_other_deductions ?? 0));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
     * Recalculate totals from items.
     */
    public function recalculateTotals(): void
    {
        $this->total_employees = $this->items()->count();
        $this->total_basic_salary = (float) $this->items()->sum('basic_salary');
        $this->total_allowances = (float) $this->items()->sum('total_allowances');
        $this->total_overtime_amount = (float) $this->items()->sum('overtime_amount');
        $this->total_gross_salary = (float) $this->items()->sum('gross_salary');
        $this->total_advance_deductions = (float) $this->items()->sum('advance_deduction');
        $this->total_absence_deductions = (float) $this->items()->sum('absence_deduction');
        $this->total_other_deductions = (float) $this->items()->sum('total_other_deductions');
        $this->total_net_payable = (float) $this->items()->sum('net_salary');
        $this->total_paid = (float) $this->items()->sum('paid_amount');
        $this->save();
    }
}
