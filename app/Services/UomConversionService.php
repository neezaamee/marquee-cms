<?php

namespace App\Services;

use App\Models\InventoryUnitConversion;
use App\Exceptions\UomConversionException;

class UomConversionService
{
    /**
     * Convert a quantity from one unit to another under the given marquee (tenant).
     *
     * Precedence:
     * 1. Direct Item-Specific Conversion
     * 2. Inverse Item-Specific Conversion
     * 3. Direct Tenant-Global Conversion
     * 4. Inverse Tenant-Global Conversion
     * 5. Throw UomConversionException if not found.
     *
     * @param float $qty
     * @param int $fromUnitId
     * @param int $toUnitId
     * @param int $marqueeId
     * @param int|null $itemId
     * @return float
     * @throws UomConversionException
     */
    public function convert(float $qty, int $fromUnitId, int $toUnitId, int $marqueeId, ?int $itemId = null): float
    {
        if ($fromUnitId === $toUnitId) {
            return $qty;
        }

        // 1. Direct Item-Specific Conversion
        if ($itemId) {
            $conversion = InventoryUnitConversion::where('marquee_id', $marqueeId)
                ->where('inventory_item_id', $itemId)
                ->where('from_unit_id', $fromUnitId)
                ->where('to_unit_id', $toUnitId)
                ->first();
            if ($conversion) {
                return $qty * (float)$conversion->factor;
            }

            // 2. Inverse Item-Specific Conversion
            $inverse = InventoryUnitConversion::where('marquee_id', $marqueeId)
                ->where('inventory_item_id', $itemId)
                ->where('from_unit_id', $toUnitId)
                ->where('to_unit_id', $fromUnitId)
                ->first();
            if ($inverse && (float)$inverse->factor > 0) {
                return $qty / (float)$inverse->factor;
            }
        }

        // 3. Direct Tenant-Global Conversion
        $global = InventoryUnitConversion::where('marquee_id', $marqueeId)
            ->whereNull('inventory_item_id')
            ->where('from_unit_id', $fromUnitId)
            ->where('to_unit_id', $toUnitId)
            ->first();
        if ($global) {
            return $qty * (float)$global->factor;
        }

        // 4. Inverse Tenant-Global Conversion
        $globalInverse = InventoryUnitConversion::where('marquee_id', $marqueeId)
            ->whereNull('inventory_item_id')
            ->where('from_unit_id', $toUnitId)
            ->where('to_unit_id', $fromUnitId)
            ->first();
        if ($globalInverse && (float)$globalInverse->factor > 0) {
            return $qty / (float)$globalInverse->factor;
        }

        throw new UomConversionException("UOM Conversion relationship not defined between unit ID {$fromUnitId} and unit ID {$toUnitId} for the given scope.");
    }
}
