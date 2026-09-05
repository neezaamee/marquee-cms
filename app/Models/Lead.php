<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\LogsActivity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use BelongsToTenant, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'customer_id',
        'name',
        'phone',
        'alternate_phone',
        'email',
        'city',
        'event_type_id',
        'preferred_date',
        'alternate_date',
        'slot_id',
        'hall_id',
        'guest_count',
        'estimated_budget',
        'lead_source',
        'status',
        'priority',
        'follow_up_date',
        'lost_reason',
        'notes',
        'assigned_to',
        'converted_booking_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'alternate_date' => 'date',
        'follow_up_date' => 'date',
        'guest_count' => 'integer',
        'estimated_budget' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Lead $lead) {
            if (auth()->check() && empty($lead->created_by)) {
                $lead->created_by = auth()->id();
            }
        });

        static::updating(function (Lead $lead) {
            if (auth()->check()) {
                $lead->updated_by = auth()->id();
            }
        });
    }

    /**
     * Branch relationship.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Customer relationship (if existing client).
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Event Type relationship.
     */
    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    /**
     * Hall relationship.
     */
    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    /**
     * Slot relationship.
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }

    /**
     * Assigned staff user.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Converted Booking relationship.
     */
    public function convertedBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'converted_booking_id');
    }

    /**
     * Activities / follow-up history.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->orderBy('created_at', 'desc');
    }

    /**
     * Creator relationship.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for overdue followups.
     */
    public function scopeOverdueFollowups($query)
    {
        return $query->whereNotNull('follow_up_date')
            ->whereDate('follow_up_date', '<', Carbon::today())
            ->whereNotIn('status', ['converted', 'lost']);
    }

    /**
     * Status UI badge CSS classes.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'new' => 'badge-subtle-primary',
            'contacted' => 'badge-subtle-info',
            'site_visit' => 'badge-subtle-warning',
            'negotiation' => 'badge-subtle-secondary',
            'converted' => 'badge-subtle-success',
            'lost' => 'badge-subtle-danger',
            default => 'badge-subtle-light text-dark',
        };
    }

    /**
     * Human-friendly status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'new' => 'New Inquiry',
            'contacted' => 'Contacted / Discussion',
            'site_visit' => 'Site Visit Scheduled',
            'negotiation' => 'Quotation / Negotiation',
            'converted' => 'Converted to Booking',
            'lost' => 'Lost / Dropped',
            default => ucfirst($this->status),
        };
    }

    /**
     * Priority UI badge CSS classes.
     */
    public function getPriorityBadgeClassAttribute(): string
    {
        return match ($this->priority) {
            'hot' => 'badge-subtle-danger',
            'warm' => 'badge-subtle-warning',
            'cold' => 'badge-subtle-info',
            default => 'badge-subtle-secondary',
        };
    }

    /**
     * Source display label.
     */
    public function getSourceLabelAttribute(): string
    {
        return match ($this->lead_source) {
            'walk_in' => 'Walk-In',
            'call' => 'Phone Call',
            'whatsapp' => 'WhatsApp',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'website' => 'Website Inquiry',
            'referral' => 'Referral',
            default => ucfirst($this->lead_source ?? 'Other'),
        };
    }

    /**
     * Check if follow-up is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (! $this->follow_up_date || in_array($this->status, ['converted', 'lost'])) {
            return false;
        }

        return $this->follow_up_date->lt(Carbon::today());
    }

    /**
     * Check if follow-up is due today.
     */
    public function getIsDueTodayAttribute(): bool
    {
        if (! $this->follow_up_date || in_array($this->status, ['converted', 'lost'])) {
            return false;
        }

        return $this->follow_up_date->isToday();
    }
}
