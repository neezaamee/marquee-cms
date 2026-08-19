<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'customer_code',
        'customer_type',
        'first_name',
        'last_name',
        'company_name',
        'gender',
        'date_of_birth',
        'cnic_national_id',
        'ntn_number',
        'email',
        'phone_number',
        'alternate_phone',
        'address',
        'city',
        'province',
        'postal_code',
        'profile_photo',
        'notes',
        'status',
        'referred_by_type',
        'referred_by_name',
        'referred_by_contact',
        'created_by',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Auto-generate customer_code and set created_by
        static::creating(function (Customer $customer) {
            if (empty($customer->customer_code)) {
                $marqueeId = $customer->marquee_id;

                if (empty($marqueeId) && auth()->check()) {
                    $marqueeId = auth()->user()->marquee_id;
                }

                $count = static::withoutGlobalScope('tenant')->withTrashed()
                    ->where('marquee_id', $marqueeId)
                    ->count();

                $customer->customer_code = 'CUST-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
            }

            if (auth()->check() && empty($customer->created_by)) {
                $customer->created_by = auth()->id();
            }
        });
    }

    /**
     * Get the customer's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    // ───────────────────── CRM Dynamic Accessors ─────────────────────

    public function getTotalBookingsAttribute(): int
    {
        return $this->bookings_count ?? $this->bookings()->count();
    }

    public function getUpcomingEventsAttribute(): int
    {
        return $this->bookings()
            ->whereDate('booking_date', '>=', now()->toDateString())
            ->whereIn('booking_status', ['Reserved', 'Confirmed'])
            ->count();
    }

    public function getCompletedEventsAttribute(): int
    {
        return $this->bookings()
            ->where('booking_status', 'Completed')
            ->count();
    }

    public function getCancelledEventsAttribute(): int
    {
        return $this->bookings()
            ->where('booking_status', 'Cancelled')
            ->count();
    }

    public function getTotalRevenueGeneratedAttribute(): float
    {
        return (float) $this->bookings()
            ->whereNotIn('booking_status', ['Cancelled', 'Rejected'])
            ->sum('grand_total');
    }

    public function getTotalInvoicedAmountAttribute(): float
    {
        return (float) $this->bookings()
            ->whereNotIn('booking_status', ['Cancelled', 'Rejected'])
            ->sum('grand_total');
    }

    public function getTotalPaidAmountAttribute(): float
    {
        $bookingIds = $this->bookings()
            ->whereNotIn('booking_status', ['Cancelled', 'Rejected'])
            ->pluck('id');
            
        return (float) \App\Models\BookingPayment::whereIn('booking_id', $bookingIds)->sum('amount');
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return max(0.00, $this->total_invoiced_amount - $this->total_paid_amount);
    }

    // ───────────────────── Relationships ─────────────────────

    /**
     * Get the user who registered this customer.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the documents uploaded for this customer.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    /**
     * Get the CRM communication logs for this customer.
     */
    public function communicationLogs(): HasMany
    {
        return $this->hasMany(CustomerCommunicationLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Future booking relationship mapping placeholder.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany('App\Models\Booking');
    }

    /**
     * Future invoices relationship mapping placeholder.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany('App\Models\Invoice');
    }

    /**
     * Future payments relationship mapping placeholder.
     */
    public function payments(): HasMany
    {
        return $this->hasMany('App\Models\Payment');
    }
}
