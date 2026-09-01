<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use App\Models\Marquee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    public function definition(): array
    {
        $categories = [
            ['name' => 'Electricity & Power Utility Bills', 'code' => 'EXP-ELEC'],
            ['name' => 'Commercial LPG Gas Cylinders', 'code' => 'EXP-GAS'],
            ['name' => 'Hall HVAC & Generator Maintenance', 'code' => 'EXP-MAINT'],
            ['name' => 'Kitchen Cleaning & Sanitation Chemicals', 'code' => 'EXP-CLEAN'],
            ['name' => 'Digital Marketing & Social Media Ads', 'code' => 'EXP-MKT'],
            ['name' => 'Printing, Stationery & Office Supplies', 'code' => 'EXP-OFFICE'],
        ];

        $cat = fake()->randomElement($categories);

        return [
            'marquee_id' => Marquee::factory(),
            'name' => $cat['name'] . ' ' . fake()->numerify('###'),
            'code' => $cat['code'] . '-' . fake()->numerify('####'),
            'description' => 'Operating expense category.',
            'status' => 'active',
        ];
    }
}
