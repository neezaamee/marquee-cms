<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventType extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'event_type_name',
        'event_type_code',
        'description',
        'default_duration_hours',
        'default_slot_preference',
        'base_price',
        'status',
        'sort_order',
        'is_system_default',
        'created_by',
    ];

    protected $casts = [
        'default_duration_hours' => 'decimal:2',
        'base_price' => 'decimal:2',
        'is_system_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Set created_by automatically
        static::creating(function (EventType $model) {
            if (auth()->check() && empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
        });

        // Prevent deletion of system default event types
        static::deleting(function (EventType $model) {
            if ($model->is_system_default) {
                throw new \Exception("System default event types cannot be deleted.");
            }
        });
    }

    // ───────────────────── Relationships ─────────────────────

    /**
     * Get the branch location associated with this event type.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who registered this event type.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Placeholder relationship for future booking integration.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany('App\Models\Booking');
    }
}
