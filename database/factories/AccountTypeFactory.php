<?php

namespace Database\Factories;

use App\Models\AccountType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountType>
 */
class AccountTypeFactory extends Factory
{
    protected $model = AccountType::class;

    public function definition(): array
    {
        $types = [
            ['name' => 'Current Assets', 'code' => 'CURRENT_ASSETS', 'nature' => 'Asset'],
            ['name' => 'Current Liabilities', 'code' => 'CURRENT_LIABILITIES', 'nature' => 'Liability'],
            ['name' => 'Operating Revenue', 'code' => 'OPERATING_REVENUE', 'nature' => 'Income'],
            ['name' => 'Operating Expenses', 'code' => 'OPERATING_EXPENSES', 'nature' => 'Expense'],
            ['name' => 'Owner Equity', 'code' => 'OWNER_EQUITY', 'nature' => 'Equity'],
        ];

        $type = fake()->randomElement($types);

        return [
            'name' => $type['name'],
            'code' => $type['code'] . '_' . fake()->numerify('####'),
            'nature' => $type['nature'],
        ];
    }
}
