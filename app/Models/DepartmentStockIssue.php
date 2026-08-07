<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentStockIssue extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, BelongsToBranch;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'department_id',
        'department_stock_request_id',
        'issue_number',
        'issue_date',
        'issued_by',
        'received_by',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
    ];

    /**
     * Get the department.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the source stock request if applicable.
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(DepartmentStockRequest::class, 'department_stock_request_id');
    }

    /**
     * Get the user who issued the stock.
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Get the employee who received the stock.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'received_by');
    }

    /**
     * Get the list of issued items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(DepartmentStockIssueItem::class);
    }
}
