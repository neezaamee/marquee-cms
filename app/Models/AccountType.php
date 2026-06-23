<?php

namespace App\Models;

use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AccountType extends Model
{
    use HasAuditColumns;

    protected $fillable = [
        'marquee_id',
        'name',
        'code',
        'nature', // Asset, Liability, Equity, Income, Expense
    ];

    /**
     * Scope a query to include both global (null marquee_id) and tenant-specific account types.
     */
    public function scopeForTenant(Builder $query, ?int $marqueeId = null)
    {
        $marqueeId = $marqueeId ?: (Auth::check() ? Auth::user()->marquee_id : null);
        return $query->where(function ($q) use ($marqueeId) {
            $q->whereNull('marquee_id');
            if ($marqueeId) {
                $q->orWhere('marquee_id', $marqueeId);
            }
        });
    }

    /**
     * Get the accounts associated with this account type.
     */
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }
}
