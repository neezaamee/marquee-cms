<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorService extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'vendor_id',
        'service_name',
        'service_code',
        'description',
        'unit', // Event, Day, Session, Hour
        'default_sale_price',
        'status', // active, inactive
        'created_by',
    ];

    protected $casts = [
        'default_sale_price' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (VendorService $service) {
            if (empty($service->service_code)) {
                $lastId = static::withTrashed()->max('id') ?? 0;
                $service->service_code = 'SRV-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
            }
            if (auth()->check() && empty($service->created_by)) {
                $service->created_by = auth()->id();
            }
        });
    }

    /**
     * Get the vendor that owns this service.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get agreements specific to this service.
     */
    public function agreements(): HasMany
    {
        return $this->hasMany(VendorCommissionAgreement::class, 'vendor_service_id');
    }

    /**
     * Get sales recorded for this service.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(VendorSale::class, 'vendor_service_id');
    }
}
