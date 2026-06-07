<?php

namespace Database\Seeders;

use App\Models\EventType;
use App\Models\Marquee;
use Illuminate\Database\Seeder;

class EventTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            [
                'name' => 'Wedding',
                'code' => 'WEDD',
                'description' => 'Traditional wedding reception ceremony.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Barat',
                'code' => 'BRAT',
                'description' => 'Wedding Barat arrival and main ceremony.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Walima',
                'code' => 'WALI',
                'description' => 'Wedding Walima reception dinner.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Mehndi',
                'code' => 'MEHN',
                'description' => 'Pre-wedding henna / musical ceremony.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Nikah',
                'code' => 'NIKA',
                'description' => 'Islamic marriage contract signing ceremony.',
                'sort_order' => 5,
            ],
            [
                'name' => 'Engagement',
                'code' => 'ENGA',
                'description' => 'Official ring exchange / commitment ceremony.',
                'sort_order' => 6,
            ],
            [
                'name' => 'Birthday',
                'code' => 'BIRT',
                'description' => 'Birthday party celebration event.',
                'sort_order' => 7,
            ],
            [
                'name' => 'Corporate Event',
                'code' => 'CORP',
                'description' => 'Corporate gatherings, dinners, or annual award ceremonies.',
                'sort_order' => 8,
            ],
            [
                'name' => 'Seminar',
                'code' => 'SEMI',
                'description' => 'Educational or business seminar lectures.',
                'sort_order' => 9,
            ],
            [
                'name' => 'Conference',
                'code' => 'CONF',
                'description' => 'Large-scale corporate or academic conferences.',
                'sort_order' => 10,
            ],
            [
                'name' => 'Private Dinner',
                'code' => 'DINR',
                'description' => 'Private family or business gathering dinner.',
                'sort_order' => 11,
            ],
            [
                'name' => 'Other',
                'code' => 'OTHR',
                'description' => 'Custom event category not specified in standard list.',
                'sort_order' => 12,
            ],
        ];

        // Seed default event types for all marquees
        $marquees = Marquee::all();
        if ($marquees->isEmpty()) {
            return;
        }

        foreach ($marquees as $marquee) {
            foreach ($defaults as $item) {
                EventType::updateOrCreate(
                    [
                        'marquee_id' => $marquee->id,
                        'event_type_code' => $item['code'],
                    ],
                    [
                        'event_type_name' => $item['name'],
                        'description' => $item['description'],
                        'status' => 'Active',
                        'sort_order' => $item['sort_order'],
                        'is_system_default' => true,
                        'default_duration_hours' => 4.00,
                        'base_price' => 50000.00,
                    ]
                );
            }
        }
    }
}
