<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine whether the user can view any bookings.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('view_bookings');
    }

    /**
     * Determine whether the user can view the specific booking.
     */
    public function view(User $user, Booking $booking): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->hasPermission('view_bookings')) {
            return false;
        }

        // Business Owner can access bookings for any owned marquee
        if ($user->isBusinessOwner()) {
            $accessibleIds = $user->getAccessibleMarquees()->pluck('id')->toArray();
            return in_array((int) $booking->marquee_id, array_map('intval', $accessibleIds), true)
                || (int) $booking->marquee_id === (int) $user->getActiveMarqueeId()
                || (int) $booking->marquee_id === (int) $user->marquee_id
                || ($booking->marquee && (int) $booking->marquee->owner_user_id === (int) $user->id);
        }

        // Area Manager can view bookings for assigned marquees
        if ($user->isAreaManager()) {
            $accessibleIds = $user->getAccessibleMarquees()->pluck('id')->toArray();
            return in_array((int) $booking->marquee_id, array_map('intval', $accessibleIds), true);
        }

        // Branch-scoped roles (Branch Manager, Accountant, Booking Officer)
        if ($user->branch_id) {
            return (int) $user->branch_id === (int) $booking->branch_id;
        }

        // Fallback to active marquee match
        return (int) $booking->marquee_id === (int) $user->getActiveMarqueeId();
    }

    /**
     * Determine whether the user can create bookings.
     */
    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->hasPermission('create_bookings')) {
            return false;
        }

        if ($user->isAreaManager() || $user->hasRole('accountant')) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can update the booking.
     */
    public function update(User $user, Booking $booking): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->hasPermission('edit_bookings')) {
            return false;
        }

        if ($user->isBusinessOwner()) {
            $accessibleIds = $user->getAccessibleMarquees()->pluck('id')->toArray();
            return in_array((int) $booking->marquee_id, array_map('intval', $accessibleIds), true)
                || (int) $booking->marquee_id === (int) $user->getActiveMarqueeId()
                || (int) $booking->marquee_id === (int) $user->marquee_id
                || ($booking->marquee && (int) $booking->marquee->owner_user_id === (int) $user->id);
        }

        if ($user->isAreaManager() || $user->hasRole('accountant')) {
            return false;
        }

        if ($user->branch_id) {
            return (int) $user->branch_id === (int) $booking->branch_id;
        }

        return (int) $booking->marquee_id === (int) $user->getActiveMarqueeId();
    }

    /**
     * Determine whether the user can cancel/delete the booking.
     */
    public function delete(User $user, Booking $booking): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->hasPermission('delete_bookings') && !$user->hasPermission('cancel_bookings')) {
            return false;
        }

        if ($user->isBusinessOwner()) {
            $accessibleIds = $user->getAccessibleMarquees()->pluck('id')->toArray();
            return in_array((int) $booking->marquee_id, array_map('intval', $accessibleIds), true)
                || (int) $booking->marquee_id === (int) $user->getActiveMarqueeId()
                || (int) $booking->marquee_id === (int) $user->marquee_id
                || ($booking->marquee && (int) $booking->marquee->owner_user_id === (int) $user->id);
        }

        if ($user->hasRole('branch_manager') && $user->branch_id) {
            return (int) $user->branch_id === (int) $booking->branch_id;
        }

        return false;
    }
}
