<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'monthly_price',
        'quarterly_price',
        'semi_annual_price',
        'annual_price',
        'currency',
        'trial_days',
        'max_storage',
        'sort_order',
        'is_popular',
        'created_by',
        'updated_by',
        'billing_interval',
        'max_marquees',
        'max_branches',
        'max_users',
        'storage_limit_mb',
        'features',
        'trial_period_days',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'monthly_price' => 'float',
            'quarterly_price' => 'float',
            'semi_annual_price' => 'float',
            'annual_price' => 'float',
            'trial_days' => 'integer',
            'max_storage' => 'integer',
            'sort_order' => 'integer',
            'is_popular' => 'boolean',
            'max_marquees' => 'integer',
            'max_branches' => 'integer',
            'max_users' => 'integer',
            'storage_limit_mb' => 'integer',
            'features' => 'array',
            'trial_period_days' => 'integer',
        ];
    }

    /**
     * Get the features mapped to this plan.
     */
    public function planFeatures()
    {
        return $this->belongsToMany(PlanFeature::class, 'plan_feature_mappings', 'plan_id', 'feature_id')
                    ->withPivot('limit_value')
                    ->withTimestamps();
    }

    /**
     * Get the billing cycles mapped to this plan.
     */
    public function billingCycles()
    {
        return $this->belongsToMany(BillingCycle::class, 'plan_billing_cycle', 'plan_id', 'billing_cycle_id')
                    ->withTimestamps();
    }

    /**
     * Get the invoices associated with this plan.
     */
    public function invoices()
    {
        return $this->hasMany(SaasInvoice::class, 'subscription_plan_id');
    }

    /**
     * Get the user who created this plan.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who updated this plan.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the users subscribed to this plan.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}

