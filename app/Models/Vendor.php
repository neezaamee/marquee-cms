<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'name',
        'vendor_type',
        'contact_person',
        'phone',
        'email',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the bookings managed by this vendor.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(VendorBooking::class);
    }
}
