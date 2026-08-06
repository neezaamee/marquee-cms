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

class BookingEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected $plan;
    protected $ownerRole;
    protected $marquee;
    protected $branch;
    protected $hall;
    protected $userOwner;
    protected $customer;
    protected $eventType;
    protected $package;
    protected $slot;

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

        $this->marquee = Marquee::create([
            'name' => 'Grand Emerald Lahore',
            'address' => 'Gulberg', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '11',
            'email' => 'emerald.lhr@marquee.com', 'status' => 'active', 'subscription_plan_id' => $this->plan->id,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Gulberg Branch',
            'address' => 'Main Blvd', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '111',
            'status' => 'active',
        ]);

        $this->hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Royal Banquet',
            'hall_code' => 'RBL',
            'capacity' => 600,
            'hall_type' => 'Banquet',
            'default_booking_price' => 75000.00,
            'status' => 'active',
        ]);

        $this->userOwner = User::create([
            'name' => 'Owner Alim',
            'email' => 'owner.alim@emerald.com',
            'password' => bcrypt('password'),
            'role_id' => $this->ownerRole->id,
            'marquee_id' => $this->marquee->id,
        ]);

        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_code' => 'CUST-00001',
            'customer_type' => 'Individual',
            'first_name' => 'Hamza',
            'last_name' => 'Rasheed',
            'phone_number' => '0300-5551234',
            'status' => 'Active',
        ]);

        $this->eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'event_type_name' => 'Walima Dinner',
            'event_type_code' => 'WAL',
            'status' => 'active',
            'is_system_default' => false,
        ]);

        $this->package = Package::create([
            'marquee_id' => $this->marquee->id,
            'package_name' => 'Standard Chicken Menu',
            'package_code' => 'SCM',
            'per_plate_price' => 1500.00,
            'minimum_guests' => 100,
            'maximum_guests' => 500,
            'status' => 'Active',
        ]);

        $this->slot = Slot::create([
            'marquee_id' => $this->marquee->id,
            'slot_name' => 'Dinner Shift',
            'start_time' => '18:00:00',
            'end_time' => '23:30:00',
            'status' => 'active',
        ]);
    }

    /**
     * Test the BookingOnePage Livewire component submissions.
     */
    public function test_one_page_booking_submits_successfully()
    {
        Livewire::actingAs($this->userOwner);

        $addon = \App\Models\ExtraService::create([
            'marquee_id' => $this->marquee->id,
            'service_name' => 'Premium Stage Decor',
            'default_price' => 10000.00,
            'status' => 'Active',
        ]);

        $component = Livewire::test('booking-one-page')
            ->set('selectedCustomerId', $this->customer->id)
            ->set('selectedEventTypeId', $this->eventType->id)
            ->set('selectedHallIds', [(string)$this->hall->id])
            ->set('selectedDate', '2026-12-25')
            ->set('checkType', 'slot')
            ->set('selectedSlotId', $this->slot->id)
            ->set('selectedPackageId', $this->package->id)
            ->set('guestCount', 150)
            ->set('perPlatePrice', 1500)
            ->set('hallCharges', 20000)
            ->set("selectedAddons.{$addon->id}.selected", true)
            ->set("selectedAddons.{$addon->id}.price", 15000)
            ->set("selectedAddons.{$addon->id}.quantity", 1)
            ->set('discountAmount', 5000)
            ->set('securityDeposit', 15000)
            ->set('taxRate', 13)
            ->call('recalculatePrices');

        // Verify subtotal, tax and grand total
        // package_amount = 150 * 1500 = 225,000
        // extraCharges = 15,000
        // hallCharges = 20,000
        // discount = 5000
        // subtotal = 225,000 + 15,000 + 20,000 - 5000 = 255,000
        // tax = 255,000 * 13% = 33,150
        // grand_total = 255,000 + 33,150 + 15,000 = 303,150
        $component->assertSet('packageAmount', 225000.00)
            ->assertSet('extraCharges', 15000.00)
            ->assertSet('subtotal', 255000.00)
            ->assertSet('taxAmount', 33150.00)
            ->assertSet('grandTotal', 303150.00);

        // Submit the booking
        $component->call('submitBooking')
            ->assertHasNoErrors();

        // Retrieve created booking
        $booking = Booking::first();
        $this->assertNotNull($booking);
        $this->assertEquals($this->customer->id, $booking->customer_id);
        $this->assertEquals(150, $booking->guest_count);
        $this->assertEquals(303150.00, $booking->grand_total);
    }

    /**
     * Test the Event-Day Final Bill system and original data preservation logic.
     */
    public function test_final_bill_preserves_original_booking_record()
    {
        Livewire::actingAs($this->userOwner);

        // Create initial booking
        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hall->id,
            'slot_id' => $this->slot->id,
            'package_id' => $this->package->id,
            'booking_date' => '2026-06-25',
            'start_time' => '2026-06-25 18:00:00',
            'end_time' => '2026-06-25 23:30:00',
            'guest_count' => 100, // 100 guest count originally contracted
            'per_plate_price' => 1500.00,
            'package_amount' => 150000.00,
            'hall_charges' => 20000.00,
            'extra_charges' => 0.00,
            'discount_amount' => 0.00,
            'security_deposit' => 15000.00,
            'tax_amount' => 22100.00, // 13% of 170,000
            'subtotal' => 170000.00,
            'grand_total' => 207100.00,
            'booking_status' => 'Reserved',
            'payment_status' => 'Unpaid',
            'deposit_status' => 'Held',
        ]);

        // Launch BookingView Livewire test
        $viewComponent = Livewire::test('booking-view', ['booking' => $booking]);

        // Open final bill modal (which templates from original booking)
        $viewComponent->call('openFinalBillModal')
            ->assertSet('fbGuestCount', 100)
            ->assertSet('fbPerPlatePrice', 1500.00)
            ->assertSet('fbHallCharges', 20000.00);

        // Modify guest count to 125, add custom addon
        $viewComponent->set('fbGuestCount', 125)
            ->set('newAddonName', 'Extra Stage Sofas')
            ->set('newAddonPrice', 5000.00)
            ->set('newAddonQty', 1)
            ->call('addFbAddon')
            ->assertSet('fbExtraCharges', 5000.00);

        // Recalculate
        // package_amount = 125 * 1500 = 187,500
        // hall_charges = 20,000
        // extra_charges = 5,000
        // subtotal = 187,500 + 20,000 + 5,000 = 212,500
        // tax = 212,500 * 13% = 27,625
        // grand_total = 212,500 + 27,625 + 15,000 (security_deposit) = 255,125
        $viewComponent->call('recalculateFinalBill')
            ->assertSet('fbExtraCharges', 5000.00)
            ->assertSet('fbTaxAmount', 27625.00);

        // Save the Final Bill
        $viewComponent->call('saveFinalBill')
            ->assertHasNoErrors();

        // Reload booking
        $booking->refresh();

        // 1. Assert original booking fields are completely unmodified
        $this->assertEquals(100, $booking->guest_count);
        $this->assertEquals(150000.00, $booking->package_amount);
        $this->assertEquals(207100.00, $booking->grand_total);

        // 2. Assert final bill details are correctly captured
        $this->assertNotNull($booking->finalBill);
        $this->assertEquals(125, $booking->finalBill->guest_count);
        $this->assertEquals(5000.00, $booking->finalBill->extra_charges);
        $this->assertEquals(255125.00, $booking->finalBill->grand_total);

        // 3. Assert outstanding balance calculation prioritizes final bill total
        // Payments: 0
        // Original total: 207,100
        // Final bill total: 255,125
        // Outstanding balance should be based on final bill total (255,125)
        $totalPaid = $booking->payments()->sum('amount');
        $outstanding = $booking->finalBill ? ($booking->finalBill->grand_total - $totalPaid) : ($booking->grand_total - $totalPaid);
        $this->assertEquals(255125.00, $outstanding);
    }

    public function test_menu_items_sorting_and_sequence_ordering()
    {
        // 1. Create a customer, package, category, and menu items
        $customer = \App\Models\Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_type' => 'Individual',
            'first_name' => 'Sort',
            'last_name' => 'Tester',
            'phone_number' => '03001234567',
        ]);

        $category = \App\Models\MenuCategory::create([
            'marquee_id' => $this->marquee->id,
            'category_name' => 'Main course',
            'category_code' => 'MC',
            'status' => 'active',
        ]);

        $itemA = \App\Models\MenuItem::create([
            'marquee_id' => $this->marquee->id,
            'category_id' => $category->id,
            'item_name' => 'Chicken Biryani',
            'item_code' => 'CB',
            'selling_price' => 200,
            'status' => 'active',
        ]);

        $itemB = \App\Models\MenuItem::create([
            'marquee_id' => $this->marquee->id,
            'category_id' => $category->id,
            'item_name' => 'Mutton Karahi',
            'item_code' => 'MK',
            'selling_price' => 500,
            'status' => 'active',
        ]);

        $itemC = \App\Models\MenuItem::create([
            'marquee_id' => $this->marquee->id,
            'category_id' => $category->id,
            'item_name' => 'Kheer',
            'item_code' => 'KH',
            'selling_price' => 100,
            'status' => 'active',
        ]);

        Livewire::actingAs($this->userOwner);

        // 2. Test One-Page Booking Wizard Component sorting helper methods
        $component = Livewire::test('booking-one-page')
            ->set('selectedCustomerId', $customer->id)
            ->set('selectedEventTypeId', $this->eventType->id)
            ->set('selectedHallIds', [(string)$this->hall->id])
            ->set('selectedDate', '2026-12-26')
            ->set('checkType', 'slot')
            ->set('selectedSlotId', $this->slot->id)
            ->set('selectedPackageId', $this->package->id)
            ->set('noFood', false);

        // Simulate adding menu items: Item A, Item B, Item C
        $component->set('bookingMenuItems', [
            ['id' => $itemA->id, 'item_name' => 'Chicken Biryani', 'custom_note' => '', 'managed_by_host' => false],
            ['id' => $itemB->id, 'item_name' => 'Mutton Karahi', 'custom_note' => '', 'managed_by_host' => false],
            ['id' => $itemC->id, 'item_name' => 'Kheer', 'custom_note' => '', 'managed_by_host' => false],
        ]);

        // Move Item B (index 1) up -> Should become: B, A, C
        $component->call('moveMenuItemUp', 1);
        $menuItems = $component->get('bookingMenuItems');
        $this->assertEquals($itemB->id, $menuItems[0]['id']);
        $this->assertEquals($itemA->id, $menuItems[1]['id']);

        // Move Item C (index 2) up -> Should become: B, C, A
        $component->call('moveMenuItemUp', 2);
        $menuItems = $component->get('bookingMenuItems');
        $this->assertEquals($itemC->id, $menuItems[1]['id']);

        // Move Item B (index 0) down -> Should become: C, B, A
        $component->call('moveMenuItemDown', 0);
        $menuItems = $component->get('bookingMenuItems');
        $this->assertEquals($itemC->id, $menuItems[0]['id']);
        $this->assertEquals($itemB->id, $menuItems[1]['id']);

        // Submit the form
        $component->call('submitBooking')
            ->assertHasNoErrors();

        // 3. Retrieve the created booking and assert it loads menu items sorted by sort_order pivot
        $booking = \App\Models\Booking::latest()->first();
        $this->assertCount(3, $booking->menuItems);

        // It should be C (Kheer), B (Mutton Karahi), A (Chicken Biryani)
        $this->assertEquals($itemC->id, $booking->menuItems[0]->id);
        $this->assertEquals(0, $booking->menuItems[0]->pivot->sort_order);

        $this->assertEquals($itemB->id, $booking->menuItems[1]->id);
        $this->assertEquals(1, $booking->menuItems[1]->pivot->sort_order);

        $this->assertEquals($itemA->id, $booking->menuItems[2]->id);
        $this->assertEquals(2, $booking->menuItems[2]->pivot->sort_order);
    }
}
