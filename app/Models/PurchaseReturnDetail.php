<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_return_id',
        'item_id',
        'quantity',
        'unit_cost',
        'amount',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_cost' => 'float',
        'amount' => 'float',
    ];

    /**
     * Get the parent purchase return.
     */
    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    /**
     * Get the item catalog.
     */
    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
