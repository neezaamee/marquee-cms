<?php

namespace App\Traits;

use App\Models\Marquee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToTenant(): void
    {
        // Automatically set marquee_id on creating new records
        static::creating(function ($model) {
            if (Auth::check() && ! $model->marquee_id) {
                $user = Auth::user();
                if ($user->marquee_id) {
                    $model->marquee_id = $user->marquee_id;
                }
            }
        });

        // Apply global scope to filter queries by marquee_id
        static::addGlobalScope('tenant', function (Builder $builder) {
            // Prevent infinite recursion when resolving the authenticated user session
            if ($builder->getModel() instanceof \Illuminate\Contracts\Auth\Authenticatable && ! Auth::hasUser()) {
                return;
            }

            if (Auth::check()) {
                $user = Auth::user();
                // If the user belongs to a marquee and is NOT a super admin, filter queries
                if ($user && $user->marquee_id && ! $user->isSuperAdmin()) {
                    $builder->where($builder->getModel()->getTable() . '.marquee_id', $user->marquee_id);
                }
            }
        });
    }

    /**
     * Get the marquee tenant associated with the model.
     */
    public function marquee()
    {
        return $this->belongsTo(Marquee::class);
    }
}
