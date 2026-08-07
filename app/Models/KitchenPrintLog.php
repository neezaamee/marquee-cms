<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenPrintLog extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'booking_id',
        'marquee_id',
        'printed_by',
        'language',
        'version_number',
        'printed_at',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
        'version_number' => 'integer',
    ];

    /**
     * Get the booking associated with this kitchen print log.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the user who printed this kitchen slip.
     */
    public function printer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }
}
