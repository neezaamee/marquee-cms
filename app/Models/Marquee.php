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
     * Get the halls associated with this marquee.
     */
    public function halls()
    {
        return $this->hasMany(Hall::class);
    }

    /**
     * Get the bookings associated with this marquee.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
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

    /**
     * Get the onboarding checklist status for this marquee.
     */
    public function getOnboardingChecklist()
    {
        $marqueeId = $this->id;
        
        $mainBranch = Branch::where('marquee_id', $marqueeId)->where('is_head_office', true)->first();
        $ownerRole = Role::whereIn('name', ['owner', 'business_owner'])->first();
        
        return [
            'marquee_info' => !empty($this->business_type),
            'branch' => $mainBranch !== null,
            'branch_config' => $mainBranch !== null && !empty($mainBranch->phone),
            'halls' => Hall::where('marquee_id', $marqueeId)->exists(),
            'departments' => Department::where('marquee_id', $marqueeId)->exists(),
            'booking_masters' => EventType::where('marquee_id', $marqueeId)->exists() 
                && Slot::where('marquee_id', $marqueeId)->exists(),
            'menu_packages' => MenuCategory::where('marquee_id', $marqueeId)->exists() 
                && Package::where('marquee_id', $marqueeId)->exists(),
            'inventory' => InventoryUnit::where('marquee_id', $marqueeId)->exists() 
                && Supplier::where('marquee_id', $marqueeId)->exists(),
            'finance' => Account::where('marquee_id', $marqueeId)->exists() 
                && PettyCashAccount::where('marquee_id', $marqueeId)->exists(),
        ];
    }

    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = \App\Services\PhoneNumberService::normalize($value);
    }

    public function getPhoneAttribute($value)
    {
        return \App\Services\PhoneNumberService::formatForDisplay($value);
    }
}
