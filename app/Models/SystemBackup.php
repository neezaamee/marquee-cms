<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemBackup extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'file_name',
        'file_path',
        'type',
        'file_size',
        'status',
        'trigger_type',
        'created_by',
        'notes',
    ];

    /**
     * Get the user who triggered/created this backup.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the audit logs associated with this backup.
     */
    public function activityLogs()
    {
        return $this->hasMany(BackupActivityLog::class, 'system_backup_id');
    }

    /**
     * Human-readable file size attribute.
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 0) {
            return $bytes . ' bytes';
        }

        return '0 KB';
    }
}
