<?php

namespace Database\Factories;

use App\Models\Marquee;
use App\Models\Vendor;
use App\Models\VendorService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorService>
 */
class VendorServiceFactory extends Factory
{
    protected $model = VendorService::class;

    public function definition(): array
    {
        $services = [
            ['name' => 'Complete Cinematic Wedding Coverage (Photo + 4K Video)', 'cost' => 60000.00, 'customer' => 85000.00],
            ['name' => 'Royal Fresh Floral Stage Backdrop & Entry Tunnel', 'cost' => 45000.00, 'customer' => 65000.00],
            ['name' => 'Intelligent Concert Stage Sound, Truss & Moving Lights', 'cost' => 30000.00, 'customer' => 45000.00],
            ['name' => 'Luxury Mercedes Benz S-Class Bridal Car with Driver', 'cost' => 20000.00, 'customer' => 30000.00],
            ['name' => 'Heavy Backup 250kVA Generator with Fuel Coverage', 'cost' => 25000.00, 'customer' => 35000.00],
        ];

        $svc = fake()->randomElement($services);

        return [
            'marquee_id' => Marquee::factory(),
            'vendor_id' => Vendor::factory(),
            'service_name' => $svc['name'],
            'description' => 'Professional event vendor partner service.',
            'unit' => 'Event',
            'default_sale_price' => $svc['customer'],
            'status' => 'active',
        ];
    }
}
