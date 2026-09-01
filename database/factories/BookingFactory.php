<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Slot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $guestCount = fake()->randomElement([200, 250, 300, 350, 400, 500, 600, 800]);
        $perPlate = fake()->randomElement([1400, 1600, 1850, 2200, 2500, 3000]);
        $packageAmount = $guestCount * $perPlate;
        $hallCharges = fake()->randomElement([40000, 50000, 75000, 100000]);
        $extraCharges = fake()->randomElement([0, 15000, 25000, 50000]);
        $discount = fake()->randomElement([0, 10000, 20000]);
        $securityDeposit = fake()->randomElement([0, 25000, 50000]);

        $subtotal = $packageAmount + $hallCharges + $extraCharges - $discount;
        $taxAmount = round(($subtotal * 16.00) / 100, 2);
        $grandTotal = $subtotal + $taxAmount;

        $bookingDate = fake()->dateTimeBetween('-1 month', '+3 months')->format('Y-m-d');

        return [
            'marquee_id' => Marquee::factory(),
            'branch_id' => Branch::factory(),
            'customer_id' => Customer::factory(),
            'booking_number' => 'BK-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(3)) . fake()->numerify('####'),
            'hall_id' => Hall::factory(),
            'slot_id' => Slot::factory(),
            'package_id' => null,
            'event_type_id' => EventType::factory(),
            'booking_date' => $bookingDate,
            'start_time' => '19:00:00',
            'end_time' => '23:30:00',
            'guest_count' => $guestCount,
            'tentative_guests' => $guestCount,
            'confirmed_guests' => null,
            'guest_status' => 'Tentative',
            'per_plate_price' => $perPlate,
            'package_amount' => $packageAmount,
            'hall_charges' => $hallCharges,
            'extra_charges' => $extraCharges,
            'discount_amount' => $discount,
            'security_deposit' => $securityDeposit,
            'tax_amount' => $taxAmount,
            'subtotal' => $subtotal,
            'grand_total' => $grandTotal,
            'advance_received' => 0.00,
            'revenue_recognized' => 0.00,
            'receivable_amount' => 0.00,
            'is_revenue_recognized' => false,
            'revenue_recognized_at' => null,
            'financial_status' => 'Pending',
            'booking_status' => 'Confirmed',
            'payment_status' => 'Unpaid',
            'special_instructions' => fake()->optional(0.4)->sentence(),
            'created_by' => User::factory(),
            'deposit_status' => 'Held',
            'no_food' => false,
            'privacy_required' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'booking_status' => 'Draft',
            'payment_status' => 'Unpaid',
            'financial_status' => 'Pending',
        ]);
    }

    public function reserved(): static
    {
        return $this->state(fn () => [
            'booking_status' => 'Reserved',
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn () => [
            'booking_status' => 'Confirmed',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_status' => 'Completed',
            'booking_date' => fake()->dateTimeBetween('-1 month', 'yesterday')->format('Y-m-d'),
            'is_revenue_recognized' => true,
            'revenue_recognized_at' => now(),
            'revenue_recognized' => $attributes['grand_total'] ?? 500000.00,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'booking_status' => 'Cancelled',
            'financial_status' => 'Cancelled',
        ]);
    }

    public function withAdvance(float $advanceAmount = 150000.00): static
    {
        return $this->state(fn (array $attributes) => [
            'advance_received' => $advanceAmount,
            'payment_status' => ($advanceAmount >= ($attributes['grand_total'] ?? 500000)) ? 'Paid' : 'Partially Paid',
            'financial_status' => 'Partially Paid',
        ]);
    }

    public function fullyPaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'advance_received' => $attributes['grand_total'] ?? 500000.00,
            'payment_status' => 'Paid',
            'financial_status' => 'Fully Paid',
        ]);
    }

    public function settled(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_status' => 'Completed',
            'is_revenue_recognized' => true,
            'revenue_recognized_at' => now(),
            'revenue_recognized' => $attributes['grand_total'] ?? 500000.00,
            'receivable_amount' => 0.00,
            'payment_status' => 'Paid',
            'financial_status' => 'Settled',
        ]);
    }

    public function today(): static
    {
        return $this->state(fn () => [
            'booking_date' => Carbon::today()->format('Y-m-d'),
            'booking_status' => 'Confirmed',
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn () => [
            'booking_date' => Carbon::today()->addDays(fake()->numberBetween(2, 30))->format('Y-m-d'),
            'booking_status' => 'Confirmed',
        ]);
    }

    public function past(): static
    {
        return $this->state(fn () => [
            'booking_date' => Carbon::today()->subDays(fake()->numberBetween(1, 45))->format('Y-m-d'),
        ]);
    }
}
