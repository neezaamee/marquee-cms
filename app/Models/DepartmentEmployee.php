<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentEmployee extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'employee_id',
        'designation',
        'reporting_to',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
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

    /**
     * Get the reporting manager for this historical period.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reporting_to');
    }
}
