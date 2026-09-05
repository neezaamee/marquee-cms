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
        'planned_recipe_qty',
        'recipe_unit_id',
        'planned_stock_qty',
        'stock_unit_id',
        'consumed_qty',
        'wastage_qty',
        'unit_cost',
        'cost_unit_id',
        'total_cost',
    ];

    protected $casts = [
        'quantity' => 'float',
        'planned_recipe_qty' => 'float',
        'planned_stock_qty' => 'float',
        'consumed_qty' => 'float',
        'wastage_qty' => 'float',
        'unit_cost' => 'float',
        'total_cost' => 'float',
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
