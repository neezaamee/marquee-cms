<?php

namespace Database\Factories;

use App\Models\InventoryCategory;
use App\Models\Marquee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryCategory>
 */
class InventoryCategoryFactory extends Factory
{
    protected $model = InventoryCategory::class;

    public function definition(): array
    {
        $categories = ['Dry Rations & Grains', 'Fresh Meat & Poultry', 'Dairy & Cooking Oils', 'Spices & Seasonings', 'Fresh Vegetables & Fruits', 'Disposables & Packaging', 'Crockery & Cutlery'];

        return [
            'marquee_id' => Marquee::factory(),
            'name' => fake()->randomElement($categories) . ' ' . fake()->numerify('###'),
            'description' => 'Inventory classification category for marquee banquets.',
            'status' => 'active',
        ];
    }
}
