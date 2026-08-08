<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorSettlement extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'settlement_number',
        'vendor_id',
        'settlement_date',
        'total_sales_amount',
        'total_commission_amount',
        'net_payable_amount',
        'paid_amount',
        'remaining_balance',
        'payment_method', // Cash, Bank Transfer, Cheque
        'reference_number',
        'account_id',
        'journal_voucher_id',
        'status', // pending, partially_settled, fully_settled, disputed, cancelled
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'total_sales_amount' => 'decimal:2',
        'total_commission_amount' => 'decimal:2',
        'net_payable_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (VendorSettlement $settlement) {
            if (empty($settlement->settlement_number)) {
                $lastId = static::withTrashed()->max('id') ?? 0;
                $settlement->settlement_number = 'SET-' . date('Y') . '-' . str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
            }
            if (auth()->check() && empty($settlement->created_by)) {
                $settlement->created_by = auth()->id();
            }
        });
    }

    /**
     * Get vendor.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get bank/cash account used for settlement.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get accounting journal voucher posted for this settlement.
     */
    public function journalVoucher(): BelongsTo
    {
        return $this->belongsTo(JournalVoucher::class);
    }
}
