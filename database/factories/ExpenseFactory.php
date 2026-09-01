<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Marquee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        $amount = fake()->randomElement([15000, 25000, 45000, 80000, 120000]);

        return [
            'marquee_id' => Marquee::factory(),
            'branch_id' => Branch::factory(),
            'expense_number' => 'EXP-' . strtoupper(\Illuminate\Support\Str::random(3)) . fake()->numerify('#####'),
            'expense_date' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'department' => 'Operations',
            'cost_center' => 'General',
            'expense_category_id' => ExpenseCategory::factory(),
            'expense_type_id' => null,
            'supplier_id' => null,
            'employee_id' => null,
            'booking_id' => null,
            'purchase_order_id' => null,
            'purchase_invoice_id' => null,
            'currency_id' => null,
            'exchange_rate' => 1.00,
            'description' => 'Operating operational utility / maintenance expenditure.',
            'internal_notes' => null,
            'amount' => $amount,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => $amount,
            'total_amount_base' => $amount,
            'payment_method' => fake()->randomElement(['Cash', 'Bank Transfer']),
            'cash_bank_account_id' => null,
            'petty_cash_account_id' => null,
            'payment_status' => 'Paid',
            'status' => 'Approved',
            'due_date' => null,
            'reference_number' => 'INV-' . fake()->numerify('####'),
            'journal_voucher_id' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'payment_status' => 'Paid',
            'status' => 'Approved',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'payment_status' => 'Unpaid',
            'status' => 'Pending Approval',
        ]);
    }
}
