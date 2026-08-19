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
            SaasModuleSeeder::class,
            GlobalDefaultDataSeeder::class,
            MultiBusinessStructureSeeder::class,
        ]);

        // Retrieve seeded records
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $ownerRole = Role::where('name', 'owner')->first();
        $managerRole = Role::where('name', 'branch_manager')->first();
        $standardPlan = SubscriptionPlan::where('slug', 'standard')->first();

        // 2. Seed default Super Admin user (unscoped SaaS admin)
        User::updateOrCreate(
            ['email' => 'superadmin@elaftech.com'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'password' => Hash::make('Password123!'),
                'role_id' => $superAdminRole->id,
                'status' => 'active',
            ]
        );

        // 3. Seed a default Marquee company tenant
        $marquee = Marquee::updateOrCreate(
            ['email' => 'contact@thesheraton.com'],
            [
                'name' => 'The Sheraton Marquee',
                'business_type' => 'Single Marquee',
                'logo' => null,
                'address' => 'Canal Road near Toyota Motors',
                'city' => 'Faisalabad',
                'province' => 'Punjab',
                'country' => 'Pakistan',
                'timezone' => 'Asia/Karachi',
                'currency' => 'PKR',
                'phone' => '+923218662726',
                'ntn' => '1234567-8',
                'strn' => '9876543-2',
                'tax_authority' => 'PRA',
                'status' => 'active',
                'is_setup_completed' => true,
            ]
        );

        // 4. Seed default branches for the Marquee tenant
        $branchMain = Branch::updateOrCreate(
            [
                'marquee_id' => $marquee->id,
                'name' => 'Main Branch',
            ],
            [
                'address' => 'Canal Road near Toyota Motors',
                'city' => 'Faisalabad',
                'province' => 'Punjab',
                'phone' => '+923218662726',
                'status' => 'active',
                'fbr_pos_id' => 'PRA-LHR-GUL-01',
                'fbr_pos_key' => 'key_lhr_gulberg_secret',
                'fbr_sandbox_mode' => true,
                'is_head_office' => true,
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
                'is_head_office' => false,
            ]
        );

        // 5. Seed default Owner user (belongs to The Sheraton Marquee, no specific branch)
        $ownerUser = User::updateOrCreate(
            ['email' => 'ghulamabbas@thesheraton.com'],
            [
                'name' => 'Ghulam Abbas',
                'username' => 'ghulamabbas',
                'password' => Hash::make('Password123!'),
                'marquee_id' => null,
                'branch_id' => null,
                'role_id' => $ownerRole->id,
                'phone' => '+923006690391',
                'status' => 'active',
                'subscription_plan_id' => $standardPlan->id,
                'subscription_ends_at' => now()->addYear(),
            ]
        );

        // Link owners using pivot
        $ownerUser->ownedMarquees()->syncWithoutDetaching([$marquee->id]);

        // 6. Seed default Manager user (belongs to The Sheraton Marquee, assigned to Main Branch)
        User::updateOrCreate(
            ['email' => 'asif@thesheraton.com'],
            [
                'name' => 'Asif',
                'username' => 'asif',
                'password' => Hash::make('Password123!'),
                'marquee_id' => $marquee->id,
                'branch_id' => $branchMain->id,
                'role_id' => $managerRole->id,
                'phone' => '+923218662726',
                'status' => 'active',
            ]
        );

        // 7. Seed default slots and master catalogs
        $this->call([
            DefaultSlotsSeeder::class,
            ExtraServicesSeeder::class,
            EventTypeSeeder::class,
            MenuModuleSeeder::class,
            DefaultHallsSeeder::class,
            AccountingModuleSeeder::class,
            InventoryModuleSeeder::class,
            ExpenseModuleSeeder::class,
            DepartmentSeeder::class,
        ]);
    }
}
