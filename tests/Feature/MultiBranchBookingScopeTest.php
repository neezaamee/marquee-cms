<?php

namespace Tests\Feature;

use App\Livewire\AvailabilityChecker;
use App\Livewire\BookingEdit;
use App\Livewire\BookingOnePage;
use App\Livewire\BookingWizard;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Package;
use App\Models\Role;
use App\Models\Slot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MultiBranchBookingScopeTest extends TestCase
{
    use RefreshDatabase;

    protected Marquee $marquee;
    protected Branch $branchMain;
    protected Branch $branchCity;
    protected Hall $hallMain1;
    protected Hall $hallCity1;
    protected User $owner;
    protected User $branchManager;
    protected Customer $customer;
    protected EventType $eventType;
    protected Package $package;
    protected Slot $slot;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant marquee
        $this->marquee = Marquee::create([
            'name' => 'Royal Palms Group',
            'slug' => 'royal-palms-group',
            'is_active' => true,
            'status' => 'active',
            'email' => 'info@royalpalms.com',
            'phone' => '03001234567',
            'address' => 'Gulberg Main Blvd',
            'city' => 'Lahore',
            'province' => 'Punjab',
        ]);

        // Create 2 Branches for this marquee
        $this->branchMain = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Branch Gulberg',
            'code' => 'BR-GLB',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'address' => '12-Main Boulevard Gulberg',
            'phone' => '042-3571234',
            'status' => 'active',
            'is_head_office' => true,
            'tax_rate' => 16.00,
        ]);

        $this->branchCity = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'City Branch DHA',
            'code' => 'BR-DHA',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'address' => 'Phase 5 DHA',
            'phone' => '042-3575678',
            'status' => 'active',
            'is_head_office' => false,
            'tax_rate' => 13.00,
        ]);

        // Create Halls for each branch
        $this->hallMain1 = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branchMain->id,
            'hall_name' => 'Grand Crystal Hall',
            'hall_code' => 'HALL-GLB-01',
            'hall_type' => 'indoor',
            'capacity' => 500,
            'seating_capacity' => 500,
            'status' => 'active',
            'default_booking_price' => 50000,
        ]);

        $this->hallCity1 = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branchCity->id,
            'hall_name' => 'DHA Royal Arena',
            'hall_code' => 'HALL-DHA-01',
            'hall_type' => 'indoor',
            'capacity' => 300,
            'seating_capacity' => 300,
            'status' => 'active',
            'default_booking_price' => 35000,
        ]);

        // Create Business Owner Role
        $ownerRole = Role::firstOrCreate(
            ['name' => 'owner'],
            ['display_name' => 'Business Owner', 'label' => 'Business Owner', 'marquee_id' => $this->marquee->id, 'description' => 'Tenant Owner']
        );

        $bmRole = Role::firstOrCreate(
            ['name' => 'branch_manager'],
            ['display_name' => 'Branch Manager', 'label' => 'Branch Manager', 'marquee_id' => $this->marquee->id, 'description' => 'Branch Manager']
        );

        // Create Business Owner (multi-branch access)
        $this->owner = User::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Mian Owner',
            'email' => 'owner@royalpalms.com',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole->id,
            'user_type' => 'business_owner',
            'branch_id' => null,
            'status' => 'active',
        ]);
        $this->marquee->update(['owner_user_id' => $this->owner->id]);

        // Create Branch Manager (restricted to City Branch)
        $this->branchManager = User::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'City Manager',
            'email' => 'manager.dha@royalpalms.com',
            'password' => bcrypt('password'),
            'role_id' => $bmRole->id,
            'user_type' => 'branch_manager',
            'branch_id' => $this->branchCity->id,
            'status' => 'active',
        ]);

        // Create Customer
        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_type' => 'Individual',
            'customer_code' => 'CUST-001',
            'first_name' => 'Tariq',
            'last_name' => 'Jamil',
            'phone_number' => '03009876543',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'status' => 'Active',
        ]);

        // Create Event Type
        $this->eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Wedding Reception',
            'event_type_code' => 'EVT-WED-01',
            'status' => 'active',
        ]);

        // Create Package
        $this->package = Package::create([
            'marquee_id' => $this->marquee->id,
            'package_name' => 'Royal Buffet',
            'package_code' => 'PKG-BUF-01',
            'per_plate_price' => 2500,
            'minimum_guests' => 50,
            'status' => 'active',
        ]);

        // Create Slot
        $this->slot = Slot::create([
            'marquee_id' => $this->marquee->id,
            'slot_name' => 'Evening Shift',
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'status' => 'active',
        ]);
    }

    /**
     * Test 1: User helper getAccessibleBranches and hasAccessToBranch work correctly.
     */
    public function test_user_branch_accessibility_methods(): void
    {
        // Owner has access to all branches of the marquee
        $ownerBranches = $this->owner->getAccessibleBranches();
        $this->assertCount(2, $ownerBranches);
        $this->assertTrue($this->owner->hasAccessToBranch($this->branchMain->id));
        $this->assertTrue($this->owner->hasAccessToBranch($this->branchCity->id));

        // Branch Manager has access only to their assigned branch
        $bmBranches = $this->branchManager->getAccessibleBranches();
        $this->assertCount(1, $bmBranches);
        $this->assertEquals($this->branchCity->id, $bmBranches->first()->id);
        $this->assertTrue($this->branchManager->hasAccessToBranch($this->branchCity->id));
        $this->assertFalse($this->branchManager->hasAccessToBranch($this->branchMain->id));
    }

    /**
     * Test 2: BookingOnePage reactive branch selection and hall filtering.
     */
    public function test_booking_one_page_reactive_branch_selection(): void
    {
        $this->actingAs($this->owner);

        // Mount component
        $component = Livewire::test(BookingOnePage::class)
            ->assertSet('isMultiBranchUser', true)
            ->assertSet('selectedBranchId', (string)$this->branchMain->id);

        // When BranchMain is selected, halls list only contains Main Branch halls
        $this->assertEquals([$this->hallMain1->id], $component->get('hallsList')->pluck('id')->toArray());

        // Switch to BranchCity
        $component->set('selectedBranchId', (string)$this->branchCity->id);

        // Halls list should reactively update to only City Branch halls
        $this->assertEquals([$this->hallCity1->id], $component->get('hallsList')->pluck('id')->toArray());
        $this->assertEquals([(string)$this->hallCity1->id], $component->get('selectedHallIds'));
        // Tax rate should update to City Branch tax rate (13.00)
        $this->assertEquals(13.00, $component->get('taxRate'));
    }

    /**
     * Test 3: BookingOnePage automatically scopes single-branch users.
     */
    public function test_booking_one_page_single_branch_user_scoped(): void
    {
        $this->actingAs($this->branchManager);

        $component = Livewire::test(BookingOnePage::class)
            ->assertSet('isMultiBranchUser', false)
            ->assertSet('selectedBranchId', (string)$this->branchCity->id);

        $this->assertCount(1, $component->get('hallsList'));
    }

    /**
     * Test 4: Booking creation with selected branch persists branch_id in database.
     */
    public function test_booking_creation_persists_branch_id(): void
    {
        $this->actingAs($this->owner);

        $bookingDate = Carbon::tomorrow()->format('Y-m-d');

        Livewire::test(BookingOnePage::class)
            ->set('selectedBranchId', (string)$this->branchCity->id)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('selectedEventTypeId', $this->eventType->id)
            ->set('selectedHallIds', [(string)$this->hallCity1->id])
            ->set('selectedDate', $bookingDate)
            ->set('checkType', 'slot')
            ->set('selectedSlotId', $this->slot->id)
            ->set('selectedPackageId', $this->package->id)
            ->set('guestCount', 200)
            ->set('tentativeGuests', 200)
            ->set('perPlatePrice', 2500)
            ->set('bookingStatus', 'Confirmed')
            ->call('submitBooking')
            ->assertHasNoErrors();

        $booking = Booking::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($booking);
        $this->assertEquals($this->branchCity->id, $booking->branch_id);
        $this->assertEquals($this->hallCity1->id, $booking->hall_id);
    }

    /**
     * Test 5: Server rejects mismatched hall from another branch.
     */
    public function test_booking_creation_rejects_mismatched_branch_hall(): void
    {
        $this->actingAs($this->owner);

        $bookingDate = Carbon::tomorrow()->format('Y-m-d');

        Livewire::test(BookingOnePage::class)
            ->set('selectedBranchId', (string)$this->branchCity->id)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('selectedEventTypeId', $this->eventType->id)
            // Selecting hall from Main Branch while City Branch is active
            ->set('selectedHallIds', [(string)$this->hallMain1->id])
            ->set('selectedDate', $bookingDate)
            ->set('checkType', 'slot')
            ->set('selectedSlotId', $this->slot->id)
            ->set('selectedPackageId', $this->package->id)
            ->set('guestCount', 200)
            ->set('tentativeGuests', 200)
            ->set('perPlatePrice', 2500)
            ->set('bookingStatus', 'Confirmed')
            ->call('submitBooking')
            ->assertHasErrors(['selectedHallIds']);
    }

    /**
     * Test 6: Branch manager cannot create booking for an unauthorized branch.
     */
    public function test_branch_manager_cannot_create_booking_for_unauthorized_branch(): void
    {
        $this->actingAs($this->branchManager);

        $bookingDate = Carbon::tomorrow()->format('Y-m-d');

        Livewire::test(BookingOnePage::class)
            // Attempt to force branchMain
            ->set('selectedBranchId', (string)$this->branchMain->id)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('selectedEventTypeId', $this->eventType->id)
            ->set('selectedHallIds', [(string)$this->hallMain1->id])
            ->set('selectedDate', $bookingDate)
            ->set('checkType', 'slot')
            ->set('selectedSlotId', $this->slot->id)
            ->set('selectedPackageId', $this->package->id)
            ->set('guestCount', 200)
            ->set('tentativeGuests', 200)
            ->set('perPlatePrice', 2500)
            ->set('bookingStatus', 'Confirmed')
            ->call('submitBooking')
            ->assertHasErrors(['selectedBranchId']);
    }

    /**
     * Test 7: Simultaneous bookings at same time in different branches do NOT conflict.
     */
    public function test_cross_branch_simultaneous_bookings_no_conflict(): void
    {
        $bookingDate = Carbon::tomorrow()->format('Y-m-d');

        // Create booking in Branch 1 (Main Hall)
        $bookingMain = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branchMain->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hallMain1->id,
            'slot_id' => $this->slot->id,
            'booking_date' => $bookingDate,
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'guest_count' => 200,
            'booking_status' => 'Confirmed',
            'grand_total' => 500000,
        ]);
        $bookingMain->halls()->sync([$this->hallMain1->id]);

        // Branch 2 (City Hall) at the EXACT same date and time should be available
        $this->actingAs($this->owner);

        Livewire::test(BookingOnePage::class)
            ->set('selectedBranchId', (string)$this->branchCity->id)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('selectedEventTypeId', $this->eventType->id)
            ->set('selectedHallIds', [(string)$this->hallCity1->id])
            ->set('selectedDate', $bookingDate)
            ->set('checkType', 'slot')
            ->set('selectedSlotId', $this->slot->id)
            ->set('selectedPackageId', $this->package->id)
            ->set('guestCount', 150)
            ->set('tentativeGuests', 150)
            ->set('perPlatePrice', 2500)
            ->set('bookingStatus', 'Confirmed')
            ->call('submitBooking')
            ->assertHasNoErrors();

        $this->assertEquals(2, Booking::count());
    }

    /**
     * Test 8: BookingWizard reactive branch selection and persistence.
     */
    public function test_booking_wizard_branch_flow(): void
    {
        $this->actingAs($this->owner);

        $bookingDate = Carbon::tomorrow()->format('Y-m-d');

        Livewire::test(BookingWizard::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->call('nextStep') // to step 2
            ->assertSet('currentStep', 2)
            ->set('selectedBranchId', (string)$this->branchCity->id)
            ->set('selectedEventTypeId', $this->eventType->id)
            ->set('selectedHallIds', [(string)$this->hallCity1->id])
            ->set('selectedDate', $bookingDate)
            ->call('nextStep') // to step 3
            ->assertSet('currentStep', 3)
            ->set('checkType', 'slot')
            ->set('selectedSlotId', $this->slot->id)
            ->call('nextStep') // to step 4
            ->assertSet('currentStep', 4)
            ->set('selectedPackageId', $this->package->id)
            ->set('guestCount', 100)
            ->set('tentativeGuests', 100)
            ->set('perPlatePrice', 2500)
            ->call('nextStep') // to step 5
            ->assertSet('currentStep', 5)
            ->set('bookingStatus', 'Confirmed')
            ->call('submitBooking')
            ->assertHasNoErrors();

        $booking = Booking::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($booking);
        $this->assertEquals($this->branchCity->id, $booking->branch_id);
    }

    /**
     * Test 9: BookingEdit locks branch changes for Confirmed bookings.
     */
    public function test_booking_edit_locks_branch_on_confirmed(): void
    {
        $this->actingAs($this->owner);

        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branchMain->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hallMain1->id,
            'slot_id' => $this->slot->id,
            'booking_date' => Carbon::tomorrow()->format('Y-m-d'),
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'guest_count' => 200,
            'booking_status' => 'Confirmed',
            'grand_total' => 500000,
        ]);
        $booking->halls()->sync([$this->hallMain1->id]);

        Livewire::test(BookingEdit::class, ['booking' => $booking])
            ->assertSet('canChangeBranch', false)
            ->set('selectedBranchId', (string)$this->branchCity->id)
            // Branch should remain reset to original branchMain
            ->assertSet('selectedBranchId', (string)$this->branchMain->id);
    }

    /**
     * Test 10: AvailabilityChecker widget supports branch selection.
     */
    public function test_availability_checker_branch_switching(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(AvailabilityChecker::class)
            ->assertSet('isMultiBranch', true)
            ->set('selectedBranchId', (string)$this->branchCity->id)
            ->assertSet('selectedHallId', (string)$this->hallCity1->id)
            ->set('selectedBranchId', (string)$this->branchMain->id)
            ->assertSet('selectedHallId', (string)$this->hallMain1->id);
    }

    /**
     * Test 11: BookingController report filters correctly by branch.
     */
    public function test_booking_report_filtered_by_branch(): void
    {
        $this->actingAs($this->owner);

        // Booking in Main Branch
        $booking1 = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branchMain->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hallMain1->id,
            'booking_date' => Carbon::tomorrow()->format('Y-m-d'),
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'guest_count' => 200,
            'booking_status' => 'Confirmed',
            'grand_total' => 500000,
        ]);

        // Booking in City Branch
        $booking2 = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branchCity->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hallCity1->id,
            'booking_date' => Carbon::tomorrow()->format('Y-m-d'),
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'guest_count' => 150,
            'booking_status' => 'Confirmed',
            'grand_total' => 350000,
        ]);

        // Filter by branchCity
        $response = $this->get(route('bookings.report', ['filterBranch' => $this->branchCity->id]));
        $response->assertOk();
        $response->assertSee('DHA Royal Arena');
        $response->assertDontSee('Grand Crystal Hall');
    }

    /**
     * Test 12: Cross-branch booking numbers do not collide across different branches on the same day.
     */
    public function test_cross_branch_booking_number_collision_prevention(): void
    {
        $prefix = Carbon::now()->format('dmY');

        // Create booking in Branch Main
        $b1 = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branchMain->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hallMain1->id,
            'booking_date' => Carbon::tomorrow()->format('Y-m-d'),
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'guest_count' => 100,
            'booking_status' => 'Reserved',
            'grand_total' => 250000,
        ]);

        $this->assertEquals($prefix . '-000001', $b1->booking_number);

        // Branch Manager (scoped to branchCity) creates a booking on the same day
        $this->actingAs($this->branchManager);

        $b2 = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branchCity->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hallCity1->id,
            'booking_date' => Carbon::tomorrow()->format('Y-m-d'),
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'guest_count' => 120,
            'booking_status' => 'Reserved',
            'grand_total' => 300000,
        ]);

        $this->assertEquals($prefix . '-000002', $b2->booking_number);
    }

    /**
     * Test 13: Business Owner login loads assigned shift slots in Step 3 of Booking Wizard.
     */
    public function test_owner_booking_wizard_step_3_slot_selection(): void
    {
        // Owner with marquee_id = null linked via ownedMarquees
        $ownerUser = User::create([
            'name' => 'Owner Multi Marquee',
            'email' => 'owner.multim@example.com',
            'password' => bcrypt('password'),
            'role_id' => $this->owner->role_id,
            'marquee_id' => null,
        ]);
        $ownerUser->ownedMarquees()->attach($this->marquee->id);

        // Assign slot explicitly to hallCity1
        $this->hallCity1->slots()->sync([
            $this->slot->id => [
                'marquee_id' => $this->marquee->id,
                'created_by' => $ownerUser->id,
                'status' => 'active',
            ]
        ]);

        $bookingDate = Carbon::tomorrow()->addDays(2)->format('Y-m-d');

        Livewire::actingAs($ownerUser)
            ->test(BookingWizard::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->call('nextStep') // Step 2
            ->assertSet('currentStep', 2)
            ->set('selectedBranchId', (string)$this->branchCity->id)
            ->set('selectedEventTypeId', $this->eventType->id)
            ->set('selectedHallIds', [(string)$this->hallCity1->id])
            ->set('selectedDate', $bookingDate)
            ->call('nextStep') // Step 3
            ->assertSet('currentStep', 3)
            ->assertCount('availableSlotsList', 1)
            ->set('checkType', 'slot')
            ->set('selectedSlotId', (string)$this->slot->id)
            ->assertSet('isAvailable', true)
            ->assertSet('availabilityChecked', true)
            ->call('nextStep') // Step 4
            ->assertSet('currentStep', 4)
            ->assertHasNoErrors();
    }

    /**
     * Test 14: Business Owner login loads assigned shift slots in Booking One Page form.
     */
    public function test_owner_booking_one_page_slot_selection(): void
    {
        $ownerUser = User::create([
            'name' => 'Owner One Page',
            'email' => 'owner.onepage@example.com',
            'password' => bcrypt('password'),
            'role_id' => $this->owner->role_id,
            'marquee_id' => null,
        ]);
        $ownerUser->ownedMarquees()->attach($this->marquee->id);

        $bookingDate = Carbon::tomorrow()->addDays(3)->format('Y-m-d');

        Livewire::actingAs($ownerUser)
            ->test(BookingOnePage::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('selectedBranchId', (string)$this->branchCity->id)
            ->set('selectedEventTypeId', $this->eventType->id)
            ->set('selectedHallIds', [(string)$this->hallCity1->id])
            ->set('selectedDate', $bookingDate)
            ->set('checkType', 'slot')
            ->assertCount('availableSlotsList', 1)
            ->set('selectedSlotId', (string)$this->slot->id)
            ->assertSet('isAvailable', true)
            ->assertSet('availabilityChecked', true);
    }

    /**
     * Test 15: Booking list displays shift slot name and post payment action.
     */
    public function test_booking_list_shows_slot_name_and_payment_actions(): void
    {
        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branchCity->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hallCity1->id,
            'slot_id' => $this->slot->id,
            'booking_date' => Carbon::tomorrow()->format('Y-m-d'),
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'guest_count' => 200,
            'tentative_guests' => 200,
            'confirmed_guests' => 220,
            'per_plate_price' => 1500,
            'grand_total' => 330000,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Partially Paid',
            'created_by' => $this->owner->id,
        ]);

        Livewire::actingAs($this->owner)
            ->test(\App\Livewire\BookingList::class)
            ->assertSee($this->slot->slot_name)
            ->assertSee('Post Payment')
            ->assertDontSeeHtml('<span class="fas fa-hand-holding-usd me-1"></span>Pay');
    }

    /**
     * Test 16: Dishes already added to booking menu are excluded from autocomplete search.
     */
    public function test_booking_wizard_dish_search_excludes_added_dishes(): void
    {
        $category = \App\Models\MenuCategory::create([
            'marquee_id' => $this->marquee->id,
            'category_code' => 'MC01',
            'category_name' => 'Main Course',
            'status' => 'active',
        ]);

        $dish1 = \App\Models\MenuItem::create([
            'marquee_id' => $this->marquee->id,
            'category_id' => $category->id,
            'item_code' => 'ITM-001',
            'item_name' => 'Chicken Biryani Special',
            'selling_price' => 250.00,
            'cost_price' => 150.00,
            'status' => 'active',
        ]);
        $dish2 = \App\Models\MenuItem::create([
            'marquee_id' => $this->marquee->id,
            'category_id' => $category->id,
            'item_code' => 'ITM-002',
            'item_name' => 'Mutton Karahi Special',
            'selling_price' => 450.00,
            'cost_price' => 300.00,
            'status' => 'active',
        ]);

        $component = Livewire::actingAs($this->owner)
            ->test(BookingWizard::class)
            ->set('menuItemSearch', 'Special');

        $autocomplete = $component->get('menuItemsAutocomplete');
        $this->assertTrue(collect($autocomplete)->pluck('id')->contains($dish1->id));
        $this->assertTrue(collect($autocomplete)->pluck('id')->contains($dish2->id));

        // Select dish 1
        $component->call('selectMenuItem', $dish1->id);

        // Re-check autocomplete: dish 1 should be gone, dish 2 should remain
        $component->set('menuItemSearch', 'Special');
        $autocompleteAfter = $component->get('menuItemsAutocomplete');
        $this->assertFalse(collect($autocompleteAfter)->pluck('id')->contains($dish1->id));
        $this->assertTrue(collect($autocompleteAfter)->pluck('id')->contains($dish2->id));

        // Remove dish 1
        $component->call('removeMenuItem', 0);
        $component->set('menuItemSearch', 'Special');
        $autocompleteRestored = $component->get('menuItemsAutocomplete');
        $this->assertTrue(collect($autocompleteRestored)->pluck('id')->contains($dish1->id));
    }

    /**
     * Test 17: One Page booking form properly computes tentative and confirmed guests.
     */
    public function test_booking_one_page_tentative_and_confirmed_guests_calculation(): void
    {
        Livewire::actingAs($this->owner)
            ->test(BookingOnePage::class)
            ->set('tentativeGuests', 150)
            ->assertSet('guestCount', 150)
            ->assertSet('guestStatus', 'Tentative')
            ->set('confirmedGuests', 180)
            ->assertSet('guestCount', 180)
            ->assertSet('guestStatus', 'Confirmed')
            ->set('confirmedGuests', null)
            ->assertSet('guestCount', 150)
            ->assertSet('guestStatus', 'Tentative');
    }
}
