<?php

namespace Database\Factories;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryUnit;
use App\Models\Marquee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        $items = [
            ['name' => 'Super Kernel Basmati Rice', 'rate' => 380.00, 'min' => 100, 'reorder' => 250],
            ['name' => 'Fresh Boneless Beef / Veal', 'rate' => 1400.00, 'min' => 50, 'reorder' => 120],
            ['name' => 'Fresh Chicken Broiler', 'rate' => 550.00, 'min' => 80, 'reorder' => 200],
            ['name' => 'Banaspati Cooking Oil 16L Tin', 'rate' => 8500.00, 'min' => 10, 'reorder' => 25],
            ['name' => 'Desi Ghee Pure Butterfat', 'rate' => 2200.00, 'min' => 15, 'reorder' => 40],
            ['name' => 'National Qorma & Biryani Masala', 'rate' => 650.00, 'min' => 20, 'reorder' => 50],
            ['name' => 'Fresh Whole Milk Dairy', 'rate' => 220.00, 'min' => 50, 'reorder' => 150],
            ['name' => 'Refined Fine White Sugar', 'rate' => 160.00, 'min' => 100, 'reorder' => 300],
            ['name' => 'Red Onions Fresh', 'rate' => 180.00, 'min' => 80, 'reorder' => 200],
            ['name' => 'Fresh Garlic & Ginger Paste', 'rate' => 450.00, 'min' => 25, 'reorder' => 60],
        ];

        $item = fake()->randomElement($items);

        return [
            'marquee_id' => Marquee::factory(),
            'item_code' => 'ITEM-' . strtoupper(\Illuminate\Support\Str::random(4)) . fake()->numerify('####'),
            'name' => $item['name'] . ' ' . strtoupper(\Illuminate\Support\Str::random(3)) . '-' . fake()->numerify('####'),
            'category_id' => InventoryCategory::factory(),
            'unit_id' => InventoryUnit::factory(),
            'purchase_unit_id' => null,
            'brand_id' => null,
            'description' => 'Standard kitchen banquet inventory raw material item.',
            'minimum_stock_level' => $item['min'],
            'reorder_level' => $item['reorder'],
            'default_purchase_rate' => $item['rate'],
            'average_cost' => $item['rate'],
            'last_purchase_cost' => $item['rate'],
            'conversion_factor' => 1.00,
            'status' => 'active',
        ];
    }
}
