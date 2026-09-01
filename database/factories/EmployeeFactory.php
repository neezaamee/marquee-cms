<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Marquee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $pakistaniFirstNames = ['Muhammad', 'Ahmed', 'Ali', 'Hamza', 'Usman', 'Bilal', 'Zain', 'Tariq', 'Sultan', 'Kashif', 'Waqas', 'Rehman', 'Shahid', 'Nadeem', 'Rashid', 'Farhan'];
        $pakistaniLastNames = ['Khan', 'Chaudhry', 'Malik', 'Sheikh', 'Bhatti', 'Butt', 'Raza', 'Siddiqui', 'Qureshi', 'Ansari', 'Mirza', 'Riaz'];

        $name = fake()->randomElement($pakistaniFirstNames) . ' ' . fake()->randomElement($pakistaniLastNames);

        $designations = [
            'Branch Manager' => 95000.00,
            'Booking Officer' => 55000.00,
            'Accountant' => 65000.00,
            'Cashier' => 45000.00,
            'Store Keeper' => 40000.00,
            'Kitchen Manager' => 75000.00,
            'Chef / Cook' => 60000.00,
            'Waiter' => 32000.00,
            'Cleaner' => 28000.00,
            'Security Guard' => 35000.00,
            'Electrician' => 40000.00,
        ];

        $designation = fake()->randomElement(array_keys($designations));
        $salary = $designations[$designation];

        return [
            'employee_id' => 'EMP-' . strtoupper(\Illuminate\Support\Str::random(3)) . fake()->numerify('####'),
            'marquee_id' => Marquee::factory(),
            'branch_id' => Branch::factory(),
            'department_id' => Department::factory(),
            'reporting_manager_id' => null,
            'name' => $name,
            'cnic' => fake()->numerify('35201-#######-#'),
            'mobile_number' => '+923' . fake()->numerify('00#######'),
            'designation' => $designation,
            'joining_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'salary' => $salary,
            'employment_type' => fake()->randomElement(['Permanent', 'Contract', 'Daily Wages']),
            'status' => 'Active',
            'photo' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => 'Active',
        ]);
    }

    public function terminated(): static
    {
        return $this->state(fn () => [
            'status' => 'Terminated',
        ]);
    }

    public function chef(): static
    {
        return $this->state(fn () => [
            'designation' => 'Chef / Cook',
            'salary' => 65000.00,
        ]);
    }

    public function waiter(): static
    {
        return $this->state(fn () => [
            'designation' => 'Waiter',
            'salary' => 32000.00,
        ]);
    }
}
