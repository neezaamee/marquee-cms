<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    /**
     * Determine whether the user can view any branches.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || 
            $user->isBusinessOwner() || 
            $user->isAreaManager() || 
            $user->hasPermission('manage_settings') || 
            $user->hasPermission('manage_staff');
    }

    /**
     * Determine whether the user can view the specific branch.
     */
    public function view(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isBusinessOwner()) {
            $accessibleIds = $user->getAccessibleMarquees()->pluck('id')->toArray();
            return in_array((int) $branch->marquee_id, array_map('intval', $accessibleIds), true)
                || (int) $branch->marquee_id === (int) $user->getActiveMarqueeId()
                || (int) $branch->marquee_id === (int) $user->marquee_id
                || ($branch->marquee && (int) $branch->marquee->owner_user_id === (int) $user->id);
        }

        if ($user->isAreaManager()) {
            $accessibleIds = $user->getAccessibleMarquees()->pluck('id')->toArray();
            return in_array((int) $branch->marquee_id, array_map('intval', $accessibleIds), true);
        }

        if ($user->branch_id) {
            return (int) $user->branch_id === (int) $branch->id;
        }

        return (int) $branch->marquee_id === (int) $user->getActiveMarqueeId();
    }

    /**
     * Determine whether the user can create branches.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isBusinessOwner() || $user->hasPermission('manage_settings');
    }

    /**
     * Determine whether the user can update the branch.
     */
    public function update(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isBusinessOwner()) {
            $accessibleIds = $user->getAccessibleMarquees()->pluck('id')->toArray();
            return in_array((int) $branch->marquee_id, array_map('intval', $accessibleIds), true)
                || (int) $branch->marquee_id === (int) $user->getActiveMarqueeId()
                || (int) $branch->marquee_id === (int) $user->marquee_id
                || ($branch->marquee && (int) $branch->marquee->owner_user_id === (int) $user->id);
        }

        if ($user->hasRole('branch_manager') && $user->branch_id) {
            return (int) $user->branch_id === (int) $branch->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the branch.
     */
    public function delete(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isBusinessOwner()) {
            $accessibleIds = $user->getAccessibleMarquees()->pluck('id')->toArray();
            return in_array((int) $branch->marquee_id, array_map('intval', $accessibleIds), true)
                || (int) $branch->marquee_id === (int) $user->getActiveMarqueeId()
                || (int) $branch->marquee_id === (int) $user->marquee_id
                || ($branch->marquee && (int) $branch->marquee->owner_user_id === (int) $user->id);
        }

        return false;
    }
}
