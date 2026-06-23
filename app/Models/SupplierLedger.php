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
}
