<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Marquee;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorSale;
use App\Models\VendorService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorSale>
 */
class VendorSaleFactory extends Factory
{
    protected $model = VendorSale::class;

    public function definition(): array
    {
        $saleAmount = fake()->randomElement([35000, 50000, 65000, 85000]);
        $commissionRate = 20.00;
        $commissionAmount = round(($saleAmount * $commissionRate) / 100, 2);
        $vendorNetAmount = $saleAmount - $commissionAmount;

        return [
            'marquee_id' => Marquee::factory(),
            'branch_id' => Branch::factory(),
            'vendor_sale_number' => 'VS-' . strtoupper(\Illuminate\Support\Str::random(3)) . fake()->numerify('#####'),
            'vendor_id' => Vendor::factory(),
            'vendor_service_id' => VendorService::factory(),
            'booking_id' => Booking::factory(),
            'customer_id' => Customer::factory(),
            'agreement_id' => null,
            'event_date' => fake()->dateTimeBetween('-1 month', '+2 months')->format('Y-m-d'),
            'sale_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'quantity' => 1,
            'unit' => 'job',
            'sale_amount' => $saleAmount,
            'customer_advance_amount' => 0.00,
            'customer_paid_amount' => 0.00,
            'customer_remaining_amount' => $saleAmount,
            'commission_type' => 'percentage',
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'vendor_net_amount' => $vendorNetAmount,
            'advance_amount' => 0.00,
            'paid_amount' => 0.00,
            'remaining_amount' => $vendorNetAmount,
            'payment_status' => 'unpaid',
            'include_in_invoice' => true,
            'status' => 'confirmed',
            'notes' => 'Vendor outsourced service attached to booking',
            'created_by' => User::factory(),
        ];
    }
}
