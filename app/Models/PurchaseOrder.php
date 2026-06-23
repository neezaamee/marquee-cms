<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\BelongsToBranch;
use App\Traits\HasAuditColumns;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, BelongsToBranch, HasAuditColumns, LogsActivity;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'po_number',
        'supplier_id',
        'order_date',
        'expected_delivery_date',
        'notes',
        'status', // Draft, Approved, Partially Received, Completed, Cancelled
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
    ];

    /**
     * Get the supplier.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get the PO line details.
     */
    public function details()
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'purchase_order_id');
    }

    /**
     * Get GRNs associated with this PO.
     */
    public function goodsReceivingNotes()
    {
        return $this->hasMany(GoodsReceivingNote::class, 'purchase_order_id');
    }

    /**
     * Helper to compute total amount.
     */
    public function getTotalAmountAttribute(): float
    {
        return (float) $this->details()->sum('amount');
    }
}
