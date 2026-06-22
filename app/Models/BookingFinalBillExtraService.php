<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingFinalBillExtraService extends Model
{
    use HasFactory;

    protected $fillable = [
        'final_bill_id',
        'service_name',
        'unit_price',
        'quantity',
        'total_price',
    ];

    protected $casts = [
        'unit_price' => 'float',
        'quantity' => 'integer',
        'total_price' => 'float',
    ];

    /**
     * Get the final bill that owns this extra service.
     */
    public function finalBill(): BelongsTo
    {
        return $this->belongsTo(BookingFinalBill::class, 'final_bill_id');
    }
}
