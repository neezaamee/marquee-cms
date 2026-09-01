<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Marquee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $pakistaniFirstNames = ['Muhammad', 'Ahmed', 'Ali', 'Hamza', 'Usman', 'Bilal', 'Zain', 'Tariq', 'Fatima', 'Ayesha', 'Zainab', 'Sana', 'Maryam', 'Hira', 'Kashif', 'Waqas', 'Rehman', 'Shahid'];
        $pakistaniLastNames = ['Khan', 'Chaudhry', 'Malik', 'Sheikh', 'Bhatti', 'Butt', 'Raza', 'Siddiqui', 'Qureshi', 'Ansari', 'Mirza', 'Riaz', 'Abbasi', 'Niazi'];

        $firstName = fake()->randomElement($pakistaniFirstNames);
        $lastName = fake()->randomElement($pakistaniLastNames);

        $addresses = [
            'House 12, Street 4, Sector Y, Phase 3, DHA',
            'Plot 45, Main Boulevard, Gulberg III',
            'House 102-C, Model Town',
            'Apartment 5B, Royal Palm Avenue, Canal Road',
            'Sector F-7/2, Street 19',
            'House 88, Block D, Bahria Town',
            'Street 9, Cavalry Ground',
            'House 304, Askari 10',
            'Flat 12, Executive Heights, Clifton Block 5',
        ];

        return [
            'marquee_id' => Marquee::factory(),
            'customer_code' => 'CUST-' . strtoupper(\Illuminate\Support\Str::random(3)) . fake()->numerify('####'),
            'customer_type' => 'Individual',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'company_name' => null,
            'gender' => fake()->randomElement(['Male', 'Female']),
            'email' => strtolower($firstName . '.' . $lastName . fake()->numerify('###') . '@example.com'),
            'phone_number' => '+923' . fake()->numerify('00#######'),
            'alternate_phone' => fake()->optional(0.4)->passthrough('+923' . fake()->numerify('11#######')),
            'cnic_national_id' => fake()->numerify('35201-#######-#'),
            'address' => fake()->randomElement($addresses),
            'city' => fake()->randomElement(['Lahore', 'Karachi', 'Islamabad', 'Faisalabad', 'Rawalpindi', 'Multan', 'Gujranwala', 'Sialkot']),
            'province' => 'Punjab',
            'postal_code' => fake()->numerify('54###'),
            'notes' => fake()->optional(0.3)->sentence(),
            'status' => 'Active',
        ];
    }

    public function corporate(): static
    {
        return $this->state(fn () => [
            'customer_type' => 'Corporate',
            'company_name' => fake()->company() . ' Pvt Ltd',
        ]);
    }

    public function vip(): static
    {
        return $this->state(fn () => [
            'notes' => 'VIP Client - Special event arrangements and hospitality required.',
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn () => [
            'status' => 'Blocked',
        ]);
    }
}
