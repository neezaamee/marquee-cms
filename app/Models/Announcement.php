<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'message',
        'severity',
        'start_date',
        'end_date',
        'is_published',
        'target_audience',
        'target_ids',
        'requires_acknowledgment',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_published' => 'boolean',
        'requires_acknowledgment' => 'boolean',
        'target_ids' => 'array',
    ];

    /**
     * Scope announcements currently published and within valid active date window.
     */
    public function scopeActive(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->where('is_published', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $now);
            });
    }

    /**
     * Scope announcements targeted to a specific authenticated user.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        // Super admin sees all announcements
        if ($user->isSuperAdmin()) {
            return $query;
        }

        $activeMarqueeId = $user->getActiveMarqueeId();
        $roleName = $user->role?->name;

        return $query->where(function ($q) use ($activeMarqueeId, $roleName) {
            $q->where('target_audience', 'all')
              ->orWhere(function ($sub) use ($activeMarqueeId) {
                  $sub->where('target_audience', 'selected_tenants');
                  if ($activeMarqueeId) {
                      $sub->whereJsonContains('target_ids', (int) $activeMarqueeId)
                          ->orWhereJsonContains('target_ids', (string) $activeMarqueeId);
                  } else {
                      $sub->whereRaw('1 = 0');
                  }
              })
              ->orWhere(function ($sub) use ($roleName) {
                  $sub->where('target_audience', 'selected_roles');
                  if ($roleName) {
                      $sub->whereJsonContains('target_ids', $roleName);
                  } else {
                      $sub->whereRaw('1 = 0');
                  }
              });
        });
    }

    /**
     * User who authored the announcement.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Acknowledgments of this announcement.
     */
    public function acknowledgments(): HasMany
    {
        return $this->hasMany(AnnouncementAcknowledgment::class, 'announcement_id');
    }

    /**
     * Check if a specific user has acknowledged this notice.
     */
    public function isAcknowledgedBy(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        return $this->acknowledgments()->where('user_id', $userId)->exists();
    }

    /**
     * Record user acknowledgment.
     */
    public function acknowledge(int $userId): void
    {
        $this->acknowledgments()->firstOrCreate(
            ['user_id' => $userId],
            ['acknowledged_at' => Carbon::now()]
        );
    }

    /**
     * Alert CSS class for banner styling.
     */
    public function getAlertClassAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'alert alert-danger',
            'warning' => 'alert alert-warning',
            'success' => 'alert alert-success',
            default => 'alert alert-info',
        };
    }

    /**
     * Badge CSS class for severity.
     */
    public function getSeverityBadgeAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'badge bg-danger',
            'warning' => 'badge bg-warning text-dark',
            'success' => 'badge bg-success',
            default => 'badge bg-info text-dark',
        };
    }

    /**
     * FontAwesome icon for severity.
     */
    public function getSeverityIconAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'fas fa-exclamation-triangle',
            'warning' => 'fas fa-exclamation-circle',
            'success' => 'fas fa-check-circle',
            default => 'fas fa-info-circle',
        };
    }
}
