<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Slot;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class HallSlotTest extends TestCase
{
    use RefreshDatabase;

    protected $plan;
    protected $superAdminRole;
    protected $ownerRole;
    protected $managerRole;
    protected $createHallsPermission;
    protected $manageSettingsPermission;
    protected $viewHallsPermission;
    protected $marquee;
    protected $branch;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Roles
        $this->superAdminRole = Role::create(['name' => 'super_admin', 'label' => 'Super Admin']);
        $this->ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner']);
        $this->managerRole = Role::create(['name' => 'branch_manager', 'label' => 'Manager']);

        // 2. Setup Permissions
        $this->createHallsPermission = Permission::create(['name' => 'create_halls', 'label' => 'Create Halls']);
        $this->viewHallsPermission = Permission::create(['name' => 'view_halls', 'label' => 'View Halls']);
        $this->manageSettingsPermission = Permission::create(['name' => 'manage_settings', 'label' => 'Manage Settings']);

        $this->ownerRole->permissions()->attach([
            $this->createHallsPermission->id,
            $this->viewHallsPermission->id,
            $this->manageSettingsPermission->id
        ]);

        // 3. Setup SaaS plan & Tenant
        $this->plan = SubscriptionPlan::create([
            'name' => 'Standard',
            'slug' => 'standard',
            'price' => 10000,
            'billing_interval' => 'month',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Royal Events',
            'address' => 'Gulberg',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '+923001234567',
            'email' => 'royal@test.com',
            'subscription_plan_id' => $this->plan->id
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Gulberg Branch',
            'address' => 'Main Boulvard',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '+92423123456',
            'status' => 'active',
        ]);
    }

    /**
     * Test tenant isolation for Halls and Slots.
     */
    public function test_tenant_scoping_for_halls_and_slots()
    {
        $marqueeB = Marquee::create([
            'name' => 'Other Events', 'address' => 'DHA', 'city' => 'Karachi', 'province' => 'Sindh', 'phone' => '2', 'email' => 'other@test.com',
            'subscription_plan_id' => $this->plan->id
        ]);
        $branchB = Branch::create([
            'marquee_id' => $marqueeB->id, 'name' => 'DHA Branch', 'address' => 'DHA', 'city' => 'Karachi', 'province' => 'Sindh', 'phone' => '2'
        ]);

        // Owner A
        $ownerA = User::create([
            'name' => 'Owner A', 'email' => 'owner.a@test.com', 'password' => bcrypt('password'),
            'marquee_id' => $this->marquee->id, 'role_id' => $this->ownerRole->id
        ]);

        // Create halls and slots in scope of Marquee A (via Owner A)
        $this->actingAs($ownerA);
        
        $hallA = Hall::create([
            'branch_id' => $this->branch->id,
            'hall_name' => 'Hall A', 'hall_code' => 'HA', 'capacity' => 100, 'hall_type' => 'Marquee', 'default_booking_price' => 1000, 'status' => 'active'
        ]);

        $slotA = Slot::create([
            'slot_name' => 'Day Shift', 'start_time' => '13:00:00', 'end_time' => '16:00:00', 'status' => 'active'
        ]);

        // Owner B
        $ownerB = User::create([
            'name' => 'Owner B', 'email' => 'owner.b@test.com', 'password' => bcrypt('password'),
            'marquee_id' => $marqueeB->id, 'role_id' => $this->ownerRole->id
        ]);

        // Switch to Owner B
        $this->actingAs($ownerB);

        // Owner B should not see Marquee A's Hall or Slot
        $this->assertCount(0, Hall::all());
        $this->assertCount(0, Slot::all());

        // Create Hall and Slot in Marquee B
        $hallB = Hall::create([
            'branch_id' => $branchB->id,
            'hall_name' => 'Hall B', 'hall_code' => 'HB', 'capacity' => 200, 'hall_type' => 'Banquet', 'default_booking_price' => 2000, 'status' => 'active'
        ]);

        $slotB = Slot::create([
            'slot_name' => 'Night Shift', 'start_time' => '18:00:00', 'end_time' => '21:00:00', 'status' => 'active'
        ]);

        $this->assertCount(1, Hall::all());
        $this->assertEquals($hallB->id, Hall::first()->id);
        
        $this->assertCount(1, Slot::all());
        $this->assertEquals($slotB->id, Slot::first()->id);
    }

    /**
     * Test assigning slots to a hall.
     */
    public function test_hall_slot_assignment_relationship()
    {
        $owner = User::create([
            'name' => 'Owner', 'email' => 'owner@test.com', 'password' => bcrypt('password'),
            'marquee_id' => $this->marquee->id, 'role_id' => $this->ownerRole->id
        ]);
        $this->actingAs($owner);

        $hall = Hall::create([
            'branch_id' => $this->branch->id,
            'hall_name' => 'Main Hall', 'hall_code' => 'MH', 'capacity' => 300, 'hall_type' => 'Marquee', 'default_booking_price' => 5000, 'status' => 'active'
        ]);

        $slot = Slot::create([
            'slot_name' => 'Lunch Shift', 'start_time' => '11:00:00', 'end_time' => '14:00:00', 'status' => 'active'
        ]);

        // Attach slot
        $hall->slots()->attach($slot->id, [
            'marquee_id' => $this->marquee->id,
            'status' => 'active',
            'created_by' => $owner->id
        ]);

        $this->assertCount(1, $hall->slots);
        $this->assertEquals($slot->id, $hall->slots->first()->id);
    }

    /**
     * Test availability checking.
     */
    public function test_availability_service_checking_rules()
    {
        $service = new AvailabilityService();

        // 1. If bookings table doesn't exist, it should return true
        Schema::dropIfExists('bookings');
        $this->assertTrue($service->checkAvailability(1, '2026-07-10', 1));

        // 2. Dynamically create a temporary bookings table
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hall_id');
            $table->date('booking_date');
            $table->foreignId('slot_id');
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });

        // 3. Perform checks
        // No bookings yet -> available
        $this->assertTrue($service->checkAvailability(1, '2026-07-10', 1));

        // Create booking: Hall 1 on 2026-07-10 for Slot 1 (Day Shift)
        \Illuminate\Support\Facades\DB::table('bookings')->insert([
            'hall_id' => 1,
            'booking_date' => '2026-07-10',
            'slot_id' => 1,
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Rule #1: Same Date + Same Hall + Same Slot = NOT ALLOWED (booked)
        $this->assertFalse($service->checkAvailability(1, '2026-07-10', 1));

        // Rule #2: Same Date + Same Hall + Different Slot = ALLOWED
        $this->assertTrue($service->checkAvailability(1, '2026-07-10', 2));

        // Rule #3: Different Hall + Same Date + Same Slot = NOT ALLOWED (Venue-wide slot lockout)
        $this->assertFalse($service->checkAvailability(2, '2026-07-10', 1));

        // Clean up temporary table
        Schema::dropIfExists('bookings');
    }
}
