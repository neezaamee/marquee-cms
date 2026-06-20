<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BillingCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'cycle_name',
        'duration_in_months',
        'discount_percentage',
        'status',
    ];

    protected $casts = [
        'duration_in_months' => 'integer',
        'discount_percentage' => 'float',
    ];

    /**
     * Get the subscription plans that map to this billing cycle.
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'plan_billing_cycle', 'billing_cycle_id', 'plan_id')
                    ->withTimestamps();
    }
}
