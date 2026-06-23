<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\BelongsToBranch;
use App\Traits\HasAuditColumns;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturn extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, BelongsToBranch, HasAuditColumns, LogsActivity;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'supplier_id',
        'purchase_invoice_id',
        'return_number',
        'return_date',
        'reason',
        'notes',
        'gross_amount',
        'tax',
        'net_amount',
        'status', // Draft, Approved, Posted, Cancelled
        'journal_voucher_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'gross_amount' => 'float',
        'tax' => 'float',
        'net_amount' => 'float',
    ];

    /**
     * Get the supplier.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get the referenced purchase invoice.
     */
    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    /**
     * Get the ledger journal voucher posted to Chart of Accounts.
     */
    public function journalVoucher()
    {
        return $this->belongsTo(JournalVoucher::class, 'journal_voucher_id');
    }

    /**
     * Get details list of return line items.
     */
    public function details()
    {
        return $this->hasMany(PurchaseReturnDetail::class, 'purchase_return_id');
    }
}
