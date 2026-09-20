<?php

use App\Models\Account;
use App\Models\AccountType;
use App\Models\ExpenseCategory;
use App\Models\Marquee;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $directType = AccountType::firstOrCreate(
            ['code' => 'DIRECT_EXPENSES'],
            ['name' => 'Direct Expenses', 'nature' => 'Expense']
        );

        $operatingType = AccountType::firstOrCreate(
            ['code' => 'OPERATING_EXPENSES'],
            ['name' => 'Operating Expenses', 'nature' => 'Expense']
        );

        $subAccounts = [
            // 1. Direct Costs / Cost of Goods Sold (5100 Series)
            [
                'account_code' => '5101',
                'name' => 'Kitchen Purchases & Raw Food',
                'type_id' => $directType->id,
                'desc' => 'Kitchen grocery, meat, poultry, vegetables, rice, oil & catering food supplies',
            ],
            [
                'account_code' => '5102',
                'name' => 'Generator Rental & Fuel',
                'type_id' => $directType->id,
                'desc' => 'Generator rental and diesel fuel for event power backup',
            ],
            [
                'account_code' => '5103',
                'name' => 'Outsourced Event Services',
                'type_id' => $directType->id,
                'desc' => 'Third-party event services (Stage decor, DJ/sound, valet, extra waiters, floral)',
            ],
            [
                'account_code' => '5104',
                'name' => 'Daily Wages & Event Labor',
                'type_id' => $directType->id,
                'desc' => 'Daily paid dishwashers, helpers, temporary hall setup and cleanup labor',
            ],
            [
                'account_code' => '5105',
                'name' => 'Laundry & Dry Cleaning',
                'type_id' => $directType->id,
                'desc' => 'Washing and pressing of chair covers, tablecloths, napkins and table skirts',
            ],
            [
                'account_code' => '5106',
                'name' => 'Cartage & Transport Expenses',
                'type_id' => $directType->id,
                'desc' => 'Freight and cartage for event supplies, equipment, gas cylinders and goods movement',
            ],

            // 2. Utilities & Energy (5200 Series)
            [
                'account_code' => '5201',
                'name' => 'Electricity Expenses (WAPDA/LESCO)',
                'type_id' => $operatingType->id,
                'desc' => 'Grid electricity bills (WAPDA / LESCO / GEPCO / FESCO)',
            ],
            [
                'account_code' => '5202',
                'name' => 'Sui Gas / SNGPL Expenses',
                'type_id' => $operatingType->id,
                'desc' => 'Piped gas bills for kitchen stoves, tandoors, ovens and geysers',
            ],
            [
                'account_code' => '5203',
                'name' => 'Water Supply & Sewerage (WASA)',
                'type_id' => $operatingType->id,
                'desc' => 'Water and Sanitation Agency bills and commercial water supply',
            ],
            [
                'account_code' => '5204',
                'name' => 'Internet & Communication',
                'type_id' => $operatingType->id,
                'desc' => 'Broadband, optical fiber internet, CCTV networking, telephone lines',
            ],

            // 3. Administrative & General Overheads (5300 Series)
            [
                'account_code' => '5301',
                'name' => 'Marquee Ground & Building Rent',
                'type_id' => $operatingType->id,
                'desc' => 'Monthly lease or rent for marquee land and hall premises',
            ],
            [
                'account_code' => '5302',
                'name' => 'Permanent Staff Salaries',
                'type_id' => $operatingType->id,
                'desc' => 'Monthly salaries for managers, accountants, permanent chefs, supervisors, security',
            ],
            [
                'account_code' => '5303',
                'name' => 'EOBI Contribution',
                'type_id' => $operatingType->id,
                'desc' => 'Employees Old-Age Benefits Institution employer statutory contributions',
            ],
            [
                'account_code' => '5304',
                'name' => 'Social Security (PESSI)',
                'type_id' => $operatingType->id,
                'desc' => 'Provincial Employees Social Security Institution contributions for healthcare',
            ],
            [
                'account_code' => '5305',
                'name' => 'Staff Uniforms & Protective Gear',
                'type_id' => $operatingType->id,
                'desc' => 'Waiter uniforms, service suits, chef coats, kitchen aprons, caps',
            ],
            [
                'account_code' => '5306',
                'name' => 'Building Maintenance & Paint',
                'type_id' => $operatingType->id,
                'desc' => 'Hall touch-up paint, pre-season renovation, plaster repairs, building upkeep',
            ],
            [
                'account_code' => '5307',
                'name' => 'Office Stationery & Printing',
                'type_id' => $operatingType->id,
                'desc' => 'Booking contract slips, receipts, menu cards, printer toner, paper, stationery',
            ],
            [
                'account_code' => '5308',
                'name' => 'Staff & Guest Refreshment',
                'type_id' => $operatingType->id,
                'desc' => 'Office tea, mineral water, coffee, biscuits, client hospitality refreshments',
            ],
            [
                'account_code' => '5309',
                'name' => 'Traveling & Conveyance',
                'type_id' => $operatingType->id,
                'desc' => 'Staff local travel, motorcycle fuel, conveyance allowance for market visits',
            ],
            [
                'account_code' => '5310',
                'name' => 'Legal & Professional Charges',
                'type_id' => $operatingType->id,
                'desc' => 'Legal retainers, lawyer fees, municipal trade licenses, compliance, auditor fees',
            ],
            [
                'account_code' => '5311',
                'name' => 'Miscellaneous & General Overheads',
                'type_id' => $operatingType->id,
                'desc' => 'Cleaning brooms, mops, trash bags, pest control, air fresheners, incidental items',
            ],

            // 4. Selling & Marketing (5400 Series)
            [
                'account_code' => '5401',
                'name' => 'Event Commission & Referral Fees',
                'type_id' => $operatingType->id,
                'desc' => 'Booking commissions and referral fees paid to event planners and agents',
            ],

            // 5. Financial Charges (5600 Series)
            [
                'account_code' => '5601',
                'name' => 'Bank Service Charges & Fees',
                'type_id' => $operatingType->id,
                'desc' => 'Bank account maintenance, cheque book fees, card POS swipe machine MDR charges',
            ],

            // 6. CSR & Donations (5700 Series)
            [
                'account_code' => '5701',
                'name' => 'Charity, Zakat & Donations',
                'type_id' => $operatingType->id,
                'desc' => 'Social welfare donations, employee emergency financial assistance, Zakat/Sadqa',
            ],

            // 7. Taxes (5800 Series)
            [
                'account_code' => '5801',
                'name' => 'Withholding Tax / Tax Deductions',
                'type_id' => $operatingType->id,
                'desc' => 'Non-adjustable tax deductions, banking transaction taxes, or pass-through tracking',
            ],
            [
                'account_code' => '5802',
                'name' => 'Sales Tax Expense (PRA/FBR)',
                'type_id' => $operatingType->id,
                'desc' => 'Sales tax paid on unadjusted bills and services treated as direct overhead',
            ],
        ];

        $categoriesData = [
            ['code' => 'KITCHEN', 'name' => 'Kitchen Purchase', 'gl_code' => '5101', 'order' => 1],
            ['code' => 'GEN_DIESEL', 'name' => 'Generator Rent + Diesel', 'gl_code' => '5102', 'order' => 2],
            ['code' => 'OUTSOURCE', 'name' => 'Out Sources', 'gl_code' => '5103', 'order' => 3],
            ['code' => 'DAILY_WAGES', 'name' => 'Daily Wages', 'gl_code' => '5104', 'order' => 4],
            ['code' => 'LAUNDRY', 'name' => 'Laundry', 'gl_code' => '5105', 'order' => 5],
            ['code' => 'TRANSPORT', 'name' => 'Transport', 'gl_code' => '5106', 'order' => 6],
            ['code' => 'ELEC_BILL', 'name' => 'Electricity Bill', 'gl_code' => '5201', 'order' => 7],
            ['code' => 'SNGPL_BILL', 'name' => 'SNGPL Bill', 'gl_code' => '5202', 'order' => 8],
            ['code' => 'WASA_BILL', 'name' => 'Wasa Bill', 'gl_code' => '5203', 'order' => 9],
            ['code' => 'INTERNET_BILL', 'name' => 'Internet Bill', 'gl_code' => '5204', 'order' => 10],
            ['code' => 'RENT', 'name' => 'Rent', 'gl_code' => '5301', 'order' => 11],
            ['code' => 'SALARY', 'name' => 'Salary', 'gl_code' => '5302', 'order' => 12],
            ['code' => 'EOBI', 'name' => 'EOBI', 'gl_code' => '5303', 'order' => 13],
            ['code' => 'SOC_SEC', 'name' => 'Social Security', 'gl_code' => '5304', 'order' => 14],
            ['code' => 'UNIFORM', 'name' => 'Uniform', 'gl_code' => '5305', 'order' => 15],
            ['code' => 'PAINT', 'name' => 'Paint', 'gl_code' => '5306', 'order' => 16],
            ['code' => 'STATIONERY', 'name' => 'Stationery', 'gl_code' => '5307', 'order' => 17],
            ['code' => 'REFRESHMENT', 'name' => 'Refreshment', 'gl_code' => '5308', 'order' => 18],
            ['code' => 'TRAVELING', 'name' => 'Traveling', 'gl_code' => '5309', 'order' => 19],
            ['code' => 'LEGAL', 'name' => 'Legal', 'gl_code' => '5310', 'order' => 20],
            ['code' => 'MISC_GEN', 'name' => 'Misc/General', 'gl_code' => '5311', 'order' => 21],
            ['code' => 'COMMISSION', 'name' => 'Commission', 'gl_code' => '5401', 'order' => 22],
            ['code' => 'BANK_CHARGES', 'name' => 'Bank Charges', 'gl_code' => '5601', 'order' => 23],
            ['code' => 'CHARITY', 'name' => 'Charity', 'gl_code' => '5701', 'order' => 24],
            ['code' => 'WHT_TAX', 'name' => 'With Holding Taxes', 'gl_code' => '5801', 'order' => 25],
            ['code' => 'SALES_TAX', 'name' => 'Sales Taxes', 'gl_code' => '5802', 'order' => 26],
        ];

        $marquees = Marquee::all();

        foreach ($marquees as $marquee) {
            // Ensure 5000 root expense account exists
            $parentExpense = Account::firstOrCreate(
                [
                    'marquee_id' => $marquee->id,
                    'account_code' => '5000',
                ],
                [
                    'name' => 'Expenses',
                    'parent_id' => null,
                    'account_type_id' => $directType->id,
                    'nature' => 'Expense',
                    'is_active' => true,
                    'system_generated' => true,
                    'description' => 'Root account for Expenses',
                ]
            );

            // Create or update each of the 26 accounts
            foreach ($subAccounts as $sub) {
                Account::updateOrCreate(
                    [
                        'marquee_id' => $marquee->id,
                        'account_code' => $sub['account_code'],
                    ],
                    [
                        'name' => $sub['name'],
                        'parent_id' => $parentExpense->id,
                        'account_type_id' => $sub['type_id'],
                        'nature' => 'Expense',
                        'is_active' => true,
                        'system_generated' => true,
                        'description' => $sub['desc'],
                    ]
                );
            }

            // Create or update each of the 26 categories and map default_account_id
            foreach ($categoriesData as $cData) {
                $acc = Account::where('marquee_id', $marquee->id)->where('account_code', $cData['gl_code'])->first();
                ExpenseCategory::updateOrCreate(
                    [
                        'marquee_id' => $marquee->id,
                        'category_code' => $cData['code'],
                    ],
                    [
                        'name' => $cData['name'],
                        'parent_id' => null,
                        'default_account_id' => $acc?->id,
                        'default_tax_rate' => 0.00,
                        'default_budget_amount' => 0.00,
                        'display_order' => $cData['order'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive: preserve user accounting data
    }
};
