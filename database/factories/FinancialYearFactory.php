<?php

namespace Database\Factories;

use App\Models\FinancialYear;
use App\Models\Marquee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialYear>
 */
class FinancialYearFactory extends Factory
{
    protected $model = FinancialYear::class;

    public function definition(): array
    {
        return [
            'marquee_id' => Marquee::factory(),
            'name' => 'FY ' . date('Y'),
            'start_date' => date('Y') . '-01-01',
            'end_date' => date('Y') . '-12-31',
            'status' => 'active',
            'is_default' => true,
        ];
    }
}
