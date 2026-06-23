<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasAuditColumns;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryCategory extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, HasAuditColumns, LogsActivity;

    protected $fillable = [
        'marquee_id',
        'name',
        'parent_id',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * Get parent category.
     */
    public function parent()
    {
        return $this->belongsTo(InventoryCategory::class, 'parent_id');
    }

    /**
     * Get sub-categories.
     */
    public function children()
    {
        return $this->hasMany(InventoryCategory::class, 'parent_id');
    }

    /**
     * Get items under this category.
     */
    public function items()
    {
        return $this->hasMany(InventoryItem::class, 'category_id');
    }
}
