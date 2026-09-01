<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Marquee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerLedger>
 */
class CustomerLedgerFactory extends Factory
{
    protected $model = CustomerLedger::class;

    public function definition(): array
    {
        $amount = fake()->randomElement([50000, 100000, 150000, 200000, 350000, 500000]);

        return [
            'marquee_id' => Marquee::factory(),
            'branch_id' => Branch::factory(),
            'customer_id' => Customer::factory(),
            'booking_id' => null,
            'booking_payment_id' => null,
            'journal_voucher_id' => null,
            'transaction_date' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'transaction_type' => 'Advance Payment',
            'reference_number' => 'REF-' . strtoupper(\Illuminate\Support\Str::random(3)) . fake()->numerify('#####'),
            'description' => 'Customer Advance Payment against booking',
            'debit' => 0.00,
            'credit' => $amount,
            'running_balance' => -$amount,
            'created_by' => User::factory(),
        ];
    }

    public function advancePayment(float $amount = 150000.00): static
    {
        return $this->state(fn () => [
            'transaction_type' => 'Advance Payment',
            'description' => 'Booking Advance Deposit collected',
            'debit' => 0.00,
            'credit' => $amount,
            'running_balance' => -$amount,
        ]);
    }

    public function revenueRecognition(float $amount = 500000.00): static
    {
        return $this->state(fn () => [
            'transaction_type' => 'Revenue Recognition / Invoice',
            'description' => 'Event Completed - Final Billing Revenue Recognized',
            'debit' => $amount,
            'credit' => 0.00,
            'running_balance' => $amount,
        ]);
    }

    public function receivablePayment(float $amount = 200000.00): static
    {
        return $this->state(fn () => [
            'transaction_type' => 'Receivable Settlement',
            'description' => 'Post-Event Outstanding Balance Settled',
            'debit' => 0.00,
            'credit' => $amount,
            'running_balance' => 0.00,
        ]);
    }
}
