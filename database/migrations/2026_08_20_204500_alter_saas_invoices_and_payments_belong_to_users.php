<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add max_marquees to subscription_plans
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->integer('max_marquees')->default(1)->after('max_branches');
        });

        // 2. Make marquee_id nullable in financial_years
        Schema::table('financial_years', function (Blueprint $table) {
            $table->dropForeign(['marquee_id']);
            $table->unsignedBigInteger('marquee_id')->nullable()->change();
            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
        });

        // 3. Make marquee_id and financial_year_id nullable in journal_vouchers
        Schema::table('journal_vouchers', function (Blueprint $table) {
            $table->dropForeign(['marquee_id']);
            $table->dropForeign(['financial_year_id']);
            
            $table->unsignedBigInteger('marquee_id')->nullable()->change();
            $table->unsignedBigInteger('financial_year_id')->nullable()->change();
            
            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
            $table->foreign('financial_year_id')->references('id')->on('financial_years')->onDelete('cascade');
        });

        // 4. Make marquee_id nullable in accounts
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropUnique(['marquee_id', 'account_code']);
            $table->dropForeign(['marquee_id']);
            
            $table->unsignedBigInteger('marquee_id')->nullable()->change();
            
            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
            $table->unique(['marquee_id', 'account_code']);
        });

        // 5. Alter saas_invoices to change marquee_id to user_id
        Schema::table('saas_invoices', function (Blueprint $table) {
            $table->dropIndex(['marquee_id', 'invoice_status']);
            $table->dropForeign(['marquee_id']);
            $table->dropColumn('marquee_id');
            $table->foreignId('user_id')->after('invoice_number')->constrained('users')->onDelete('cascade');
            
            $table->index(['user_id', 'invoice_status']);
        });

        // 6. Alter saas_payments to change marquee_id to user_id
        Schema::table('saas_payments', function (Blueprint $table) {
            $table->dropForeign(['marquee_id']);
            $table->dropColumn('marquee_id');
            $table->foreignId('user_id')->after('invoice_id')->constrained('users')->onDelete('cascade');
        });

        // 7. Seed Super Admin (Platform) Financial Year
        DB::table('financial_years')->insert([
            'marquee_id' => null,
            'name' => 'SaaS Platform FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 8. Seed Super Admin hierarchical Chart of Accounts (where marquee_id is null)
        $this->seedSuperAdminCOA();
    }

    private function seedSuperAdminCOA()
    {
        $types = DB::table('account_types')->whereNull('marquee_id')->get()->keyBy('code');

        if ($types->isEmpty()) {
            return;
        }

        // Roots
        $roots = [
            '1000' => ['name' => 'Assets', 'nature' => 'Asset', 'type' => 'CURRENT_ASSETS'],
            '2000' => ['name' => 'Liabilities', 'nature' => 'Liability', 'type' => 'CURRENT_LIABILITIES'],
            '3000' => ['name' => 'Equity', 'nature' => 'Equity', 'type' => 'OWNER_EQUITY'],
            '4000' => ['name' => 'Income', 'nature' => 'Income', 'type' => 'OPERATING_REVENUE'],
            '5000' => ['name' => 'Expenses', 'nature' => 'Expense', 'type' => 'DIRECT_EXPENSES'],
        ];

        $rootIds = [];
        foreach ($roots as $code => $data) {
            $rootIds[$code] = DB::table('accounts')->insertGetId([
                'marquee_id' => null,
                'account_code' => $code,
                'name' => $data['name'],
                'parent_id' => null,
                'account_type_id' => $types[$data['type']]->id,
                'nature' => $data['nature'],
                'description' => "Root account for {$data['name']}",
                'is_active' => true,
                'system_generated' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Sub accounts
        $subs = [
            // Assets
            ['parent_code' => '1000', 'code' => '1001', 'name' => 'SaaS Cash on Hand', 'type' => 'CURRENT_ASSETS', 'nature' => 'Asset', 'desc' => 'Cash received from subscription payments'],
            ['parent_code' => '1000', 'code' => '1002', 'name' => 'SaaS Bank Account', 'type' => 'CURRENT_ASSETS', 'nature' => 'Asset', 'desc' => 'Bank / Stripe Account for online payments'],
            ['parent_code' => '1000', 'code' => '1003', 'name' => 'Accounts Receivable (SaaS)', 'type' => 'CURRENT_ASSETS', 'nature' => 'Asset', 'desc' => 'Subscription payments due from owners'],
            
            // Liabilities
            ['parent_code' => '2000', 'code' => '2001', 'name' => 'Deferred SaaS Revenue', 'type' => 'CURRENT_LIABILITIES', 'nature' => 'Liability', 'desc' => 'Unearned subscription revenue'],

            // Equity
            ['parent_code' => '3000', 'code' => '3001', 'name' => 'Platform Retained Earnings', 'type' => 'RETAINED_EARNINGS', 'nature' => 'Equity', 'desc' => 'Platform accumulated earnings'],

            // Income
            ['parent_code' => '4000', 'code' => '4001', 'name' => 'Monthly Plan Subscription Revenue', 'type' => 'OPERATING_REVENUE', 'nature' => 'Income', 'desc' => 'Income from monthly subscription plans'],
            ['parent_code' => '4000', 'code' => '4002', 'name' => 'Annual Plan Subscription Revenue', 'type' => 'OPERATING_REVENUE', 'nature' => 'Income', 'desc' => 'Income from annual subscription plans'],

            // Expenses
            ['parent_code' => '5000', 'code' => '5001', 'name' => 'Stripe Gateway Fees', 'type' => 'OPERATING_EXPENSES', 'nature' => 'Expense', 'desc' => 'Stripe transaction fees'],
            ['parent_code' => '5000', 'code' => '5002', 'name' => 'Server & Cloud Hosting Expenses', 'type' => 'OPERATING_EXPENSES', 'nature' => 'Expense', 'desc' => 'AWS/Hosting server costs'],
        ];

        foreach ($subs as $sub) {
            DB::table('accounts')->insert([
                'marquee_id' => null,
                'account_code' => $sub['code'],
                'name' => $sub['name'],
                'parent_id' => $rootIds[$sub['parent_code']],
                'account_type_id' => $types[$sub['type']]->id,
                'nature' => $sub['nature'],
                'description' => $sub['desc'],
                'is_active' => true,
                'system_generated' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Null
    }
};
