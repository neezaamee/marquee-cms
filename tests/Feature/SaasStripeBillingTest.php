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
            'subscription_plan_id' => $this->plan->id,
            'subscription_ends_at' => now()->addDays(5),
        ]);

        $this->user = User::create([
            'name' => 'Marquee Owner',
            'email' => 'owner@global.com',
            'username' => 'globalowner',
            'password' => bcrypt('Password123!'),
            'marquee_id' => $this->marquee->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        $this->cycle = BillingCycle::create([
            'cycle_name' => 'Quarterly',
            'duration_in_months' => 3,
            'status' => 'active',
        ]);

        $this->invoice = SaasInvoice::create([
            'marquee_id' => $this->marquee->id,
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

        $originalExpiry = $this->marquee->subscription_ends_at;

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
        $this->marquee->refresh();
        $expectedExpiry = $originalExpiry->copy()->addMonths(3);
        $this->assertEquals($expectedExpiry->toDateString(), $this->marquee->subscription_ends_at->toDateString());
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
}
