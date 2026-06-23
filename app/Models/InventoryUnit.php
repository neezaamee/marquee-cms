<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasAuditColumns;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryUnit extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, HasAuditColumns, LogsActivity;

    protected $fillable = [
        'marquee_id',
        'name',
        'short_code',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * Get items that use this unit.
     */
    public function items()
    {
        return $this->hasMany(InventoryItem::class, 'unit_id');
    }
}
