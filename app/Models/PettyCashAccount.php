<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use App\Traits\HasAuditColumns;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PettyCashAccount extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, BelongsToBranch, HasAuditColumns, LogsActivity;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'account_name',
        'gl_account_id',
        'custodian_id',
        'limit_amount',
        'current_balance',
        'is_active',
    ];

    protected $casts = [
        'limit_amount' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the general ledger account mapped to this petty cash drawer.
     */
    public function glAccount()
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }

    /**
     * Get the user managing the physical drawer.
     */
    public function custodian()
    {
        return $this->belongsTo(User::class, 'custodian_id');
    }

    /**
     * Get reconciliations logged for this drawer.
     */
    public function reconciliations()
    {
        return $this->hasMany(PettyCashReconciliation::class)->orderBy('reconciliation_date', 'desc');
    }

    /**
     * Scope to active.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
