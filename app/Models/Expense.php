<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use App\Traits\HasAuditColumns;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, BelongsToBranch, HasAuditColumns, LogsActivity;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'expense_number',
        'expense_date',
        'department',
        'cost_center',
        'expense_category_id',
        'expense_type_id',
        'supplier_id',
        'employee_id',
        'booking_id',
        'purchase_order_id',
        'purchase_invoice_id',
        'currency_id',
        'exchange_rate',
        'description',
        'internal_notes',
        'amount',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'total_amount_base',
        'payment_method',
        'cash_bank_account_id',
        'petty_cash_account_id',
        'payment_status',
        'status',
        'due_date',
        'reference_number',
        'journal_voucher_id',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'due_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_amount_base' => 'decimal:2',
    ];

    // Status Constants
    public const STATUS_DRAFT = 'Draft';
    public const STATUS_SUBMITTED = 'Submitted';
    public const STATUS_PENDING = 'Pending Approval';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_REJECTED = 'Rejected';
    public const STATUS_PAID = 'Paid';
    public const STATUS_POSTED = 'Posted';
    public const STATUS_CANCELLED = 'Cancelled';
    public const STATUS_CLOSED = 'Closed';

    // Payment Methods
    public const METHOD_CASH = 'Cash';
    public const METHOD_BANK = 'Bank';
    public const METHOD_CREDIT = 'Accounts Payable';
    public const METHOD_PETTY_CASH = 'Petty Cash';

    /**
     * Get the category of the expense.
     */
    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * Get the type of the expense.
     */
    public function type()
    {
        return $this->belongsTo(ExpenseType::class, 'expense_type_id');
    }

    /**
     * Get the vendor (supplier).
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get the employee (for salaries, travel reimbursements, etc.).
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Get the booking this expense was created for.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    /**
     * Get the related purchase order.
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    /**
     * Get the related purchase invoice.
     */
    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    /**
     * Get the transactional currency.
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Get the Cash/Bank account used for payment.
     */
    public function cashBankAccount()
    {
        return $this->belongsTo(CashBankAccount::class, 'cash_bank_account_id');
    }

    /**
     * Get the Petty Cash account used.
     */
    public function pettyCashAccount()
    {
        return $this->belongsTo(PettyCashAccount::class, 'petty_cash_account_id');
    }

    /**
     * Get the generated Journal Voucher record.
     */
    public function journalVoucher()
    {
        return $this->belongsTo(JournalVoucher::class, 'journal_voucher_id');
    }

    /**
     * Get the itemized detail lines.
     */
    public function items()
    {
        return $this->hasMany(ExpenseItem::class);
    }

    /**
     * Get the utility bill detail record.
     */
    public function utilityBill()
    {
        return $this->hasOne(ExpenseUtilityBill::class);
    }

    /**
     * Get the maintenance detail record.
     */
    public function maintenanceRecord()
    {
        return $this->hasOne(ExpenseMaintenance::class);
    }

    /**
     * Get the approvals log.
     */
    public function approvals()
    {
        return $this->hasMany(ExpenseApproval::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get all attachments.
     */
    public function attachments()
    {
        return $this->hasMany(ExpenseAttachment::class);
    }
}
