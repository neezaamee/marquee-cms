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
                $activeId = $user->getActiveMarqueeId();
                if ($activeId) {
                    $model->marquee_id = $activeId;
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
                // Super Admins bypass tenant scope
                if ($user->isSuperAdmin()) {
                    return;
                }

                $activeMarqueeId = $user->getActiveMarqueeId();
                if ($activeMarqueeId) {
                    $builder->where($builder->getModel()->getTable() . '.marquee_id', $activeMarqueeId);
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
