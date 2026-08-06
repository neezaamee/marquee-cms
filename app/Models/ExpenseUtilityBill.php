<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseUtilityBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_id',
        'utility_type',
        'consumer_number',
        'account_number',
        'billing_period',
        'previous_reading',
        'current_reading',
        'late_charges',
    ];

    protected $casts = [
        'previous_reading' => 'decimal:2',
        'current_reading' => 'decimal:2',
        'late_charges' => 'decimal:2',
    ];

    /**
     * Get the parent expense record.
     */
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
