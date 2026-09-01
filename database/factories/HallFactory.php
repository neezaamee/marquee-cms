<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Hall;
use App\Models\Marquee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hall>
 */
class HallFactory extends Factory
{
    protected $model = Hall::class;

    public function definition(): array
    {
        $hallNames = [
            'Grand Ballroom', 'Crystal Hall', 'Royal Mughal Lawn',
            'Koh-e-Noor Hall', 'Imperial Marquee', 'Sheesh Mahal Hall',
            'Emerald Banquet', 'Diamond Suite', 'Paradise Garden Lawn'
        ];

        return [
            'marquee_id' => Marquee::factory(),
            'branch_id' => Branch::factory(),
            'hall_name' => fake()->randomElement($hallNames) . ' ' . fake()->numerify('###'),
            'hall_code' => 'HALL-' . fake()->numerify('####'),
            'capacity' => fake()->numberBetween(200, 1200),
            'hall_type' => fake()->randomElement(['Marquee', 'Banquet Hall', 'Open Lawn', 'Rooftop']),
            'default_booking_price' => fake()->randomElement([40000, 50000, 75000, 100000, 150000]),
            'description' => 'Luxury air-conditioned banquet venue with state of the art lighting and audiovisual equipment.',
            'status' => 'active',
        ];
    }

    public function marquee(): static
    {
        return $this->state(fn () => [
            'hall_type' => 'Marquee',
            'capacity' => 600,
        ]);
    }

    public function lawn(): static
    {
        return $this->state(fn () => [
            'hall_type' => 'Open Lawn',
            'capacity' => 1000,
        ]);
    }

    public function banquet(): static
    {
        return $this->state(fn () => [
            'hall_type' => 'Banquet Hall',
            'capacity' => 400,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => 'inactive',
        ]);
    }
}
