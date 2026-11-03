<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'message',
        'attachment_path',
        'attachment_name',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Parent support conversation.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportConversation::class, 'conversation_id');
    }

    /**
     * Author of this chat message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Determine if sender is internal support staff.
     */
    public function isSenderStaff(): bool
    {
        if (!$this->sender) {
            return false;
        }

        return $this->sender->isSuperAdmin() || in_array($this->sender->role?->name, ['super_admin', 'support_agent', 'admin']);
    }
}
