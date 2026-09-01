<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorLedger extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'vendor_id',
        'vendor_sale_id',
        'settlement_id',
        'booking_id',
        'transaction_date',
        'reference_number',
        'transaction_type', // sale_credit, commission_debit, settlement_payout, opening_balance, adjustment
        'description',
        'sale_amount',
        'commission_amount',
        'payment_amount',
        'running_balance',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'sale_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'running_balance' => 'decimal:2',
    ];

    /**
     * Get vendor.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get sale.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(VendorSale::class, 'vendor_sale_id');
    }

    /**
     * Get settlement.
     */
    public function settlement(): BelongsTo
    {
        return $this->belongsTo(VendorSettlement::class, 'settlement_id');
    }

    /**
     * Get booking.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the user who recorded this ledger transaction.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Alias for creator.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
