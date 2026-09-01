<?php

namespace Database\Factories;

use App\Models\ExtraService;
use App\Models\Marquee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtraService>
 */
class ExtraServiceFactory extends Factory
{
    protected $model = ExtraService::class;

    public function definition(): array
    {
        $services = [
            ['name' => 'VIP Bridal Room / Lounge Suite', 'price' => 15000.00],
            ['name' => 'DJ Sound & Moving Head Lighting System', 'price' => 35000.00],
            ['name' => 'LED Video Wall Stage Backdrop (20x10 ft)', 'price' => 45000.00],
            ['name' => 'Fresh Floral Stage & Walkway Decor', 'price' => 50000.00],
            ['name' => 'Heavy-Duty Power Backup Generator', 'price' => 20000.00],
            ['name' => 'Valet Parking & Security Detail', 'price' => 15000.00],
            ['name' => 'Cold Fire & Pyro Stage Effects', 'price' => 12000.00],
        ];

        $svc = fake()->randomElement($services);

        return [
            'marquee_id' => Marquee::factory(),
            'service_name' => $svc['name'],
            'price' => $svc['price'],
            'status' => 'active',
        ];
    }
}
