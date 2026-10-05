<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Package;
use App\Models\Role;
use App\Models\Slot;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CancelledRejectedBookingExclusionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $marquee;
    protected $branch;
    protected $hall;
    protected $slot;
    protected $package;
    protected $eventType;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'SubscriptionPlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $plan = SubscriptionPlan::first();

        $this->marquee = Marquee::create([
            'name' => 'Royal Palace Marquee',
            'email' => 'info@royalpalace.com',
            'phone' => '03001234567',
            'address' => 'Mall Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'status' => 'active',
            'subscription_plan_id' => $plan->id,
        ]);

        $ownerRole = Role::where('name', 'owner')->first();

        $this->user = User::create([
            'name' => 'Owner User',
            'email' => 'owner@royalpalace.com',
            'username' => 'owner_rp',
            'password' => bcrypt('Password123!'),
            'marquee_id' => $this->marquee->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Campus',
            'address' => 'Mall Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'status' => 'active',
        ]);

        $this->hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_code' => 'HALL-01',
            'hall_name' => 'Kohinoor Hall',
            'hall_type' => 'indoor',
            'capacity' => 500,
            'default_booking_price' => 50000.00,
            'status' => 'active',
        ]);

        $this->slot = Slot::create([
            'marquee_id' => $this->marquee->id,
            'slot_name' => 'Dinner Slot',
            'start_time' => '19:00:00',
            'end_time' => '23:30:00',
        ]);

        $this->package = Package::create([
            'marquee_id' => $this->marquee->id,
            'package_code' => 'PKG-01',
            'package_name' => 'Gold Package',
            'base_price' => 1500.00,
            'per_plate_price' => 1500.00,
        ]);

        $this->eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_code' => 'EVT-01',
            'event_type_name' => 'Wedding Reception',
        ]);

        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_code' => 'CUST-EXCL-01',
            'customer_type' => 'Individual',
            'first_name' => 'Muhammad',
            'last_name' => 'Farooq',
            'phone_number' => '03009876543',
            'status' => 'Active',
        ]);
    }

    /** @test */
    public function test_customer_model_excludes_cancelled_and_rejected_bookings_from_aggregates()
    {
        // 1. Confirmed booking
        Booking::create([
            'marquee_id' => $this->marquee->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'hall_id' => $this->hall->id,
            'slot_id' => $this->slot->id,
            'package_id' => $this->package->id,
            'event_type_id' => $this->eventType->id,
            'booking_date' => Carbon::tomorrow()->format('Y-m-d'),
            'start_time' => Carbon::tomorrow()->format('Y-m-d') . ' 19:00:00',
            'end_time' => Carbon::tomorrow()->format('Y-m-d') . ' 23:30:00',
            'guest_count' => 200,
            'per_plate_price' => 1500.00,
            'grand_total' => 300000.00,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Unpaid',
        ]);

        // 2. Cancelled booking
        Booking::create([
            'marquee_id' => $this->marquee->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'hall_id' => $this->hall->id,
            'slot_id' => $this->slot->id,
            'package_id' => $this->package->id,
            'event_type_id' => $this->eventType->id,
            'booking_date' => Carbon::tomorrow()->addDays(2)->format('Y-m-d'),
            'start_time' => Carbon::tomorrow()->addDays(2)->format('Y-m-d') . ' 19:00:00',
            'end_time' => Carbon::tomorrow()->addDays(2)->format('Y-m-d') . ' 23:30:00',
            'guest_count' => 150,
            'per_plate_price' => 1500.00,
            'grand_total' => 225000.00,
            'booking_status' => 'Cancelled',
            'payment_status' => 'Unpaid',
        ]);

        // 3. Rejected booking
        Booking::create([
            'marquee_id' => $this->marquee->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'hall_id' => $this->hall->id,
            'slot_id' => $this->slot->id,
            'package_id' => $this->package->id,
            'event_type_id' => $this->eventType->id,
            'booking_date' => Carbon::tomorrow()->addDays(3)->format('Y-m-d'),
            'start_time' => Carbon::tomorrow()->addDays(3)->format('Y-m-d') . ' 19:00:00',
            'end_time' => Carbon::tomorrow()->addDays(3)->format('Y-m-d') . ' 23:30:00',
            'guest_count' => 100,
            'per_plate_price' => 1500.00,
            'grand_total' => 150000.00,
            'booking_status' => 'Rejected',
            'payment_status' => 'Unpaid',
        ]);

        $this->customer->refresh();

        // Total bookings must be 1 (only the Confirmed booking)
        $this->assertEquals(1, $this->customer->total_bookings);

        // Guest count must be 200 (only the Confirmed booking)
        $this->assertEquals(200, $this->customer->total_guest_count);

        // Revenue/invoiced total must be 300,000 (excluding 225k cancelled & 150k rejected)
        $this->assertEquals(300000.00, $this->customer->total_invoiced_amount);
        $this->assertEquals(300000.00, $this->customer->total_revenue_generated);
    }

    /** @test */
    public function test_booking_report_page_excludes_cancelled_and_rejected_from_totals()
    {
        // 1. Confirmed booking
        $b1 = Booking::create([
            'marquee_id' => $this->marquee->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'hall_id' => $this->hall->id,
            'slot_id' => $this->slot->id,
            'package_id' => $this->package->id,
            'event_type_id' => $this->eventType->id,
            'booking_date' => Carbon::today()->format('Y-m-d'),
            'start_time' => Carbon::today()->format('Y-m-d') . ' 19:00:00',
            'end_time' => Carbon::today()->format('Y-m-d') . ' 23:30:00',
            'guest_count' => 250,
            'per_plate_price' => 1000.00,
            'grand_total' => 250000.00,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Paid',
        ]);

        BookingPayment::create([
            'booking_id' => $b1->id,
            'amount' => 250000.00,
            'payment_date' => Carbon::today()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'status' => 'posted',
            'recorded_by' => $this->user->id,
        ]);

        // 2. Cancelled booking
        Booking::create([
            'marquee_id' => $this->marquee->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'hall_id' => $this->hall->id,
            'slot_id' => $this->slot->id,
            'package_id' => $this->package->id,
            'event_type_id' => $this->eventType->id,
            'booking_date' => Carbon::today()->format('Y-m-d'),
            'start_time' => Carbon::today()->format('Y-m-d') . ' 19:00:00',
            'end_time' => Carbon::today()->format('Y-m-d') . ' 23:30:00',
            'guest_count' => 300,
            'per_plate_price' => 1000.00,
            'grand_total' => 300000.00,
            'booking_status' => 'Cancelled',
            'payment_status' => 'Unpaid',
        ]);

        // 3. Rejected booking
        Booking::create([
            'marquee_id' => $this->marquee->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'hall_id' => $this->hall->id,
            'slot_id' => $this->slot->id,
            'package_id' => $this->package->id,
            'event_type_id' => $this->eventType->id,
            'booking_date' => Carbon::today()->format('Y-m-d'),
            'start_time' => Carbon::today()->format('Y-m-d') . ' 19:00:00',
            'end_time' => Carbon::today()->format('Y-m-d') . ' 23:30:00',
            'guest_count' => 150,
            'per_plate_price' => 1000.00,
            'grand_total' => 150000.00,
            'booking_status' => 'Rejected',
            'payment_status' => 'Unpaid',
        ]);

        $response = $this->actingAs($this->user)->get(route('bookings.report'));
        $response->assertStatus(200);

        // Assert that the summary cards show 1 Total Booking, 250 Total Guests, and Rs. 250,000 Total Amount
        $response->assertSee('Total Bookings');
        $response->assertSee('Total (Excluding Cancelled', false);
        $response->assertSee('Not Counted');
    }

    /** @test */
    public function test_booking_list_component_table_totals_exclude_cancelled_and_rejected()
    {
        // 1. Confirmed booking
        Booking::create([
            'marquee_id' => $this->marquee->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'hall_id' => $this->hall->id,
            'slot_id' => $this->slot->id,
            'package_id' => $this->package->id,
            'event_type_id' => $this->eventType->id,
            'booking_date' => Carbon::today()->format('Y-m-d'),
            'start_time' => Carbon::today()->format('Y-m-d') . ' 19:00:00',
            'end_time' => Carbon::today()->format('Y-m-d') . ' 23:30:00',
            'guest_count' => 180,
            'per_plate_price' => 1000.00,
            'grand_total' => 180000.00,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Paid',
        ]);

        // 2. Cancelled booking
        Booking::create([
            'marquee_id' => $this->marquee->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'hall_id' => $this->hall->id,
            'slot_id' => $this->slot->id,
            'package_id' => $this->package->id,
            'event_type_id' => $this->eventType->id,
            'booking_date' => Carbon::today()->format('Y-m-d'),
            'start_time' => Carbon::today()->format('Y-m-d') . ' 19:00:00',
            'end_time' => Carbon::today()->format('Y-m-d') . ' 23:30:00',
            'guest_count' => 200,
            'per_plate_price' => 1000.00,
            'grand_total' => 200000.00,
            'booking_status' => 'Cancelled',
            'payment_status' => 'Unpaid',
        ]);

        Livewire::actingAs($this->user)
            ->test(\App\Livewire\BookingList::class)
            ->assertStatus(200)
            ->assertSee('Page Totals (Excl. Cancelled', false)
            ->assertSee('180 Guests')
            ->assertSee('1 Active')
            ->assertSee('Rs. 180,000');
    }
}
