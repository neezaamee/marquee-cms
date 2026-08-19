<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiBusinessScopingTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdminRole;
    protected $businessOwnerRole;
    protected $areaManagerRole;
    protected $branchManagerRole;
    protected $accountantRole;
    protected $bookingOfficerRole;
    protected $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminRole = Role::create(['name' => 'super_admin', 'label' => 'Super Admin']);
        $this->businessOwnerRole = Role::create(['name' => 'business_owner', 'label' => 'Business Owner']);
        $this->areaManagerRole = Role::create(['name' => 'area_manager', 'label' => 'Area Manager']);
        $this->branchManagerRole = Role::create(['name' => 'branch_manager', 'label' => 'Branch Manager']);
        $this->accountantRole = Role::create(['name' => 'accountant', 'label' => 'Accountant']);
        $this->bookingOfficerRole = Role::create(['name' => 'booking_officer', 'label' => 'Booking Officer']);

        // Create basic permissions
        $viewBookings = Permission::create(['name' => 'view_bookings', 'label' => 'View Bookings']);
        $createBookings = Permission::create(['name' => 'create_bookings', 'label' => 'Create Bookings']);
        $editBookings = Permission::create(['name' => 'edit_bookings', 'label' => 'Edit Bookings']);
        $deleteBookings = Permission::create(['name' => 'delete_bookings', 'label' => 'Delete Bookings']);

        $this->businessOwnerRole->permissions()->sync([$viewBookings->id, $createBookings->id, $editBookings->id, $deleteBookings->id]);
        $this->areaManagerRole->permissions()->sync([$viewBookings->id]);
        $this->branchManagerRole->permissions()->sync([$viewBookings->id, $createBookings->id, $editBookings->id, $deleteBookings->id]);
        $this->accountantRole->permissions()->sync([$viewBookings->id]);
        $this->bookingOfficerRole->permissions()->sync([$viewBookings->id, $createBookings->id, $editBookings->id]);

        $this->plan = SubscriptionPlan::create([
            'name' => 'Standard',
            'slug' => 'standard',
            'code' => 'STANDARD',
            'price' => 1000,
        ]);
    }

    public function test_super_admin_can_create_business_owner_without_marquee()
    {
        $superAdmin = User::factory()->create(['role_id' => $this->superAdminRole->id]);

        $businessOwner = User::create([
            'name' => 'Ali',
            'email' => 'ali@abcgroup.com',
            'username' => 'ali_abc',
            'password' => bcrypt('password123'),
            'role_id' => $this->businessOwnerRole->id,
            'marquee_id' => null,
            'branch_id' => null,
        ]);

        $this->assertTrue($businessOwner->isBusinessOwner());
        $this->assertNull($businessOwner->marquee_id);
        $this->assertCount(0, $businessOwner->ownedMarquees);
    }

    public function test_business_owner_can_own_and_switch_multiple_marquees()
    {
        $businessOwner = User::create([
            'name' => 'Ali',
            'email' => 'ali@abcgroup.com',
            'username' => 'ali_abc',
            'password' => bcrypt('password123'),
            'role_id' => $this->businessOwnerRole->id,
        ]);

        $sheraton = Marquee::create([
            'name' => 'The Sheraton Marquee',
            'business_type' => 'Banquet',
            'address' => 'Address 1',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001111111',
            'email' => 'sheraton@test.com',
            'tax_authority' => 'PRA',
            'status' => 'active',
            'is_setup_completed' => true,
        ]);

        $star = Marquee::create([
            'name' => 'The Star Marquee',
            'business_type' => 'Banquet',
            'address' => 'Address 2',
            'city' => 'Karachi',
            'province' => 'Sindh',
            'phone' => '03002222222',
            'email' => 'star@test.com',
            'tax_authority' => 'SRB',
            'status' => 'active',
            'is_setup_completed' => true,
        ]);

        $businessOwner->ownedMarquees()->syncWithoutDetaching([$sheraton->id, $star->id]);

        $this->assertCount(2, $businessOwner->fresh()->ownedMarquees);
        $this->assertEquals($sheraton->id, $businessOwner->getActiveMarqueeId());

        // Test context switching via controller
        $this->actingAs($businessOwner)
            ->post(route('marquee.switch'), ['marquee_id' => $star->id])
            ->assertSessionHas('active_marquee_id', $star->id);

        $this->assertEquals($star->id, $businessOwner->getActiveMarqueeId());
    }

    public function test_area_manager_has_read_only_access_across_owner_marquees()
    {
        $businessOwner = User::create([
            'name' => 'Ali Owner',
            'email' => 'owner@abcgroup.com',
            'username' => 'ali_owner',
            'password' => bcrypt('password123'),
            'role_id' => $this->businessOwnerRole->id,
        ]);

        $marquee1 = Marquee::create([
            'name' => 'Marquee 1',
            'business_type' => 'Banquet',
            'address' => 'Addr 1',
            'city' => 'City 1',
            'province' => 'Prov 1',
            'phone' => '03001234567',
            'email' => 'm1@test.com',
            'tax_authority' => 'PRA',
            'status' => 'active',
            'is_setup_completed' => true,
        ]);

        $businessOwner->ownedMarquees()->syncWithoutDetaching([$marquee1->id]);

        $areaManager = User::create([
            'name' => 'Area Manager User',
            'email' => 'areamanager@abcgroup.com',
            'username' => 'area_mgr',
            'password' => bcrypt('password123'),
            'role_id' => $this->areaManagerRole->id,
            'marquee_id' => $marquee1->id,
        ]);

        $this->assertTrue($areaManager->isAreaManager());
        $this->assertTrue($areaManager->hasPermission('view_bookings'));
        $this->assertFalse($areaManager->hasPermission('create_bookings'));
    }

    public function test_booking_policy_enforces_branch_scoping()
    {
        $marquee = Marquee::create([
            'name' => 'Elite Marquee',
            'business_type' => 'Banquet',
            'address' => 'Main Blvd',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001112233',
            'email' => 'elite@test.com',
            'tax_authority' => 'PRA',
            'status' => 'active',
            'is_setup_completed' => true,
        ]);

        $branchA = Branch::create([
            'marquee_id' => $marquee->id,
            'name' => 'Branch Alpha',
            'address' => 'Alpha St',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001112233',
            'status' => 'active',
        ]);

        $branchB = Branch::create([
            'marquee_id' => $marquee->id,
            'name' => 'Branch Beta',
            'address' => 'Beta St',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03004445566',
            'status' => 'active',
        ]);

        $hallA = Hall::create([
            'marquee_id' => $marquee->id,
            'branch_id' => $branchA->id,
            'hall_name' => 'Grand Hall A',
            'hall_code' => 'GHA',
            'capacity' => 500,
            'hall_type' => 'Banquet',
            'default_booking_price' => 50000.00,
            'status' => 'active',
        ]);

        $hallB = Hall::create([
            'marquee_id' => $marquee->id,
            'branch_id' => $branchB->id,
            'hall_name' => 'Royal Hall B',
            'hall_code' => 'RHB',
            'capacity' => 300,
            'hall_type' => 'Banquet',
            'default_booking_price' => 40000.00,
            'status' => 'active',
        ]);

        $bookingA = Booking::create([
            'marquee_id' => $marquee->id,
            'branch_id' => $branchA->id,
            'hall_id' => $hallA->id,
            'booking_date' => '2026-07-01',
            'start_time' => '2026-07-01 12:00:00',
            'end_time' => '2026-07-01 16:00:00',
            'booking_status' => 'Confirmed',
            'payment_status' => 'Unpaid',
            'guest_count' => 200,
            'per_plate_price' => 1500.00,
            'subtotal' => 300000.00,
            'grand_total' => 300000.00,
        ]);

        $bookingB = Booking::create([
            'marquee_id' => $marquee->id,
            'branch_id' => $branchB->id,
            'hall_id' => $hallB->id,
            'booking_date' => '2026-07-01',
            'start_time' => '2026-07-01 12:00:00',
            'end_time' => '2026-07-01 16:00:00',
            'booking_status' => 'Confirmed',
            'payment_status' => 'Unpaid',
            'guest_count' => 150,
            'per_plate_price' => 1200.00,
            'subtotal' => 180000.00,
            'grand_total' => 180000.00,
        ]);

        $branchManagerA = User::create([
            'name' => 'Branch Manager A',
            'email' => 'bm.a@test.com',
            'username' => 'bm_a',
            'password' => bcrypt('password123'),
            'role_id' => $this->branchManagerRole->id,
            'marquee_id' => $marquee->id,
            'branch_id' => $branchA->id,
        ]);

        // Branch Manager A can view & update Booking A in Branch A
        $this->assertTrue($branchManagerA->can('view', $bookingA));
        $this->assertTrue($branchManagerA->can('update', $bookingA));

        // Branch Manager A cannot view or update Booking B in Branch B
        $this->assertFalse($branchManagerA->can('view', $bookingB));
        $this->assertFalse($branchManagerA->can('update', $bookingB));
    }

    public function test_availability_conflict_is_scoped_per_hall()
    {
        $service = new AvailabilityService();

        $marquee = Marquee::create([
            'name' => 'Multi Hall Marquee',
            'business_type' => 'Banquet',
            'address' => 'Main Blvd',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001112233',
            'email' => 'multihall@test.com',
            'tax_authority' => 'PRA',
            'status' => 'active',
            'is_setup_completed' => true,
        ]);

        $branch = Branch::create([
            'marquee_id' => $marquee->id,
            'name' => 'Main Branch',
            'address' => 'Address',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001112233',
            'status' => 'active',
        ]);

        $hall1 = Hall::create([
            'marquee_id' => $marquee->id,
            'branch_id' => $branch->id,
            'hall_name' => 'Hall 1',
            'hall_code' => 'H1',
            'capacity' => 500,
            'hall_type' => 'Banquet',
            'default_booking_price' => 50000.00,
            'status' => 'active',
        ]);

        $hall2 = Hall::create([
            'marquee_id' => $marquee->id,
            'branch_id' => $branch->id,
            'hall_name' => 'Hall 2',
            'hall_code' => 'H2',
            'capacity' => 300,
            'hall_type' => 'Banquet',
            'default_booking_price' => 40000.00,
            'status' => 'active',
        ]);

        // Create confirmed booking on Hall 1
        Booking::create([
            'marquee_id' => $marquee->id,
            'branch_id' => $branch->id,
            'hall_id' => $hall1->id,
            'booking_date' => '2026-08-20',
            'start_time' => '2026-08-20 18:00:00',
            'end_time' => '2026-08-20 23:00:00',
            'booking_status' => 'Confirmed',
        ]);

        // Hall 1 is NOT available during the slot
        $this->assertFalse($service->checkAvailability($hall1->id, '2026-08-20', '19:00', '22:00'));

        // Hall 2 IS available at the exact same slot
        $this->assertTrue($service->checkAvailability($hall2->id, '2026-08-20', '19:00', '22:00'));
    }
}
