<?php

namespace Database\Seeders;

use App\Models\ExtraService;
use App\Models\Marquee;
use Illuminate\Database\Seeder;

class ExtraServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $marquee = Marquee::first();
        if (!$marquee) {
            return;
        }

        $services = [
            [
                'service_name' => 'Floral Stage Decor (Premium Rose theme)',
                'default_price' => 45000.00,
            ],
            [
                'service_name' => 'DJ / Professional Sound System & Lights',
                'default_price' => 25000.00,
            ],
            [
                'service_name' => 'Cold Fire / Smoke Machine Entry (4 shots)',
                'default_price' => 15000.00,
            ],
            [
                'service_name' => 'Valet Parking Assistance (Per 100 guests)',
                'default_price' => 10000.00,
            ],
            [
                'service_name' => 'Additional AC / Heating Unit (Portable)',
                'default_price' => 30000.00,
            ],
            [
                'service_name' => 'Bridal Room Extended Stay (Per hour)',
                'default_price' => 5000.00,
            ],
        ];

        foreach ($services as $srv) {
            ExtraService::updateOrCreate(
                [
                    'marquee_id' => $marquee->id,
                    'service_name' => $srv['service_name']
                ],
                [
                    'default_price' => $srv['default_price'],
                    'status' => 'Active',
                ]
            );
        }
    }
}
