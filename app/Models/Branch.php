<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'name',
        'address',
        'city',
        'province',
        'phone',
        'status',
        'fbr_pos_id',
        'fbr_pos_key',
        'fbr_sandbox_mode',
        'is_head_office',
    ];

    protected $casts = [
        'is_head_office' => 'boolean',
    ];

    /**
     * Get the users assigned to this branch.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Boot the model.
     */
    protected static function booted()
    {
        static::saving(function ($branch) {
            if ($branch->is_head_office) {
                // Ensure only one head office exists per marquee
                static::where('marquee_id', $branch->marquee_id)
                    ->where('id', '!=', $branch->id)
                    ->update(['is_head_office' => false]);
            }
        });
    }
}
