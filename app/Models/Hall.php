<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hall extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'hall_name',
        'hall_code',
        'capacity',
        'hall_type',
        'default_booking_price',
        'description',
        'status',
        'created_by',
    ];

    /**
     * Boot model events to set created_by automatically.
     */
    protected static function booted()
    {
        // Fallback: BelongsToTenant trait handles marquee_id booting. We append created_by here.
        static::creating(function ($model) {
            if (auth()->check() && !$model->created_by) {
                $model->created_by = auth()->id();
            }
        });
    }

    /**
     * Get the branch that owns the hall.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created this hall.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the slot assignments for this hall.
     */
    public function hallSlots(): HasMany
    {
        return $this->hasMany(HallSlot::class);
    }

    /**
     * Get the slots assigned to this hall.
     */
    public function slots(): BelongsToMany
    {
        return $this->belongsToMany(Slot::class, 'hall_slots')
                    ->withPivot('id', 'status', 'created_by')
                    ->withTimestamps();
    }
}
