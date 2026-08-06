<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Package;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerReferralAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $marquee;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & plans
        $this->artisan('db:seed', ['--class' => 'SubscriptionPlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $plan = SubscriptionPlan::first();

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

        $this->user = User::create([
            'name' => 'Owner User',
            'email' => 'owner@test.com',
            'username' => 'owner',
            'password' => bcrypt('Password123!'),
            'marquee_id' => $this->marquee->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Test Branch',
            'address' => 'Test Address',
            'city' => 'Test City',
            'province' => 'Test Province',
            'phone' => '123456',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function test_customer_referral_analytics_aggregation()
    {
        $this->actingAs($this->user);

        // Setup test data (Hall, Event Type, Package, Slot etc)
        $hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Main Ballroom',
            'hall_code' => 'MBR',
            'capacity' => 300,
            'hall_type' => 'Banquet',
            'default_booking_price' => 40000.00,
            'status' => 'active',
        ]);
        $eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'event_type_name' => 'Walima',
            'event_type_code' => 'WAL',
            'status' => 'active',
            'is_system_default' => false,
        ]);

        // Create referred customers
        $customerA = Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_code' => 'CUST-00001',
            'customer_type' => 'Individual',
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'phone_number' => '03001234567',
            'referred_by_name' => 'Social Media Campaign',
            'referred_by_contact' => 'Facebook Ads Manager',
            'status' => 'Active',
        ]);

        $customerB = Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_code' => 'CUST-00002',
            'customer_type' => 'Individual',
            'first_name' => 'Zainab',
            'last_name' => 'Ahmed',
            'phone_number' => '03211234567',
            'referred_by_name' => 'Social Media Campaign',
            'referred_by_contact' => 'Facebook Ads Manager',
            'status' => 'Active',
        ]);

        $customerC = Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_code' => 'CUST-00003',
            'customer_type' => 'Individual',
            'first_name' => 'Usman',
            'last_name' => 'Tariq',
            'phone_number' => '03331234567',
            'referred_by_name' => 'Uncle John',
            'referred_by_contact' => '03009999999',
            'status' => 'Active',
        ]);

        // Create bookings
        Booking::create([
            'marquee_id' => $this->marquee->id,
            'customer_id' => $customerA->id,
            'hall_id' => $hall->id,
            'event_type_id' => $eventType->id,
            'booking_date' => '2026-11-20',
            'start_time' => '2026-11-20 18:00:00',
            'end_time' => '2026-11-20 23:00:00',
            'guest_count' => 200,
            'grand_total' => 200000.00,
            'booking_status' => 'Confirmed',
        ]);

        Booking::create([
            'marquee_id' => $this->marquee->id,
            'customer_id' => $customerB->id,
            'hall_id' => $hall->id,
            'event_type_id' => $eventType->id,
            'booking_date' => '2026-11-25',
            'start_time' => '2026-11-25 18:00:00',
            'end_time' => '2026-11-25 23:00:00',
            'guest_count' => 150,
            'grand_total' => 150000.00,
            'booking_status' => 'Confirmed',
        ]);

        Booking::create([
            'marquee_id' => $this->marquee->id,
            'customer_id' => $customerC->id,
            'hall_id' => $hall->id,
            'event_type_id' => $eventType->id,
            'booking_date' => '2026-12-05',
            'start_time' => '2026-12-05 18:00:00',
            'end_time' => '2026-12-05 23:00:00',
            'guest_count' => 100,
            'grand_total' => 80000.00,
            'booking_status' => 'Confirmed',
        ]);

        // Test Livewire component
        Livewire::test('customer-referral-analytics')
            ->assertSee('Social Media Campaign')
            ->assertSee('Uncle John')
            ->assertSee('Rs. 350,000.00')
            ->assertSee('Rs. 80,000.00')
            // Apply search
            ->set('search', 'Uncle')
            ->assertSee('Uncle John')
            ->assertDontSee('Social Media Campaign');
    }
}
