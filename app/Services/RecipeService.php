<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Recipe;

class RecipeService
{
    /**
     * Calculate required raw ingredients for a booking based on guest count.
     *
     * @param Booking $booking
     * @return array
     */
    public function calculateRequiredIngredients(Booking $booking): array
    {
        $guestCount = $booking->guest_count ?? 0;
        if ($guestCount <= 0) {
            return [];
        }

        // Get menu items: check customized booking items first, fallback to package items
        $menuItems = $booking->menuItems()->with('recipe.details.inventoryItem.unit')->get();
        if ($menuItems->isEmpty() && $booking->package_id) {
            $package = $booking->package()->with('menuItems.recipe.details.inventoryItem.unit')->first();
            $menuItems = $package ? $package->menuItems : collect();
        }

        $requirements = [];

        foreach ($menuItems as $item) {
            $recipe = $item->recipe;
            if (!$recipe) {
                continue;
            }

            foreach ($recipe->details as $detail) {
                $invItem = $detail->inventoryItem;
                if (!$invItem) {
                    continue;
                }

                $itemId = $invItem->id;
                $quantityNeeded = $detail->quantity_per_head * $guestCount;

                if (isset($requirements[$itemId])) {
                    $requirements[$itemId]['required_qty'] += $quantityNeeded;
                } else {
                    $requirements[$itemId] = [
                        'item_id' => $itemId,
                        'name' => $invItem->name,
                        'required_qty' => $quantityNeeded,
                        'unit' => $invItem->unit ? $invItem->unit->short_code : 'Pcs',
                    ];
                }
            }
        }

        return array_values($requirements);
    }

    /**
     * Get raw ingredients list for a specific recipe (for production form auto-fill).
     *
     * @param int $recipeId
     * @return array  [['item_id' => x, 'quantity_per_head' => y, 'name' => z, 'unit' => u], ...]
     */
    public function getIngredients(int $recipeId): array
    {
        $recipe = Recipe::with('details.inventoryItem.unit')->find($recipeId);

        if (!$recipe) {
            return [];
        }

        $ingredients = [];
        foreach ($recipe->details as $detail) {
            $invItem = $detail->inventoryItem;
            if (!$invItem) {
                continue;
            }

            $ingredients[] = [
                'item_id' => $invItem->id,
                'quantity_per_head' => (float) $detail->quantity_per_head,
                'name' => $invItem->name,
                'unit' => $invItem->unit ? $invItem->unit->short_code : 'Pcs',
            ];
        }

        return $ingredients;
    }
}
