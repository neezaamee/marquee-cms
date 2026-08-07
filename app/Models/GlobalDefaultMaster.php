<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GlobalDefaultMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_type',
        'name',
        'code',
        'description',
        'extra_attributes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'extra_attributes' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope query to active global defaults.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to specific category type.
     */
    public function scopeCategory($query, string $categoryType)
    {
        return $query->where('category_type', $categoryType);
    }

    /**
     * Get the user who created this global master record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
