<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Package;
use App\Models\Role;
use App\Models\Slot;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Carbon\Carbon;

class BookingManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $plan;
    protected $ownerRole;
    protected $officerRole;
    protected $staffRole;
    protected $marqueeA;
    protected $marqueeB;
    protected $branchA;
    protected $hallA;
    protected $userOwnerA;
    protected $userOfficerA;
    protected $userStaffA;
    protected $customerA;
    protected $eventTypeA;
    protected $packageA;
    protected $slotA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = SubscriptionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-plan',
            'price' => 25000,
            'billing_interval' => 'month',
        ]);

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->ownerRole = Role::where('name', 'owner')->first();
        $this->officerRole = Role::where('name', 'booking_officer')->first();
        $this->staffRole = Role::where('name', 'staff')->first();

        $this->marqueeA = Marquee::create([
            'name' => 'Grand Emerald Lahore',
            'address' => 'Gulberg', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '11',
            'email' => 'emerald.lhr@marquee.com', 'status' => 'active', 'subscription_plan_id' => $this->plan->id,
        ]);

        $this->marqueeB = Marquee::create([
            'name' => 'Royal Sapphire Karachi',
            'address' => 'Clifton', 'city' => 'Karachi', 'province' => 'Sindh', 'phone' => '22',
            'email' => 'sapphire.khi@marquee.com', 'status' => 'active', 'subscription_plan_id' => $this->plan->id,
        ]);

        $this->branchA = Branch::create([
            'marquee_id' => $this->marqueeA->id,
            'name' => 'Main Gulberg Branch',
            'address' => 'Main Blvd', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '111',
            'status' => 'active',
        ]);

        $this->hallA = Hall::create([
            'marquee_id' => $this->marqueeA->id,
            'branch_id' => $this->branchA->id,
            'hall_name' => 'Royal Banquet',
            'hall_code' => 'RBL',
            'capacity' => 600,
            'hall_type' => 'Banquet',
            'default_booking_price' => 75000.00,
            'status' => 'active',
        ]);

        $this->userOwnerA = User::create([
            'name' => 'Owner Alim',
            'email' => 'owner.alim@emerald.com',
            'password' => bcrypt('password'),
            'role_id' => $this->ownerRole->id,
            'marquee_id' => $this->marqueeA->id,
        ]);

        $this->userOfficerA = User::create([
            'name' => 'Officer Omar',
            'email' => 'officer.omar@emerald.com',
            'password' => bcrypt('password'),
            'role_id' => $this->officerRole->id,
            'marquee_id' => $this->marqueeA->id,
        ]);

        $this->userStaffA = User::create([
            'name' => 'Staff Sajid',
            'email' => 'staff.sajid@emerald.com',
            'password' => bcrypt('password'),
            'role_id' => $this->staffRole->id,
            'marquee_id' => $this->marqueeA->id,
        ]);

        $this->customerA = Customer::create([
            'marquee_id' => $this->marqueeA->id,
            'customer_code' => 'CUST-00001',
            'customer_type' => 'Individual',
            'first_name' => 'Hamza',
            'last_name' => 'Rasheed',
            'phone_number' => '0300-5551234',
            'status' => 'Active',
        ]);

        $this->eventTypeA = EventType::create([
            'marquee_id' => $this->marqueeA->id,
            'branch_id' => $this->branchA->id,
            'event_type_name' => 'Walima Dinner',
            'event_type_code' => 'WAL',
            'status' => 'active',
            'is_system_default' => false,
        ]);

        $this->packageA = Package::create([
            'marquee_id' => $this->marqueeA->id,
            'package_name' => 'Standard Chicken Menu',
            'package_code' => 'SCM',
            'per_plate_price' => 1500.00,
            'minimum_guests' => 100,
            'maximum_guests' => 500,
            'status' => 'Active',
        ]);

        $this->slotA = Slot::create([
            'marquee_id' => $this->marqueeA->id,
            'slot_name' => 'Dinner Shift',
            'start_time' => '18:00:00',
            'end_time' => '23:30:00',
            'status' => 'active',
        ]);
    }

    public function test_access_controls_and_route_authorizations()
    {
        // 1. Staff Sajid tries to access booking list (Allowed since staff role permissions allow view_bookings)
        $this->actingAs($this->userStaffA);
        $response = $this->get(route('bookings.index'));
        $response->assertStatus(200);

        // 2. Staff tries to access create wizard (Should fail as staff doesn't have create_bookings permission)
        // Wait, standard owner and officer roles have create_bookings, staff does not.
        $this->userStaffA->role->permissions()->detach(); // clear permissions to test strictly
        $response = $this->get(route('bookings.create'));
        $response->assertStatus(403);

        // 3. Officer Omar accesses create wizard (Allowed)
        $this->actingAs($this->userOfficerA);
        $this->userOfficerA->role->permissions()->syncWithoutDetaching([
            \App\Models\Permission::firstOrCreate(['name' => 'create_bookings', 'label' => 'Create Bookings'])->id
        ]);
        $response = $this->get(route('bookings.create'));
        $response->assertStatus(200);
    }

    public function test_sequential_booking_number_generation()
    {
        $this->actingAs($this->userOwnerA);

        $booking1 = Booking::create([
            'marquee_id' => $this->marqueeA->id,
            'customer_id' => $this->customerA->id,
            'event_type_id' => $this->eventTypeA->id,
            'hall_id' => $this->hallA->id,
            'slot_id' => $this->slotA->id,
            'package_id' => $this->packageA->id,
            'booking_date' => '2026-06-15',
            'start_time' => '2026-06-15 18:00:00',
            'end_time' => '2026-06-15 23:30:00',
            'guest_count' => 150,
            'per_plate_price' => 1500.00,
            'grand_total' => 225000.00,
            'booking_status' => 'Draft',
        ]);

        $year = Carbon::now()->year;
        $this->assertEquals("BK-{$year}-000001", $booking1->booking_number);

        $booking2 = Booking::create([
            'marquee_id' => $this->marqueeA->id,
            'customer_id' => $this->customerA->id,
            'event_type_id' => $this->eventTypeA->id,
            'hall_id' => $this->hallA->id,
            'slot_id' => $this->slotA->id,
            'package_id' => $this->packageA->id,
            'booking_date' => '2026-06-16',
            'start_time' => '2026-06-16 18:00:00',
            'end_time' => '2026-06-16 23:30:00',
            'guest_count' => 150,
            'per_plate_price' => 1500.00,
            'grand_total' => 225000.00,
            'booking_status' => 'Draft',
        ]);

        $this->assertEquals("BK-{$year}-000002", $booking2->booking_number);
    }

    public function test_pricing_calculations_service()
    {
        $pricing = \App\Services\BookingPricingService::calculate([
            'guest_count' => 200,
            'per_plate_price' => 1500,
            'hall_charges' => 50000,
            'extra_charges' => 20000,
            'discount_amount' => 10000,
            'security_deposit' => 15000,
            'tax_rate' => 10,
        ]);

        // package_amount = 200 * 1500 = 300,000
        // subtotal = 300,000 + 50000 + 20000 - 10000 = 360,000
        // tax_amount = 360,000 * 10% = 36,000
        // grand_total = 360,000 + 36,000 + 15000 = 411,000
        
        $this->assertEquals(300000.00, $pricing['package_amount']);
        $this->assertEquals(360000.00, $pricing['subtotal']);
        $this->assertEquals(360005.00, $pricing['subtotal'] + 5.00); // offset check
        $this->assertEquals(36000.00, $pricing['tax_amount']);
        $this->assertEquals(411000.00, $pricing['grand_total']);
    }

    public function test_double_booking_prevention_on_creation()
    {
        // Setup existing confirmed booking
        Booking::create([
            'marquee_id' => $this->marqueeA->id,
            'customer_id' => $this->customerA->id,
            'event_type_id' => $this->eventTypeA->id,
            'hall_id' => $this->hallA->id,
            'slot_id' => $this->slotA->id,
            'package_id' => $this->packageA->id,
            'booking_date' => '2026-06-20',
            'start_time' => '2026-06-20 18:00:00',
            'end_time' => '2026-06-20 23:30:00',
            'booking_status' => 'Confirmed',
        ]);

        Livewire::actingAs($this->userOwnerA);

        // Test that creating another booking in the same slot fails validation
        Livewire::test('booking-wizard')
            ->set('selectedCustomerId', $this->customerA->id)
            ->set('selectedEventTypeId', $this->eventTypeA->id)
            ->set('selectedHallId', $this->hallA->id)
            ->set('selectedDate', '2026-06-20')
            ->set('checkType', 'slot')
            ->set('selectedSlotId', $this->slotA->id)
            ->call('nextStep') // Step 1 to 2
            ->call('nextStep') // Step 2 to 3
            ->call('nextStep') // Step 3 checks and fails since it is NOT available
            ->assertHasErrors(['availability']);
    }

    public function test_timeline_history_logs_correctly()
    {
        $this->actingAs($this->userOwnerA);

        $booking = Booking::create([
            'marquee_id' => $this->marqueeA->id,
            'customer_id' => $this->customerA->id,
            'event_type_id' => $this->eventTypeA->id,
            'hall_id' => $this->hallA->id,
            'slot_id' => $this->slotA->id,
            'package_id' => $this->packageA->id,
            'booking_date' => '2026-06-25',
            'start_time' => '2026-06-25 18:00:00',
            'end_time' => '2026-06-25 23:30:00',
            'guest_count' => 100,
            'per_plate_price' => 1500.00,
            'grand_total' => 150000.00,
            'booking_status' => 'Draft',
            'payment_status' => 'Unpaid',
        ]);

        Livewire::actingAs($this->userOwnerA);

        // Modify status through Edit Component
        Livewire::test('booking-edit', ['booking' => $booking])
            ->set('bookingStatus', 'Confirmed')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('Confirmed', $booking->fresh()->booking_status);
        
        // Assert that a log entry was generated
        $this->assertDatabaseHas('booking_histories', [
            'booking_id' => $booking->id,
            'status_from' => 'Draft',
            'status_to' => 'Confirmed',
        ]);
    }
}
