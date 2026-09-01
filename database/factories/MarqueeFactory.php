<?php

namespace Database\Factories;

use App\Models\Marquee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Marquee>
 */
class MarqueeFactory extends Factory
{
    protected $model = Marquee::class;

    public function definition(): array
    {
        $venues = [
            'Royal Palm Marquee', 'The Sheraton Grand', 'Pearl Continental Banquet',
            'Grand Palm Event Complex', 'Mughal-e-Azam Banquet', 'Crystal Palace Hall',
            'Shalimar Grand Ballroom', 'Crown Plaza Marquee', 'Imperial Gardens Banquet',
            'Majestic Heights Arena', 'Regal Event Hall', 'Avari Banquet Suites'
        ];

        $name = fake()->randomElement($venues) . ' ' . fake()->city() . ' ' . fake()->numerify('###');

        return [
            'name' => $name,
            'business_type' => fake()->randomElement(['Single Marquee', 'Marquee Chain', 'Catering & Event Complex']),
            'logo' => null,
            'address' => fake()->streetAddress() . ', Main Boulevard',
            'city' => fake()->randomElement(['Lahore', 'Karachi', 'Islamabad', 'Faisalabad', 'Rawalpindi', 'Multan', 'Sialkot']),
            'province' => 'Punjab',
            'country' => 'Pakistan',
            'timezone' => 'Asia/Karachi',
            'currency' => 'PKR',
            'phone' => '+923' . fake()->numerify('00#######'),
            'email' => fake()->unique()->safeEmail(),
            'ntn' => fake()->numerify('#######-#'),
            'strn' => fake()->numerify('#######-#'),
            'tax_authority' => fake()->randomElement(['PRA', 'SRB', 'FBR', 'KPRA']),
            'status' => 'active',
            'is_setup_completed' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => 'inactive',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
            'is_setup_completed' => false,
        ]);
    }
}
