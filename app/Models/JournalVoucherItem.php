<?php

namespace App\Models;

use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalVoucherItem extends Model
{
    use HasFactory, HasAuditColumns;

    protected $fillable = [
        'journal_voucher_id',
        'account_id',
        'debit',
        'credit',
        'narration',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    /**
     * Get the parent journal voucher.
     */
    public function journalVoucher()
    {
        return $this->belongsTo(JournalVoucher::class);
    }

    /**
     * Get the general ledger account.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
