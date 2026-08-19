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
    ];

    /**
     * Get the Business Owner user accounts that own this Marquee.
     */
    public function owners()
    {
        return $this->belongsToMany(User::class, 'marquee_owners', 'marquee_id', 'user_id')->withTimestamps();
    }

    // Subscriptions have been moved to User model

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
