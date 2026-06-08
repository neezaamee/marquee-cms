<?php

namespace App\Services;

use App\Models\Package;
use Illuminate\Support\Facades\DB;

class PackagePricingService
{
    /**
     * Calculate quote details for a package based on headcount.
     *
     * @param int $packageId
     * @param int $guestsCount
     * @return array
     */
    public function calculateQuote(int $packageId, int $guestsCount): array
    {
        $package = Package::with('menuItems')->findOrFail($packageId);

        $basePrice = (float) ($package->base_price ?? 0);
        $perPlatePrice = (float) $package->per_plate_price;

        $totalSellingPrice = $basePrice + ($perPlatePrice * $guestsCount);

        // Sum up base costs of individual menu items inside the package
        $totalBaseCost = 0;
        foreach ($package->menuItems as $item) {
            $quantity = (float) ($item->pivot->quantity ?? 1.00);
            $itemBaseCost = (float) ($item->base_cost ?? 0);
            $totalBaseCost += ($itemBaseCost * $quantity * $guestsCount);
        }

        $estimatedProfit = $totalSellingPrice - $totalBaseCost;
        $profitMarginPercent = $totalSellingPrice > 0 
            ? ($estimatedProfit / $totalSellingPrice) * 100 
            : 0;

        return [
            'package_name' => $package->package_name,
            'guests_count' => $guestsCount,
            'package_base_price' => $basePrice,
            'per_plate_price' => $perPlatePrice,
            'total_selling_price' => $totalSellingPrice,
            'estimated_total_base_cost' => $totalBaseCost,
            'estimated_profit' => $estimatedProfit,
            'profit_margin_percent' => round($profitMarginPercent, 2),
            'seasonal_active' => $package->isSeasonalActive(),
        ];
    }

    /**
     * Clone an existing package and its associated items.
     *
     * @param int $packageId
     * @param string $newName
     * @param string $newCode
     * @return Package
     */
    public function clonePackage(int $packageId, string $newName, string $newCode): Package
    {
        $sourcePackage = Package::findOrFail($packageId);

        return DB::transaction(function () use ($sourcePackage, $newName, $newCode) {
            $newPackage = $sourcePackage->replicate();
            $newPackage->package_name = $newName;
            $newPackage->package_code = $newCode;
            $newPackage->status = 'Draft'; // Set cloned package status to Draft by default
            $newPackage->save();

            // Duplicate pivot relations
            $pivotData = [];
            $sourceItems = DB::table('package_menu_items')
                ->where('package_id', $sourcePackage->id)
                ->get();

            foreach ($sourceItems as $item) {
                $pivotData[$item->menu_item_id] = [
                    'quantity' => $item->quantity,
                    'display_order' => $item->display_order,
                ];
            }

            if (!empty($pivotData)) {
                $newPackage->menuItems()->sync($pivotData);
            }

            return $newPackage;
        });
    }
}
