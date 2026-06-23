<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasAuditColumns;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashBankAccount extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, HasAuditColumns, LogsActivity;

    protected $fillable = [
        'marquee_id',
        'account_id',
        'type', // cash, bank
        'bank_name',
        'account_number',
        'iban',
        'branch_name',
        'status', // active, inactive
    ];

    /**
     * Get the associated Chart of Accounts account.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
