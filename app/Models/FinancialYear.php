<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasAuditColumns;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialYear extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, HasAuditColumns, LogsActivity;

    protected $fillable = [
        'marquee_id',
        'name',
        'start_date',
        'end_date',
        'status', // active, closed
        'is_default',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_default' => 'boolean',
    ];

    /**
     * Get the opening balances for this financial year.
     */
    public function openingBalances()
    {
        return $this->hasMany(AccountOpeningBalance::class);
    }

    /**
     * Get the journal vouchers for this financial year.
     */
    public function journalVouchers()
    {
        return $this->hasMany(JournalVoucher::class);
    }
}
