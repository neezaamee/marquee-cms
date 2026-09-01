<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\CashBankAccount;
use App\Models\Marquee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashBankAccount>
 */
class CashBankAccountFactory extends Factory
{
    protected $model = CashBankAccount::class;

    public function definition(): array
    {
        return [
            'marquee_id' => Marquee::factory(),
            'account_id' => Account::factory(),
            'type' => fake()->randomElement(['cash', 'bank']),
            'bank_name' => fake()->randomElement(['Meezan Bank', 'Habib Bank Limited (HBL)', 'Bank Alfalah', 'MCB Bank', 'Faysal Bank']),
            'account_number' => fake()->numerify('0102##########'),
            'iban' => 'PK' . fake()->numerify('##MEZN0000000102######'),
            'branch_name' => fake()->city() . ' Main Branch',
            'status' => 'active',
        ];
    }

    public function cash(): static
    {
        return $this->state(fn () => [
            'type' => 'cash',
            'bank_name' => null,
            'account_number' => null,
            'iban' => null,
            'branch_name' => null,
        ]);
    }

    public function bank(): static
    {
        return $this->state(fn () => [
            'type' => 'bank',
        ]);
    }
}
