<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorBooking extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'vendor_id',
        'booking_id',
        'agreed_price',
        'commission_rate',
        'commission_amount',
        'payment_status',
    ];

    protected $casts = [
        'agreed_price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Auto-calculate commission amount on saving
        static::saving(function (VendorBooking $vendorBooking) {
            $vendorBooking->commission_amount = ($vendorBooking->agreed_price * $vendorBooking->commission_rate) / 100;
        });
    }

    /**
     * Get the vendor associated with this booking record.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the booking associated with this record.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
