<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, BelongsToTenant, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'marquee_id',
        'branch_id',
        'role_id',
        'employee_id',
        'phone',
        'status',
        'profile_photo',
        'subscription_plan_id',
        'subscription_trial_ends_at',
        'subscription_ends_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'subscription_trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    /**
     * Get the marquee company this user belongs to.
     */
    public function marquee()
    {
        return $this->belongsTo(Marquee::class);
    }

    /**
     * Get the branch location this user belongs to.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the employee record linked to this CMS user account.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get user's name (dynamic fallback to employee name if linked).
     */
    public function getNameAttribute($value)
    {
        return $this->employee ? $this->employee->name : $value;
    }

    /**
     * Get user's phone (dynamic fallback to employee mobile number if linked).
     */
    public function getPhoneAttribute($value)
    {
        return $this->employee ? $this->employee->mobile_number : $value;
    }

    /**
     * Get the role assigned to this user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the marquees owned by this user (Business Owner).
     */
    public function ownedMarquees()
    {
        return $this->belongsToMany(Marquee::class, 'marquee_owners', 'user_id', 'marquee_id')->withTimestamps();
    }

    /**
     * Get the subscription plan assigned to this business owner.
     */
    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Get marquees explicitly assigned to this user if an Area Manager with restricted access.
     */
    public function assignedAreaMarquees()
    {
        return $this->belongsToMany(Marquee::class, 'area_manager_marquees');
    }

    /**
     * Check if the user is a Business Owner.
     */
    public function isBusinessOwner(): bool
    {
        return $this->hasRole(['business_owner', 'owner']);
    }

    /**
     * Check if the user is an Admin / Area Manager / Branches Head.
     */
    public function isAreaManager(): bool
    {
        return $this->hasRole('area_manager');
    }

    /**
     * Get active selected marquee ID for the current session or fallback.
     */
    public function getActiveMarqueeId(): ?int
    {
        if ($this->isSuperAdmin()) {
            return session('active_marquee_id', $this->marquee_id);
        }

        if ($this->isBusinessOwner()) {
            $sessionActive = session('active_marquee_id');
            if ($sessionActive && $this->ownedMarquees()->where('marquees.id', $sessionActive)->exists()) {
                return (int) $sessionActive;
            }
            $firstOwned = $this->ownedMarquees()->first();
            if ($firstOwned) {
                return (int) $firstOwned->id;
            }
            if ($this->marquee_id) {
                return (int) $this->marquee_id;
            }
            $fallback = Marquee::first();
            return $fallback ? (int) $fallback->id : null;
        }

        if ($this->marquee_id) {
            return (int) $this->marquee_id;
        }

        if ($this->branch && $this->branch->marquee_id) {
            return (int) $this->branch->marquee_id;
        }

        return null;
    }

    /**
     * Get accessible marquees for multi-business context switcher and scoping.
     */
    public function getAccessibleMarquees()
    {
        if ($this->isSuperAdmin()) {
            return Marquee::orderBy('name')->get();
        }

        if ($this->isBusinessOwner()) {
            $marquees = $this->ownedMarquees()->orderBy('name')->get();
            if ($marquees->isNotEmpty()) {
                return $marquees;
            }
            if ($this->marquee_id && $this->marquee) {
                return collect([$this->marquee]);
            }
            return Marquee::orderBy('name')->get();
        }

        if ($this->isAreaManager()) {
            // If explicit restrictions exist in pivot table, return assigned marquees
            if ($this->assignedAreaMarquees()->exists()) {
                return $this->assignedAreaMarquees()->orderBy('name')->get();
            }

            // Otherwise default to all marquees of the Business Owner who created/linked them
            if ($this->marquee_id && $this->marquee) {
                $owner = $this->marquee->owners()->first();
                if ($owner) {
                    return $owner->ownedMarquees()->orderBy('name')->get();
                }
            }

            return $this->marquee ? collect([$this->marquee]) : collect();
        }

        return $this->marquee ? collect([$this->marquee]) : collect();
    }

    /**
     * Check if the user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role && $this->role->name === 'super_admin';
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string|array $roleName): bool
    {
        if (is_array($roleName)) {
            return $this->role && in_array($this->role->name, $roleName);
        }

        return $this->role && $this->role->name === $roleName;
    }

    /**
     * Check if the user has a specific permission.
     */
    public function hasPermission(string $permissionName): bool
    {
        if ($this->isSuperAdmin() || $this->isBusinessOwner()) {
            return true;
        }

        return $this->role && $this->role->permissions()->where('name', $permissionName)->exists();
    }
}
