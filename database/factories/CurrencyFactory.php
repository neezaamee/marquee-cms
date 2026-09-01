<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Marquee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'marquee_id' => Marquee::factory(),
            'code' => 'PKR',
            'name' => 'Pakistani Rupee',
            'symbol' => 'Rs.',
            'is_base' => true,
            'exchange_rate' => 1.000000,
            'is_active' => true,
        ];
    }
}
