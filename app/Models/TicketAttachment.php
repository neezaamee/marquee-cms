<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'ticket_message_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    /**
     * Parent support ticket.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id')->withoutGlobalScopes();
    }

    /**
     * Associated message.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class, 'ticket_message_id');
    }

    /**
     * Formatted human readable file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if (!$bytes) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1) . ' ' . $units[$i];
    }

    /**
     * FontAwesome icon based on file extension.
     */
    public function getFileIconAttribute(): string
    {
        $ext = strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'far fa-file-pdf text-danger',
            'doc', 'docx' => 'far fa-file-word text-primary',
            'xls', 'xlsx', 'csv' => 'far fa-file-excel text-success',
            'zip', 'rar', '7z', 'tar', 'gz' => 'far fa-file-archive text-warning',
            'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg' => 'far fa-file-image text-info',
            default => 'far fa-file text-secondary',
        };
    }
}
