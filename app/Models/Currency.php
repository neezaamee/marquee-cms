<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'code',
        'name',
        'symbol',
        'is_base',
        'exchange_rate',
        'is_active',
    ];

    protected $casts = [
        'is_base' => 'boolean',
        'exchange_rate' => 'decimal:6',
        'is_active' => 'boolean',
    ];

    /**
     * Scope to only include active currencies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
