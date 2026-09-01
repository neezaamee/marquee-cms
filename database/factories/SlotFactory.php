<?php

namespace Database\Factories;

use App\Models\Marquee;
use App\Models\Slot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Slot>
 */
class SlotFactory extends Factory
{
    protected $model = Slot::class;

    public function definition(): array
    {
        return [
            'marquee_id' => Marquee::factory(),
            'slot_name' => fake()->randomElement(['Night / Dinner Shift', 'Lunch / Day Shift', 'Morning / Breakfast Shift', 'Late Night Qawwali']) . ' ' . fake()->numerify('####'),
            'start_time' => '19:00:00',
            'end_time' => '23:30:00',
            'status' => 'active',
        ];
    }

    public function night(): static
    {
        return $this->state(fn () => [
            'slot_name' => 'Night / Dinner Shift',
            'start_time' => '19:00:00',
            'end_time' => '23:30:00',
        ]);
    }

    public function lunch(): static
    {
        return $this->state(fn () => [
            'slot_name' => 'Lunch / Day Shift',
            'start_time' => '11:30:00',
            'end_time' => '16:00:00',
        ]);
    }
}
