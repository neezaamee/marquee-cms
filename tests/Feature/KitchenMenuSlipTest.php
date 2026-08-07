<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Department;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\KitchenPrintLog;
use App\Models\Marquee;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Role;
use App\Models\Slot;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Livewire\BookingView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KitchenMenuSlipTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Marquee $marquee;
    protected Booking $booking;
    protected MenuItem $chickenTikka;
    protected MenuItem $naan;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles
        $ownerRole = Role::create(['name' => 'owner', 'label' => 'Marquee Owner']);

        // 2. Subscription Plan & Marquee Tenant
        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-plan',
            'price' => 1000,
            'billing_interval' => 'monthly',
            'max_branches' => 5,
            'status' => 'active',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Royal Pearl Marquee',
            'slug' => 'royal-pearl-marquee',
            'status' => 'active',
            'address' => '12 Main Gulberg',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'email' => 'contact@royalpearl.test',
            'subscription_plan_id' => $plan->id,
            'is_setup_completed' => true,
        ]);

        $this->owner = User::create([
            'name' => 'Owner Ahmad',
            'email' => 'ahmad@royalpearl.test',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marquee->id,
        ]);

        // 3. Branch, Hall, Slot, EventType, Customer
        $branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Executive Branch',
            'code' => 'EX-01',
            'address' => '12 Main Gulberg',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'status' => 'active',
        ]);

        $hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $branch->id,
            'hall_name' => 'Grand Pearl Hall',
            'hall_code' => 'HALL-01',
            'hall_type' => 'Indoor',
            'default_booking_price' => 50000,
            'capacity' => 500,
            'status' => 'active',
        ]);

        $slot = Slot::create([
            'marquee_id' => $this->marquee->id,
            'slot_name' => 'Night Shift',
            'slot_code' => 'SLOT-NIGHT',
            'start_time' => '19:00:00',
            'end_time' => '22:00:00',
            'status' => 'active',
        ]);

        $eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Wedding (Baraat)',
            'event_type_code' => 'ET-BARAAT',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_code' => 'CUST-99',
            'first_name' => 'Muhammad',
            'last_name' => 'Tariq',
            'phone_number' => '03009988776',
            'status' => 'active',
        ]);

        // 4. Departments & Menu Categories
        $bbqDept = Department::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $branch->id,
            'department_code' => 'DEP-BBQ',
            'name' => 'BBQ Station',
            'department_type' => 'Operations',
            'status' => 'Active',
        ]);

        $tandoorDept = Department::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $branch->id,
            'department_code' => 'DEP-TAND',
            'name' => 'Tandoor & Bakery',
            'department_type' => 'Operations',
            'status' => 'Active',
        ]);

        $bbqCategory = MenuCategory::create([
            'marquee_id' => $this->marquee->id,
            'department_id' => $bbqDept->id,
            'category_name' => 'BBQ & Grill Delicacies',
            'category_code' => 'CAT-BBQ',
            'status' => 'active',
        ]);

        $tandoorCategory = MenuCategory::create([
            'marquee_id' => $this->marquee->id,
            'department_id' => $tandoorDept->id,
            'category_name' => 'Tandoori Breads & Naans',
            'category_code' => 'CAT-NAAN',
            'status' => 'active',
        ]);

        $this->chickenTikka = MenuItem::create([
            'marquee_id' => $this->marquee->id,
            'category_id' => $bbqCategory->id,
            'item_name' => 'Chicken Tikka Boti',
            'item_code' => 'ITEM-TIKKA',
            'urdu_name' => 'چکن تکہ بوٹی',
            'base_cost' => 300,
            'selling_price' => 450,
            'unit' => 'Pcs',
            'status' => 'active',
        ]);

        $this->naan = MenuItem::create([
            'marquee_id' => $this->marquee->id,
            'category_id' => $tandoorCategory->id,
            'item_name' => 'Roghni Naan',
            'item_code' => 'ITEM-NAAN',
            'urdu_name' => 'روغنی نان',
            'base_cost' => 30,
            'selling_price' => 60,
            'unit' => 'Pcs',
            'status' => 'active',
        ]);

        // 5. Booking
        $this->booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $branch->id,
            'hall_id' => $hall->id,
            'slot_id' => $slot->id,
            'event_type_id' => $eventType->id,
            'customer_id' => $customer->id,
            'booking_number' => 'BK-2026-9901',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '19:00:00',
            'end_time' => '22:00:00',
            'tentative_guests' => 450,
            'confirmed_guests' => 450,
            'guest_status' => 'Confirmed',
            'guest_count' => 450,
            'per_plate_price' => 1500,
            'subtotal' => 675000,
            'grand_total' => 675000,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Partially Paid',
            'kitchen_special_instructions' => 'Less spicy, VIP table service requested.',
        ]);

        $this->booking->menuItems()->attach([
            $this->chickenTikka->id => ['custom_note' => 'Mild Spice'],
            $this->naan->id => ['custom_note' => 'Hot Naan'],
        ]);
    }

    public function test_kitchen_slip_route_renders_successfully_for_authorized_owner()
    {
        $response = $this->actingAs($this->owner)
            ->get(route('bookings.kitchen-slip', ['booking' => $this->booking->id, 'lang' => 'bilingual']));

        $response->assertStatus(200);
        $response->assertSee('KITCHEN MENU SLIP');
        $response->assertSee('BK-2026-9901');
        $response->assertSee('Muhammad Tariq');
        $response->assertSee('450 Persons');
        $response->assertSee('Chicken Tikka Boti');
        $response->assertSee('Roghni Naan');
        $response->assertSee('BBQ STATION');
        $response->assertSee('TANDOOR');
    }

    public function test_kitchen_slip_excludes_all_confidential_financial_data()
    {
        $response = $this->actingAs($this->owner)
            ->get(route('bookings.kitchen-slip', ['booking' => $this->booking->id, 'lang' => 'bilingual']));

        $response->assertStatus(200);
        $response->assertDontSee('675,000');
        $response->assertDontSee('Grand Total');
        $response->assertDontSee('Advance Payment');
        $response->assertDontSee('Outstanding Balance');
        $response->assertDontSee('Selling Price');
        $response->assertDontSee('Base Cost');
    }

    public function test_kitchen_slip_records_print_history_log_and_increments_version()
    {
        $this->assertEquals(0, $this->booking->kitchen_print_version);
        $this->assertNull($this->booking->kitchen_printed_at);

        $this->actingAs($this->owner)
            ->get(route('bookings.kitchen-slip', ['booking' => $this->booking->id, 'lang' => 'bilingual']));

        $this->booking->refresh();

        $this->assertEquals(1, $this->booking->kitchen_print_version);
        $this->assertNotNull($this->booking->kitchen_printed_at);
        $this->assertDatabaseHas('kitchen_print_logs', [
            'booking_id' => $this->booking->id,
            'marquee_id' => $this->marquee->id,
            'printed_by' => $this->owner->id,
            'version_number' => 1,
            'language' => 'bilingual',
        ]);
    }

    public function test_menu_modification_detects_post_print_changes_and_triggers_warning()
    {
        // 1. Initial print
        $this->actingAs($this->owner)
            ->get(route('bookings.kitchen-slip', ['booking' => $this->booking->id, 'lang' => 'bilingual']));

        $this->booking->refresh();
        $this->assertFalse($this->booking->is_kitchen_menu_modified);

        // 2. Modify confirmed guests headcount
        $this->booking->update(['confirmed_guests' => 500, 'guest_count' => 500]);
        $this->booking->refresh();

        // 3. Verify modification flag triggers warning
        $this->assertTrue($this->booking->is_kitchen_menu_modified);

        Livewire::actingAs($this->owner)
            ->test(BookingView::class, ['booking' => $this->booking])
            ->assertSee('Kitchen Menu Modified!');
    }

    public function test_tenant_isolation_prevents_unauthorized_cross_tenant_access()
    {
        // Tenant B
        $marqueeB = Marquee::create([
            'name' => 'Imperial Marquee B',
            'slug' => 'imperial-marquee-b',
            'status' => 'active',
            'address' => '45 Main Mall',
            'city' => 'Faisalabad',
            'province' => 'Punjab',
            'phone' => '03009998877',
            'email' => 'contact@imperialb.test',
            'subscription_plan_id' => $this->marquee->subscription_plan_id,
            'is_setup_completed' => true,
        ]);

        $ownerB = User::create([
            'name' => 'Owner B',
            'email' => 'ownerb@imperial.test',
            'password' => bcrypt('password'),
            'marquee_id' => $marqueeB->id,
        ]);

        $response = $this->actingAs($ownerB)
            ->get(route('bookings.kitchen-slip', ['booking' => $this->booking->id, 'lang' => 'bilingual']));

        $response->assertStatus(404);
    }
}
