<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PlanFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'feature_name',
        'feature_key',
        'description',
        'status',
    ];

    /**
     * Get the subscription plans that map to this feature.
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'plan_feature_mappings', 'feature_id', 'plan_id')
                    ->withPivot('limit_value')
                    ->withTimestamps();
    }
}
