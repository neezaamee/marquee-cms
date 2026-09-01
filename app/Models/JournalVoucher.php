<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use App\Traits\HasAuditColumns;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalVoucher extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, BelongsToBranch, HasAuditColumns, LogsActivity;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'financial_year_id',
        'voucher_no',
        'voucher_date',
        'reference',
        'notes',
        'status', // draft, posted, cancelled
    ];

    protected $casts = [
        'voucher_date' => 'date',
    ];

    /**
     * Get the double-entry items for this voucher.
     */
    public function items()
    {
        return $this->hasMany(JournalVoucherItem::class);
    }

    /**
     * Get the financial year this voucher belongs to.
     */
    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    /**
     * Get the total debit sum of all double-entry items.
     */
    public function getTotalDebitAttribute(): float
    {
        return (float) $this->items()->sum('debit');
    }

    /**
     * Get the total credit sum of all double-entry items.
     */
    public function getTotalCreditAttribute(): float
    {
        return (float) $this->items()->sum('credit');
    }
}
