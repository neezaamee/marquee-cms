<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Slot extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'slot_name',
        'start_time',
        'end_time',
        'description',
        'status',
        'created_by',
    ];

    /**
     * Boot model events to set created_by automatically.
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (auth()->check() && !$model->created_by) {
                $model->created_by = auth()->id();
            }
        });
    }

    /**
     * Get the user who created this slot.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the hall assignments for this slot.
     */
    public function hallSlots(): HasMany
    {
        return $this->hasMany(HallSlot::class);
    }

    /**
     * Get the halls assigned to this slot.
     */
    public function halls(): BelongsToMany
    {
        return $this->belongsToMany(Hall::class, 'hall_slots')
                    ->withPivot('id', 'status', 'created_by')
                    ->withTimestamps();
    }
}
