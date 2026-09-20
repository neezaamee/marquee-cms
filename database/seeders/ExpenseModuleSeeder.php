<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseType;
use App\Models\ExpenseBudget;
use App\Models\ExpenseApprovalRule;
use App\Models\Marquee;
use App\Models\Permission;
use App\Models\PettyCashAccount;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed & link Permissions
        $permissions = [
            ['name' => 'view_expenses', 'label' => 'View Expenses'],
            ['name' => 'create_expenses', 'label' => 'Create Expenses'],
            ['name' => 'edit_expenses', 'label' => 'Edit Expenses'],
            ['name' => 'approve_expenses', 'label' => 'Approve Expenses'],
            ['name' => 'manage_expense_settings', 'label' => 'Manage Expense Settings'],
        ];

        $permsLinked = [];
        foreach ($permissions as $p) {
            $permsLinked[] = Permission::updateOrCreate(
                ['name' => $p['name']],
                ['label' => $p['label']]
            );
        }

        // Link to Owner and Accountant roles
        $rolesToSync = Role::whereIn('name', ['owner', 'accountant', 'branch_manager'])->get();
        foreach ($rolesToSync as $role) {
            $currentPerms = $role->permissions()->pluck('permissions.id')->toArray();
            foreach ($permsLinked as $perm) {
                if (!in_array($perm->id, $currentPerms)) {
                    $currentPerms[] = $perm->id;
                }
            }
            $role->permissions()->sync($currentPerms);
        }

        // 2. Setup tenant specific mock records
        $marquees = Marquee::all();
        foreach ($marquees as $marquee) {
            DB::transaction(function () use ($marquee) {
                // Base Currency
                $pkr = Currency::updateOrCreate(
                    ['marquee_id' => $marquee->id, 'code' => 'PKR'],
                    ['name' => 'Pakistani Rupee', 'symbol' => 'Rs.', 'is_base' => true, 'exchange_rate' => 1.000000]
                );

                $usd = Currency::updateOrCreate(
                    ['marquee_id' => $marquee->id, 'code' => 'USD'],
                    ['name' => 'US Dollar', 'symbol' => '$', 'is_base' => false, 'exchange_rate' => 0.003600] // 1 PKR = 0.0036 USD, i.e. 278 PKR per USD
                );

                // Expense Types
                $types = [
                    ['name' => 'Utility Bills', 'code' => 'utility_bills', 'desc' => 'Electric, gas, water, internet charges'],
                    ['name' => 'Repairs & Maintenance', 'code' => 'maintenance', 'desc' => 'Repair and upkeep of assets'],
                    ['name' => 'Staff Salaries & Welfare', 'code' => 'salaries', 'desc' => 'Salary disbursements and meals'],
                    ['name' => 'Marketing & Sales', 'code' => 'marketing', 'desc' => 'Advertising and promo campaigns'],
                    ['name' => 'Vendor Payments', 'code' => 'vendor_payments', 'desc' => 'Purchasing inventory or rentals'],
                    ['name' => 'Miscellaneous', 'code' => 'miscellaneous', 'desc' => 'Other overheads'],
                ];

                $seededTypes = [];
                foreach ($types as $t) {
                    $seededTypes[$t['code']] = ExpenseType::updateOrCreate(
                        ['marquee_id' => $marquee->id, 'code' => $t['code']],
                        ['name' => $t['name'], 'description' => $t['desc'], 'is_active' => true]
                    );
                }

                // 26 Standard Marquee Operational Expense Categories
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

                $catElec = null;
                $catMaint = null;

                foreach ($categoriesData as $cData) {
                    $acc = Account::where('marquee_id', $marquee->id)->where('account_code', $cData['gl_code'])->first();
                    $createdCat = ExpenseCategory::updateOrCreate(
                        ['marquee_id' => $marquee->id, 'category_code' => $cData['code']],
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

                    if ($cData['code'] === 'ELEC_BILL') {
                        $catElec = $createdCat;
                    }
                    if ($cData['code'] === 'PAINT') {
                        $catMaint = $createdCat;
                    }
                }

                // Fetch a default branch
                $branch = Branch::where('marquee_id', $marquee->id)->first();
                if (!$branch) {
                    return;
                }

                // Custodian / User
                $custodian = User::where('marquee_id', $marquee->id)->first();

                // Petty Cash Accounts
                $pettyGL = Account::where('marquee_id', $marquee->id)->where('account_code', '1001')->first(); // Cash on Hand
                $pettyDrawer = PettyCashAccount::updateOrCreate(
                    ['marquee_id' => $marquee->id, 'branch_id' => $branch->id, 'account_name' => 'Main Reception Cash Drawer'],
                    ['gl_account_id' => $pettyGL?->id, 'custodian_id' => $custodian?->id, 'limit_amount' => 50000.00, 'current_balance' => 35000.00, 'is_active' => true]
                );

                // Budgets
                ExpenseBudget::updateOrCreate(
                    ['marquee_id' => $marquee->id, 'branch_id' => $branch->id, 'category_id' => $catElec->id, 'year' => 2026, 'month' => 8],
                    ['allocated_amount' => 80000.00, 'consumed_amount' => 0.00]
                );

                ExpenseBudget::updateOrCreate(
                    ['marquee_id' => $marquee->id, 'branch_id' => $branch->id, 'category_id' => $catMaint->id, 'year' => 2026, 'month' => null],
                    ['allocated_amount' => 120000.00, 'consumed_amount' => 0.00]
                );

                // Configurable Approval Rules
                $managerRole = Role::where('name', 'branch_manager')->first();
                $ownerRole = Role::where('name', 'owner')->first();

                if ($managerRole) {
                    ExpenseApprovalRule::updateOrCreate(
                        ['marquee_id' => $marquee->id, 'branch_id' => $branch->id, 'min_amount' => 0.00, 'approver_role_id' => $managerRole->id],
                        ['sequence' => 1]
                    );
                }

                if ($ownerRole) {
                    ExpenseApprovalRule::updateOrCreate(
                        ['marquee_id' => $marquee->id, 'branch_id' => $branch->id, 'min_amount' => 100000.00, 'approver_role_id' => $ownerRole->id],
                        ['sequence' => 2]
                    );
                }

                // Seed some draft expenses
                $draftExpense = Expense::create([
                    'marquee_id' => $marquee->id,
                    'branch_id' => $branch->id,
                    'expense_number' => 'EXP-20260805-00001',
                    'expense_date' => now(),
                    'department' => 'Administration',
                    'expense_category_id' => $catPromo->id,
                    'expense_type_id' => $seededTypes['marketing']->id,
                    'currency_id' => $pkr->id,
                    'exchange_rate' => 1.000000,
                    'description' => 'Social Media post ads for booking promotion',
                    'amount' => 15000.00,
                    'tax_amount' => 0.00,
                    'discount_amount' => 0.00,
                    'total_amount' => 15000.00,
                    'total_amount_base' => 15000.00,
                    'payment_method' => Expense::METHOD_CASH,
                    'status' => Expense::STATUS_DRAFT,
                ]);
            });
        }
    }
}
