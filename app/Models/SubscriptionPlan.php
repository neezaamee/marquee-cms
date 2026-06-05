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
        'billing_interval',
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
            'max_branches' => 'integer',
            'max_users' => 'integer',
            'storage_limit_mb' => 'integer',
            'features' => 'array',
            'trial_period_days' => 'integer',
        ];
    }

    /**
     * Get the marquees subscribed to this plan.
     */
    public function marquees()
    {
        return $this->hasMany(Marquee::class);
    }
}
