<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasAuditColumns;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, HasAuditColumns, LogsActivity;

    protected $fillable = [
        'marquee_id',
        'item_code',
        'name',
        'category_id',
        'unit_id',
        'purchase_unit_id',
        'brand_id',
        'description',
        'minimum_stock_level',
        'reorder_level',
        'default_purchase_rate',
        'average_cost',
        'last_purchase_cost',
        'conversion_factor',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'minimum_stock_level' => 'float',
        'reorder_level' => 'float',
        'default_purchase_rate' => 'float',
        'average_cost' => 'float',
        'last_purchase_cost' => 'float',
        'conversion_factor' => 'float',
    ];

    /**
     * Get item's category.
     */
    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    /**
     * Get item's unit of measure.
     */
    public function unit()
    {
        return $this->belongsTo(InventoryUnit::class, 'unit_id');
    }

    /**
     * Get item's brand.
     */
    public function brand()
    {
        return $this->belongsTo(InventoryBrand::class, 'brand_id');
    }
}
