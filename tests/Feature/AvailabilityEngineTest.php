<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\Slot;
use App\Models\Marquee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Carbon\Carbon;

class AvailabilityEngineTest extends TestCase
{
    use RefreshDatabase;

    protected $plan;
    protected $ownerRole;
    protected $marqueeA;
    protected $marqueeB;
    protected $branchA;
    protected $branchB;
    protected $hallA;
    protected $hallB;
    protected $userA;
    protected $userB;
    protected $slotDay;
    protected $slotNight;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = SubscriptionPlan::create([
            'name' => 'Premium',
            'slug' => 'premium',
            'price' => 15000,
            'billing_interval' => 'month',
        ]);

        $this->ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner']);

        $this->marqueeA = Marquee::create([
            'name' => 'Emerald Hall Lahore',
            'address' => 'Gulberg', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '1',
            'email' => 'a@emerald.com', 'status' => 'active', 'subscription_plan_id' => $this->plan->id,
        ]);

        $this->marqueeB = Marquee::create([
            'name' => 'Sapphire Hall Karachi',
            'address' => 'Clifton', 'city' => 'Karachi', 'province' => 'Sindh', 'phone' => '2',
            'email' => 'b@sapphire.com', 'status' => 'active', 'subscription_plan_id' => $this->plan->id,
        ]);

        $this->branchA = Branch::create([
            'marquee_id' => $this->marqueeA->id,
            'name' => 'Lahore Branch A',
            'address' => 'Gulberg', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '1',
            'status' => 'active',
        ]);

        $this->branchB = Branch::create([
            'marquee_id' => $this->marqueeB->id,
            'name' => 'Karachi Branch B',
            'address' => 'Clifton', 'city' => 'Karachi', 'province' => 'Sindh', 'phone' => '2',
            'status' => 'active',
        ]);

        $this->hallA = Hall::create([
            'marquee_id' => $this->marqueeA->id,
            'branch_id' => $this->branchA->id,
            'hall_name' => 'Crystal Palace A',
            'hall_code' => 'CPA',
            'capacity' => 500,
            'hall_type' => 'Banquet',
            'default_booking_price' => 50000.00,
            'status' => 'active',
        ]);

        $this->hallB = Hall::create([
            'marquee_id' => $this->marqueeB->id,
            'branch_id' => $this->branchB->id,
            'hall_name' => 'Royal Banquet B',
            'hall_code' => 'RBB',
            'capacity' => 400,
            'hall_type' => 'Marquee',
            'default_booking_price' => 40000.00,
            'status' => 'active',
        ]);

        $this->userA = User::create([
            'name' => 'Owner A',
            'email' => 'owner.a@emerald.com',
            'password' => bcrypt('password'),
            'role_id' => $this->ownerRole->id,
            'marquee_id' => $this->marqueeA->id,
        ]);

        $this->userB = User::create([
            'name' => 'Owner B',
            'email' => 'owner.b@sapphire.com',
            'password' => bcrypt('password'),
            'role_id' => $this->ownerRole->id,
            'marquee_id' => $this->marqueeB->id,
        ]);

        // Predefined slots for Marquee A
        $this->slotDay = Slot::create([
            'marquee_id' => $this->marqueeA->id,
            'slot_name' => 'Day Shift',
            'start_time' => '09:00:00',
            'end_time' => '16:00:00',
            'status' => 'active',
        ]);

        $this->slotNight = Slot::create([
            'marquee_id' => $this->marqueeA->id,
            'slot_name' => 'Night Shift',
            'start_time' => '18:00:00',
            'end_time' => '23:30:00',
            'status' => 'active',
        ]);
    }

    public function test_conflict_detection_calculations()
    {
        $service = new AvailabilityService();

        // Setup an existing Confirmed booking: 12:00 to 18:00
        Booking::create([
            'marquee_id' => $this->marqueeA->id,
            'hall_id' => $this->hallA->id,
            'booking_date' => '2026-06-15',
            'start_time' => '2026-06-15 12:00:00',
            'end_time' => '2026-06-15 18:00:00',
            'booking_status' => 'Confirmed',
        ]);

        // Case 1: Left-overlap (09:00 to 13:00) -> Conflicting
        $this->assertFalse($service->checkAvailability($this->hallA->id, '2026-06-15', '09:00', '13:00'));

        // Case 2: Right-overlap (17:00 to 21:00) -> Conflicting
        $this->assertFalse($service->checkAvailability($this->hallA->id, '2026-06-15', '17:00', '21:00'));

        // Case 3: Enclosure (13:00 to 17:00) -> Conflicting
        $this->assertFalse($service->checkAvailability($this->hallA->id, '2026-06-15', '13:00', '17:00'));

        // Case 4: Expansion (10:00 to 20:00) -> Conflicting
        $this->assertFalse($service->checkAvailability($this->hallA->id, '2026-06-15', '10:00', '20:00'));

        // Case 5: No overlap - early (08:00 to 11:30) -> Available
        $this->assertTrue($service->checkAvailability($this->hallA->id, '2026-06-15', '08:00', '11:30'));

        // Case 6: No overlap - late (18:30 to 23:00) -> Available
        $this->assertTrue($service->checkAvailability($this->hallA->id, '2026-06-15', '18:30', '23:00'));
    }

    public function test_midnight_crossing_calculations()
    {
        $service = new AvailabilityService();

        // Booking starting night of June 15 at 18:00 and ending past midnight (June 16 at 02:00)
        $start = Carbon::parse('2026-06-15 18:00:00');
        $end = Carbon::parse('2026-06-16 02:00:00');

        Booking::create([
            'marquee_id' => $this->marqueeA->id,
            'hall_id' => $this->hallA->id,
            'booking_date' => '2026-06-15',
            'start_time' => $start,
            'end_time' => $end,
            'booking_status' => 'Confirmed',
        ]);

        // Overlapping check: June 15, 23:00 to June 16, 01:00 -> Conflicting
        $this->assertFalse($service->checkAvailability($this->hallA->id, '2026-06-15', '23:00', '01:00'));

        // Non-overlapping check: June 16, 03:00 to 07:00 -> Available
        $this->assertTrue($service->checkAvailability($this->hallA->id, '2026-06-16', '03:00', '07:00'));
    }

    public function test_booking_status_validation_rules()
    {
        $service = new AvailabilityService();

        // 1. Reserved status blocks
        $reserved = Booking::create([
            'marquee_id' => $this->marqueeA->id,
            'hall_id' => $this->hallA->id,
            'booking_date' => '2026-06-15',
            'start_time' => '2026-06-15 10:00:00',
            'end_time' => '2026-06-15 14:00:00',
            'booking_status' => 'Reserved',
        ]);
        $this->assertFalse($service->checkAvailability($this->hallA->id, '2026-06-15', '11:00', '13:00'));

        // 2. Draft status does NOT block
        $reserved->update(['booking_status' => 'Draft']);
        $this->assertTrue($service->checkAvailability($this->hallA->id, '2026-06-15', '11:00', '13:00'));

        // 3. Cancelled status does NOT block
        $reserved->update(['booking_status' => 'Cancelled']);
        $this->assertTrue($service->checkAvailability($this->hallA->id, '2026-06-15', '11:00', '13:00'));

        // 4. Rejected status does NOT block
        $reserved->update(['booking_status' => 'Rejected']);
        $this->assertTrue($service->checkAvailability($this->hallA->id, '2026-06-15', '11:00', '13:00'));
    }

    public function test_available_and_booked_slots_helpers()
    {
        $service = new AvailabilityService();

        // Confirmed booking covering the Day Shift slot (09:00 - 16:00)
        Booking::create([
            'marquee_id' => $this->marqueeA->id,
            'hall_id' => $this->hallA->id,
            'booking_date' => '2026-06-15',
            'start_time' => '2026-06-15 10:00:00',
            'end_time' => '2026-06-15 14:00:00',
            'booking_status' => 'Confirmed',
        ]);

        $bookedSlots = $service->getBookedSlots($this->hallA->id, '2026-06-15');
        $availableSlots = $service->getAvailableSlots($this->hallA->id, '2026-06-15');

        $this->assertCount(1, $bookedSlots);
        $this->assertEquals($this->slotDay->id, $bookedSlots->first()->id);

        $this->assertCount(1, $availableSlots);
        $this->assertEquals($this->slotNight->id, $availableSlots->first()->id);
    }

    public function test_tenant_isolation_on_conflict_detection()
    {
        $service = new AvailabilityService();

        // Booking on Tenant B
        Booking::create([
            'marquee_id' => $this->marqueeB->id,
            'hall_id' => $this->hallB->id,
            'booking_date' => '2026-06-15',
            'start_time' => '2026-06-15 12:00:00',
            'end_time' => '2026-06-15 18:00:00',
            'booking_status' => 'Confirmed',
        ]);

        // Scoping context to User A (Tenant A)
        $this->actingAs($this->userA);

        // Verification query for Hall A (Tenant A) at the same range should be available,
        // because Tenant B's bookings must be isolated and not affect Tenant A.
        $this->assertTrue($service->checkAvailability($this->hallA->id, '2026-06-15', '13:00', '17:00'));
    }

    public function test_interactive_checker_component_logic()
    {
        // Confirmed booking covering Day Shift slot
        Booking::create([
            'marquee_id' => $this->marqueeA->id,
            'hall_id' => $this->hallA->id,
            'booking_date' => '2026-06-15',
            'start_time' => '2026-06-15 12:00:00',
            'end_time' => '2026-06-15 15:00:00',
            'booking_status' => 'Confirmed',
        ]);

        Livewire::actingAs($this->userA);

        // Check availability via Day Shift selection
        Livewire::test('availability-checker')
            ->set('selectedHallId', $this->hallA->id)
            ->set('selectedDate', '2026-06-15')
            ->set('checkType', 'slot')
            ->set('selectedSlotId', $this->slotDay->id)
            ->assertSet('isAvailable', false)
            ->assertSet('availabilityChecked', true)
            ->assertSee('NOT AVAILABLE');

        // Check availability via Night Shift selection (Free)
        Livewire::test('availability-checker')
            ->set('selectedHallId', $this->hallA->id)
            ->set('selectedDate', '2026-06-15')
            ->set('checkType', 'slot')
            ->set('selectedSlotId', $this->slotNight->id)
            ->assertSet('isAvailable', true)
            ->assertSet('availabilityChecked', true)
            ->assertSee('AVAILABLE');
    }
}
