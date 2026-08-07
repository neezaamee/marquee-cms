<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentStockIssueItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_stock_issue_id',
        'item_id',
        'quantity',
        'unit_price',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
    ];

    /**
     * Get the parent stock issue record.
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(DepartmentStockIssue::class, 'department_stock_issue_id');
    }

    /**
     * Get the inventory item details.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
