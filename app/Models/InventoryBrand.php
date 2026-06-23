<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasAuditColumns;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryBrand extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, HasAuditColumns, LogsActivity;

    protected $fillable = [
        'marquee_id',
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * Get items under this brand.
     */
    public function items()
    {
        return $this->hasMany(InventoryItem::class, 'brand_id');
    }
}
