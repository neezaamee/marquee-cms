<?php

namespace App\Traits;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToBranch
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToBranch(): void
    {
        // Automatically set branch_id on creating new records if logged in
        static::creating(function ($model) {
            if (Auth::check() && ! $model->branch_id) {
                $user = Auth::user();
                if ($user->branch_id) {
                    $model->branch_id = $user->branch_id;
                }
            }
        });

        // Apply global scope to filter queries by branch_id
        static::addGlobalScope('branch', function (Builder $builder) {
            if (Auth::check()) {
                $user = Auth::user();
                // If user is restricted to a branch and is not a super admin, apply scope
                if ($user && $user->branch_id && ! $user->isSuperAdmin()) {
                    $builder->where($builder->getModel()->getTable() . '.branch_id', $user->branch_id);
                }
            }
        });
    }

    /**
     * Get the branch associated with the model.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
