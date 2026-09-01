<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;
    protected static ?string $password;

    public function definition(): array
    {
        $pakistaniFirstNames = ['Muhammad', 'Ahmed', 'Ali', 'Hamza', 'Usman', 'Bilal', 'Zain', 'Tariq', 'Fatima', 'Ayesha', 'Zainab', 'Sana', 'Maryam', 'Hira'];
        $pakistaniLastNames = ['Khan', 'Chaudhry', 'Malik', 'Sheikh', 'Bhatti', 'Butt', 'Raza', 'Siddiqui', 'Qureshi', 'Ansari', 'Mirza', 'Riaz'];

        $firstName = fake()->randomElement($pakistaniFirstNames);
        $lastName = fake()->randomElement($pakistaniLastNames);

        $randomSuffix = fake()->numerify('####') . Str::lower(Str::random(3));

        return [
            'name' => "{$firstName} {$lastName}",
            'username' => strtolower(Str::slug($firstName . '.' . $lastName) . '.' . $randomSuffix),
            'email' => strtolower(Str::slug($firstName . '.' . $lastName) . '.' . $randomSuffix . '@example.com'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Password123!'),
            'remember_token' => Str::random(10),
            'phone' => '+923' . fake()->numerify('00#######'),
            'marquee_id' => null,
            'branch_id' => null,
            'role_id' => null,
            'status' => 'active',
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(function () {
            $role = Role::firstOrCreate(
                ['name' => 'super_admin'],
                ['display_name' => 'Super Administrator', 'label' => 'Super Admin', 'description' => 'System SaaS Owner']
            );
            return [
                'role_id' => $role->id,
                'marquee_id' => null,
                'branch_id' => null,
            ];
        });
    }

    public function owner(Marquee $marquee = null): static
    {
        return $this->state(function () use ($marquee) {
            $role = Role::firstOrCreate(
                ['name' => 'owner'],
                ['display_name' => 'Business Owner', 'label' => 'Owner', 'description' => 'Marquee Business Owner']
            );
            return [
                'role_id' => $role->id,
                'marquee_id' => $marquee ? $marquee->id : null,
                'branch_id' => null,
            ];
        });
    }

    public function branchManager(Branch $branch = null): static
    {
        return $this->state(function () use ($branch) {
            $role = Role::firstOrCreate(
                ['name' => 'branch_manager'],
                ['display_name' => 'Branch Manager', 'label' => 'Manager', 'description' => 'Operational Branch Manager']
            );
            return [
                'role_id' => $role->id,
                'marquee_id' => $branch ? $branch->marquee_id : null,
                'branch_id' => $branch ? $branch->id : null,
            ];
        });
    }

    public function accountant(Branch $branch = null): static
    {
        return $this->state(function () use ($branch) {
            $role = Role::firstOrCreate(
                ['name' => 'accountant'],
                ['display_name' => 'Accountant', 'label' => 'Accountant', 'description' => 'Finance & Accounting Lead']
            );
            return [
                'role_id' => $role->id,
                'marquee_id' => $branch ? $branch->marquee_id : null,
                'branch_id' => $branch ? $branch->id : null,
            ];
        });
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => 'inactive',
        ]);
    }
}
