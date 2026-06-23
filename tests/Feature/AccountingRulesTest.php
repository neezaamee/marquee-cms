<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Branch;
use App\Models\FinancialYear;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingRulesTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $marquee;
    protected $branch;
    protected $accountingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountingService = new AccountingService();

        // Seed roles & permissions and subscription plans
        $this->artisan('db:seed', ['--class' => 'SubscriptionPlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $plan = SubscriptionPlan::first();

        // Create tenant Marquee
        $this->marquee = Marquee::create([
            'name' => 'Test Marquee',
            'email' => 'test@marquee.com',
            'phone' => '12345678',
            'address' => 'Test Address',
            'city' => 'Test City',
            'province' => 'Test Province',
            'ntn' => '123456',
            'status' => 'active',
            'subscription_plan_id' => $plan->id,
        ]);

        $ownerRole = Role::where('name', 'owner')->first();

        // Create Owner User
        $this->user = User::create([
            'name' => 'Owner User',
            'email' => 'owner@test.com',
            'username' => 'owner',
            'password' => bcrypt('Password123!'),
            'marquee_id' => $this->marquee->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        // Create Branch
        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Test Branch',
            'address' => 'Test Address',
            'city' => 'Test City',
            'province' => 'Test Province',
            'phone' => '123456',
            'status' => 'active',
        ]);

        // Run default accounts seeder
        $this->artisan('db:seed', ['--class' => 'AccountingModuleSeeder']);
    }

    /** @test */
    public function test_it_enforces_balanced_vouchers()
    {
        $this->actingAs($this->user);

        // Fetch Cash and Retained Earnings accounts
        $cashAccount = Account::where('marquee_id', $this->marquee->id)->where('account_code', '1001')->first();
        $retainedEarnings = Account::where('marquee_id', $this->marquee->id)->where('account_code', '3501')->first();

        // Create financial year
        FinancialYear::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'is_default' => true,
        ]);

        // Unbalanced items (Debits: 100, Credits: 90)
        $items = [
            ['account_id' => $cashAccount->id, 'debit' => 100, 'credit' => 0, 'narration' => 'Cash in'],
            ['account_id' => $retainedEarnings->id, 'debit' => 0, 'credit' => 90, 'narration' => 'Equity out'],
        ];

        $header = [
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_date' => '2026-05-15',
            'notes' => 'Test unbalanced entry',
            'status' => 'draft',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total Debits must equal Total Credits. Unbalanced entries are not allowed.');

        $this->accountingService->createJournalVoucher($header, $items);
    }

    /** @test */
    public function test_it_prevents_entries_in_closed_financial_years()
    {
        $this->actingAs($this->user);

        $cashAccount = Account::where('marquee_id', $this->marquee->id)->where('account_code', '1001')->first();
        $retainedEarnings = Account::where('marquee_id', $this->marquee->id)->where('account_code', '3501')->first();

        // Create a closed financial year
        FinancialYear::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'FY 2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'closed',
            'is_default' => false,
        ]);

        $items = [
            ['account_id' => $cashAccount->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $retainedEarnings->id, 'debit' => 0, 'credit' => 100],
        ];

        $header = [
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_date' => '2025-06-15',
            'status' => 'draft',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Voucher date does not fall within any active financial year.');

        $this->accountingService->createJournalVoucher($header, $items);
    }

    /** @test */
    public function test_it_protects_system_generated_accounts_from_deletion()
    {
        $this->actingAs($this->user);

        // Fetch a system-generated account (Cash)
        $cashAccount = Account::where('marquee_id', $this->marquee->id)->where('account_code', '1001')->first();
        $this->assertTrue($cashAccount->system_generated);

        // Expect Exception when attempting to delete system-generated accounts
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('System-generated accounts cannot be deleted.');

        $cashAccount->delete();
    }

    /** @test */
    public function test_it_calculates_correct_ledger_running_balances()
    {
        $this->actingAs($this->user);

        $cashAccount = Account::where('marquee_id', $this->marquee->id)->where('account_code', '1001')->first();
        $retainedEarnings = Account::where('marquee_id', $this->marquee->id)->where('account_code', '3501')->first();

        // Create active financial year
        $fy = FinancialYear::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'is_default' => true,
        ]);

        // Post balanced journal voucher 1 (Debit Cash 500, Credit Equity 500)
        $items1 = [
            ['account_id' => $cashAccount->id, 'debit' => 500, 'credit' => 0, 'narration' => 'Initial capital'],
            ['account_id' => $retainedEarnings->id, 'debit' => 0, 'credit' => 500, 'narration' => 'Initial capital'],
        ];

        $header1 = [
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_date' => '2026-02-01',
            'status' => 'posted',
        ];

        $this->accountingService->createJournalVoucher($header1, $items1);

        // Post balanced journal voucher 2 (Debit Cash 200, Credit Equity 200)
        $items2 = [
            ['account_id' => $cashAccount->id, 'debit' => 200, 'credit' => 0, 'narration' => 'Add cash'],
            ['account_id' => $retainedEarnings->id, 'debit' => 0, 'credit' => 200, 'narration' => 'Add cash'],
        ];

        $header2 = [
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'voucher_date' => '2026-02-15',
            'status' => 'posted',
        ];

        $this->accountingService->createJournalVoucher($header2, $items2);

        // Get General Ledger for Cash (Asset - Debit nature)
        $glCash = $this->accountingService->getGeneralLedger(
            $cashAccount->id,
            '2026-01-01',
            '2026-02-28',
            $this->branch->id,
            $fy->id
        );

        $this->assertEquals(700.00, $glCash['closing_balance']);
        $this->assertCount(2, $glCash['entries']);
        $this->assertEquals(500.00, $glCash['entries'][0]['running_balance']);
        $this->assertEquals(700.00, $glCash['entries'][1]['running_balance']);
    }
}
