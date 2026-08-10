<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'tentative_guests',
        'confirmed_guests',
        'guest_status', // Tentative, Confirmed
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
        'deposit_status', // Held, Refunded, Deducted
        'deposit_refunded_amount',
        'deposit_deducted_amount',
        'deposit_notes',
        'no_food',
        'kitchen_printed_at',
        'kitchen_print_version',
        'kitchen_menu_hash',
        'kitchen_special_instructions',
        'privacy_required',
        'privacy_ladies_percentage',
        'privacy_gents_percentage',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'guest_count' => 'integer',
        'tentative_guests' => 'integer',
        'confirmed_guests' => 'integer',
        'per_plate_price' => 'float',
        'package_amount' => 'float',
        'hall_charges' => 'float',
        'extra_charges' => 'float',
        'discount_amount' => 'float',
        'security_deposit' => 'float',
        'tax_amount' => 'float',
        'subtotal' => 'float',
        'grand_total' => 'float',
        'deposit_refunded_amount' => 'float',
        'deposit_deducted_amount' => 'float',
        'no_food' => 'boolean',
        'kitchen_printed_at' => 'datetime',
        'kitchen_print_version' => 'integer',
        'privacy_required' => 'boolean',
        'privacy_ladies_percentage' => 'integer',
        'privacy_gents_percentage' => 'integer',
    ];

    /**
     * Get the effective guest headcount used for pricing and operations.
     */
    public function getEffectiveGuestCountAttribute(): int
    {
        return $this->confirmed_guests ?? $this->tentative_guests ?? $this->guest_count ?? 0;
    }

    /**
     * Determine if guest headcount is confirmed.
     */
    public function getIsGuestConfirmedAttribute(): bool
    {
        return $this->guest_status === 'Confirmed' || !is_null($this->confirmed_guests);
    }

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

                $prefix = Carbon::now()->format('dmY');

                // Find how many bookings have been created for this tenant with this prefix
                $count = static::withTrashed()
                    ->where('marquee_id', $marqueeId)
                    ->where('booking_number', 'like', "{$prefix}-%")
                    ->count();

                $model->booking_number = $prefix . '-' . str_pad($count + 1, 6, '0', STR_PAD_LEFT);
            }
        });

        static::updated(function ($booking) {
            if ($booking->isDirty('booking_status')) {
                $oldStatus = $booking->getOriginal('booking_status');
                $newStatus = $booking->booking_status;

                $customer = $booking->customer;
                if ($customer) {
                    // Send Email Notification dynamically to customer email
                    if ($customer->email) {
                        \Illuminate\Support\Facades\Notification::route('mail', $customer->email)
                            ->notify(new \App\Notifications\BookingStatusNotification($booking, $oldStatus, $newStatus));
                    }

                    // Log simulated SMS broadcast to customer phone
                    if ($customer->phone_number) {
                        \Illuminate\Support\Facades\Log::info("SMS ALERT Sent to {$customer->phone_number}: Booking #{$booking->booking_number} status updated from {$oldStatus} to {$newStatus}.");
                    }
                }
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

    /**
     * Get the payment ledger transactions for this booking.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(BookingPayment::class)->orderBy('payment_date', 'asc');
    }

    /**
     * Get the customized extra services (add-ons) for this booking.
     */
    public function extraServices(): HasMany
    {
        return $this->hasMany(BookingExtraService::class);
    }

    /**
     * Get the customized menu item selections for this booking.
     */
    public function menuItems(): BelongsToMany
    {
        return $this->belongsToMany(MenuItem::class, 'booking_menu_items')
                    ->withPivot(['custom_note', 'managed_by_host', 'sort_order'])
                    ->withTimestamps()
                    ->orderBy('booking_menu_items.sort_order', 'asc');
    }

    /**
     * Get the allocated halls for this booking.
     */
    public function halls(): BelongsToMany
    {
        return $this->belongsToMany(Hall::class, 'booking_halls')
                    ->withTimestamps();
    }

    /**
     * Get the final bill adjustments record for this booking.
     */
    public function finalBill(): HasOne
    {
        return $this->hasOne(BookingFinalBill::class);
    }

    /**
     * Get the checklists (operational tasks) for this booking.
     */
    public function checklists(): HasMany
    {
        return $this->hasMany(EventChecklist::class);
    }

    /**
     * Scope query for today's events.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('booking_date', Carbon::today());
    }

    /**
     * Scope query for upcoming events.
     */
    public function scopeUpcoming($query)
    {
        return $query->whereDate('booking_date', '>=', Carbon::today())
                     ->whereNotIn('booking_status', ['Cancelled', 'Completed']);
    }

    /**
     * Scope query for events in the next 7 days.
     */
    public function scopeNext7Days($query)
    {
        return $query->whereBetween('booking_date', [Carbon::today(), Carbon::today()->addDays(7)])
                     ->whereNotIn('booking_status', ['Cancelled']);
    }

    /**
     * Scope query for pending approval bookings.
     */
    public function scopePendingApproval($query)
    {
        return $query->whereIn('booking_status', ['Draft', 'Pending']);
    }

    /**
     * Get the vendor commissions booked against this event.
     */
    public function vendorBookings(): HasMany
    {
        return $this->hasMany(VendorBooking::class);
    }

    /**
     * Get the print audit logs for kitchen slips.
     */
    public function kitchenPrintLogs(): HasMany
    {
        return $this->hasMany(KitchenPrintLog::class)->orderBy('printed_at', 'desc');
    }

    /**
     * Compute a hash of booked menu items and effective guest count.
     */
    public function computeMenuHash(): string
    {
        $items = $this->menuItems()
            ->select('menu_items.id')
            ->orderBy('menu_items.id')
            ->get()
            ->pluck('id')
            ->toArray();

        return md5(implode('-', $items) . ':' . $this->effective_guest_count . ':' . ($this->kitchen_special_instructions ?? ''));
    }

    /**
     * Determine if the booking menu was modified after the last kitchen print.
     */
    public function getIsKitchenMenuModifiedAttribute(): bool
    {
        if (empty($this->kitchen_printed_at) || empty($this->kitchen_menu_hash)) {
            return false;
        }

        return $this->computeMenuHash() !== $this->kitchen_menu_hash;
    }
}
