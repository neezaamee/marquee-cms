<?php

namespace App\Policies;

use App\Models\User;

class InventoryPolicy
{
    /**
     * Determine if the user can view inventory catalogs.
     */
    public function viewInventory(User $user): bool
    {
        return $user->isSuperAdmin() ||
            $user->hasPermission('view_inventory') ||
            $user->hasPermission('manage_inventory');
    }

    /**
     * Determine if the user can modify inventory catalogs (categories, units, items).
     */
    public function manageInventory(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('manage_inventory');
    }

    /**
     * Determine if the user can view purchase records and supplier ledgers.
     */
    public function viewPurchases(User $user): bool
    {
        return $user->isSuperAdmin() ||
            $user->hasPermission('manage_inventory') ||
            $user->hasPermission('manage_accounting');
    }

    /**
     * Determine if the user can create, update, or cancel purchase documents.
     */
    public function managePurchases(User $user): bool
    {
        return $user->isSuperAdmin() ||
            $user->hasPermission('manage_inventory') ||
            $user->hasPermission('manage_accounting');
    }
}
