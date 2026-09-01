<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Marquee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'marquee_id' => Marquee::factory(),
            'account_code' => fake()->numerify('####'),
            'name' => fake()->words(3, true),
            'account_type_id' => AccountType::factory(),
            'nature' => 'Asset',
            'opening_balance' => 0.00,
            'current_balance' => 0.00,
            'is_active' => true,
        ];
    }

    public function cash(): static
    {
        return $this->state(fn () => [
            'account_code' => '1001',
            'name' => 'Cash in Hand',
            'nature' => 'Asset',
        ]);
    }

    public function bank(): static
    {
        return $this->state(fn () => [
            'account_code' => '1002',
            'name' => 'Meezan Bank - Main Account',
            'nature' => 'Asset',
        ]);
    }

    public function receivable(): static
    {
        return $this->state(fn () => [
            'account_code' => '1003',
            'name' => 'Accounts Receivable - Customers',
            'nature' => 'Asset',
        ]);
    }

    public function advanceLiability(): static
    {
        return $this->state(fn () => [
            'account_code' => '2003',
            'name' => 'Customer Advances / Contract Liabilities',
            'nature' => 'Liability',
        ]);
    }

    public function revenue(): static
    {
        return $this->state(fn () => [
            'account_code' => '4001',
            'name' => 'Hall Booking Revenue',
            'nature' => 'Income',
        ]);
    }
}
