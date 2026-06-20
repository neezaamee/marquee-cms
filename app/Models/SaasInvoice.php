<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaasInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'marquee_id',
        'subscription_plan_id',
        'billing_cycle_id',
        'amount',
        'tax',
        'discount',
        'total_amount',
        'payment_status',
        'invoice_status',
        'due_date',
        'paid_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'tax' => 'float',
        'discount' => 'float',
        'total_amount' => 'float',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $prefix = 'INV-' . date('Ymd');
                $count = static::where('invoice_number', 'like', $prefix . '-%')->count();
                $invoice->invoice_number = $prefix . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Get the marquee company this invoice belongs to.
     */
    public function marquee(): BelongsTo
    {
        return $this->belongsTo(Marquee::class);
    }

    /**
     * Get the subscription plan.
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Get the billing cycle.
     */
    public function billingCycle(): BelongsTo
    {
        return $this->belongsTo(BillingCycle::class);
    }

    /**
     * Get the payments associated with this invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SaasPayment::class, 'invoice_id');
    }
}
