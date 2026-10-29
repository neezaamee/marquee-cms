<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\BelongsToBranch;
use App\Traits\HasAuditColumns;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoice extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, BelongsToBranch, HasAuditColumns, LogsActivity;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'supplier_id',
        'purchase_order_id',
        'goods_receiving_note_id',
        'invoice_number',
        'purchase_date',
        'reference_number',
        'notes',
        'gross_amount',
        'discount',
        'tax',
        'net_amount',
        'status', // Draft, Approved, Posted, Cancelled
        'journal_voucher_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'gross_amount' => 'float',
        'discount' => 'float',
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
     * Get the ledger journal voucher posted to Chart of Accounts.
     */
    public function journalVoucher()
    {
        return $this->belongsTo(JournalVoucher::class, 'journal_voucher_id');
    }

    /**
     * Get the referenced Purchase Order.
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    /**
     * Get the referenced Goods Receiving Note.
     */
    public function goodsReceivingNote()
    {
        return $this->belongsTo(GoodsReceivingNote::class, 'goods_receiving_note_id');
    }

    /**
     * Get details list of invoice line items.
     */
    public function details()
    {
        return $this->hasMany(PurchaseInvoiceDetail::class, 'purchase_invoice_id');
    }

    /**
     * Get purchase returns linked to this invoice.
     */
    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class, 'purchase_invoice_id');
    }

    /**
     * Get posted purchase returns linked to this invoice.
     */
    public function postedReturns()
    {
        return $this->hasMany(PurchaseReturn::class, 'purchase_invoice_id')->where('status', 'Posted');
    }

    /**
     * Get total returned amount across non-cancelled purchase returns.
     */
    public function getReturnedAmountAttribute(): float
    {
        if ($this->relationLoaded('returns')) {
            return (float) $this->returns->where('status', '!=', 'Cancelled')->sum('net_amount');
        }
        return (float) $this->returns()->where('status', '!=', 'Cancelled')->sum('net_amount');
    }

    /**
     * Get net payable balance on this invoice after subtracting returns.
     */
    public function getBalanceAfterReturnsAttribute(): float
    {
        return max(0.0, round((float) $this->net_amount - $this->returned_amount, 2));
    }

    /**
     * Get return status badge text (null, Partially Returned, Fully Returned).
     */
    public function getReturnStatusAttribute(): ?string
    {
        $returned = $this->returned_amount;
        if ($returned <= 0.001) {
            return null;
        }
        if ($returned >= ((float) $this->net_amount - 0.01)) {
            return 'Fully Returned';
        }
        return 'Partially Returned';
    }
}
