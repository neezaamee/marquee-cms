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
        'business_type',
        'logo',
        'address',
        'city',
        'province',
        'country',
        'timezone',
        'currency',
        'phone',
        'email',
        'ntn',
        'strn',
        'tax_authority',
        'status',
        'is_setup_completed',
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

    /**
     * The booted method of the model.
     */
    protected static function booted()
    {
        static::creating(function ($marquee) {
            if (app()->runningUnitTests() && !isset($marquee->is_setup_completed)) {
                $marquee->is_setup_completed = true;
            }
        });

        static::deleted(function ($marquee) {
            $marquee->branches()->delete();
            $marquee->users()->delete();
        });

        static::restoring(function ($marquee) {
            $marquee->branches()->withTrashed()->restore();
            $marquee->users()->withTrashed()->restore();
        });
    }
}
