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
use Illuminate\Support\Facades\DB;

class SupportTicket extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, LogsActivity;

    protected $fillable = [
        'ticket_number',
        'marquee_id',
        'branch_id',
        'user_id',
        'assigned_to',
        'category_id',
        'subject',
        'description',
        'priority',
        'status',
        'related_module',
        'related_record_id',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'sla_due_at',
        'is_sla_breached',
        'satisfaction_rating',
        'satisfaction_comment',
    ];

    protected $casts = [
        'is_sla_breached' => 'boolean',
        'satisfaction_rating' => 'integer',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'sla_due_at' => 'datetime',
    ];

    /**
     * Generate sequential unique ticket number: TKT-YYYY-XXXX
     */
    public static function generateTicketNumber(): string
    {
        $year = Carbon::now()->year;
        $prefix = "TKT-{$year}-";

        // Query maximum existing sequence for this year without tenant scope interference
        $lastTicket = static::withoutGlobalScopes()
            ->where('ticket_number', 'LIKE', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $nextSeq = 1;
        if ($lastTicket && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $lastTicket->ticket_number, $matches)) {
            $nextSeq = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%04d', $prefix, $nextSeq);
    }

    /**
     * Calculate SLA resolution target datetime based on priority.
     */
    public static function calculateSlaTarget(string $priority): ?Carbon
    {
        $slaSettings = SupportSetting::get('sla_targets_hours', [
            'urgent' => ['response' => 1, 'resolution' => 4],
            'high' => ['response' => 2, 'resolution' => 8],
            'normal' => ['response' => 4, 'resolution' => 24],
            'low' => ['response' => 8, 'resolution' => 48],
        ]);

        $hours = $slaSettings[$priority]['resolution'] ?? 24;
        return Carbon::now()->addHours((int) $hours);
    }

    /**
     * The branch associated with this ticket.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * The user who created the ticket.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The support agent assigned to the ticket.
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * The category of the ticket.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(SupportCategory::class, 'category_id');
    }

    /**
     * All messages in this ticket (admin view).
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id')->oldest();
    }

    /**
     * Customer visible messages (strictly excluding private internal notes).
     */
    public function customerVisibleMessages(): HasMany
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id')
            ->where('is_internal_note', false)
            ->oldest();
    }

    /**
     * Attachments linked to this ticket.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_id');
    }

    /**
     * Status audit history.
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(TicketStatusHistory::class, 'ticket_id')->latest();
    }

    /**
     * Helper to transition ticket status and record audit log.
     */
    public function transitionTo(string $newStatus, ?string $notes = null, ?int $userId = null): void
    {
        $oldStatus = $this->status;
        if ($oldStatus === $newStatus) {
            return;
        }

        $now = Carbon::now();
        $updates = ['status' => $newStatus];

        if ($newStatus === 'resolved') {
            $updates['resolved_at'] = $now;
        } elseif ($newStatus === 'closed') {
            $updates['closed_at'] = $now;
            if (!$this->resolved_at) {
                $updates['resolved_at'] = $now;
            }
        } elseif ($newStatus === 'reopened') {
            $updates['closed_at'] = null;
            $updates['resolved_at'] = null;
        }

        $this->update($updates);

        $this->statusHistories()->create([
            'user_id' => $userId ?: auth()->id(),
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'notes' => $notes,
        ]);
    }

    /**
     * Check if SLA is currently breached.
     */
    public function checkSlaBreach(): bool
    {
        if (in_array($this->status, ['resolved', 'closed'])) {
            return $this->is_sla_breached;
        }

        if ($this->sla_due_at && Carbon::now()->isAfter($this->sla_due_at)) {
            if (!$this->is_sla_breached) {
                $this->update(['is_sla_breached' => true]);
            }
            return true;
        }

        return false;
    }

    /**
     * Badge CSS class for status.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'open' => 'badge bg-primary',
            'assigned' => 'badge bg-info text-dark',
            'in_progress' => 'badge bg-warning text-dark',
            'awaiting_customer' => 'badge bg-secondary',
            'awaiting_internal' => 'badge bg-dark',
            'resolved' => 'badge bg-success',
            'closed' => 'badge bg-secondary',
            'reopened' => 'badge bg-danger',
            default => 'badge bg-light text-dark',
        };
    }

    /**
     * Human readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'open' => 'Open',
            'assigned' => 'Assigned',
            'in_progress' => 'In Progress',
            'awaiting_customer' => 'Awaiting Customer',
            'awaiting_internal' => 'Awaiting Staff',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            'reopened' => 'Reopened',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    /**
     * Badge CSS class for priority.
     */
    public function getPriorityBadgeAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'badge bg-danger',
            'high' => 'badge bg-warning text-dark',
            'normal' => 'badge bg-info text-dark',
            'low' => 'badge bg-secondary',
            default => 'badge bg-secondary',
        };
    }
}
