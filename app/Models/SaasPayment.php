<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_reference',
        'invoice_id',
        'user_id',
        'amount',
        'payment_method',
        'transaction_id',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'payment_date' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($payment) {
            if (empty($payment->payment_reference)) {
                $prefix = 'PAY-' . date('Ymd');
                $count = static::where('payment_reference', 'like', $prefix . '-%')->count();
                $payment->payment_reference = $prefix . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Get the invoice this payment belongs to.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SaasInvoice::class, 'invoice_id');
    }

    /**
     * Get the business owner user this payment belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
