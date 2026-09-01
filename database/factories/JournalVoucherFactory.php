<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\FinancialYear;
use App\Models\JournalVoucher;
use App\Models\Marquee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalVoucher>
 */
class JournalVoucherFactory extends Factory
{
    protected $model = JournalVoucher::class;

    public function definition(): array
    {
        return [
            'marquee_id' => Marquee::factory(),
            'branch_id' => Branch::factory(),
            'financial_year_id' => FinancialYear::factory(),
            'voucher_no' => 'JV-' . date('Y') . '-' . strtoupper(\Illuminate\Support\Str::random(3)) . fake()->numerify('#####'),
            'voucher_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'voucher_type' => 'Bank Payment',
            'narration' => 'Financial transaction journal voucher posting',
            'status' => 'posted',
            'is_auto_generated' => true,
            'source_type' => 'App\Models\BookingPayment',
            'source_id' => null,
            'created_by' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => 'draft',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => 'cancelled',
        ]);
    }
}
