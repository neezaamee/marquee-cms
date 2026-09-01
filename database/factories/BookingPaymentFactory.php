<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\JournalVoucher;
use App\Models\Marquee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingPayment>
 */
class BookingPaymentFactory extends Factory
{
    protected $model = BookingPayment::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'account_id' => null,
            'journal_voucher_id' => null,
            'amount' => fake()->randomElement([50000, 100000, 150000, 200000, 300000]),
            'payment_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'payment_method' => fake()->randomElement(['Cash', 'Bank Transfer', 'Cheque', 'Online / Card']),
            'payment_type' => 'advance',
            'transaction_reference' => 'TXN-' . strtoupper(\Illuminate\Support\Str::random(3)) . fake()->numerify('#####'),
            'notes' => 'Booking payment installment',
            'recorded_by' => null,
        ];
    }

    public function cash(): static
    {
        return $this->state(fn () => [
            'payment_method' => 'Cash',
        ]);
    }

    public function bank(): static
    {
        return $this->state(fn () => [
            'payment_method' => 'Bank Transfer',
        ]);
    }

    public function advance(): static
    {
        return $this->state(fn () => [
            'payment_type' => 'advance',
            'notes' => 'Booking token/advance deposit',
        ]);
    }

    public function receivablePayment(): static
    {
        return $this->state(fn () => [
            'payment_type' => 'receivable_payment',
            'notes' => 'Post-event final receivable settlement',
        ]);
    }

    public function refund(): static
    {
        return $this->state(fn () => [
            'payment_type' => 'refund',
            'notes' => 'Customer advance refund disbursement',
        ]);
    }
}
