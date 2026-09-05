<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasAuditColumns;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, HasAuditColumns, LogsActivity;

    protected $fillable = [
        'marquee_id',
        'supplier_code',
        'name',
        'contact_person',
        'mobile_number',
        'whatsapp_number',
        'email',
        'address',
        'city',
        'notes',
        'opening_balance',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'opening_balance' => 'float',
    ];

    /**
     * Get dynamic current balance from ledger.
     */
    public function getCurrentBalanceAttribute(): float
    {
        $lastLedger = $this->ledgers()->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->first();
        return $lastLedger ? (float) $lastLedger->running_balance : (float) $this->opening_balance;
    }

    /**
     * Get supplier ledgers.
     */
    public function ledgers()
    {
        return $this->hasMany(SupplierLedger::class, 'supplier_id');
    }

    /**
     * Get supplier purchase orders.
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id');
    }

    /**
     * Get categories assigned to this supplier.
     */
    public function categories()
    {
        return $this->belongsToMany(
            SupplierCategory::class,
            'supplier_supplier_category',
            'supplier_id',
            'supplier_category_id'
        )->withTimestamps();
    }

    /**
     * Get supplier purchase invoices.
     */
    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class, 'supplier_id');
    }

    public function setMobileNumberAttribute($value)
    {
        $this->attributes['mobile_number'] = \App\Services\PhoneNumberService::normalize($value);
    }

    public function getMobileNumberAttribute($value)
    {
        return \App\Services\PhoneNumberService::formatForDisplay($value);
    }

    public function setWhatsappNumberAttribute($value)
    {
        $this->attributes['whatsapp_number'] = \App\Services\PhoneNumberService::normalize($value);
    }

    public function getWhatsappNumberAttribute($value)
    {
        return \App\Services\PhoneNumberService::formatForDisplay($value);
    }
}
