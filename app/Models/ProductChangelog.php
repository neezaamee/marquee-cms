<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductChangelog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'version',
        'title',
        'summary',
        'description',
        'release_date',
        'release_type',
        'affected_modules',
        'is_published',
        'created_by',
    ];

    protected $casts = [
        'release_date' => 'date',
        'is_published' => 'boolean',
        'affected_modules' => 'array',
    ];

    /**
     * Scope only published releases.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * User who authored the release.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Badge CSS class for release type.
     */
    public function getTypeBadgeAttribute(): string
    {
        return match ($this->release_type) {
            'new_feature' => 'badge bg-success',
            'improvement' => 'badge bg-primary',
            'bug_fix' => 'badge bg-warning text-dark',
            'security_update' => 'badge bg-danger',
            'performance' => 'badge bg-info text-dark',
            'maintenance' => 'badge bg-secondary',
            default => 'badge bg-secondary',
        };
    }

    /**
     * Human readable label for release type.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->release_type) {
            'new_feature' => 'New Feature',
            'improvement' => 'Improvement',
            'bug_fix' => 'Bug Fix',
            'security_update' => 'Security Update',
            'performance' => 'Performance',
            'maintenance' => 'Maintenance',
            default => ucfirst(str_replace('_', ' ', $this->release_type)),
        };
    }
}
