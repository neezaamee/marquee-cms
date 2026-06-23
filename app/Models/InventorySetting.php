<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventorySetting extends Model
{
    use HasFactory, BelongsToTenant, HasAuditColumns;

    protected $fillable = [
        'marquee_id',
        'inventory_asset_account_id',
        'accounts_payable_account_id',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the mapped inventory asset account.
     */
    public function inventoryAssetAccount()
    {
        return $this->belongsTo(Account::class, 'inventory_asset_account_id');
    }

    /**
     * Get the mapped accounts payable account.
     */
    public function accountsPayableAccount()
    {
        return $this->belongsTo(Account::class, 'accounts_payable_account_id');
    }
}
