<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, BelongsToBranch;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'department_code',
        'name',
        'department_type',
        'manager_id',
        'description',
        'status',
        'display_order',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the manager (head) of this department.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Get the employees currently assigned to this department.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get historical employee assignments.
     */
    public function employeeHistory(): HasMany
    {
        return $this->hasMany(DepartmentEmployee::class);
    }

    /**
     * Get attendance records for this department.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(DepartmentAttendance::class);
    }

    /**
     * Get stock requests submitted by this department.
     */
    public function stockRequests(): HasMany
    {
        return $this->hasMany(DepartmentStockRequest::class);
    }

    /**
     * Get stock issues to this department.
     */
    public function stockIssues(): HasMany
    {
        return $this->hasMany(DepartmentStockIssue::class);
    }

    /**
     * Get stock returns from this department.
     */
    public function stockReturns(): HasMany
    {
        return $this->hasMany(DepartmentStockReturn::class);
    }

    /**
     * Get stock ledger cards for this department.
     */
    public function stockLedgers(): HasMany
    {
        return $this->hasMany(DepartmentStockLedger::class);
    }

    /**
     * Get production batches for this department.
     */
    public function productions(): HasMany
    {
        return $this->hasMany(DepartmentProduction::class);
    }
}
