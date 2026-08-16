<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'system_backup_id',
        'user_id',
        'action',
        'file_name',
        'description',
        'ip_address',
        'user_agent',
        'status',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->created_at ?? now();
        });
    }

    /**
     * Relationship to SystemBackup.
     */
    public function backup()
    {
        return $this->belongsTo(SystemBackup::class, 'system_backup_id');
    }

    /**
     * Relationship to User who performed the action.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
