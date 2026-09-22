<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use App\Traits\HasAuditColumns;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentVoucher extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, BelongsToBranch, HasAuditColumns, LogsActivity;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'financial_year_id',
        'voucher_no',
        'voucher_type',
        'voucher_date',
        'payee_type',
        'payee_id',
        'payee_name',
        'payee_cnic',
        'payee_phone',
        'cash_bank_account_id',
        'debit_account_id',
        'amount',
        'amount_in_words',
        'payment_method',
        'cheque_no',
        'cheque_date',
        'reference_no',
        'description',
        'status',
        'journal_voucher_id',
        'supplier_id',
        'vendor_id',
        'expense_id',
        'prepared_by',
        'checked_by',
        'approved_by',
        'disbursed_by',
        'disbursed_at',
        'receiver_signature_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'cheque_date' => 'date',
        'disbursed_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    // Status Constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_POSTED = 'posted';
    public const STATUS_CANCELLED = 'cancelled';

    // Voucher Types
    public const TYPE_CPV = 'CPV'; // Cash Payment Voucher
    public const TYPE_BPV = 'BPV'; // Bank Payment Voucher

    // Payee Types
    public const PAYEE_SUPPLIER = 'supplier';
    public const PAYEE_VENDOR = 'vendor';
    public const PAYEE_EXPENSE = 'expense';
    public const PAYEE_GENERAL = 'general';

    /**
     * Relationships
     */
    public function marquee()
    {
        return $this->belongsTo(Marquee::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function cashBankAccount()
    {
        return $this->belongsTo(CashBankAccount::class, 'cash_bank_account_id');
    }

    public function debitAccount()
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    public function journalVoucher()
    {
        return $this->belongsTo(JournalVoucher::class, 'journal_voucher_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function disbursedBy()
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    /**
     * Status presentation helpers
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'badge-subtle-secondary',
            self::STATUS_APPROVED => 'badge-subtle-warning',
            self::STATUS_POSTED => 'badge-subtle-success',
            self::STATUS_CANCELLED => 'badge-subtle-danger',
            default => 'badge-subtle-secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft (Unposted)',
            self::STATUS_APPROVED => 'Approved (Pending Payment)',
            self::STATUS_POSTED => 'Paid & Posted',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    public function getVoucherTypeFullAttribute(): string
    {
        return $this->voucher_type === self::TYPE_CPV 
            ? 'Cash Payment Voucher (CPV)' 
            : 'Bank Payment Voucher (BPV)';
    }
}
