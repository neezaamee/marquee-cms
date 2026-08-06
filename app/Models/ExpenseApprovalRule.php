<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseApprovalRule extends Model
{
    use HasFactory, BelongsToTenant, BelongsToBranch;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'department',
        'category_id',
        'min_amount',
        'max_amount',
        'approver_role_id',
        'sequence',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'sequence' => 'integer',
    ];

    /**
     * Get the role mapped as authorized to approve this rule's stage.
     */
    public function approverRole()
    {
        return $this->belongsTo(Role::class, 'approver_role_id');
    }

    /**
     * Get the category filter if set.
     */
    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }
}
