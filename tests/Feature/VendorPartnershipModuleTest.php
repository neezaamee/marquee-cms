<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Marquee;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCommissionAgreement;
use App\Models\VendorLedger;
use App\Models\VendorSale;
use App\Models\VendorService;
use App\Models\VendorSettlement;
use App\Services\VendorCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorPartnershipModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Marquee $marquee;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-plan-' . uniqid(),
            'price' => 1000,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Royal Pearl Marquee',
            'subscription_plan_id' => $plan->id,
            'slug' => 'royal-pearl-' . uniqid(),
            'address' => '12 Main Gulberg',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'email' => 'contact@royalpearl.test',
            'status' => 'active',
        ]);

        $this->owner = User::create([
            'name' => 'Owner Ahmad',
            'email' => 'owner@royalpearl.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'marquee_id' => $this->marquee->id,
        ]);

        // Seed Account Types
        AccountType::firstOrCreate(['name' => 'Revenue'], ['code' => 'REV', 'nature' => 'Income']);
        AccountType::firstOrCreate(['name' => 'Current Liabilities'], ['code' => 'CLIAB', 'nature' => 'Liability']);
    }

    public function test_vendor_registration_auto_generates_code_and_creates_vendor()
    {
        $this->actingAs($this->owner);

        $vendor = Vendor::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'ABC Photography & Films',
            'vendor_type' => 'Photography',
            'contact_person' => 'Muhammad Tariq',
            'phone' => '03001234567',
            'email' => 'tariq@abcfilms.com',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'vendor_code' => 'VEN-000001',
            'name' => 'ABC Photography & Films',
        ]);
    }

    public function test_vendor_service_and_commission_agreement_lifecycle()
    {
        $this->actingAs($this->owner);

        $vendor = Vendor::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'XYZ Sound Systems',
            'vendor_type' => 'Sound System',
            'phone' => '03219876543',
        ]);

        $service = VendorService::create([
            'marquee_id' => $this->marquee->id,
            'vendor_id' => $vendor->id,
            'service_name' => 'Full Concert Sound & DJ Package',
            'unit' => 'Event',
            'default_sale_price' => 100000.00,
        ]);

        $agreement = VendorCommissionAgreement::create([
            'marquee_id' => $this->marquee->id,
            'vendor_id' => $vendor->id,
            'vendor_service_id' => $service->id,
            'commission_type' => 'percentage',
            'commission_percentage' => 15.00,
            'effective_from' => '2026-01-01',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('vendor_services', ['id' => $service->id, 'service_code' => 'SRV-0001']);
        $this->assertDatabaseHas('vendor_commission_agreements', ['id' => $agreement->id, 'agreement_number' => 'AGR-000001']);
    }

    public function test_vendor_sale_creation_snapshots_rate_and_posts_ledger_and_accounting()
    {
        $this->actingAs($this->owner);

        $vendor = Vendor::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Royal Decorators',
            'vendor_type' => 'Decoration',
            'phone' => '03005554433',
        ]);

        $agreement = VendorCommissionAgreement::create([
            'marquee_id' => $this->marquee->id,
            'vendor_id' => $vendor->id,
            'commission_type' => 'percentage',
            'commission_percentage' => 20.00,
            'effective_from' => '2026-01-01',
            'status' => 'active',
        ]);

        $serviceEngine = app(VendorCommissionService::class);
        $sale = $serviceEngine->createVendorSale([
            'vendor_id' => $vendor->id,
            'sale_date' => '2026-08-08',
            'event_date' => '2026-08-15',
            'sale_amount' => 100000.00,
        ]);

        $this->assertEquals(20.00, $sale->commission_rate);
        $this->assertEquals(20000.00, $sale->commission_amount);
        $this->assertEquals(80000.00, $sale->vendor_net_amount);

        // Verify Vendor Ledger
        $this->assertDatabaseHas('vendor_ledgers', [
            'vendor_id' => $vendor->id,
            'vendor_sale_id' => $sale->id,
            'sale_amount' => 100000.00,
            'commission_amount' => 20000.00,
            'running_balance' => 80000.00,
        ]);

        // Verify Vendor Current Balance Accessor
        $this->assertEquals(80000.00, $vendor->fresh()->current_balance);

        // Verify Accounting Entry
        $this->assertDatabaseHas('accounts', [
            'marquee_id' => $this->marquee->id,
            'name' => 'Vendor Commission Income',
        ]);
    }

    public function test_historical_commission_rate_preservation()
    {
        $this->actingAs($this->owner);

        $vendor = Vendor::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Elite Photographers',
            'vendor_type' => 'Photography',
            'phone' => '03001112233',
        ]);

        $agreement1 = VendorCommissionAgreement::create([
            'marquee_id' => $this->marquee->id,
            'vendor_id' => $vendor->id,
            'commission_type' => 'percentage',
            'commission_percentage' => 10.00,
            'effective_from' => '2026-01-01',
            'effective_to' => '2026-06-30',
            'status' => 'active',
        ]);

        $serviceEngine = app(VendorCommissionService::class);
        $saleJune = $serviceEngine->createVendorSale([
            'vendor_id' => $vendor->id,
            'sale_date' => '2026-05-10',
            'event_date' => '2026-05-10',
            'sale_amount' => 50000.00,
        ]);

        $this->assertEquals(10.00, $saleJune->commission_rate);
        $this->assertEquals(5000.00, $saleJune->commission_amount);

        // Now create a new agreement for July at 25% rate
        $agreement2 = VendorCommissionAgreement::create([
            'marquee_id' => $this->marquee->id,
            'vendor_id' => $vendor->id,
            'commission_type' => 'percentage',
            'commission_percentage' => 25.00,
            'effective_from' => '2026-07-01',
            'status' => 'active',
        ]);

        // Historical May sale MUST retain 10% rate!
        $this->assertEquals(10.00, $saleJune->fresh()->commission_rate);
        $this->assertEquals(5000.00, $saleJune->fresh()->commission_amount);
    }

    public function test_vendor_settlement_payout_updates_ledger_and_posts_jv()
    {
        $this->actingAs($this->owner);

        $vendor = Vendor::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Grand Caterers',
            'vendor_type' => 'Caterer',
            'phone' => '03004445566',
        ]);

        $serviceEngine = app(VendorCommissionService::class);
        $sale = $serviceEngine->createVendorSale([
            'vendor_id' => $vendor->id,
            'sale_date' => '2026-08-01',
            'event_date' => '2026-08-01',
            'sale_amount' => 200000.00,
            'commission_rate' => 10.00,
        ]);

        // Vendor net balance = 180,000
        $this->assertEquals(180000.00, $vendor->fresh()->current_balance);

        // Process Partial Settlement Payout of 100,000
        $settlement = $serviceEngine->processSettlement($vendor, 100000.00, [
            'settlement_date' => '2026-08-08',
            'payment_method' => 'Bank Transfer',
            'remarks' => 'Partial payment disbursement',
        ]);

        $this->assertEquals(80000.00, $settlement->remaining_balance);
        $this->assertEquals(80000.00, $vendor->fresh()->current_balance);

        // Verify Ledger Settlement Log
        $this->assertDatabaseHas('vendor_ledgers', [
            'vendor_id' => $vendor->id,
            'settlement_id' => $settlement->id,
            'payment_amount' => 100000.00,
            'running_balance' => 80000.00,
        ]);
    }

    public function test_multi_tenant_isolation_prevents_unauthorized_cross_tenant_access()
    {
        $plan2 = SubscriptionPlan::create([
            'name' => 'Plan 2',
            'slug' => 'plan-2-' . uniqid(),
            'price' => 1000,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        $marquee2 = Marquee::create([
            'name' => 'Other Tenant Marquee',
            'subscription_plan_id' => $plan2->id,
            'slug' => 'other-tenant-' . uniqid(),
            'address' => '45 Model Town',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03009998877',
            'email' => 'contact@othertenant.test',
            'status' => 'active',
        ]);

        $owner2 = User::create([
            'name' => 'Owner Other',
            'email' => 'owner@other.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'marquee_id' => $marquee2->id,
        ]);

        $vendor1 = Vendor::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Tenant 1 Vendor',
            'vendor_type' => 'Florist',
            'phone' => '03000000001',
        ]);

        $this->actingAs($owner2);

        $response = $this->get(route('vendors.show', $vendor1->id));
        $this->assertTrue(in_array($response->status(), [403, 404]));
    }
}
