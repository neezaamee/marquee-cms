<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'vendor_code',
        'name',
        'vendor_type', // Florist, Sound System, Photography, Videography, Decoration, DJ, Makeup Artist, Event Planner, Transport, Furniture Rental, Generator, Caterer, Security, Other
        'contact_person',
        'phone',
        'alternate_phone',
        'email',
        'address',
        'city',
        'branch_id',
        'tax_ntn',
        'bank_name',
        'account_title',
        'account_number_iban',
        'payment_terms',
        'notes',
        'opening_balance',
        'status', // active, inactive, suspended
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Vendor $vendor) {
            if (empty($vendor->vendor_code)) {
                $lastId = static::withTrashed()->max('id') ?? 0;
                $vendor->vendor_code = 'VEN-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
            }
            if (auth()->check() && empty($vendor->created_by)) {
                $vendor->created_by = auth()->id();
            }
        });

        static::saving(function (Vendor $vendor) {
            if ($vendor->status) {
                $vendor->status = strtolower($vendor->status);
            }
        });
    }

    /**
     * Get the branch associated with this vendor (if branch-specific).
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the bookings/commissions mapped to this vendor.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(VendorBooking::class);
    }

    /**
     * Get the services provided by this vendor.
     */
    public function services(): HasMany
    {
        return $this->hasMany(VendorService::class)->where('status', 'active');
    }

    /**
     * Get all commission agreements for this vendor.
     */
    public function agreements(): HasMany
    {
        return $this->hasMany(VendorCommissionAgreement::class)->orderBy('effective_from', 'desc');
    }

    /**
     * Get current active agreement.
     */
    public function activeAgreement()
    {
        $today = now()->format('Y-m-d');
        return $this->hasOne(VendorCommissionAgreement::class)
            ->where('status', 'active')
            ->where('effective_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $today);
            });
    }

    /**
     * Get all vendor sales transactions.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(VendorSale::class)->orderBy('sale_date', 'desc');
    }

    /**
     * Get all vendor settlements.
     */
    public function settlements(): HasMany
    {
        return $this->hasMany(VendorSettlement::class)->orderBy('settlement_date', 'desc');
    }

    /**
     * Get all vendor ledger entries.
     */
    public function ledgers(): HasMany
    {
        return $this->hasMany(VendorLedger::class)->orderBy('transaction_date', 'desc')->orderBy('id', 'desc');
    }

    /**
     * Accessor: Current running net balance payable to the vendor.
     */
    public function getCurrentBalanceAttribute(): float
    {
        $lastLedger = $this->ledgers()->first();
        return $lastLedger ? (float) $lastLedger->running_balance : (float) $this->opening_balance;
    }

    /**
     * Accessor: Total lifetime sales.
     */
    public function getTotalSalesAttribute(): float
    {
        return (float) $this->sales()->whereIn('status', ['confirmed', 'settled'])->sum('sale_amount');
    }

    /**
     * Accessor: Total lifetime commission generated.
     */
    public function getTotalCommissionAttribute(): float
    {
        return (float) $this->sales()->whereIn('status', ['confirmed', 'settled'])->sum('commission_amount');
    }

    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = \App\Services\PhoneNumberService::normalize($value);
    }

    public function getPhoneAttribute($value)
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
