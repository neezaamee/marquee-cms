<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseMaintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_id',
        'maintenance_type',
        'asset_name',
        'scheduled_date',
        'completion_date',
        'warranty_period_months',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completion_date' => 'date',
        'warranty_period_months' => 'integer',
    ];

    /**
     * Get the parent expense record.
     */
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
