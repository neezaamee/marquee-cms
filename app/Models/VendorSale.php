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
        'customer_advance_amount',
        'customer_paid_amount',
        'customer_remaining_amount',
        'commission_type',
        'commission_rate',
        'commission_amount',
        'vendor_net_amount',
        'advance_amount',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'include_in_invoice',
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
        'customer_advance_amount' => 'decimal:2',
        'customer_paid_amount' => 'decimal:2',
        'customer_remaining_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'vendor_net_amount' => 'decimal:2',
        'advance_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'include_in_invoice' => 'boolean',
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

    /**
     * Get ledger transactions associated with this sale.
     */
    public function ledgers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VendorLedger::class, 'vendor_sale_id');
    }

    /**
     * Get user who created this vendor sale record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get customer payments recorded specifically for this vendor service.
     */
    public function customerPayments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BookingPayment::class, 'vendor_sale_id')->orderBy('payment_date', 'asc');
    }

    /**
     * Helper: Customer charge amount.
     */
    public function getCustomerChargeAttribute(): float
    {
        return (float) $this->sale_amount;
    }

    /**
     * Helper: Total Customer Advance & Installments Paid.
     */
    public function getCustomerPaidAttribute(): float
    {
        return (float) ($this->customer_paid_amount > 0 ? $this->customer_paid_amount : $this->customer_advance_amount);
    }

    /**
     * Helper: Customer remaining balance to be charged.
     */
    public function getCustomerRemainingAttribute(): float
    {
        $paid = (float) ($this->customer_paid_amount > 0 ? $this->customer_paid_amount : $this->customer_advance_amount);
        return max(0.00, (float) $this->sale_amount - $paid);
    }

    /**
     * Helper: Vendor cost / payable amount.
     */
    public function getVendorCostAttribute(): float
    {
        return (float) $this->vendor_net_amount;
    }

    /**
     * Helper: Calculated vendor remaining amount.
     */
    public function getVendorRemainingAttribute(): float
    {
        return max(0.00, (float) $this->vendor_net_amount - (float) $this->paid_amount);
    }
}
