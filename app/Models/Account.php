<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasAuditColumns;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, HasAuditColumns, LogsActivity;

    protected $fillable = [
        'marquee_id',
        'account_code',
        'name',
        'parent_id',
        'account_type_id',
        'nature', // Asset, Liability, Equity, Income, Expense
        'description',
        'is_active',
        'system_generated',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'system_generated' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($account) {
            if ($account->system_generated) {
                throw new \Exception("System-generated accounts cannot be deleted.");
            }
        });
    }

    /**
     * Get the parent account of this account.
     */
    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * Get the child accounts of this account.
     */
    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    /**
     * Get the account type.
     */
    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
    }

    /**
     * Get the opening balances for this account.
     */
    public function openingBalances()
    {
        return $this->hasMany(AccountOpeningBalance::class);
    }

    /**
     * Get the journal voucher items posted to this account.
     */
    public function journalVoucherItems()
    {
        return $this->hasMany(JournalVoucherItem::class);
    }
}
