<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Marquee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'logo',
        'address',
        'city',
        'province',
        'phone',
        'email',
        'ntn',
        'strn',
        'tax_authority',
        'status',
        'subscription_plan_id',
        'subscription_trial_ends_at',
        'subscription_ends_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'subscription_trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    /**
     * Get the subscription plan this marquee is subscribed to.
     */
    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Get the branches associated with this marquee.
     */
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Get the users associated with this marquee.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
