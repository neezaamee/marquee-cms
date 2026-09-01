<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Marquee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'marquee_id' => function (array $attributes) {
                if (isset($attributes['employee_id'])) {
                    return Employee::find($attributes['employee_id'])?->marquee_id ?? Marquee::factory();
                }
                return Marquee::factory();
            },
            'branch_id' => function (array $attributes) {
                if (isset($attributes['employee_id'])) {
                    return Employee::find($attributes['employee_id'])?->branch_id ?? Branch::factory();
                }
                return Branch::factory();
            },
            'employee_id' => Employee::factory(),
            'date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
            'status' => fake()->randomElement(['present', 'present', 'present', 'late', 'absent', 'leave', 'half_day']),
            'notes' => fake()->optional(0.2)->sentence(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function present(): static
    {
        return $this->state(fn () => [
            'status' => 'present',
            'check_in' => '08:55:00',
            'check_out' => '18:05:00',
        ]);
    }

    public function absent(): static
    {
        return $this->state(fn () => [
            'status' => 'absent',
            'check_in' => null,
            'check_out' => null,
        ]);
    }

    public function late(): static
    {
        return $this->state(fn () => [
            'status' => 'late',
            'check_in' => '09:45:00',
            'check_out' => '18:00:00',
        ]);
    }
}
