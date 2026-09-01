<?php

namespace Tests\Feature;

use App\Models\BillingCycle;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\SaasInvoice;
use App\Models\SaasPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\StripeBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class SaasStripeBillingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $marquee;
    protected $plan;
    protected $cycle;
    protected $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & plans
        $this->artisan('db:seed', ['--class' => 'SubscriptionPlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $ownerRole = Role::where('name', 'owner')->first();

        // Create subscription plan in USD
        $this->plan = SubscriptionPlan::create([
            'name' => 'Premium International Plan',
            'slug' => 'premium-intl',
            'price' => 99.00,
            'monthly_price' => 99.00,
            'annual_price' => 999.00,
            'billing_interval' => 'month',
            'currency' => 'USD',
            'max_branches' => 5,
            'max_users' => 20,
            'storage_limit_mb' => 2048,
            'status' => 'active',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Global Events Inc',
            'email' => 'global@events.com',
            'phone' => '0015551234',
            'address' => '5th Avenue, NYC',
            'city' => 'New York',
            'province' => 'NY',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'name' => 'Marquee Owner',
            'email' => 'owner@global.com',
            'username' => 'globalowner',
            'password' => bcrypt('Password123!'),
            'marquee_id' => $this->marquee->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
            'subscription_ends_at' => now()->addDays(5),
        ]);

        $this->user->ownedMarquees()->syncWithoutDetaching([$this->marquee->id]);

        $this->cycle = BillingCycle::create([
            'cycle_name' => 'Quarterly',
            'duration_in_months' => 3,
            'status' => 'active',
        ]);

        $this->invoice = SaasInvoice::create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->plan->id,
            'billing_cycle_id' => $this->cycle->id,
            'amount' => 297.00,
            'tax' => 0.00,
            'discount' => 0.00,
            'total_amount' => 297.00,
            'payment_status' => 'Unpaid',
            'invoice_status' => 'Pending',
            'due_date' => now()->addDays(15),
        ]);
    }

    /** @test */
    public function test_tenant_billing_dashboard_displays_invoices_and_multi_currency()
    {
        $this->actingAs($this->user);

        Livewire::test(\App\Livewire\TenantBilling::class)
            ->assertSee('Premium International Plan')
            ->assertSee('297.00 USD')
            ->assertSee('Quarterly')
            ->assertSee('Pay Online');
    }

    /** @test */
    public function test_checkout_initiates_stripe_session_and_redirects()
    {
        $this->actingAs($this->user);

        // Fake successful Stripe session response
        Http::fake([
            'https://api.stripe.com/v1/checkout/sessions' => Http::response([
                'id' => 'cs_test_mock_12345',
                'url' => 'https://checkout.stripe.com/pay/cs_test_mock_12345',
            ], 200)
        ]);

        Livewire::test(\App\Livewire\TenantBilling::class)
            ->call('checkout', $this->invoice->id)
            ->assertRedirect('https://checkout.stripe.com/pay/cs_test_mock_12345');
    }

    /** @test */
    public function test_checkout_callback_validates_and_extends_subscription()
    {
        $this->actingAs($this->user);

        // Mock Stripe Checkout Session query response
        Http::fake([
            'https://api.stripe.com/v1/checkout/sessions/cs_test_mock_12345' => Http::response([
                'id' => 'cs_test_mock_12345',
                'payment_status' => 'paid',
                'amount_total' => 29700,
                'currency' => 'usd',
                'payment_intent' => 'pi_mock_987654321',
            ], 200)
        ]);

        $originalExpiry = $this->user->subscription_ends_at;

        // Perform success callback request
        $response = $this->get(route('billing.success', [
            'session_id' => 'cs_test_mock_12345',
            'invoice_id' => $this->invoice->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Subscription Activated!');
        $response->assertSee('pi_mock_987654321');

        // Assert Invoice is updated
        $this->invoice->refresh();
        $this->assertEquals('Paid', $this->invoice->payment_status);
        $this->assertEquals('Paid', $this->invoice->invoice_status);

        // Assert SaaS payment was created
        $paymentExists = SaasPayment::where('transaction_id', 'pi_mock_987654321')->exists();
        $this->assertTrue($paymentExists);

        // Assert tenant subscription ends date is extended by 3 months
        $this->user->refresh();
        $expectedExpiry = $originalExpiry->copy()->addMonths(3);
        $this->assertEquals($expectedExpiry->toDateString(), $this->user->subscription_ends_at->toDateString());
    }

    /** @test */
    public function test_checkout_callback_cancellation_shows_warning()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('billing.cancel', [
            'invoice_id' => $this->invoice->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Payment Cancelled');
        $response->assertSee('297.00 USD');
    }

    /** @test */
    public function test_super_admin_billing_dashboard_displays_no_marquee_warning()
    {
        $adminRole = Role::where('name', 'super_admin')->first();
        $adminUser = User::create([
            'name' => 'System Super Admin',
            'email' => 'admin@system.com',
            'username' => 'sysadmin',
            'password' => bcrypt('Password123!'),
            'marquee_id' => null, // No Marquee Tenant
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        $this->actingAs($adminUser);

        $response = $this->get(route('billing.index'));

        $response->assertStatus(200);
        $response->assertSee('No Associated Marquee Tenant Account');
    }

    /** @test */
    public function test_cancelled_invoices_are_not_shown_in_billing_views()
    {
        $this->actingAs($this->user);

        // Cancel the invoice
        $this->invoice->update(['invoice_status' => 'Cancelled']);

        // Check TenantBilling does not show it
        Livewire::test(\App\Livewire\TenantBilling::class)
            ->assertDontSee($this->invoice->invoice_number);

        // Check BusinessOwnerDetail does not show it
        // Super Admin user needed
        $adminRole = Role::where('name', 'super_admin')->first();
        $adminUser = User::create([
            'name' => 'System Super Admin',
            'email' => 'admin_test@system.com',
            'username' => 'sysadmin_test',
            'password' => bcrypt('Password123!'),
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        $this->actingAs($adminUser);

        Livewire::test(\App\Livewire\SuperAdmin\BusinessOwnerDetail::class, ['id' => $this->user->id])
            ->assertDontSee($this->invoice->invoice_number);

        // By default, SaasInvoicesList does not show the cancelled invoice
        Livewire::test(\App\Livewire\SaasInvoicesList::class)
            ->assertDontSee($this->invoice->invoice_number);

        // When filtered by Cancelled, it does show the cancelled invoice
        Livewire::test(\App\Livewire\SaasInvoicesList::class)
            ->set('filterInvoiceStatus', 'Cancelled')
            ->assertSee($this->invoice->invoice_number);
    }

    /** @test */
    public function test_cancelling_invoice_cancels_associated_journal_vouchers()
    {
        $adminRole = Role::where('name', 'super_admin')->first();
        $adminUser = User::create([
            'name' => 'System Super Admin',
            'email' => 'admin_test2@system.com',
            'username' => 'sysadmin_test2',
            'password' => bcrypt('Password123!'),
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        $this->actingAs($adminUser);

        $voucher = \App\Models\JournalVoucher::create([
            'voucher_no' => 'JV-0001',
            'voucher_date' => now(),
            'reference' => $this->invoice->invoice_number,
            'status' => 'posted',
        ]);

        $this->assertEquals('posted', $voucher->status);

        Livewire::test(\App\Livewire\SaasInvoicesList::class)
            ->call('updateStatus', $this->invoice->id, 'Cancelled');

        $this->invoice->refresh();
        $this->assertEquals('Cancelled', $this->invoice->invoice_status);

        $voucher->refresh();
        $this->assertEquals('cancelled', $voucher->status);
    }

    /** @test */
    public function test_subscription_status_badge_displays_correctly_based_on_user_status()
    {
        $this->actingAs($this->user);

        // Active
        $this->user->update(['status' => 'active']);
        Livewire::test(\App\Livewire\TenantBilling::class)
            ->assertSee('Active Subscription');

        // Inactive
        $this->user->update(['status' => 'inactive']);
        Livewire::test(\App\Livewire\TenantBilling::class)
            ->assertSee('Inactive')
            ->assertDontSee('Active Subscription');

        // Suspended
        $this->user->update(['status' => 'suspended']);
        Livewire::test(\App\Livewire\TenantBilling::class)
            ->assertSee('Suspended')
            ->assertDontSee('Active Subscription');
            
        // Super Admin viewing BusinessOwnerDetail
        $adminRole = Role::where('name', 'super_admin')->first();
        $adminUser = User::create([
            'name' => 'System Super Admin',
            'email' => 'admin_test3@system.com',
            'username' => 'sysadmin_test3',
            'password' => bcrypt('Password123!'),
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        $this->actingAs($adminUser);

        // Inactive
        $this->user->update(['status' => 'inactive']);
        Livewire::test(\App\Livewire\SuperAdmin\BusinessOwnerDetail::class, ['id' => $this->user->id])
            ->assertSee('Inactive')
            ->assertDontSee('Active Subscription');

        // Suspended
        $this->user->update(['status' => 'suspended']);
        Livewire::test(\App\Livewire\SuperAdmin\BusinessOwnerDetail::class, ['id' => $this->user->id])
            ->assertSee('Suspended')
            ->assertDontSee('Active Subscription');
    }

    /** @test */
    public function test_cancelled_invoices_are_dynamically_excluded_from_general_ledger()
    {
        // 1. Seed Account Types
        $this->artisan('db:seed', ['--class' => 'AccountingModuleSeeder']);

        $accountType = \App\Models\AccountType::where('code', 'CURRENT_ASSETS')->first();

        // 2. Create a SaaS account
        $account = \App\Models\Account::create([
            'name' => 'Test SaaS AR',
            'account_code' => 'TEST-1003',
            'nature' => 'Asset',
            'is_active' => true,
            'account_type_id' => $accountType->id,
        ]);

        // 2. Create financial year
        $fy = \App\Models\FinancialYear::create([
            'name' => 'FY 2026',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 'active',
            'is_default' => true,
        ]);

        // 3. Create a posted journal voucher for the SaaS invoice
        $voucher = \App\Models\JournalVoucher::create([
            'financial_year_id' => $fy->id,
            'voucher_no' => 'JV-TEST-01',
            'voucher_date' => now(),
            'reference' => $this->invoice->invoice_number,
            'status' => 'posted',
        ]);

        $voucher->items()->create([
            'account_id' => $account->id,
            'debit' => 200.00,
            'credit' => 0.00,
        ]);

        // 4. Retrieve ledger and assert the invoice amount is included
        $accountingService = app(\App\Services\AccountingService::class);
        $ledger = $accountingService->getGeneralLedger(
            $account->id,
            now()->startOfYear()->format('Y-m-d'),
            now()->endOfYear()->format('Y-m-d'),
            null,
            $fy->id
        );

        // Ensure the debit is registered
        $this->assertEquals(200.00, $ledger['closing_balance']);

        // 5. Now mark the invoice as Cancelled
        $this->invoice->update(['invoice_status' => 'Cancelled']);

        // Retrieve ledger again and assert it is now excluded dynamically
        $ledger = $accountingService->getGeneralLedger(
            $account->id,
            now()->startOfYear()->format('Y-m-d'),
            now()->endOfYear()->format('Y-m-d'),
            null,
            $fy->id
        );

        $this->assertEquals(0.00, $ledger['closing_balance']);
    }

    /** @test */
    public function test_recording_payment_with_discount_updates_invoice_and_ledger()
    {
        $adminRole = Role::where('name', 'super_admin')->first();
        $adminUser = User::create([
            'name' => 'System Super Admin',
            'email' => 'admin_discount@system.com',
            'username' => 'sysadmindiscount',
            'password' => bcrypt('Password123!'),
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        $this->actingAs($adminUser);

        // 1. Seed Account Types so postSaasInvoiceJournal can run
        $this->artisan('db:seed', ['--class' => 'AccountingModuleSeeder']);

        $assetType = \App\Models\AccountType::where('code', 'CURRENT_ASSETS')->first();
        $revenueType = \App\Models\AccountType::where('code', 'OPERATING_REVENUE')->first();

        // 2. Create SaaS level accounts
        $arAccount = \App\Models\Account::create([
            'name' => 'SaaS Accounts Receivable',
            'account_code' => '1003',
            'nature' => 'Asset',
            'is_active' => true,
            'account_type_id' => $assetType->id,
            'marquee_id' => null,
        ]);

        $revAccount = \App\Models\Account::create([
            'name' => 'SaaS Subscription Revenue',
            'account_code' => '4001',
            'nature' => 'Income',
            'is_active' => true,
            'account_type_id' => $revenueType->id,
            'marquee_id' => null,
        ]);

        // 3. Create the initial journal voucher for the invoice
        $voucher = \App\Models\JournalVoucher::create([
            'voucher_no' => 'JV-INV-01',
            'voucher_date' => now(),
            'reference' => $this->invoice->invoice_number,
            'status' => 'posted',
        ]);

        // Let's test SaasPaymentForm
        // Invoice total is 297.00. We want to apply a 50.00 discount and pay 247.00.
        Livewire::test(\App\Livewire\SaasPaymentForm::class)
            ->set('invoice_id', $this->invoice->id)
            ->assertSet('remainingBalance', 297.00)
            ->assertSet('discount', '')
            ->set('discount', 50.00)
            // Changing discount should auto-calculate amount = remainingBalance - discount = 247.00
            ->assertSet('amount', 247.00)
            ->call('save');

        // Assert invoice is fully paid and status updated
        $this->invoice->refresh();
        $this->assertEquals(50.00, $this->invoice->discount);
        $this->assertEquals(247.00, $this->invoice->total_amount);
        $this->assertEquals('Paid', $this->invoice->payment_status);

        // Assert payment is created with amount 247.00
        $payment = SaasPayment::where('invoice_id', $this->invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(247.00, $payment->amount);

        // Assert the old journal voucher was deleted
        $oldVoucherExists = \App\Models\JournalVoucher::where('id', $voucher->id)->exists();
        $this->assertFalse($oldVoucherExists);

        // Assert a new journal voucher was created with the updated net total_amount (247.00)
        $newVoucher = \App\Models\JournalVoucher::where('reference', $this->invoice->invoice_number)
            ->whereNull('marquee_id')
            ->first();
        $this->assertNotNull($newVoucher);
        // The ledger items should have debit/credit equal to the updated invoice total amount
        $debitTotal = $newVoucher->items()->sum('debit');
        $this->assertEquals(247.00, $debitTotal);
    }

    /** @test */
    public function test_discount_is_blank_by_default_and_can_be_submitted_blank()
    {
        $adminRole = Role::where('name', 'super_admin')->first();
        $adminUser = User::create([
            'name' => 'System Super Admin',
            'email' => 'admin_discount_blank@system.com',
            'username' => 'sysadmindiscountblank',
            'password' => bcrypt('Password123!'),
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        $this->actingAs($adminUser);

        // Seed Account Types
        $this->artisan('db:seed', ['--class' => 'AccountingModuleSeeder']);

        // Create the initial journal voucher for the invoice
        $voucher = \App\Models\JournalVoucher::create([
            'voucher_no' => 'JV-INV-01',
            'voucher_date' => now(),
            'reference' => $this->invoice->invoice_number,
            'status' => 'posted',
        ]);

        Livewire::test(\App\Livewire\SaasPaymentForm::class)
            ->set('invoice_id', $this->invoice->id)
            ->assertSet('remainingBalance', 297.00)
            // Assert default discount is empty string
            ->assertSet('discount', '')
            // Assert payment amount defaults to full remaining balance
            ->assertSet('amount', 297.00)
            ->call('save');

        $this->invoice->refresh();
        // Assert no discount was added (remains 0.00)
        $this->assertEquals(0.00, $this->invoice->discount);
        $this->assertEquals(297.00, $this->invoice->total_amount);
        $this->assertEquals('Paid', $this->invoice->payment_status);

        // Old journal voucher should NOT have been deleted because no discount was applied
        $oldVoucherExists = \App\Models\JournalVoucher::where('id', $voucher->id)->exists();
        $this->assertTrue($oldVoucherExists);
    }

    /** @test */
    public function test_business_owner_form_phone_number_uniqueness_and_formatting()
    {
        $adminRole = Role::where('name', 'super_admin')->first();
        $adminUser = User::create([
            'name' => 'System Super Admin',
            'email' => 'admin_phone@system.com',
            'username' => 'sysadminphone',
            'password' => bcrypt('Password123!'),
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        $this->actingAs($adminUser);

        // 1. Test creation of business owner with formatted phone number
        Livewire::test(\App\Livewire\SuperAdmin\BusinessOwnerForm::class)
            ->set('name', 'Test Owner')
            ->set('email', 'owner_phone@test.com')
            ->set('username', 'ownerphone')
            ->set('password', 'password123')
            ->set('phone', '0321-8611353') // Masked format
            ->set('status', 'active')
            ->set('subscription_plan_id', $this->plan->id)
            ->set('subscription_ends_at', '2027-12-31')
            ->call('save')
            ->assertHasNoErrors();

        // Assert user was created and phone was formatted to 0092...
        $user = User::where('email', 'owner_phone@test.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('0321-8611353', $user->phone);
        $this->assertEquals('03218611353', $user->getRawOriginal('phone'));

        // 2. Test uniqueness - trying to create another user with same phone fails
        Livewire::test(\App\Livewire\SuperAdmin\BusinessOwnerForm::class)
            ->set('name', 'Test Owner 2')
            ->set('email', 'owner_phone2@test.com')
            ->set('username', 'ownerphone2')
            ->set('password', 'password123')
            ->set('phone', '0321-8611353')
            ->set('status', 'active')
            ->set('subscription_plan_id', $this->plan->id)
            ->set('subscription_ends_at', '2027-12-31')
            ->call('save')
            ->assertHasErrors(['phone']);

        // 3. Test validation constraint on incomplete format (regex mismatch)
        Livewire::test(\App\Livewire\SuperAdmin\BusinessOwnerForm::class)
            ->set('name', 'Test Owner 3')
            ->set('email', 'owner_phone3@test.com')
            ->set('username', 'ownerphone3')
            ->set('password', 'password123')
            ->set('phone', '0321-86') // Incomplete
            ->set('status', 'active')
            ->set('subscription_plan_id', $this->plan->id)
            ->set('subscription_ends_at', '2027-12-31')
            ->call('save')
            ->assertHasErrors(['phone']);

        // 4. Test loading for edit formats phone back to UI representation
        Livewire::test(\App\Livewire\SuperAdmin\BusinessOwnerForm::class, ['id' => $user->id])
            ->assertSet('phone', '0321-8611353');
    }

    /** @test */
    public function test_trial_accounts_list_filters_and_actions()
    {
        $adminRole = Role::where('name', 'super_admin')->first();
        $adminUser = User::create([
            'name' => 'System Super Admin',
            'email' => 'admin_trials@system.com',
            'username' => 'sysadmintrials',
            'password' => bcrypt('Password123!'),
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        $this->actingAs($adminUser);

        // Seed Accounting Types
        $this->artisan('db:seed', ['--class' => 'AccountingModuleSeeder']);

        // Create trial user
        $trialUser = User::create([
            'name' => 'Trial User',
            'email' => 'trial@test.com',
            'username' => 'trialuser',
            'password' => bcrypt('Password123!'),
            'role_id' => Role::where('name', 'business_owner')->first()->id,
            'subscription_plan_id' => $this->plan->id,
            'subscription_trial_ends_at' => now()->addDays(14),
            'status' => 'active',
            'phone' => '00923218611353',
        ]);

        // Test list
        Livewire::test(\App\Livewire\SuperAdmin\Trials\TrialAccounts::class)
            ->assertSee('Trial User')
            // Test select for extend
            ->call('selectUserForExtend', $trialUser->id)
            ->assertSet('selectedUserId', $trialUser->id)
            ->set('new_trial_ends_at', now()->addDays(20)->format('Y-m-d'))
            ->call('extendTrial')
            ->assertHasNoErrors();

        $trialUser->refresh();
        $this->assertEquals(now()->addDays(20)->format('Y-m-d'), $trialUser->subscription_trial_ends_at->format('Y-m-d'));

        // Create billing cycles to convert
        $cycle = \App\Models\BillingCycle::create([
            'cycle_name' => 'Monthly',
            'duration_in_months' => 1,
            'discount_percentage' => 0.00,
        ]);

        // Test select for convert
        Livewire::test(\App\Livewire\SuperAdmin\Trials\TrialAccounts::class)
            ->call('selectUserForConvert', $trialUser->id)
            ->assertSet('selectedUserId', $trialUser->id)
            ->set('plan_id', $this->plan->id)
            ->set('billing_cycle_id', $cycle->id)
            ->set('mark_as_paid', true)
            ->call('convertToPaid')
            ->assertHasNoErrors();

        $trialUser->refresh();
        $this->assertNotNull($trialUser->subscription_ends_at);
        $this->assertTrue($trialUser->subscription_ends_at->isFuture());
    }

    /** @test */
    public function test_expiring_trials_list_and_send_reminder()
    {
        $adminRole = Role::where('name', 'super_admin')->first();
        $adminUser = User::create([
            'name' => 'System Super Admin',
            'email' => 'admin_expiring@system.com',
            'username' => 'sysadminexpir',
            'password' => bcrypt('Password123!'),
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        $this->actingAs($adminUser);

        // Create user expiring in 3 days
        $expiringUser = User::create([
            'name' => 'Expiring User',
            'email' => 'expiring@test.com',
            'username' => 'expiringuser',
            'password' => bcrypt('Password123!'),
            'role_id' => Role::where('name', 'business_owner')->first()->id,
            'subscription_plan_id' => $this->plan->id,
            'subscription_trial_ends_at' => now()->addDays(3),
            'status' => 'active',
            'phone' => '00923218611354',
        ]);

        Livewire::test(\App\Livewire\SuperAdmin\Trials\ExpiringTrials::class)
            ->assertSee('Expiring User')
            ->call('sendReminder', $expiringUser->id)
            ->assertStatus(200);
    }

    /** @test */
    public function test_renewal_invoice_is_generated_automatically_when_nearing_expiration()
    {
        // Seed accounting
        $this->artisan('db:seed', ['--class' => 'AccountingModuleSeeder']);

        $ownerRole = Role::where('name', 'business_owner')->first() ?? Role::where('name', 'owner')->first();
        $user = User::create([
            'name' => 'Nearing Expiry Owner',
            'email' => 'nearing@test.com',
            'username' => 'nearingowner',
            'password' => bcrypt('Password123!'),
            'role_id' => $ownerRole->id,
            'subscription_plan_id' => $this->plan->id,
            'subscription_ends_at' => now()->addDays(2),
            'status' => 'active',
            'phone' => '00923218611355',
        ]);

        $cycle = \App\Models\BillingCycle::create([
            'cycle_name' => 'Monthly',
            'duration_in_months' => 1,
            'discount_percentage' => 0.00,
        ]);

        // Run the command
        $this->artisan('subscription:billing-run')
            ->assertExitCode(0);

        // Check that renewal invoice is generated
        $invoice = SaasInvoice::where('user_id', $user->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('Pending', $invoice->invoice_status);
        $this->assertEquals($this->plan->price, $invoice->amount);
    }

    /** @test */
    public function test_expired_trials_and_subscriptions_are_suspended_after_grace_period()
    {
        $ownerRole = Role::where('name', 'business_owner')->first() ?? Role::where('name', 'owner')->first();
        $expiredUser = User::create([
            'name' => 'Expired Owner',
            'email' => 'expired_owner@test.com',
            'username' => 'expiredowner',
            'password' => bcrypt('Password123!'),
            'role_id' => $ownerRole->id,
            'subscription_plan_id' => $this->plan->id,
            'subscription_ends_at' => now()->subDays(4),
            'status' => 'active',
            'phone' => '00923218611356',
        ]);

        $this->artisan('subscription:billing-run')
            ->assertExitCode(0);

        $expiredUser->refresh();
        $this->assertEquals('inactive', $expiredUser->status);
    }

    /** @test */
    public function test_tenant_can_calculate_proration_and_upgrade_plan()
    {
        // Seed accounting
        $this->artisan('db:seed', ['--class' => 'AccountingModuleSeeder']);

        $ownerRole = Role::where('name', 'business_owner')->first() ?? Role::where('name', 'owner')->first();
        $user = User::create([
            'name' => 'Upgrading Tenant',
            'email' => 'upgrade_tenant@test.com',
            'username' => 'upgradetenant',
            'password' => bcrypt('Password123!'),
            'role_id' => $ownerRole->id,
            'subscription_plan_id' => $this->plan->id,
            'subscription_ends_at' => now()->addDays(15),
            'status' => 'active',
            'phone' => '00923218611357',
        ]);

        $this->actingAs($user);

        // Current plan invoice to simulate last invoice
        $cycle = \App\Models\BillingCycle::create([
            'cycle_name' => 'Monthly',
            'duration_in_months' => 1,
            'discount_percentage' => 0.00,
        ]);

        SaasInvoice::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $this->plan->id,
            'billing_cycle_id' => $cycle->id,
            'amount' => $this->plan->price,
            'tax' => 0.00,
            'discount' => 0.00,
            'total_amount' => $this->plan->price,
            'payment_status' => 'Paid',
            'invoice_status' => 'Paid',
            'due_date' => now()->subDays(15),
            'paid_date' => now()->subDays(15),
        ]);

        $newPlan = SubscriptionPlan::create([
            'name' => 'Pro Plan',
            'slug' => 'pro-plan',
            'price' => 5000.00,
            'monthly_price' => 5000.00,
            'currency' => 'PKR',
        ]);

        // Test Livewire
        Livewire::test(\App\Livewire\TenantBilling::class)
            ->call('openChangePlanModal')
            ->set('selectedPlanId', $newPlan->id)
            ->set('selectedCycleId', $cycle->id)
            ->assertSet('unusedCredit', 49.50) // 15 days left out of 30 for 99.00 USD plan
            ->assertSet('netPayable', 4950.50)
            ->call('changePlan')
            ->assertHasNoErrors();

        // Assert invoice is generated
        $invoice = SaasInvoice::where('user_id', $user->id)->where('subscription_plan_id', $newPlan->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('Pending', $invoice->invoice_status);
        $this->assertEquals(4950.50, $invoice->total_amount);
    }

    /** @test */
    public function test_active_subscription_allows_access_to_operational_routes()
    {
        $ownerRole = Role::where('name', 'business_owner')->first() ?? Role::where('name', 'owner')->first();
        $user = User::create([
            'name' => 'Active Owner',
            'email' => 'active_owner@test.com',
            'username' => 'activeowner',
            'password' => bcrypt('Password123!'),
            'role_id' => $ownerRole->id,
            'subscription_plan_id' => $this->plan->id,
            'subscription_ends_at' => now()->addDays(15),
            'status' => 'active',
            'phone' => '00923218611358',
        ]);

        $this->actingAs($user);

        // Access operational route (e.g. halls)
        $response = $this->get(route('halls.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function test_expired_or_suspended_subscription_blocks_access_to_operational_routes()
    {
        $ownerRole = Role::where('name', 'business_owner')->first() ?? Role::where('name', 'owner')->first();
        
        // Expired subscription
        $expiredUser = User::create([
            'name' => 'Expired Owner',
            'email' => 'expired_owner_access@test.com',
            'username' => 'expiredowneraccess',
            'password' => bcrypt('Password123!'),
            'role_id' => $ownerRole->id,
            'subscription_plan_id' => $this->plan->id,
            'subscription_ends_at' => now()->subDays(1),
            'status' => 'active',
            'phone' => '00923218611359',
        ]);

        $this->actingAs($expiredUser);

        $response = $this->get(route('halls.index'));
        $response->assertStatus(403);
        $response->assertSee('Subscription Inactive');

        // Suspended status
        $suspendedUser = User::create([
            'name' => 'Suspended Owner',
            'email' => 'suspended_owner_access@test.com',
            'username' => 'suspendedowneraccess',
            'password' => bcrypt('Password123!'),
            'role_id' => $ownerRole->id,
            'subscription_plan_id' => $this->plan->id,
            'subscription_ends_at' => now()->addDays(15),
            'status' => 'suspended',
            'phone' => '00923218611360',
        ]);

        $this->actingAs($suspendedUser);

        $response = $this->get(route('halls.index'));
        $response->assertStatus(403);
        $response->assertSee('Suspended');
    }

    /** @test */
    public function test_expired_subscription_allows_access_to_billing_index()
    {
        $ownerRole = Role::where('name', 'business_owner')->first() ?? Role::where('name', 'owner')->first();
        $expiredUser = User::create([
            'name' => 'Expired Owner',
            'email' => 'expired_billing@test.com',
            'username' => 'expiredbilling',
            'password' => bcrypt('Password123!'),
            'role_id' => $ownerRole->id,
            'subscription_plan_id' => $this->plan->id,
            'subscription_ends_at' => now()->subDays(1),
            'status' => 'active',
            'phone' => '00923218611361',
        ]);

        $this->actingAs($expiredUser);

        // Billing index should be accessible even when expired so they can pay
        $response = $this->get(route('billing.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function test_employee_of_suspended_owner_is_blocked_from_operational_routes()
    {
        $ownerRole = Role::where('name', 'business_owner')->first() ?? Role::where('name', 'owner')->first();
        $suspendedOwner = User::create([
            'name' => 'Suspended Boss',
            'email' => 'suspended_boss@test.com',
            'username' => 'suspendedboss',
            'password' => bcrypt('Password123!'),
            'role_id' => $ownerRole->id,
            'subscription_plan_id' => $this->plan->id,
            'subscription_ends_at' => now()->addDays(15),
            'status' => 'suspended',
            'phone' => '00923218611362',
        ]);

        // Link to marquee
        $marquee = Marquee::create([
            'name' => 'Suspended Marquee',
            'email' => 'susp@marquee.com',
            'phone' => '00923218611363',
            'address' => 'Test Address',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'status' => 'active',
        ]);
        // Link to marquee
        $suspendedOwner->ownedMarquees()->attach($marquee->id);

        // Get branch manager role (has view_halls permission by default)
        $empRole = Role::where('name', 'branch_manager')->first();
        
        $employee = User::create([
            'name' => 'Staff Employee',
            'email' => 'staff_emp@test.com',
            'username' => 'staffemp',
            'password' => bcrypt('Password123!'),
            'role_id' => $empRole->id,
            'marquee_id' => $marquee->id,
            'status' => 'active',
            'phone' => '00923218611364',
        ]);

        $this->assertNotNull($employee->marquee_id);
        $this->assertNotNull($employee->marquee);
        $this->assertCount(1, $marquee->owners);

        $this->actingAs($employee);

        // Accessing operational route should block them
        $response = $this->get(route('halls.index'));
        $response->assertStatus(403);
        $response->assertSee('Suspended');
    }
}
