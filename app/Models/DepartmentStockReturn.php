<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentStockReturn extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, BelongsToBranch;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'department_id',
        'return_number',
        'return_date',
        'returned_by',
        'received_by',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

    /**
     * Get the department.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the employee/user who returned the stock.
     */
    public function returner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'returned_by');
    }

    /**
     * Get the user who received/verified the return in central store.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Get the list of returned items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(DepartmentStockReturnItem::class);
    }
}
