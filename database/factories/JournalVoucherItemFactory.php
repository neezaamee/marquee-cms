<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalVoucherItem>
 */
class JournalVoucherItemFactory extends Factory
{
    protected $model = JournalVoucherItem::class;

    public function definition(): array
    {
        $amount = fake()->randomElement([50000, 100000, 150000, 200000, 350000]);

        return [
            'journal_voucher_id' => JournalVoucher::factory(),
            'account_id' => Account::factory(),
            'debit' => $amount,
            'credit' => 0.00,
            'narration' => 'Journal Voucher Item line',
        ];
    }

    public function debit(float $amount = 100000.00): static
    {
        return $this->state(fn () => [
            'debit' => $amount,
            'credit' => 0.00,
        ]);
    }

    public function credit(float $amount = 100000.00): static
    {
        return $this->state(fn () => [
            'debit' => 0.00,
            'credit' => $amount,
        ]);
    }
}
