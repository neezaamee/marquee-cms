<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Marquee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        $branchNames = ['Main Branch', 'DHA Phase 5 Branch', 'Gulberg Executive Branch', 'Bahria Town Branch', 'Canal Road Branch', 'F-7 Islamabad Branch'];

        return [
            'marquee_id' => Marquee::factory(),
            'name' => fake()->randomElement($branchNames) . ' ' . fake()->numerify('##'),
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Lahore', 'Karachi', 'Islamabad', 'Faisalabad', 'Rawalpindi']),
            'province' => 'Punjab',
            'phone' => '+923' . fake()->numerify('00#######'),
            'status' => 'active',
            'is_head_office' => false,
            'tax_rate' => 16.00,
            'invoice_prefix' => 'INV-B' . fake()->numerify('##'),
            'booking_prefix' => 'BK-B' . fake()->numerify('##'),
            'branch_manager' => fake()->name(),
            'fbr_pos_id' => 'PRA-POS-' . fake()->numerify('####'),
            'fbr_pos_key' => 'fbr_key_' . fake()->sha1(),
            'fbr_sandbox_mode' => true,
        ];
    }

    public function headOffice(): static
    {
        return $this->state(fn () => [
            'name' => 'Head Office / Main Branch',
            'is_head_office' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => 'inactive',
        ]);
    }
}
