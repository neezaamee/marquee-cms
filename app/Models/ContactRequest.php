<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'marquee_id',
        'user_id',
        'name',
        'email',
        'phone',
        'company_name',
        'category',
        'subject',
        'message',
        'preferred_contact_method',
        'status',
        'assigned_to',
        'internal_notes',
        'follow_up_date',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
    ];

    /**
     * Marquee company of contact.
     */
    public function marquee(): BelongsTo
    {
        return $this->belongsTo(Marquee::class);
    }

    /**
     * Registered user if authenticated when submitting.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Assigned staff agent.
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Badge CSS class for status.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'new' => 'badge bg-primary',
            'contacted' => 'badge bg-info text-dark',
            'in_progress' => 'badge bg-warning text-dark',
            'completed' => 'badge bg-success',
            'closed' => 'badge bg-secondary',
            default => 'badge bg-secondary',
        };
    }

    /**
     * Human readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'new' => 'New Inquiry',
            'contacted' => 'Contacted Client',
            'in_progress' => 'In Discussion',
            'completed' => 'Completed / Closed',
            'closed' => 'Closed',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
