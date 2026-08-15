<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeVersionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_version_id',
        'inventory_item_id',
        'quantity_per_head',
        'recipe_unit_id',
    ];

    protected $casts = [
        'quantity_per_head' => 'decimal:4',
    ];

    /**
     * Get the parent recipe version.
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(RecipeVersion::class, 'recipe_version_id');
    }

    /**
     * Get the inventory item representing the raw material ingredient.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * Get the recipe unit of measure.
     */
    public function recipeUnit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'recipe_unit_id');
    }
}
