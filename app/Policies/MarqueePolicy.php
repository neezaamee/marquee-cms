<?php

namespace App\Policies;

use App\Models\Marquee;
use App\Models\User;

class MarqueePolicy
{
    /**
     * Determine whether the user can view any marquees.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isBusinessOwner() || $user->isAreaManager();
    }

    /**
     * Determine whether the user can view the specific marquee.
     */
    public function view(User $user, Marquee $marquee): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isBusinessOwner()) {
            $accessibleIds = $user->getAccessibleMarquees()->pluck('id')->toArray();
            return in_array((int) $marquee->id, array_map('intval', $accessibleIds), true)
                || (int) $marquee->id === (int) $user->getActiveMarqueeId()
                || (int) $marquee->id === (int) $user->marquee_id
                || (int) $marquee->owner_user_id === (int) $user->id;
        }

        if ($user->isAreaManager()) {
            $accessibleIds = $user->getAccessibleMarquees()->pluck('id')->toArray();
            return in_array((int) $marquee->id, array_map('intval', $accessibleIds), true);
        }

        return (int) $marquee->id === (int) $user->getActiveMarqueeId();
    }

    /**
     * Determine whether the user can create marquees.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isBusinessOwner();
    }

    /**
     * Determine whether the user can update the marquee.
     */
    public function update(User $user, Marquee $marquee): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isBusinessOwner()) {
            $accessibleIds = $user->getAccessibleMarquees()->pluck('id')->toArray();
            return in_array((int) $marquee->id, array_map('intval', $accessibleIds), true)
                || (int) $marquee->id === (int) $user->getActiveMarqueeId()
                || (int) $marquee->id === (int) $user->marquee_id
                || (int) $marquee->owner_user_id === (int) $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the marquee.
     */
    public function delete(User $user, Marquee $marquee): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isBusinessOwner()) {
            $accessibleIds = $user->getAccessibleMarquees()->pluck('id')->toArray();
            return in_array((int) $marquee->id, array_map('intval', $accessibleIds), true)
                || (int) $marquee->id === (int) $user->getActiveMarqueeId()
                || (int) $marquee->id === (int) $user->marquee_id
                || (int) $marquee->owner_user_id === (int) $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can switch context to this marquee.
     */
    public function switch(User $user, Marquee $marquee): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $accessibleIds = $user->getAccessibleMarquees()->pluck('id')->toArray();
        return in_array((int) $marquee->id, array_map('intval', $accessibleIds), true);
    }
}
