<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use App\Traits\HasAuditColumns;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseBudget extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, BelongsToBranch, HasAuditColumns, LogsActivity;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'department',
        'category_id',
        'year',
        'month',
        'allocated_amount',
        'consumed_amount',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'allocated_amount' => 'decimal:2',
        'consumed_amount' => 'decimal:2',
    ];

    /**
     * Get the category mapped.
     */
    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    /**
     * Get the remaining budget value.
     */
    public function getRemainingAmountAttribute()
    {
        return $this->allocated_amount - $this->consumed_amount;
    }
}
