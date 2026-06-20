<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingExtraService extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'extra_service_id',
        'service_name',
        'unit_price',
        'quantity',
        'total_price',
    ];

    protected $casts = [
        'unit_price' => 'float',
        'quantity' => 'integer',
        'total_price' => 'float',
    ];

    /**
     * Get the booking.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the source extra service.
     */
    public function extraService(): BelongsTo
    {
        return $this->belongsTo(ExtraService::class, 'extra_service_id');
    }
}
