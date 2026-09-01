<?php

namespace Database\Factories;

use App\Models\EventType;
use App\Models\Marquee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventType>
 */
class EventTypeFactory extends Factory
{
    protected $model = EventType::class;

    public function definition(): array
    {
        $events = [
            ['name' => 'Wedding Barat', 'code' => 'EV-BARAT'],
            ['name' => 'Walima Reception', 'code' => 'EV-WALIMA'],
            ['name' => 'Mehndi / Mayun', 'code' => 'EV-MEHNDI'],
            ['name' => 'Qawwali Night / Musical', 'code' => 'EV-QAWWALI'],
            ['name' => 'Corporate Annual Dinner', 'code' => 'EV-CORP'],
            ['name' => 'Birthday Party', 'code' => 'EV-BDAY'],
            ['name' => 'Commercial Exhibition', 'code' => 'EV-EXHIB'],
        ];

        $event = fake()->randomElement($events);

        return [
            'marquee_id' => Marquee::factory(),
            'event_type_name' => $event['name'],
            'event_type_code' => $event['code'] . '-' . fake()->numerify('####'),
            'status' => 'active',
        ];
    }
}
