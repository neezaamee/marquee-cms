<?php

namespace App\Services;

class BookingPricingService
{
    /**
     * Calculate financial summary based on input values.
     *
     * @param array $data
     * @return array
     */
    public static function calculate(array $data): array
    {
        $guestCount = intval($data['guest_count'] ?? 0);
        $perPlatePrice = floatval($data['per_plate_price'] ?? 0);
        $hallCharges = floatval($data['hall_charges'] ?? 0);
        $extraCharges = floatval($data['extra_charges'] ?? 0);
        $discountAmount = floatval($data['discount_amount'] ?? 0);
        $securityDeposit = floatval($data['security_deposit'] ?? 0);
        
        // Tax rate defaults to 13% (standard service sales tax)
        $taxRate = floatval($data['tax_rate'] ?? 13.00);

        // Subtotals calculation
        $packageAmount = $guestCount * $perPlatePrice;
        $subtotal = $packageAmount + $hallCharges + $extraCharges - $discountAmount;
        
        // Calculate tax based on subtotal (before security deposit, as security deposit is refundable/non-revenue)
        $taxAmount = ($subtotal * $taxRate) / 100;
        
        // Grand total includes security deposit and tax
        $grandTotal = $subtotal + $taxAmount + $securityDeposit;

        return [
            'package_amount' => round($packageAmount, 2),
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'grand_total' => round($grandTotal, 2),
        ];
    }
}
