<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Marquee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountingModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed global Account Types
        $accountTypes = [
            // Assets
            ['name' => 'Current Assets', 'code' => 'CURRENT_ASSETS', 'nature' => 'Asset'],
            ['name' => 'Fixed Assets', 'code' => 'FIXED_ASSETS', 'nature' => 'Asset'],
            // Liabilities
            ['name' => 'Current Liabilities', 'code' => 'CURRENT_LIABILITIES', 'nature' => 'Liability'],
            ['name' => 'Long-Term Liabilities', 'code' => 'LONG_TERM_LIABILITIES', 'nature' => 'Liability'],
            // Equity
            ['name' => 'Owner Equity', 'code' => 'OWNER_EQUITY', 'nature' => 'Equity'],
            ['name' => 'Retained Earnings', 'code' => 'RETAINED_EARNINGS', 'nature' => 'Equity'],
            // Income
            ['name' => 'Operating Revenue', 'code' => 'OPERATING_REVENUE', 'nature' => 'Income'],
            ['name' => 'Other Income', 'code' => 'OTHER_INCOME', 'nature' => 'Income'],
            // Expenses
            ['name' => 'Direct Expenses', 'code' => 'DIRECT_EXPENSES', 'nature' => 'Expense'],
            ['name' => 'Operating Expenses', 'code' => 'OPERATING_EXPENSES', 'nature' => 'Expense'],
        ];

        $seededTypes = [];
        foreach ($accountTypes as $type) {
            $seededTypes[$type['code']] = AccountType::updateOrCreate(
                ['code' => $type['code'], 'marquee_id' => null],
                [
                    'name' => $type['name'],
                    'nature' => $type['nature'],
                ]
            );
        }

        // 2. Seed default hierarchical Chart of Accounts for all Marquee tenants
        $marquees = Marquee::all();
        foreach ($marquees as $marquee) {
            DB::transaction(function () use ($marquee, $seededTypes) {
                // Ensure default top-level accounts exist
                $topLevelAccounts = [
                    '1000' => ['name' => 'Assets', 'nature' => 'Asset', 'code_type' => 'CURRENT_ASSETS'],
                    '2000' => ['name' => 'Liabilities', 'nature' => 'Liability', 'code_type' => 'CURRENT_LIABILITIES'],
                    '3000' => ['name' => 'Equity', 'nature' => 'Equity', 'code_type' => 'OWNER_EQUITY'],
                    '4000' => ['name' => 'Income', 'nature' => 'Income', 'code_type' => 'OPERATING_REVENUE'],
                    '5000' => ['name' => 'Expenses', 'nature' => 'Expense', 'code_type' => 'DIRECT_EXPENSES'],
                ];

                $topLevelInstances = [];

                foreach ($topLevelAccounts as $code => $data) {
                    $topLevelInstances[$code] = Account::updateOrCreate(
                        [
                            'marquee_id' => $marquee->id,
                            'account_code' => $code,
                        ],
                        [
                            'name' => $data['name'],
                            'parent_id' => null,
                            'account_type_id' => $seededTypes[$data['code_type']]->id,
                            'nature' => $data['nature'],
                            'is_active' => true,
                            'system_generated' => true,
                            'description' => "Root account for {$data['name']}",
                        ]
                    );
                }

                // Define sub-accounts and hierarchy
                $subAccounts = [
                    // Assets sub-accounts
                    [
                        'parent_code' => '1000',
                        'account_code' => '1001',
                        'name' => 'Cash',
                        'type_code' => 'CURRENT_ASSETS',
                        'nature' => 'Asset',
                        'system' => true,
                        'desc' => 'General Cash Account',
                    ],
                    [
                        'parent_code' => '1000',
                        'account_code' => '1002',
                        'name' => 'Bank',
                        'type_code' => 'CURRENT_ASSETS',
                        'nature' => 'Asset',
                        'system' => true,
                        'desc' => 'Default Bank Account',
                    ],
                    [
                        'parent_code' => '1000',
                        'account_code' => '1003',
                        'name' => 'Accounts Receivable',
                        'type_code' => 'CURRENT_ASSETS',
                        'nature' => 'Asset',
                        'system' => true,
                        'desc' => 'Outstanding Customer Payments',
                    ],
                    [
                        'parent_code' => '1000',
                        'account_code' => '1004',
                        'name' => 'Inventory',
                        'type_code' => 'CURRENT_ASSETS',
                        'nature' => 'Asset',
                        'system' => true,
                        'desc' => 'Inventory Assets',
                    ],
                    [
                        'parent_code' => '1000',
                        'account_code' => '1201',
                        'name' => 'Furniture & Fixtures',
                        'type_code' => 'FIXED_ASSETS',
                        'nature' => 'Asset',
                        'system' => false,
                        'desc' => 'Furniture Fixed Assets',
                    ],

                    // Liabilities sub-accounts
                    [
                        'parent_code' => '2000',
                        'account_code' => '2001',
                        'name' => 'Accounts Payable',
                        'type_code' => 'CURRENT_LIABILITIES',
                        'nature' => 'Liability',
                        'system' => true,
                        'desc' => 'Outstanding Vendor Payments',
                    ],
                    [
                        'parent_code' => '2000',
                        'account_code' => '2002',
                        'name' => 'Security Deposits',
                        'type_code' => 'CURRENT_LIABILITIES',
                        'nature' => 'Liability',
                        'system' => true,
                        'desc' => 'Refundable Booking Security Deposits',
                    ],

                    // Equity sub-accounts
                    [
                        'parent_code' => '3000',
                        'account_code' => '3001',
                        'name' => 'Owner\'s Capital',
                        'type_code' => 'OWNER_EQUITY',
                        'nature' => 'Equity',
                        'system' => true,
                        'desc' => 'Capital Invested by Owner',
                    ],
                    [
                        'parent_code' => '3000',
                        'account_code' => '3501',
                        'name' => 'Retained Earnings',
                        'type_code' => 'RETAINED_EARNINGS',
                        'nature' => 'Equity',
                        'system' => true,
                        'desc' => 'Accumulated Earnings',
                    ],

                    // Income sub-accounts
                    [
                        'parent_code' => '4000',
                        'account_code' => '4001',
                        'name' => 'Hall Booking Revenue',
                        'type_code' => 'OPERATING_REVENUE',
                        'nature' => 'Income',
                        'system' => true,
                        'desc' => 'Revenue from Hall Bookings',
                    ],
                    [
                        'parent_code' => '4000',
                        'account_code' => '4002',
                        'name' => 'Catering Revenue',
                        'type_code' => 'OPERATING_REVENUE',
                        'nature' => 'Income',
                        'system' => true,
                        'desc' => 'Revenue from Catering Services',
                    ],
                    [
                        'parent_code' => '4000',
                        'account_code' => '4003',
                        'name' => 'Decoration Revenue',
                        'type_code' => 'OPERATING_REVENUE',
                        'nature' => 'Income',
                        'system' => true,
                        'desc' => 'Revenue from Hall Decoration Services',
                    ],

                    // Expenses sub-accounts
                    [
                        'parent_code' => '5000',
                        'account_code' => '5501',
                        'name' => 'Salaries',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Employee Salaries',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5502',
                        'name' => 'Utilities',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Electricity, Gas, and Water Bills',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5503',
                        'name' => 'Maintenance',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Hall Repair & Maintenance Costs',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5504',
                        'name' => 'Marketing',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => false,
                        'desc' => 'Advertising & Marketing Expenses',
                    ],
                ];

                foreach ($subAccounts as $sub) {
                    $parentInstance = $topLevelInstances[$sub['parent_code']];
                    Account::updateOrCreate(
                        [
                            'marquee_id' => $marquee->id,
                            'account_code' => $sub['account_code'],
                        ],
                        [
                            'name' => $sub['name'],
                            'parent_id' => $parentInstance->id,
                            'account_type_id' => $seededTypes[$sub['type_code']]->id,
                            'nature' => $sub['nature'],
                            'is_active' => true,
                            'system_generated' => $sub['system'],
                            'description' => $sub['desc'],
                        ]
                    );
                }

                // Seed active Financial Years (5-year window around current year)
                $currentYear = (int) date('Y');
                for ($year = $currentYear - 2; $year <= $currentYear + 2; $year++) {
                    \App\Models\FinancialYear::updateOrCreate(
                        [
                            'marquee_id' => $marquee->id,
                            'name' => "FY " . $year,
                        ],
                        [
                            'start_date' => $year . "-01-01",
                            'end_date' => $year . "-12-31",
                            'status' => 'active',
                            'is_default' => ($year === $currentYear),
                            'created_by' => null,
                        ]
                    );
                }
            });
        }
    }
}
