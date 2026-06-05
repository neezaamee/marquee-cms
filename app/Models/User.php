<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'name',
        'email',
        'password',
        'marquee_id',
        'branch_id',
        'role_id',
        'phone',
        'status',
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
     * Get the role assigned to this user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
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
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->role && $this->role->permissions()->where('name', $permissionName)->exists();
    }
}
