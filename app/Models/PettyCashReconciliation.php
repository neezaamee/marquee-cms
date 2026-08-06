<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PettyCashReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'petty_cash_account_id',
        'reconciliation_date',
        'system_balance',
        'physical_balance',
        'difference',
        'status', // Balanced, Discrepancy
        'notes',
        'created_by',
    ];

    protected $casts = [
        'reconciliation_date' => 'date',
        'system_balance' => 'decimal:2',
        'physical_balance' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    /**
     * Get the petty cash drawer.
     */
    public function pettyCashAccount()
    {
        return $this->belongsTo(PettyCashAccount::class);
    }

    /**
     * Get the user who executed the audit.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
