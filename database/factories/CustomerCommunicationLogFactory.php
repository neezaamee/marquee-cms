<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerCommunicationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerCommunicationLog>
 */
class CustomerCommunicationLogFactory extends Factory
{
    protected $model = CustomerCommunicationLog::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'communication_medium' => fake()->randomElement(['Phone Call', 'WhatsApp', 'In-Person Meeting', 'Email', 'SMS']),
            'subject' => fake()->randomElement([
                'Discussed menu options and final per-plate pricing.',
                'Follow-up regarding advance deposit installment.',
                'Confirmed headcount and guest arrival schedule.',
                'Discussed stage decor and LED wall requirements.',
                'Customer requested quotation for additional live BBQ counters.'
            ]),
            'content' => fake()->paragraph(),
            'status' => fake()->randomElement(['Delivered', 'Completed', 'Sent', 'Pending']),
            'logged_by' => null,
        ];
    }
}
