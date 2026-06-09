<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraService extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'service_name',
        'default_price',
        'status',
    ];

    protected $casts = [
        'default_price' => 'float',
    ];

    /**
     * Get the marquee tenant.
     */
    public function marquee(): BelongsTo
    {
        return $this->belongsTo(Marquee::class);
    }
}
