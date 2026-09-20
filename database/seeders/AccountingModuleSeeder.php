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
                    [
                        'parent_code' => '2000',
                        'account_code' => '2003',
                        'name' => 'Customer Advances / Contract Liabilities',
                        'type_code' => 'CURRENT_LIABILITIES',
                        'nature' => 'Liability',
                        'system' => true,
                        'desc' => 'Unearned Customer Booking Advances & Contract Liabilities',
                    ],
                    [
                        'parent_code' => '2000',
                        'account_code' => '2004',
                        'name' => 'Sales Tax Payable',
                        'type_code' => 'CURRENT_LIABILITIES',
                        'nature' => 'Liability',
                        'system' => true,
                        'desc' => 'Sales Tax & Government Levies Payable',
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
                    [
                        'parent_code' => '4000',
                        'account_code' => '4004',
                        'name' => 'Cancellation Charges Income',
                        'type_code' => 'OPERATING_REVENUE',
                        'nature' => 'Income',
                        'system' => true,
                        'desc' => 'Earned Cancellation Penalties & Retained Forfeitures',
                    ],

                    // Expenses sub-accounts
                    // 1. Direct Costs / Cost of Goods Sold (5100 Series)
                    [
                        'parent_code' => '5000',
                        'account_code' => '5101',
                        'name' => 'Kitchen Purchases & Raw Food',
                        'type_code' => 'DIRECT_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Kitchen grocery, meat, poultry, vegetables, rice, oil & catering food supplies',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5102',
                        'name' => 'Generator Rental & Fuel',
                        'type_code' => 'DIRECT_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Generator rental and diesel fuel for event power backup',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5103',
                        'name' => 'Outsourced Event Services',
                        'type_code' => 'DIRECT_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Third-party event services (Stage decor, DJ/sound, valet, extra waiters, floral)',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5104',
                        'name' => 'Daily Wages & Event Labor',
                        'type_code' => 'DIRECT_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Daily paid dishwashers, helpers, temporary hall setup and cleanup labor',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5105',
                        'name' => 'Laundry & Dry Cleaning',
                        'type_code' => 'DIRECT_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Washing and pressing of chair covers, tablecloths, napkins and table skirts',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5106',
                        'name' => 'Cartage & Transport Expenses',
                        'type_code' => 'DIRECT_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Freight and cartage for event supplies, equipment, gas cylinders and goods movement',
                    ],

                    // 2. Utilities & Energy (5200 Series)
                    [
                        'parent_code' => '5000',
                        'account_code' => '5201',
                        'name' => 'Electricity Expenses (WAPDA/LESCO)',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Grid electricity bills (WAPDA / LESCO / GEPCO / FESCO)',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5202',
                        'name' => 'Sui Gas / SNGPL Expenses',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Piped gas bills for kitchen stoves, tandoors, ovens and geysers',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5203',
                        'name' => 'Water Supply & Sewerage (WASA)',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Water and Sanitation Agency bills and commercial water supply',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5204',
                        'name' => 'Internet & Communication',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Broadband, optical fiber internet, CCTV networking, telephone lines',
                    ],

                    // 3. Administrative & General Overheads (5300 Series)
                    [
                        'parent_code' => '5000',
                        'account_code' => '5301',
                        'name' => 'Marquee Ground & Building Rent',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Monthly lease or rent for marquee land and hall premises',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5302',
                        'name' => 'Permanent Staff Salaries',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Monthly salaries for managers, accountants, permanent chefs, supervisors, security',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5303',
                        'name' => 'EOBI Contribution',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Employees Old-Age Benefits Institution employer statutory contributions',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5304',
                        'name' => 'Social Security (PESSI)',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Provincial Employees Social Security Institution contributions for healthcare',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5305',
                        'name' => 'Staff Uniforms & Protective Gear',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Waiter uniforms, service suits, chef coats, kitchen aprons, caps',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5306',
                        'name' => 'Building Maintenance & Paint',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Hall touch-up paint, pre-season renovation, plaster repairs, building upkeep',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5307',
                        'name' => 'Office Stationery & Printing',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Booking contract slips, receipts, menu cards, printer toner, paper, stationery',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5308',
                        'name' => 'Staff & Guest Refreshment',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Office tea, mineral water, coffee, biscuits, client hospitality refreshments',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5309',
                        'name' => 'Traveling & Conveyance',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Staff local travel, motorcycle fuel, conveyance allowance for market visits',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5310',
                        'name' => 'Legal & Professional Charges',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Legal retainers, lawyer fees, municipal trade licenses, compliance, auditor fees',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5311',
                        'name' => 'Miscellaneous & General Overheads',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Cleaning brooms, mops, trash bags, pest control, air fresheners, incidental items',
                    ],

                    // 4. Selling & Marketing (5400 Series)
                    [
                        'parent_code' => '5000',
                        'account_code' => '5401',
                        'name' => 'Event Commission & Referral Fees',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Booking commissions and referral fees paid to event planners and agents',
                    ],

                    // 5. Legacy/Existing Accounts (Preserved for compatibility)
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

                    // 6. Financial Charges (5600 Series)
                    [
                        'parent_code' => '5000',
                        'account_code' => '5601',
                        'name' => 'Bank Service Charges & Fees',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Bank account maintenance, cheque book fees, card POS swipe machine MDR charges',
                    ],

                    // 7. CSR & Donations (5700 Series)
                    [
                        'parent_code' => '5000',
                        'account_code' => '5701',
                        'name' => 'Charity, Zakat & Donations',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Social welfare donations, employee emergency financial assistance, Zakat/Sadqa',
                    ],

                    // 8. Taxes (5800 Series)
                    [
                        'parent_code' => '5000',
                        'account_code' => '5801',
                        'name' => 'Withholding Tax / Tax Deductions',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Non-adjustable tax deductions, banking transaction taxes, or pass-through tracking',
                    ],
                    [
                        'parent_code' => '5000',
                        'account_code' => '5802',
                        'name' => 'Sales Tax Expense (PRA/FBR)',
                        'type_code' => 'OPERATING_EXPENSES',
                        'nature' => 'Expense',
                        'system' => true,
                        'desc' => 'Sales tax paid on unadjusted bills and services treated as direct overhead',
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
