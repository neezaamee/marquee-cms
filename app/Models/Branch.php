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
    ];

    /**
     * Get the users assigned to this branch.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
