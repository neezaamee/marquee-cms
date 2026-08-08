<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorSale extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'vendor_sale_number',
        'vendor_id',
        'vendor_service_id',
        'booking_id',
        'customer_id',
        'agreement_id',
        'event_date',
        'sale_date',
        'quantity',
        'unit',
        'sale_amount',
        'commission_type',
        'commission_rate',
        'commission_amount',
        'vendor_net_amount',
        'status', // draft, confirmed, settled, cancelled
        'override_reason',
        'override_by',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'sale_date' => 'date',
        'quantity' => 'decimal:2',
        'sale_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'vendor_net_amount' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (VendorSale $sale) {
            if (empty($sale->vendor_sale_number)) {
                $lastId = static::withTrashed()->max('id') ?? 0;
                $sale->vendor_sale_number = 'VS-' . date('Y') . '-' . str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
            }
            if (auth()->check() && empty($sale->created_by)) {
                $sale->created_by = auth()->id();
            }
        });
    }

    /**
     * Get the vendor associated with this sale.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the service sold.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(VendorService::class, 'vendor_service_id');
    }

    /**
     * Get the booking associated with this sale.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the customer associated with this sale.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the agreement snapshot.
     */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(VendorCommissionAgreement::class, 'agreement_id');
    }

    /**
     * Get the user who authorized a commission override.
     */
    public function overrideBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'override_by');
    }

    /**
     * Get branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
