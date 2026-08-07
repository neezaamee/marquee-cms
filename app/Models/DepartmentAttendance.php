<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentAttendance extends Model
{
    use HasFactory, BelongsToTenant, BelongsToBranch;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'department_id',
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'overtime_minutes',
        'late_minutes',
        'early_departure_minutes',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the department.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the employee.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
