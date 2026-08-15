<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryUnitConversion extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'inventory_item_id',
        'from_unit_id',
        'to_unit_id',
        'factor',
    ];

    protected $casts = [
        'factor' => 'decimal:4',
    ];

    /**
     * Get the tenant marquee.
     */
    public function marquee(): BelongsTo
    {
        return $this->belongsTo(Marquee::class);
    }

    /**
     * Get the inventory item this conversion is specific to.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * Get the source unit of measure.
     */
    public function fromUnit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'from_unit_id');
    }

    /**
     * Get the target unit of measure.
     */
    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'to_unit_id');
    }
}
