<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'booking_number',
        'customer_id',
        'event_type_id',
        'hall_id',
        'slot_id',
        'package_id',
        'booking_date',
        'start_time',
        'end_time',
        'guest_count',
        'per_plate_price',
        'package_amount',
        'hall_charges',
        'extra_charges',
        'discount_amount',
        'security_deposit',
        'tax_amount',
        'subtotal',
        'grand_total',
        'special_instructions',
        'booking_status', // Draft, Reserved, Confirmed, Cancelled, Rejected
        'payment_status', // Unpaid, Partially Paid, Paid, Refunded
        'created_by',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'guest_count' => 'integer',
        'per_plate_price' => 'decimal:2',
        'package_amount' => 'decimal:2',
        'hall_charges' => 'decimal:2',
        'extra_charges' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Booking $model) {
            if (auth()->check() && empty($model->created_by)) {
                $model->created_by = auth()->id();
            }

            // Auto-generate booking_number if empty
            if (empty($model->booking_number)) {
                $marqueeId = $model->marquee_id;
                if (empty($marqueeId) && auth()->check()) {
                    $marqueeId = auth()->user()->marquee_id;
                }

                $year = $model->booking_date ? Carbon::parse($model->booking_date)->year : Carbon::now()->year;

                // Find how many bookings have been created for this tenant in this year
                $count = static::withTrashed()
                    ->where('marquee_id', $marqueeId)
                    ->where('booking_number', 'like', "BK-{$year}-%")
                    ->count();

                $model->booking_number = 'BK-' . $year . '-' . str_pad($count + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Get the customer associated with this booking.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the event type associated with this booking.
     */
    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    /**
     * Get the hall associated with this booking.
     */
    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    /**
     * Get the predefined shift slot associated with this booking.
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }

    /**
     * Get the package associated with this booking.
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the user who created this booking.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the history trail/audit log entries for this booking.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(BookingHistory::class)->orderBy('created_at', 'desc');
    }
}
