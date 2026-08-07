<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentStockReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_stock_return_id',
        'item_id',
        'quantity',
        'unit_price',
        'status',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
    ];

    /**
     * Get the parent stock return.
     */
    public function stockReturn(): BelongsTo
    {
        return $this->belongsTo(DepartmentStockReturn::class, 'department_stock_return_id');
    }

    /**
     * Get the inventory item details.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
