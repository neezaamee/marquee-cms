<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic Plan',
                'slug' => 'basic',
                'description' => 'Perfect for a single small banquet hall.',
                'price' => 5000.00,
                'billing_interval' => 'month',
                'max_branches' => 1,
                'max_users' => 3,
                'storage_limit_mb' => 1024, // 1GB
                'features' => ['bookings' => true, 'halls' => true, 'customers' => true, 'billing' => true, 'inventory' => false, 'reports' => false],
                'trial_period_days' => 14,
                'status' => 'active',
            ],
            [
                'name' => 'Standard Plan',
                'slug' => 'standard',
                'description' => 'Ideal for businesses with up to 3 branches.',
                'price' => 15000.00,
                'billing_interval' => 'month',
                'max_branches' => 3,
                'max_users' => 10,
                'storage_limit_mb' => 5120, // 5GB
                'features' => ['bookings' => true, 'halls' => true, 'customers' => true, 'billing' => true, 'inventory' => true, 'reports' => true],
                'trial_period_days' => 14,
                'status' => 'active',
            ],
            [
                'name' => 'Premium Plan',
                'slug' => 'premium',
                'description' => 'Unlimited scaling for major wedding marquee chains.',
                'price' => 35000.00,
                'billing_interval' => 'month',
                'max_branches' => 10,
                'max_users' => 30,
                'storage_limit_mb' => 20480, // 20GB
                'features' => ['bookings' => true, 'halls' => true, 'customers' => true, 'billing' => true, 'inventory' => true, 'reports' => true, 'kitchen' => true, 'vendors' => true],
                'trial_period_days' => 14,
                'status' => 'active',
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
