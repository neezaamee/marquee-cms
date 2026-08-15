<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStockTakeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_stock_take_id',
        'item_id',
        'system_qty',
        'physical_qty',
        'difference',
        'reason',
    ];

    protected $casts = [
        'system_qty' => 'float',
        'physical_qty' => 'float',
        'difference' => 'float',
    ];

    /**
     * Get the parent stock take sheet.
     */
    public function stockTake(): BelongsTo
    {
        return $this->belongsTo(InventoryStockTake::class, 'inventory_stock_take_id');
    }

    /**
     * Get the inventory item details.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
