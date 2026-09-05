<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'user_id',
        'activity_type', // call, whatsapp, meeting, site_visit, quotation_sent, status_change, note
        'notes',
        'follow_up_date',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
    ];

    /**
     * Parent lead.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Staff user who performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Icon for the activity type.
     */
    public function getActivityIconAttribute(): string
    {
        return match ($this->activity_type) {
            'call' => 'fas fa-phone text-success',
            'whatsapp' => 'fab fa-whatsapp text-success',
            'meeting' => 'fas fa-handshake text-primary',
            'site_visit' => 'fas fa-building text-warning',
            'quotation_sent' => 'fas fa-file-invoice-dollar text-info',
            'status_change' => 'fas fa-exchange-alt text-secondary',
            default => 'fas fa-sticky-note text-muted',
        };
    }

    /**
     * Human-friendly activity type label.
     */
    public function getActivityLabelAttribute(): string
    {
        return match ($this->activity_type) {
            'call' => 'Phone Call',
            'whatsapp' => 'WhatsApp Message',
            'meeting' => 'In-Person Meeting',
            'site_visit' => 'Site Visit / Hall Tour',
            'quotation_sent' => 'Quotation Sent',
            'status_change' => 'Pipeline Status Change',
            default => 'Note / Comment',
        };
    }
}
