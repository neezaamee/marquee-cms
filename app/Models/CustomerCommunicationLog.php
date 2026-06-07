<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCommunicationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'communication_medium',
        'subject',
        'content',
        'status',
        'logged_by',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (CustomerCommunicationLog $log) {
            if (auth()->check() && empty($log->logged_by)) {
                $log->logged_by = auth()->id();
            }
        });
    }

    /**
     * Get the customer this log belongs to.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user who logged this communication.
     */
    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
