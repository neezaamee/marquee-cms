<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentStockRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_stock_request_id',
        'item_id',
        'requested_qty',
        'approved_qty',
        'issued_qty',
    ];

    protected $casts = [
        'requested_qty' => 'float',
        'approved_qty' => 'float',
        'issued_qty' => 'float',
    ];

    /**
     * Get the parent stock request.
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(DepartmentStockRequest::class, 'department_stock_request_id');
    }

    /**
     * Get the inventory item details.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
