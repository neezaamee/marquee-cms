<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class FeatureRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'marquee_id',
        'user_id',
        'title',
        'description',
        'module',
        'status',
        'admin_notes',
        'public_notes',
        'votes_count',
        'target_release',
        'released_at',
    ];

    protected $casts = [
        'votes_count' => 'integer',
        'released_at' => 'datetime',
    ];

    /**
     * Marquee where this request originated.
     */
    public function marquee(): BelongsTo
    {
        return $this->belongsTo(Marquee::class);
    }

    /**
     * User who created the feature request.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Upvotes received.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(FeatureRequestVote::class, 'feature_request_id');
    }

    /**
     * Check if a specific user has voted for this feature request.
     */
    public function isVotedBy(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        return $this->votes()->where('user_id', $userId)->exists();
    }

    /**
     * Toggle upvote for user with transaction safety.
     * Returns true if now voted, false if unvoted.
     */
    public function toggleVote(int $userId, ?int $marqueeId = null): bool
    {
        return DB::transaction(function () use ($userId, $marqueeId) {
            $existing = $this->votes()->where('user_id', $userId)->first();

            if ($existing) {
                $existing->delete();
                $this->decrement('votes_count');
                return false;
            } else {
                $this->votes()->create([
                    'user_id' => $userId,
                    'marquee_id' => $marqueeId,
                ]);
                $this->increment('votes_count');
                return true;
            }
        });
    }

    /**
     * Badge CSS class for status.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'submitted' => 'badge bg-secondary',
            'under_review' => 'badge bg-warning text-dark',
            'planned' => 'badge bg-primary',
            'in_development' => 'badge bg-info text-dark',
            'released' => 'badge bg-success',
            'declined' => 'badge bg-danger',
            'duplicate' => 'badge bg-dark',
            default => 'badge bg-secondary',
        };
    }

    /**
     * Human readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'submitted' => 'Under Community Review',
            'under_review' => 'In Product Review',
            'planned' => 'Planned for Release',
            'in_development' => 'In Active Development',
            'released' => 'Shipped / Released',
            'declined' => 'Declined',
            'duplicate' => 'Duplicate',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
