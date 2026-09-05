<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, LogsActivity;

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
                    $marqueeId = auth()->user()->getActiveMarqueeId();
                }

                $existingCodes = static::withoutGlobalScopes()
                    ->withTrashed()
                    ->where('marquee_id', $marqueeId)
                    ->where('customer_code', 'like', 'CUST-%')
                    ->pluck('customer_code');

                $maxSeq = 0;
                foreach ($existingCodes as $code) {
                    $parts = explode('-', $code);
                    $seq = (int) end($parts);
                    if ($seq > $maxSeq) {
                        $maxSeq = $seq;
                    }
                }

                $nextSeq = $maxSeq + 1;
                do {
                    $candidateCode = 'CUST-' . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);
                    $exists = static::withoutGlobalScopes()
                        ->withTrashed()
                        ->where('marquee_id', $marqueeId)
                        ->where('customer_code', $candidateCode)
                        ->exists();
                    if ($exists) {
                        $nextSeq++;
                    }
                } while ($exists);

                $customer->customer_code = $candidateCode;
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
        $recognized = (float) $this->bookings()
            ->where('is_revenue_recognized', true)
            ->sum('revenue_recognized');

        if ($recognized > 0 || $this->bookings()->where('is_revenue_recognized', true)->exists()) {
            return $recognized;
        }

        return (float) $this->bookings()
            ->whereNotIn('booking_status', ['Cancelled', 'Rejected'])
            ->sum('grand_total');
    }

    public function getTotalAdvanceLiabilityAttribute(): float
    {
        return (float) $this->bookings()
            ->where('is_revenue_recognized', false)
            ->whereNotIn('booking_status', ['Cancelled', 'Rejected'])
            ->sum('advance_received');
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
            
        $received = (float) \App\Models\BookingPayment::whereIn('booking_id', $bookingIds)
            ->where('status', 'posted')
            ->whereIn('payment_type', ['advance', 'receivable_payment', 'security_deposit'])
            ->sum('amount');

        $refunded = (float) \App\Models\BookingPayment::whereIn('booking_id', $bookingIds)
            ->where('status', 'posted')
            ->where('payment_type', 'refund')
            ->sum('amount');

        return max(0.00, $received - $refunded);
    }

    public function getOutstandingBalanceAttribute(): float
    {
        if ($this->bookings()->where('is_revenue_recognized', true)->exists()) {
            return (float) $this->bookings()
                ->where('is_revenue_recognized', true)
                ->sum('receivable_amount');
        }

        return max(0.00, $this->total_invoiced_amount - $this->total_paid_amount);
    }

    // ───────────────────── Relationships ─────────────────────

    /**
     * Get the customer ledger transactions.
     */
    public function ledgers(): HasMany
    {
        return $this->hasMany(CustomerLedger::class)->orderBy('transaction_date', 'asc')->orderBy('id', 'asc');
    }

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
     * Get the bookings for this customer.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
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

    public function setPhoneNumberAttribute($value)
    {
        $this->attributes['phone_number'] = \App\Services\PhoneNumberService::normalize($value);
    }

    public function getPhoneNumberAttribute($value)
    {
        return \App\Services\PhoneNumberService::formatForDisplay($value);
    }

    public function setAlternatePhoneAttribute($value)
    {
        $this->attributes['alternate_phone'] = \App\Services\PhoneNumberService::normalize($value);
    }

    public function getAlternatePhoneAttribute($value)
    {
        return \App\Services\PhoneNumberService::formatForDisplay($value);
    }
}
