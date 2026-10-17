<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierLedger extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, HasAuditColumns;

    protected $fillable = [
        'marquee_id',
        'supplier_id',
        'transaction_date',
        'reference_type',
        'reference_id',
        'voucher_no',
        'supplier_invoice_no',
        'debit',
        'credit',
        'running_balance',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'debit' => 'float',
        'credit' => 'float',
        'running_balance' => 'float',
    ];

    /**
     * Get the supplier.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get polymorphic reference model.
     */
    public function reference()
    {
        return $this->morphTo(null, 'reference_type', 'reference_id');
    }

    /**
     * Get linked purchase invoice if applicable.
     */
    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'reference_id');
    }

    /**
     * Get the resolved supplier invoice number.
     */
    public function getResolvedSupplierInvoiceNumberAttribute(): ?string
    {
        if (!empty($this->supplier_invoice_no)) {
            return $this->supplier_invoice_no;
        }

        if ($this->reference_type === 'PurchaseInvoice') {
            $inv = $this->purchaseInvoice;
            if ($inv) {
                return !empty($inv->reference_number) ? $inv->reference_number : $inv->invoice_number;
            }
        }

        return null;
    }
}
