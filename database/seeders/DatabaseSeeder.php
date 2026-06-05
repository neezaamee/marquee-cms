<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Run core static seeders
        $this->call([
            SubscriptionPlanSeeder::class,
            RolesAndPermissionsSeeder::class,
        ]);

        // Retrieve seeded records
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $ownerRole = Role::where('name', 'owner')->first();
        $managerRole = Role::where('name', 'branch_manager')->first();
        $standardPlan = SubscriptionPlan::where('slug', 'standard')->first();

        // 2. Seed default Super Admin user (unscoped SaaS admin)
        User::updateOrCreate(
            ['email' => 'superadmin@marquee.cms'],
            [
                'name' => 'SaaS Super Admin',
                'password' => Hash::make('Password123!'),
                'role_id' => $superAdminRole->id,
                'status' => 'active',
            ]
        );

        // 3. Seed a default Marquee company tenant
        $marquee = Marquee::updateOrCreate(
            ['email' => 'contact@royalmarquee.com'],
            [
                'name' => 'Royal Marquee & Events Group',
                'logo' => null,
                'address' => 'Main Boulevard, Gulberg III',
                'city' => 'Lahore',
                'province' => 'Punjab',
                'phone' => '+923001234567',
                'ntn' => '1234567-8',
                'strn' => '9876543-2',
                'tax_authority' => 'PRA',
                'status' => 'active',
                'subscription_plan_id' => $standardPlan->id,
                'subscription_ends_at' => now()->addYear(),
            ]
        );

        // 4. Seed default branches for the Marquee tenant
        $branchLahore = Branch::updateOrCreate(
            [
                'marquee_id' => $marquee->id,
                'name' => 'Lahore Gulberg Branch',
            ],
            [
                'address' => 'Gulberg III, Near Ghalib Market',
                'city' => 'Lahore',
                'province' => 'Punjab',
                'phone' => '+92423123456',
                'status' => 'active',
                'fbr_pos_id' => 'PRA-LHR-GUL-01',
                'fbr_pos_key' => 'key_lhr_gulberg_secret',
                'fbr_sandbox_mode' => true,
            ]
        );

        $branchKarachi = Branch::updateOrCreate(
            [
                'marquee_id' => $marquee->id,
                'name' => 'Karachi DHA Branch',
            ],
            [
                'address' => 'Phase VI, DHA',
                'city' => 'Karachi',
                'province' => 'Sindh',
                'phone' => '+92213123456',
                'status' => 'active',
                'fbr_pos_id' => 'SRB-KHI-DHA-02',
                'fbr_pos_key' => 'key_khi_dha_secret',
                'fbr_sandbox_mode' => true,
            ]
        );

        // 5. Seed default Owner user (belongs to Royal Marquee, no specific branch)
        User::updateOrCreate(
            ['email' => 'owner@royalmarquee.com'],
            [
                'name' => 'Mian Akbar (Owner)',
                'password' => Hash::make('Password123!'),
                'marquee_id' => $marquee->id,
                'branch_id' => null,
                'role_id' => $ownerRole->id,
                'phone' => '+923007654321',
                'status' => 'active',
            ]
        );

        // 6. Seed default Manager user (belongs to Royal Marquee, assigned to Lahore Branch)
        User::updateOrCreate(
            ['email' => 'manager.lh@royalmarquee.com'],
            [
                'name' => 'Asif Mehmood (Manager)',
                'password' => Hash::make('Password123!'),
                'marquee_id' => $marquee->id,
                'branch_id' => $branchLahore->id,
                'role_id' => $managerRole->id,
                'phone' => '+923009876543',
                'status' => 'active',
            ]
        );
    }
}
