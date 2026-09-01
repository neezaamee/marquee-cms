<?php

namespace Database\Factories;

use App\Models\InventoryUnit;
use App\Models\Marquee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryUnit>
 */
class InventoryUnitFactory extends Factory
{
    protected $model = InventoryUnit::class;

    public function definition(): array
    {
        $units = [
            ['name' => 'Kilogram', 'symbol' => 'kg'],
            ['name' => 'Gram', 'symbol' => 'g'],
            ['name' => 'Liter', 'symbol' => 'L'],
            ['name' => 'Dozen', 'symbol' => 'dz'],
            ['name' => 'Carton / Box', 'symbol' => 'ctn'],
            ['name' => 'Bag (50kg)', 'symbol' => 'bag'],
            ['name' => 'Piece', 'symbol' => 'pcs'],
        ];

        $unit = fake()->randomElement($units);

        return [
            'marquee_id' => Marquee::factory(),
            'name' => $unit['name'] . ' ' . fake()->numerify('###'),
            'symbol' => $unit['symbol'] . fake()->numerify('##'),
            'allow_decimal' => true,
            'status' => 'active',
        ];
    }
}
