<?php

namespace Database\Factories;

use App\Models\Marquee;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        $suppliers = [
            'Punjab Grains & Rice Traders', 'Al-Madina Meat & Poultry Suppliers',
            'Pak Pure Dairy & Ghee Mart', 'Lahore Spices & Herbs Importers',
            'Fresh Valley Produce Wholesale', 'Royal Crockery & Event Equipment Rentals',
            'Super Clean Sanitation & Hygiene Supplies', 'Golden Crown Cooking Oil Distributers'
        ];

        return [
            'marquee_id' => Marquee::factory(),
            'supplier_code' => 'SUP-' . strtoupper(\Illuminate\Support\Str::random(3)) . fake()->numerify('####'),
            'name' => fake()->randomElement($suppliers) . ' ' . fake()->numerify('###'),
            'contact_person' => fake()->name('male'),
            'mobile_number' => '+923' . fake()->numerify('00#######'),
            'whatsapp_number' => '+923' . fake()->numerify('00#######'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'city' => fake()->randomElement(['Lahore', 'Karachi', 'Islamabad', 'Faisalabad', 'Multan']),
            'notes' => 'Authorized food raw material vendor/supplier.',
            'opening_balance' => 0.00,
            'status' => 'active',
        ];
    }
}
