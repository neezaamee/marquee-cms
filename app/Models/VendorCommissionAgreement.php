<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorCommissionAgreement extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'vendor_id',
        'vendor_service_id',
        'agreement_number',
        'commission_type', // percentage, fixed_per_event, fixed_monthly, hybrid
        'commission_percentage',
        'fixed_commission_amount',
        'monthly_fixed_amount',
        'minimum_commission',
        'maximum_commission',
        'effective_from',
        'effective_to',
        'settlement_terms',
        'notes',
        'status', // active, expired, draft, terminated
        'created_by',
    ];

    protected $casts = [
        'commission_percentage' => 'decimal:2',
        'fixed_commission_amount' => 'decimal:2',
        'monthly_fixed_amount' => 'decimal:2',
        'minimum_commission' => 'decimal:2',
        'maximum_commission' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (VendorCommissionAgreement $agreement) {
            if (empty($agreement->agreement_number)) {
                $lastId = static::withTrashed()->max('id') ?? 0;
                $agreement->agreement_number = 'AGR-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
            }
            if (auth()->check() && empty($agreement->created_by)) {
                $agreement->created_by = auth()->id();
            }
        });
    }

    /**
     * Get the vendor associated with this agreement.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the specific service for this agreement (optional).
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(VendorService::class, 'vendor_service_id');
    }

    /**
     * Scope query to find agreements active on a specific date.
     */
    public function scopeActiveOn($query, string $date)
    {
        return $query->where('status', 'active')
            ->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
            });
    }
}
