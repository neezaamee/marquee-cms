<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingFinalBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'guest_count',
        'per_plate_price',
        'package_amount',
        'hall_charges',
        'extra_charges',
        'discount_amount',
        'tax_amount',
        'subtotal',
        'grand_total',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'guest_count' => 'integer',
        'per_plate_price' => 'float',
        'package_amount' => 'float',
        'hall_charges' => 'float',
        'extra_charges' => 'float',
        'discount_amount' => 'float',
        'tax_amount' => 'float',
        'subtotal' => 'float',
        'grand_total' => 'float',
    ];

    /**
     * Get the booking associated with this final bill.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the final bill's extra services.
     */
    public function extraServices(): HasMany
    {
        return $this->hasMany(BookingFinalBillExtraService::class, 'final_bill_id');
    }

    /**
     * Get the user who recorded this final bill.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
