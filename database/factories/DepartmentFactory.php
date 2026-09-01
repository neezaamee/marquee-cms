<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Marquee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        $depts = [
            ['name' => 'Main Hot Kitchen & Cooking', 'code' => 'DEPT-KITCHEN', 'type' => 'kitchen'],
            ['name' => 'Bakery, Sweets & Gourmet Desserts', 'code' => 'DEPT-BAKERY', 'type' => 'kitchen'],
            ['name' => 'Live BBQ & Tandoor Station', 'code' => 'DEPT-BBQ', 'type' => 'kitchen'],
            ['name' => 'Banquet Hall Service & Waiters', 'code' => 'DEPT-SERVICE', 'type' => 'service'],
            ['name' => 'Hygiene, Cleaning & Sanitation', 'code' => 'DEPT-CLEAN', 'type' => 'operations'],
            ['name' => 'Security & Valet Parking', 'code' => 'DEPT-SEC', 'type' => 'security'],
            ['name' => 'Accounts & Front Desk Operations', 'code' => 'DEPT-ADMIN', 'type' => 'management'],
        ];

        $dept = fake()->randomElement($depts);

        return [
            'marquee_id' => Marquee::factory(),
            'name' => $dept['name'] . ' ' . fake()->numerify('###'),
            'code' => $dept['code'] . '-' . fake()->numerify('####'),
            'type' => $dept['type'],
            'description' => 'Marquee operational department.',
            'status' => 'active',
        ];
    }
}
