<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentProductionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_production_id',
        'item_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    /**
     * Get the parent department production log.
     */
    public function production(): BelongsTo
    {
        return $this->belongsTo(DepartmentProduction::class, 'department_production_id');
    }

    /**
     * Get the inventory item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
