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

                // Retrieve Mapped Accounts from pre-seeded Chart of Accounts
                $salaryAccount = Account::where('marquee_id', $marquee->id)->where('account_code', '5501')->first();
                $utilityAccount = Account::where('marquee_id', $marquee->id)->where('account_code', '5502')->first();
                $maintenanceAccount = Account::where('marquee_id', $marquee->id)->where('account_code', '5503')->first();
                $marketingAccount = Account::where('marquee_id', $marquee->id)->where('account_code', '5504')->first();

                // Categories
                $catSalaries = ExpenseCategory::updateOrCreate(
                    ['marquee_id' => $marquee->id, 'category_code' => 'SAL'],
                    ['name' => 'Salaries & Advances', 'parent_id' => null, 'default_account_id' => $salaryAccount?->id, 'default_tax_rate' => 0.00, 'default_budget_amount' => 500000.00, 'display_order' => 1, 'is_active' => true]
                );

                $catUtilities = ExpenseCategory::updateOrCreate(
                    ['marquee_id' => $marquee->id, 'category_code' => 'UTL'],
                    ['name' => 'Utilities (Bills)', 'parent_id' => null, 'default_account_id' => $utilityAccount?->id, 'default_tax_rate' => 15.00, 'default_budget_amount' => 150000.00, 'display_order' => 2, 'is_active' => true]
                );

                $catElec = ExpenseCategory::updateOrCreate(
                    ['marquee_id' => $marquee->id, 'category_code' => 'UTL-E'],
                    ['name' => 'Electricity Bills', 'parent_id' => $catUtilities->id, 'default_account_id' => $utilityAccount?->id, 'default_tax_rate' => 17.00, 'default_budget_amount' => 100000.00, 'display_order' => 1, 'is_active' => true]
                );

                $catMaint = ExpenseCategory::updateOrCreate(
                    ['marquee_id' => $marquee->id, 'category_code' => 'MNT'],
                    ['name' => 'Maintenance Repairs', 'parent_id' => null, 'default_account_id' => $maintenanceAccount?->id, 'default_tax_rate' => 5.00, 'default_budget_amount' => 80000.00, 'display_order' => 3, 'is_active' => true]
                );

                $catPromo = ExpenseCategory::updateOrCreate(
                    ['marquee_id' => $marquee->id, 'category_code' => 'MKT'],
                    ['name' => 'Marketing Campaigns', 'parent_id' => null, 'default_account_id' => $marketingAccount?->id, 'default_tax_rate' => 0.00, 'default_budget_amount' => 120000.00, 'display_order' => 4, 'is_active' => true]
                );

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
