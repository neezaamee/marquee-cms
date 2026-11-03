<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SupportConversation extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'conversation_number',
        'marquee_id',
        'user_id',
        'assigned_to',
        'channel',
        'status',
        'subject',
        'converted_ticket_id',
        'last_message_at',
        'closed_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Generate sequential unique conversation number: CHAT-YYYY-XXXX
     */
    public static function generateConversationNumber(): string
    {
        $year = Carbon::now()->year;
        $prefix = "CHAT-{$year}-";

        $lastChat = static::withoutGlobalScopes()
            ->where('conversation_number', 'LIKE', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $nextSeq = 1;
        if ($lastChat && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $lastChat->conversation_number, $matches)) {
            $nextSeq = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%04d', $prefix, $nextSeq);
    }

    /**
     * Customer user who initiated the chat.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Assigned support specialist.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Ticket if this chat was converted.
     */
    public function convertedTicket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'converted_ticket_id');
    }

    /**
     * Messages in chronological order.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id')->oldest();
    }

    /**
     * Most recent message for inbox listings.
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class, 'conversation_id')->latestOfMany();
    }

    /**
     * Badge CSS class for status.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active' => 'badge bg-success',
            'waiting_customer' => 'badge bg-secondary',
            'waiting_agent' => 'badge bg-warning text-dark',
            'closed' => 'badge bg-light text-secondary border',
            default => 'badge bg-info text-dark',
        };
    }

    /**
     * Human readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Active',
            'waiting_customer' => 'Waiting for Customer',
            'waiting_agent' => 'Waiting for Agent',
            'closed' => 'Closed',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
