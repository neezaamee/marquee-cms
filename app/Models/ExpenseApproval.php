<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_id',
        'user_id',
        'role_id',
        'action',
        'comments',
    ];

    /**
     * Get the parent expense record.
     */
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Get the user who executed this step.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the role of the approver at the time of approval.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
