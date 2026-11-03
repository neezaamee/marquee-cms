<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportFeedback extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $table = 'support_feedbacks';

    protected $fillable = [
        'marquee_id',
        'user_id',
        'feedback_type',
        'subject',
        'description',
        'rating',
        'module',
        'status',
        'admin_response',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    /**
     * User who submitted feedback.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Administrator who reviewed feedback.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Badge CSS class for status.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'new' => 'badge bg-primary',
            'under_review' => 'badge bg-warning text-dark',
            'planned' => 'badge bg-info text-dark',
            'in_progress' => 'badge bg-secondary',
            'completed' => 'badge bg-success',
            'declined' => 'badge bg-danger',
            'archived' => 'badge bg-light text-secondary border',
            default => 'badge bg-secondary',
        };
    }

    /**
     * Human readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'new' => 'New Submission',
            'under_review' => 'Under Review',
            'planned' => 'Planned on Roadmap',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'declined' => 'Declined',
            'archived' => 'Archived',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    /**
     * Badge CSS class for feedback type.
     */
    public function getTypeBadgeAttribute(): string
    {
        return match ($this->feedback_type) {
            'bug_report' => 'badge bg-danger',
            'feature_request' => 'badge bg-primary',
            'suggestion' => 'badge bg-info text-dark',
            'ux' => 'badge bg-warning text-dark',
            'service_rating' => 'badge bg-success',
            default => 'badge bg-secondary',
        };
    }
}

