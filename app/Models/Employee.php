<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'employee_id',
        'marquee_id',
        'branch_id',
        'name',
        'cnic',
        'mobile_number',
        'designation',
        'joining_date',
        'salary',
        'employment_type',
        'status',
        'photo',
    ];

    /**
     * Available designations for marquee staff.
     */
    public const DESIGNATIONS = [
        'Branch Manager',
        'Booking Officer',
        'Accountant',
        'Cashier',
        'Store Keeper',
        'Kitchen Manager',
        'Chef / Cook',
        'Waiter',
        'Cleaner',
        'Security Guard',
        'Electrician',
        'Decorator',
        'Driver',
        'Helper / Labor',
    ];

    /**
     * Employment types.
     */
    public const EMPLOYMENT_TYPES = [
        'Permanent',
        'Contract',
        'Daily Wages',
        'Part-Time',
    ];

    /**
     * Employment statuses.
     */
    public const STATUSES = [
        'active'     => 'Active',
        'inactive'   => 'Inactive',
        'resigned'   => 'Resigned',
        'terminated' => 'Terminated',
    ];

    /**
     * CMS login roles that can be assigned to staff members.
     */
    public const LOGIN_ROLES = [
        'owner',
        'admin',
        'branch_manager',
        'booking_officer',
        'accountant',
        'store_keeper',
        'kitchen_manager',
        'hr_officer',
    ];

    /**
     * Boot the model and register event listeners.
     */
    protected static function booted(): void
    {
        // Auto-generate employee_id before creating a new record
        static::creating(function (Employee $employee) {
            if (empty($employee->employee_id)) {
                $marqueeId = $employee->marquee_id;

                // Count existing employees (including trashed) for this marquee to get the next serial
                $count = static::withTrashed()
                    ->where('marquee_id', $marqueeId)
                    ->count();

                $employee->employee_id = 'EMP-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
            }
        });

        // Automatically sync name and phone to all linked users on save
        static::saved(function (Employee $employee) {
            foreach ($employee->users as $user) {
                $user->update([
                    'name' => $employee->name,
                    'phone' => $employee->mobile_number,
                ]);
            }
        });
    }

    // ───────────────────── Relationships ─────────────────────

    /**
     * Get the branch this employee works at.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the first CMS user account linked to this employee (for backward compatibility).
     */
    public function user()
    {
        return $this->hasOne(User::class)->latestOfMany();
    }

    /**
     * Get the CMS user accounts linked to this employee.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the daily attendance records for this employee.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
