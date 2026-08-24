<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\PlanFeature;
use App\Models\BillingCycle;
use Illuminate\Database\Seeder;

class SaasModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Billing Cycles
        $cycles = [
            [
                'cycle_name' => 'Monthly',
                'duration_in_months' => 1,
                'discount_percentage' => 0.00,
                'status' => 'Active',
            ],
            [
                'cycle_name' => 'Quarterly',
                'duration_in_months' => 3,
                'discount_percentage' => 5.00,
                'status' => 'Active',
            ],
            [
                'cycle_name' => 'Semi-Annual',
                'duration_in_months' => 6,
                'discount_percentage' => 10.00,
                'status' => 'Active',
            ],
            [
                'cycle_name' => 'Annual',
                'duration_in_months' => 12,
                'discount_percentage' => 15.00,
                'status' => 'Active',
            ],
        ];

        $cycleModels = [];
        foreach ($cycles as $cycle) {
            $cycleModels[$cycle['cycle_name']] = BillingCycle::updateOrCreate(
                ['cycle_name' => $cycle['cycle_name']],
                $cycle
            );
        }

        // 2. Seed Plan Features
        $features = [
            [
                'feature_name' => 'Max Branches',
                'feature_key' => 'max_branches',
                'description' => 'Number of branches permitted under this plan',
                'status' => 'Active',
            ],
            [
                'feature_name' => 'Max Marquees',
                'feature_key' => 'max_marquees',
                'description' => 'Number of marquees/businesses permitted under this plan',
                'status' => 'Active',
            ],
            [
                'feature_name' => 'Max Users',
                'feature_key' => 'max_users',
                'description' => 'Number of user accounts permitted under this plan',
                'status' => 'Active',
            ],
            [
                'feature_name' => 'Storage Limit',
                'feature_key' => 'storage_limit_mb',
                'description' => 'File/media storage limit in Megabytes',
                'status' => 'Active',
            ],
            [
                'feature_name' => 'FBR POS Integration',
                'feature_key' => 'fbr_pos_integration',
                'description' => 'Support for Federal Board of Revenue POS hardware reporting integration',
                'status' => 'Active',
            ],
            [
                'feature_name' => 'SaaS Reports & Analytics',
                'feature_key' => 'reports_analytics',
                'description' => 'Advanced reports and dashboard analytics tools',
                'status' => 'Active',
            ],
        ];

        $featureModels = [];
        foreach ($features as $feature) {
            $featureModels[$feature['feature_key']] = PlanFeature::updateOrCreate(
                ['feature_key' => $feature['feature_key']],
                $feature
            );
        }

        // 3. Update Existing Subscription Plans with prices and sync billing cycles/features
        $planData = [
            'basic' => [
                'monthly_price' => 5000.00,
                'quarterly_price' => 14250.00, // 5% discount
                'semi_annual_price' => 27000.00, // 10% discount
                'annual_price' => 51000.00, // 15% discount
                'trial_days' => 14,
                'max_storage' => 1024,
                'sort_order' => 1,
                'is_popular' => false,
                'features_mapping' => [
                    'max_marquees' => '1',
                    'max_branches' => '1',
                    'max_users' => '3',
                    'storage_limit_mb' => '1024',
                ]
            ],
            'standard' => [
                'monthly_price' => 15000.00,
                'quarterly_price' => 42750.00, // 5% discount
                'semi_annual_price' => 81000.00, // 10% discount
                'annual_price' => 153000.00, // 15% discount
                'trial_days' => 14,
                'max_storage' => 5120,
                'sort_order' => 2,
                'is_popular' => true,
                'features_mapping' => [
                    'max_marquees' => '2',
                    'max_branches' => '3',
                    'max_users' => '10',
                    'storage_limit_mb' => '5120',
                    'reports_analytics' => 'Yes',
                ]
            ],
            'premium' => [
                'monthly_price' => 35000.00,
                'quarterly_price' => 99750.00, // 5% discount
                'semi_annual_price' => 189000.00, // 10% discount
                'annual_price' => 357000.00, // 15% discount
                'trial_days' => 14,
                'max_storage' => 20480,
                'sort_order' => 3,
                'is_popular' => false,
                'features_mapping' => [
                    'max_marquees' => '5',
                    'max_branches' => '10',
                    'max_users' => '30',
                    'storage_limit_mb' => '20480',
                    'reports_analytics' => 'Yes',
                    'fbr_pos_integration' => 'Yes',
                ]
            ],
        ];

        foreach ($planData as $slug => $data) {
            $plan = SubscriptionPlan::where('slug', $slug)->first();
            if ($plan) {
                // Update plan attributes
                $plan->update([
                    'monthly_price' => $data['monthly_price'],
                    'quarterly_price' => $data['quarterly_price'],
                    'semi_annual_price' => $data['semi_annual_price'],
                    'annual_price' => $data['annual_price'],
                    'trial_days' => $data['trial_days'],
                    'max_storage' => $data['max_storage'],
                    'sort_order' => $data['sort_order'],
                    'is_popular' => $data['is_popular'],
                ]);

                // Sync Billing Cycles
                $plan->billingCycles()->sync(array_values(array_map(fn($m) => $m->id, $cycleModels)));

                // Sync Features Matrix
                $syncFeatures = [];
                foreach ($data['features_mapping'] as $key => $val) {
                    if (isset($featureModels[$key])) {
                        $syncFeatures[$featureModels[$key]->id] = ['limit_value' => $val];
                    }
                }
                $plan->planFeatures()->sync($syncFeatures);
            }
        }
    }
}
