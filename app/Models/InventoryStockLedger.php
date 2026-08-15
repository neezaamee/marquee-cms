<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStockLedger extends Model
{
    use HasFactory, BelongsToTenant, BelongsToBranch;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'item_id',
        'transaction_date',
        'transaction_type',
        'reference_type',
        'reference_id',
        'qty_in',
        'qty_out',
        'running_balance',
        'unit_price',
        'total_cost',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'qty_in' => 'float',
        'qty_out' => 'float',
        'running_balance' => 'float',
        'unit_price' => 'float',
        'total_cost' => 'float',
    ];

    /**
     * Get the inventory item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    /**
     * Get the user who logged this transaction.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
