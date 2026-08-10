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
        Carbon::setTestNow(Carbon::parse('2026-06-25 12:00:00'));

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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
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

        $prefix = Carbon::now()->format('dmY');
        $this->assertEquals("{$prefix}-000001", $booking1->booking_number);

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

        $this->assertEquals("{$prefix}-000002", $booking2->booking_number);
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
        $futureDate = Carbon::today()->addDays(5)->format('Y-m-d');

        // Setup existing confirmed booking
        Booking::create([
            'marquee_id' => $this->marqueeA->id,
            'customer_id' => $this->customerA->id,
            'event_type_id' => $this->eventTypeA->id,
            'hall_id' => $this->hallA->id,
            'slot_id' => $this->slotA->id,
            'package_id' => $this->packageA->id,
            'booking_date' => $futureDate,
            'start_time' => "{$futureDate} 18:00:00",
            'end_time' => "{$futureDate} 23:30:00",
            'booking_status' => 'Confirmed',
        ]);

        Livewire::actingAs($this->userOwnerA);

        // Test that creating another booking in the same slot fails validation
        Livewire::test('booking-wizard')
            ->set('selectedCustomerId', $this->customerA->id)
            ->set('selectedEventTypeId', $this->eventTypeA->id)
            ->set('selectedHallIds', [(string)$this->hallA->id])
            ->set('selectedDate', $futureDate)
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

    public function test_component_math_calculations_with_string_inputs()
    {
        Livewire::actingAs($this->userOwnerA);

        // Create an ExtraService (Addon)
        $addon = \App\Models\ExtraService::create([
            'marquee_id' => $this->marqueeA->id,
            'service_name' => 'Premium Stage Decor',
            'default_price' => 10000.00,
            'status' => 'Active',
        ]);

        // 1. Test Wizard
        Livewire::test('booking-wizard')
            ->set('selectedCustomerId', $this->customerA->id)
            ->set('selectedEventTypeId', $this->eventTypeA->id)
            ->set('selectedHallIds', [(string)$this->hallA->id])
            ->set('selectedDate', '2026-06-25')
            ->set('selectedPackageId', $this->packageA->id)
            // Send strings & empty values
            ->set('guestCount', '150')
            ->set('perPlatePrice', '1200.50')
            ->set('hallCharges', '')
            ->set("selectedAddons.{$addon->id}.selected", true)
            ->set("selectedAddons.{$addon->id}.price", '15000') // string price
            ->set("selectedAddons.{$addon->id}.quantity", '1') // string quantity
            ->set('discountAmount', '')
            ->set('securityDeposit', '10000.50')
            ->set('taxRate', '13')
            ->call('recalculatePrices')
            ->assertSet('guestCount', 150)
            ->assertSet('perPlatePrice', 1200.50)
            ->assertSet('hallCharges', 0.00)
            ->assertSet('extraCharges', 15000.00)
            ->assertSet('discountAmount', 0.00)
            ->assertSet('securityDeposit', 10000.50)
            ->assertSet('taxRate', 13.00)
            ->assertHasNoErrors();

        // 2. Test Edit Component
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

        Livewire::test('booking-edit', ['booking' => $booking])
            ->set('guestCount', '')
            ->set('perPlatePrice', '1400')
            ->set('hallCharges', '50000')
            ->set('extraCharges', '')
            ->set('discountAmount', '5000')
            ->set('securityDeposit', '')
            ->set('taxRate', '10')
            ->call('recalculatePrices')
            ->assertSet('guestCount', 0)
            ->assertSet('perPlatePrice', 1400.00)
            ->assertSet('hallCharges', 50000.00)
            ->assertSet('extraCharges', 0.00)
            ->assertSet('discountAmount', 5000.00)
            ->assertSet('securityDeposit', 0.00)
            ->assertSet('taxRate', 10.00)
            ->assertHasNoErrors();
    }

    public function test_wizard_submits_booking_successfully()
    {
        Livewire::actingAs($this->userOwnerA);

        Livewire::test('booking-wizard')
            // Step 1: Customer Selection
            ->set('selectedCustomerId', $this->customerA->id)
            ->call('nextStep') // step 1 -> 2
            ->assertSet('currentStep', 2)

            // Step 2: Event Details
            ->set('selectedEventTypeId', $this->eventTypeA->id)
            ->set('selectedHallIds', [(string)$this->hallA->id])
            ->set('selectedDate', '2026-06-25')
            ->call('nextStep') // step 2 -> 3
            ->assertSet('currentStep', 3)

            // Step 3: Shift / Slot Selection
            ->set('checkType', 'slot')
            ->set('selectedSlotId', $this->slotA->id)
            ->call('nextStep') // step 3 -> 4
            ->assertSet('currentStep', 4)

            // Step 4: Package & Pricing
            ->set('selectedPackageId', $this->packageA->id)
            ->set('guestCount', 150)
            ->set('perPlatePrice', 1500)
            ->set('hallCharges', 20000)
            ->set('extraCharges', 10000)
            ->set('discountAmount', 5000)
            ->set('securityDeposit', 15000)
            ->set('taxRate', 13)
            ->call('nextStep') // step 4 -> 5
            ->assertSet('currentStep', 5)

            // Step 5: Review & Instructions
            ->set('specialInstructions', 'Please set up round tables.')
            ->set('bookingStatus', 'Confirmed')
            ->call('submitBooking')
            ->assertHasNoErrors();

        // Retrieve created booking and verify details
        $booking = Booking::first();
        $this->assertNotNull($booking);

        $this->assertDatabaseHas('bookings', [
            'customer_id' => $this->customerA->id,
            'event_type_id' => $this->eventTypeA->id,
            'hall_id' => $this->hallA->id,
            'booking_date' => '2026-06-25 00:00:00',
            'guest_count' => 150,
            'booking_status' => 'Confirmed',
            'special_instructions' => 'Please set up round tables.',
        ]);
    }

    public function test_wizard_registers_addons_and_customized_menus()
    {
        Livewire::actingAs($this->userOwnerA);

        // Create an ExtraService (Addon)
        $addon = \App\Models\ExtraService::create([
            'marquee_id' => $this->marqueeA->id,
            'service_name' => 'Premium Stage Decor',
            'default_price' => 50000.00,
            'status' => 'Active',
        ]);

        // Create a MenuCategory first
        $category = \App\Models\MenuCategory::create([
            'marquee_id' => $this->marqueeA->id,
            'category_name' => 'Main Course',
            'category_code' => 'MAIN',
            'status' => 'Active',
        ]);

        // Create some MenuItems and associate them to the package
        $menuItem1 = \App\Models\MenuItem::create([
            'marquee_id' => $this->marqueeA->id,
            'category_id' => $category->id,
            'item_name' => 'Chicken Korma',
            'item_code' => 'CK-KORMA',
            'selling_price' => 300.00,
            'status' => 'Active',
        ]);
        $menuItem2 = \App\Models\MenuItem::create([
            'marquee_id' => $this->marqueeA->id,
            'category_id' => $category->id,
            'item_name' => 'Chicken Karahi',
            'item_code' => 'CK-KARAHI',
            'selling_price' => 350.00,
            'status' => 'Active',
        ]);

        $this->packageA->menuItems()->sync([$menuItem1->id]);

        // Run the Booking Wizard
        $wizard = Livewire::test('booking-wizard')
            ->set('selectedCustomerId', $this->customerA->id)
            ->call('nextStep') // Step 1 -> 2
            ->set('selectedEventTypeId', $this->eventTypeA->id)
            ->set('selectedHallIds', [(string)$this->hallA->id])
            ->set('selectedDate', '2026-06-25')
            ->call('nextStep') // Step 2 -> 3
            ->set('checkType', 'slot')
            ->set('selectedSlotId', $this->slotA->id)
            ->call('nextStep') // Step 3 -> 4
            ->set('selectedPackageId', $this->packageA->id);

        // Assert menu item copied from package
        $wizard->assertSet('bookingMenuItems', [
            [
                'id' => $menuItem1->id,
                'item_name' => 'Chicken Korma',
                'urdu_name' => null,
                'custom_note' => '',
                'managed_by_host' => false,
            ]
        ]);

        // Customize menu items: Swap or add a dish
        $wizard->set('selectedMenuItemToAdd', $menuItem2->id)
            ->call('addMenuItem');

        // It should now have both items in bookingMenuItems
        $wizard->assertSet('bookingMenuItems', [
            [
                'id' => $menuItem1->id,
                'item_name' => 'Chicken Korma',
                'urdu_name' => null,
                'custom_note' => '',
                'managed_by_host' => false,
            ],
            [
                'id' => $menuItem2->id,
                'item_name' => 'Chicken Karahi',
                'urdu_name' => null,
                'custom_note' => '',
                'managed_by_host' => false,
            ]
        ]);

        // Add custom note
        $wizard->set('bookingMenuItems.0.custom_note', 'Spicy chicken');

        // Select the addon
        $wizard->set("selectedAddons.{$addon->id}.selected", true)
            ->set("selectedAddons.{$addon->id}.quantity", 2)
            ->call('recalculatePrices');

        // Assert extra charges updated
        // unit_price = 50000 * 2 = 100000.00
        $wizard->assertSet('extraCharges', 100000.00);

        // Move to step 5 and submit
        $wizard->call('nextStep') // Step 4 -> 5
            ->set('bookingStatus', 'Confirmed')
            ->call('submitBooking')
            ->assertHasNoErrors();

        // Retrieve created booking and verify details
        $booking = Booking::orderBy('id', 'desc')->first();
        $this->assertNotNull($booking);

        // Verify extra services are saved
        $this->assertDatabaseHas('booking_extra_services', [
            'booking_id' => $booking->id,
            'extra_service_id' => $addon->id,
            'service_name' => 'Premium Stage Decor',
            'unit_price' => 50000.00,
            'quantity' => 2,
            'total_price' => 100000.00,
        ]);

        // Verify custom menu items are saved
        $this->assertDatabaseHas('booking_menu_items', [
            'booking_id' => $booking->id,
            'menu_item_id' => $menuItem1->id,
            'custom_note' => 'Spicy chicken',
        ]);
        $this->assertDatabaseHas('booking_menu_items', [
            'booking_id' => $booking->id,
            'menu_item_id' => $menuItem2->id,
            'custom_note' => null,
        ]);
    }

    public function test_view_details_page_payment_registration()
    {
        // Create a booking
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

        // Initialize Livewire component
        $viewComponent = Livewire::test('booking-view', ['booking' => $booking])
            ->set('amountPaid', 50000.00)
            ->set('paymentMethod', 'Bank Transfer')
            ->set('transactionReference', 'TRX-998877')
            ->set('paymentNote', 'Initial deposit paid.')
            ->call('recordPayment')
            ->assertHasNoErrors();

        // Payment status should now be 'Partially Paid'
        $booking->refresh();
        $this->assertEquals('Partially Paid', $booking->payment_status);

        // Sum of payments should be 50,000
        $this->assertEquals(50000.00, $booking->payments()->sum('amount'));

        // Let's pay the rest
        $viewComponent->set('amountPaid', 100000.00)
            ->set('paymentMethod', 'Cash')
            ->call('recordPayment')
            ->assertHasNoErrors();

        // Payment status should now be 'Paid'
        $booking->refresh();
        $this->assertEquals('Paid', $booking->payment_status);
        $this->assertEquals(150000.00, $booking->payments()->sum('amount'));
    }

    public function test_deposit_release_handles_damages_refund_amounts_and_notes()
    {
        // Create a booking
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
            'security_deposit' => 20000.00,
            'grand_total' => 170000.00,
            'booking_status' => 'Draft',
            'payment_status' => 'Unpaid',
            'deposit_status' => 'Held',
        ]);

        Livewire::actingAs($this->userOwnerA);

        // Case 1: Partial refund / deduction
        $viewComponent = Livewire::test('booking-view', ['booking' => $booking])
            ->set('depositAction', 'refund_partial')
            ->set('depositRefundedAmount', 15000.00)
            ->set('depositDeductedAmount', 5000.00)
            ->set('depositNotes', 'Deducted Rs. 5000 for sofa damage.')
            ->call('processDeposit')
            ->assertHasNoErrors();

        $booking->refresh();
        $this->assertEquals('Deducted', $booking->deposit_status);
        $this->assertEquals(15000.00, $booking->deposit_refunded_amount);
        $this->assertEquals(5000.00, $booking->deposit_deducted_amount);
        $this->assertEquals('Deducted Rs. 5000 for sofa damage.', $booking->deposit_notes);

        // Reset deposit status to test full refund
        $booking->update([
            'deposit_status' => 'Held',
            'deposit_refunded_amount' => 0.00,
            'deposit_deducted_amount' => 0.00,
            'deposit_notes' => null,
        ]);

        // Case 2: Full refund
        Livewire::test('booking-view', ['booking' => $booking])
            ->set('depositAction', 'refund_full')
            ->call('processDeposit')
            ->assertHasNoErrors();

        $booking->refresh();
        $this->assertEquals('Refunded', $booking->deposit_status);
        $this->assertEquals(20000.00, $booking->deposit_refunded_amount);
        $this->assertEquals(0.00, $booking->deposit_deducted_amount);
    }

    public function test_editing_locked_bookings_fails_unless_owner()
    {
        // Create a Completed booking
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
            'booking_status' => 'Cancelled',
            'payment_status' => 'Paid',
        ]);

        // Try accessing route with Booking Officer (not an owner) -> should redirect
        $this->actingAs($this->userOfficerA);
        $response = $this->get(route('bookings.edit', $booking->id));
        $response->assertRedirect(route('bookings.show', $booking->id));

        // Try accessing route with Owner (Allowed) -> should load successfully
        $this->actingAs($this->userOwnerA);
        $responseOwner = $this->get(route('bookings.edit', $booking->id));
        $responseOwner->assertStatus(200);
    }

    public function test_multi_hall_booking_and_conflict_detection()
    {
        $futureDate = Carbon::today()->addDays(5)->format('Y-m-d');

        $hallB = Hall::create([
            'marquee_id' => $this->marqueeA->id,
            'branch_id' => $this->branchA->id,
            'hall_name' => 'Sapphire Lounge',
            'hall_code' => 'SLL',
            'capacity' => 400,
            'hall_type' => 'Banquet',
            'default_booking_price' => 50000.00,
            'status' => 'active',
        ]);

        // Setup existing confirmed booking in Hall B
        Booking::create([
            'marquee_id' => $this->marqueeA->id,
            'customer_id' => $this->customerA->id,
            'event_type_id' => $this->eventTypeA->id,
            'hall_id' => $hallB->id,
            'slot_id' => $this->slotA->id,
            'package_id' => $this->packageA->id,
            'booking_date' => $futureDate,
            'start_time' => "{$futureDate} 18:00:00",
            'end_time' => "{$futureDate} 23:30:00",
            'booking_status' => 'Confirmed',
        ]);

        Livewire::actingAs($this->userOwnerA);

        // Test that checking availability for both Hall A and Hall B on same slot fails
        Livewire::test('booking-wizard')
            ->set('selectedCustomerId', $this->customerA->id)
            ->set('selectedEventTypeId', $this->eventTypeA->id)
            ->set('selectedHallIds', [(string)$this->hallA->id, (string)$hallB->id])
            ->set('selectedDate', $futureDate)
            ->set('checkType', 'slot')
            ->set('selectedSlotId', $this->slotA->id)
            ->call('nextStep') // Step 1 to 2
            ->call('nextStep') // Step 2 to 3
            ->call('nextStep') // Step 3 checks and fails since Hall B has conflict
            ->assertHasErrors(['availability']);
            
        // Test that checking availability for only Hall A fails due to venue-wide slot lockout
        Livewire::test('booking-wizard')
            ->set('selectedCustomerId', $this->customerA->id)
            ->set('selectedEventTypeId', $this->eventTypeA->id)
            ->set('selectedHallIds', [(string)$this->hallA->id])
            ->set('selectedDate', $futureDate)
            ->set('checkType', 'slot')
            ->set('selectedSlotId', $this->slotA->id)
            ->call('nextStep') // Step 1 to 2
            ->call('nextStep') // Step 2 to 3
            ->call('nextStep') // Step 3 checks and fails
            ->assertHasErrors(['availability']);
    }

    public function test_no_food_booking_recalculates_to_zero_plate_charges()
    {
        Livewire::actingAs($this->userOwnerA);

        Livewire::test('booking-wizard')
            // Step 1: Customer Selection
            ->set('selectedCustomerId', $this->customerA->id)
            ->call('nextStep')

            // Step 2: Event Details
            ->set('selectedEventTypeId', $this->eventTypeA->id)
            ->set('selectedHallIds', [(string)$this->hallA->id])
            ->set('selectedDate', '2026-06-25')
            ->call('nextStep')

            // Step 3: Shift / Slot Selection
            ->set('checkType', 'slot')
            ->set('selectedSlotId', $this->slotA->id)
            ->call('nextStep')

            // Step 4: Packages & Pricing (enable Sitting Plan Only / No Food)
            ->set('noFood', true)
            ->set('guestCount', 200)
            ->set('hallCharges', 50000)
            ->set('discountAmount', 0)
            ->set('securityDeposit', 10000)
            ->set('taxRate', 0)
            ->call('recalculatePrices')
            ->assertSet('perPlatePrice', 0.00)
            ->assertSet('packageAmount', 0.00)
            ->assertSet('subtotal', 50000.00)
            ->assertSet('grandTotal', 60000.00) // subtotal + security deposit
            ->call('nextStep')

            // Step 5: Submit
            ->set('bookingStatus', 'Confirmed')
            ->call('submitBooking')
            ->assertHasNoErrors();

        // Verify the database has the no_food flag and 0 per plate price
        $booking = Booking::orderBy('id', 'desc')->first();
        $this->assertNotNull($booking);
        $this->assertTrue($booking->no_food);
        $this->assertEquals(0.00, $booking->per_plate_price);
        $this->assertEquals(0.00, $booking->package_amount);
        $this->assertEquals(50000.00, $booking->hall_charges);
        $this->assertEquals(60000.00, $booking->grand_total);
        // Check pivot table exists
        $this->assertDatabaseHas('booking_halls', [
            'booking_id' => $booking->id,
            'hall_id' => $this->hallA->id,
        ]);
    }

    public function test_completed_bookings_are_locked_from_editing_and_cancellation()
    {
        $booking = Booking::create([
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
            'booking_status' => 'Completed',
        ]);

        // 1. Staff is blocked
        Livewire::actingAs($this->userStaffA);
        
        // Edit component blocks completed booking edit for staff
        Livewire::test('booking-edit', ['booking' => $booking])
            ->assertRedirect(route('bookings.show', $booking->id));

        // BookingView component blocks status changes for staff
        Livewire::test('booking-view', ['booking' => $booking])
            ->call('updateStatus', 'Draft');
            
        $booking->refresh();
        $this->assertEquals('Completed', $booking->booking_status);

        // BookingList component blocks deleting/cancelling for staff
        Livewire::test('booking-list')
            ->set('deleteId', $booking->id)
            ->call('deleteRecord');
            
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'deleted_at' => null,
        ]);

        // 2. Owner is NOT blocked
        Livewire::actingAs($this->userOwnerA);
        
        // Edit component does NOT block completed booking edit for Owner
        Livewire::test('booking-edit', ['booking' => $booking])
            ->assertStatus(200);

        // BookingView component allows status changes for Owner
        Livewire::test('booking-view', ['booking' => $booking])
            ->call('updateStatus', 'Draft');
            
        $booking->refresh();
        $this->assertEquals('Draft', $booking->booking_status);
        
        // Restore status to Completed to test delete
        $booking->update(['booking_status' => 'Completed']);

        // BookingList component allows cancelling/deleting for Owner
        Livewire::test('booking-list')
            ->set('deleteId', $booking->id)
            ->call('deleteRecord');
            
        $this->assertSoftDeleted('bookings', [
            'id' => $booking->id,
        ]);
    }

    public function test_customer_dynamic_statistics_calculations()
    {
        $customer = Customer::create([
            'marquee_id' => $this->marqueeA->id,
            'customer_code' => 'CUST-TEST-STATS',
            'customer_type' => 'Individual',
            'first_name' => 'Test',
            'last_name' => 'Stats',
            'phone_number' => '0300-1111111',
            'status' => 'Active',
        ]);

        // Create bookings:
        // 1. Completed
        $booking1 = Booking::create([
            'marquee_id' => $this->marqueeA->id,
            'customer_id' => $customer->id,
            'event_type_id' => $this->eventTypeA->id,
            'hall_id' => $this->hallA->id,
            'slot_id' => $this->slotA->id,
            'package_id' => $this->packageA->id,
            'booking_date' => Carbon::yesterday()->format('Y-m-d'),
            'start_time' => Carbon::yesterday()->format('Y-m-d') . ' 18:00:00',
            'end_time' => Carbon::yesterday()->format('Y-m-d') . ' 23:30:00',
            'guest_count' => 100,
            'per_plate_price' => 1000.00,
            'grand_total' => 100000.00,
            'booking_status' => 'Completed',
            'payment_status' => 'Paid',
        ]);

        // Record a payment transaction
        \App\Models\BookingPayment::create([
            'booking_id' => $booking1->id,
            'amount' => 100000.00,
            'payment_date' => Carbon::yesterday()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'recorded_by' => $this->userOwnerA->id,
        ]);

        // 2. Upcoming Confirmed
        $booking2 = Booking::create([
            'marquee_id' => $this->marqueeA->id,
            'customer_id' => $customer->id,
            'event_type_id' => $this->eventTypeA->id,
            'hall_id' => $this->hallA->id,
            'slot_id' => $this->slotA->id,
            'package_id' => $this->packageA->id,
            'booking_date' => Carbon::tomorrow()->format('Y-m-d'),
            'start_time' => Carbon::tomorrow()->format('Y-m-d') . ' 18:00:00',
            'end_time' => Carbon::tomorrow()->format('Y-m-d') . ' 23:30:00',
            'guest_count' => 100,
            'per_plate_price' => 1000.00,
            'grand_total' => 100000.00,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Unpaid',
        ]);

        // 3. Cancelled
        $booking3 = Booking::create([
            'marquee_id' => $this->marqueeA->id,
            'customer_id' => $customer->id,
            'event_type_id' => $this->eventTypeA->id,
            'hall_id' => $this->hallA->id,
            'slot_id' => $this->slotA->id,
            'package_id' => $this->packageA->id,
            'booking_date' => Carbon::tomorrow()->format('Y-m-d'),
            'start_time' => Carbon::tomorrow()->format('Y-m-d') . ' 18:00:00',
            'end_time' => Carbon::tomorrow()->format('Y-m-d') . ' 23:30:00',
            'guest_count' => 100,
            'per_plate_price' => 1000.00,
            'grand_total' => 100000.00,
            'booking_status' => 'Cancelled',
            'payment_status' => 'Unpaid',
        ]);

        $this->assertEquals(3, $customer->total_bookings);
        $this->assertEquals(1, $customer->upcoming_events);
        $this->assertEquals(1, $customer->completed_events);
        $this->assertEquals(1, $customer->cancelled_events);
        $this->assertEquals(200000.00, $customer->total_revenue_generated);
        $this->assertEquals(100000.00, $customer->total_paid_amount);
        $this->assertEquals(100000.00, $customer->outstanding_balance);
    }

    public function test_booking_slips_v1_and_v2_accessibility_and_tenant_isolation()
    {
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
            'guest_count' => 150,
            'per_plate_price' => 1500.00,
            'grand_total' => 225000.00,
            'booking_status' => 'Confirmed',
        ]);

        // 1. Authorized user can view V1, V2 and V3 slips
        $this->actingAs($this->userOwnerA);
        $this->get(route('bookings.slip', $booking->id))->assertStatus(200)->assertSeeLivewire('booking-slip');
        $this->get(route('bookings.slip-v2', $booking->id))->assertStatus(200)->assertSeeLivewire('booking-slip-v2');
        $this->get(route('bookings.slip-v3', $booking->id))->assertStatus(200)->assertSeeLivewire('booking-slip-v3');

        // 2. Tenant isolation blocks access to other tenant's booking slips
        $bookingB = Booking::create([
            'marquee_id' => $this->marqueeB->id,
            'customer_id' => $this->customerA->id,
            'event_type_id' => $this->eventTypeA->id,
            'hall_id' => $this->hallA->id,
            'slot_id' => $this->slotA->id,
            'package_id' => $this->packageA->id,
            'booking_date' => '2026-06-26',
            'start_time' => '2026-06-26 18:00:00',
            'end_time' => '2026-06-26 23:30:00',
            'guest_count' => 150,
            'per_plate_price' => 1500.00,
            'grand_total' => 225000.00,
            'booking_status' => 'Confirmed',
        ]);

        $this->get(route('bookings.slip', $bookingB->id))->assertStatus(404);
        $this->get(route('bookings.slip-v2', $bookingB->id))->assertStatus(404);
        $this->get(route('bookings.slip-v3', $bookingB->id))->assertStatus(404);
    }

    public function test_bookings_report_view_accessibility_and_tenant_isolation()
    {
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
            'guest_count' => 150,
            'per_plate_price' => 1500.00,
            'grand_total' => 225000.00,
            'booking_status' => 'Confirmed',
        ]);

        // 1. Authorized user can access bookings report route
        $this->actingAs($this->userOwnerA);
        $response = $this->get(route('bookings.report', [
            'search' => $booking->booking_number,
        ]));
        $response->assertStatus(200);
        $response->assertViewHas('bookings');
        $response->assertSee($booking->booking_number);

        // 2. Tenant isolation limits query results automatically
        $bookingB = Booking::create([
            'marquee_id' => $this->marqueeB->id,
            'booking_number' => 'REPORT-TEST-B-999999',
            'customer_id' => $this->customerA->id,
            'event_type_id' => $this->eventTypeA->id,
            'hall_id' => $this->hallA->id,
            'slot_id' => $this->slotA->id,
            'package_id' => $this->packageA->id,
            'booking_date' => '2026-06-26',
            'start_time' => '2026-06-26 18:00:00',
            'end_time' => '2026-06-26 23:30:00',
            'guest_count' => 150,
            'per_plate_price' => 1500.00,
            'grand_total' => 225000.00,
            'booking_status' => 'Confirmed',
        ]);

        // Owner A should not see Booking B on the report
        $responseB = $this->get(route('bookings.report'));
        $responseB->assertStatus(200);
        $responseB->assertDontSee($bookingB->booking_number);
    }

    public function test_excel_export_action_streams_matching_bookings()
    {
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
            'guest_count' => 150,
            'per_plate_price' => 1500.00,
            'grand_total' => 225000.00,
            'booking_status' => 'Confirmed',
        ]);

        Livewire::actingAs($this->userOwnerA);

        $response = Livewire::test('booking-list')
            ->set('search', $booking->booking_number)
            ->call('exportExcel');

        $response->assertFileDownloaded();
    }

    public function test_booking_privacy_partition_configuration_workflow()
    {
        Livewire::actingAs($this->userOwnerA);

        // 1. Test Privacy OFF creates booking successfully
        $wizard1 = Livewire::test('booking-wizard')
            ->set('selectedCustomerId', $this->customerA->id)
            ->call('nextStep') // Step 1 -> 2
            ->set('selectedEventTypeId', $this->eventTypeA->id)
            ->set('selectedHallIds', [(string)$this->hallA->id])
            ->set('selectedDate', '2026-06-25')
            ->call('nextStep') // Step 2 -> 3
            ->set('checkType', 'slot')
            ->set('selectedSlotId', $this->slotA->id)
            ->call('nextStep') // Step 3 -> 4
            ->set('selectedPackageId', $this->packageA->id)
            ->set('privacyRequired', false)
            ->call('nextStep') // Step 4 -> 5
            ->set('bookingStatus', 'Confirmed')
            ->call('submitBooking')
            ->assertHasNoErrors();

        $booking1 = Booking::orderBy('id', 'desc')->first();
        $this->assertFalse($booking1->privacy_required);
        $this->assertNull($booking1->privacy_ladies_percentage);
        $this->assertNull($booking1->privacy_gents_percentage);

        // Verify V1 slip view has Privacy/Partition: No
        $responseSlip1 = $this->get(route('bookings.slip', $booking1->id));
        $responseSlip1->assertStatus(200);
        $responseSlip1->assertSee('Privacy / Partition:');
        $responseSlip1->assertSee('No');

        // 2. Test Invalid Percentages fails validation
        $wizard2 = Livewire::test('booking-wizard')
            ->set('selectedCustomerId', $this->customerA->id)
            ->call('nextStep') // Step 1 -> 2
            ->set('selectedEventTypeId', $this->eventTypeA->id)
            ->set('selectedHallIds', [(string)$this->hallA->id])
            ->set('selectedDate', '2026-06-26')
            ->call('nextStep') // Step 2 -> 3
            ->set('checkType', 'slot')
            ->set('selectedSlotId', $this->slotA->id)
            ->call('nextStep') // Step 3 -> 4
            ->set('selectedPackageId', $this->packageA->id)
            ->set('privacyRequired', true)
            ->set('privacyLadiesPercentage', 60)
            ->set('privacyGentsPercentage', 30) // Total 90% (Invalid)
            ->call('nextStep')
            ->assertHasErrors(['privacyLadiesPercentage', 'privacyGentsPercentage']);

        // 3. Test Privacy ON with correct percentages (60/40) saves successfully
        $wizard3 = Livewire::test('booking-wizard')
            ->set('selectedCustomerId', $this->customerA->id)
            ->call('nextStep') // Step 1 -> 2
            ->set('selectedEventTypeId', $this->eventTypeA->id)
            ->set('selectedHallIds', [(string)$this->hallA->id])
            ->set('selectedDate', '2026-06-27')
            ->call('nextStep') // Step 2 -> 3
            ->set('checkType', 'slot')
            ->set('selectedSlotId', $this->slotA->id)
            ->call('nextStep') // Step 3 -> 4
            ->set('selectedPackageId', $this->packageA->id)
            ->set('privacyRequired', true)
            ->set('privacyLadiesPercentage', 60)
            ->set('privacyGentsPercentage', 40)
            ->call('nextStep') // Step 4 -> 5
            ->set('bookingStatus', 'Confirmed')
            ->call('submitBooking')
            ->assertHasNoErrors();

        $booking2 = Booking::orderBy('id', 'desc')->first();
        $this->assertTrue($booking2->privacy_required);
        $this->assertEquals(60, $booking2->privacy_ladies_percentage);
        $this->assertEquals(40, $booking2->privacy_gents_percentage);

        // Verify V2 slip view has Privacy/Partition: Yes (Ladies: 60%, Gents: 40%)
        $responseSlip2 = $this->get(route('bookings.slip-v2', $booking2->id));
        $responseSlip2->assertStatus(200);
        $responseSlip2->assertSee('Privacy / Partition:');
        $responseSlip2->assertSee('Yes (Ladies: 60%, Gents: 40%)');

        // 4. Test Edit Booking correctly loads and updates privacy options
        $edit = Livewire::test('booking-edit', ['booking' => $booking2])
            ->assertSet('privacyRequired', true)
            ->assertSet('privacyLadiesPercentage', 60)
            ->assertSet('privacyGentsPercentage', 40)
            ->set('privacyLadiesPercentage', 55)
            ->set('privacyGentsPercentage', 45)
            ->call('save')
            ->assertHasNoErrors();

        $booking2->refresh();
        $this->assertEquals(55, $booking2->privacy_ladies_percentage);
        $this->assertEquals(45, $booking2->privacy_gents_percentage);

        // 5. Test Old Booking behaviour
        $oldBooking = Booking::create([
            'marquee_id' => $this->marqueeA->id,
            'customer_id' => $this->customerA->id,
            'event_type_id' => $this->eventTypeA->id,
            'hall_id' => $this->hallA->id,
            'slot_id' => $this->slotA->id,
            'package_id' => $this->packageA->id,
            'booking_date' => '2026-06-25',
            'start_time' => '2026-06-25 18:00:00',
            'end_time' => '2026-06-25 23:30:00',
            'guest_count' => 150,
            'per_plate_price' => 1500.00,
            'grand_total' => 225000.00,
            'booking_status' => 'Confirmed',
        ]);

        $this->assertFalse((bool) $oldBooking->privacy_required);
        $this->assertNull($oldBooking->privacy_ladies_percentage);
        $this->assertNull($oldBooking->privacy_gents_percentage);
    }
}

