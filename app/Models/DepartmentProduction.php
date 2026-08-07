<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentProduction extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, BelongsToBranch;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'department_id',
        'batch_number',
        'production_date',
        'booking_id',
        'recipe_id',
        'produced_qty',
        'wastage_qty',
        'prepared_by',
        'approved_by',
        'production_time',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'production_date' => 'date',
        'produced_qty' => 'float',
        'wastage_qty' => 'float',
    ];

    /**
     * Get the department.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the related booking.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the recipe.
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Get the staff member who prepared the batch.
     */
    public function prepStaff(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'prepared_by');
    }

    /**
     * Get the user who approved this production record.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get raw materials consumed for this production.
     */
    public function items(): HasMany
    {
        return $this->hasMany(DepartmentProductionItem::class);
    }
}
