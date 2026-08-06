<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasAuditColumns;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseCategory extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, HasAuditColumns, LogsActivity;

    protected $fillable = [
        'marquee_id',
        'parent_id',
        'category_code',
        'name',
        'description',
        'default_account_id',
        'default_tax_rate',
        'default_budget_amount',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'default_tax_rate' => 'decimal:2',
        'default_budget_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the parent category.
     */
    public function parent()
    {
        return $this->belongsTo(ExpenseCategory::class, 'parent_id');
    }

    /**
     * Get the subcategories.
     */
    public function children()
    {
        return $this->hasMany(ExpenseCategory::class, 'parent_id')->orderBy('display_order');
    }

    /**
     * Get the default GL Account mapped to this category.
     */
    public function defaultAccount()
    {
        return $this->belongsTo(Account::class, 'default_account_id');
    }

    /**
     * Scope to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
