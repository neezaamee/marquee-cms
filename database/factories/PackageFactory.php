<?php

namespace Database\Factories;

use App\Models\Marquee;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    protected $model = Package::class;

    public function definition(): array
    {
        $packages = [
            ['name' => 'Royal Mughal Wedding Menu', 'price' => 2400.00],
            ['name' => 'Executive Barat Feast', 'price' => 2800.00],
            ['name' => 'Signature Walima Buffet', 'price' => 2200.00],
            ['name' => 'Standard Mehfil Menu', 'price' => 1650.00],
            ['name' => 'Corporate High Tea Package', 'price' => 1200.00],
            ['name' => 'Imperial Platinum Banquet', 'price' => 3500.00],
        ];

        $pkg = fake()->randomElement($packages);

        return [
            'marquee_id' => Marquee::factory(),
            'package_name' => $pkg['name'],
            'price_per_head' => $pkg['price'],
            'description' => 'Comprehensive multi-course banquet dining package with starters, main dishes, live BBQ, and gourmet desserts.',
            'status' => 'active',
        ];
    }
}
