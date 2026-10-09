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

    public function test_kitchen_slip_displays_shift_slot_and_empty_instructions_when_no_notes()
    {
        $slot = \App\Models\Slot::create([
            'marquee_id' => $this->marquee->id,
            'slot_name' => 'Dinner Shift',
            'start_time' => '19:00:00',
            'end_time' => '23:30:00',
            'status' => 'active',
        ]);

        $this->booking->update(['slot_id' => $slot->id]);

        // Attach an item without custom_note and another with empty custom_note
        $this->booking->menuItems()->sync([
            $this->chickenTikka->id => ['custom_note' => null],
            $this->naan->id => ['custom_note' => 'Extra Crispy'],
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('bookings.kitchen-slip', ['booking' => $this->booking->id, 'lang' => 'bilingual']));

        $response->assertStatus(200);
        $response->assertSee('Dinner Shift');
        $response->assertSee('Shift Slot / شفٹ سلاٹ');
        $response->assertSee('Extra Crispy');
        $response->assertDontSee('Standard Preparation');
    }

    public function test_kitchen_slip_manages_20_dishes_on_a5_page()
    {
        $itemsToAttach = [];
        for ($i = 1; $i <= 20; $i++) {
            $dish = MenuItem::create([
                'marquee_id' => $this->marquee->id,
                'category_id' => $this->chickenTikka->category_id,
                'item_name' => "Special Dish Item #{$i}",
                'item_code' => "CODE-DISH-{$i}",
                'urdu_name' => "خصوصی ڈش نمبر {$i}",
                'base_cost' => 100 + $i,
                'selling_price' => 200 + $i,
                'unit' => 'Servings',
                'status' => 'active',
            ]);
            $itemsToAttach[$dish->id] = ['custom_note' => $i % 2 === 0 ? "Instruction note for dish {$i}" : null];
        }

        $this->booking->menuItems()->sync($itemsToAttach);

        $response = $this->actingAs($this->owner)
            ->get(route('bookings.kitchen-slip', ['booking' => $this->booking->id, 'lang' => 'bilingual', 'paper' => 'a5']));

        $response->assertStatus(200);
        $response->assertSee('paper-a5');
        $response->assertSee('A5');
        $response->assertSee('size: A5 portrait', false);
        for ($i = 1; $i <= 20; $i++) {
            $response->assertSee("Special Dish Item #{$i}");
        }
        $response->assertSee('Instruction note for dish 2');
        $response->assertDontSee('Standard Preparation');
    }

    public function test_kitchen_slip_v2_renders_without_dish_categories_and_excludes_financials()
    {
        $response = $this->actingAs($this->owner)
            ->get(route('bookings.kitchen-slip-v2', ['booking' => $this->booking->id, 'lang' => 'bilingual']));

        $response->assertStatus(200);
        $response->assertSee('KITCHEN MENU SLIP');
        $response->assertSee('BK-2026-9901');
        $response->assertSee('Muhammad Tariq');
        $response->assertSee('450 Persons');
        $response->assertSee('Chicken Tikka Boti');
        $response->assertSee('Roghni Naan');

        // Assert dish category headers are completely removed
        $response->assertDontSee('BBQ STATION');
        $response->assertDontSee('TANDOOR');
        $response->assertDontSee('dept-header');

        // Assert financial figures are excluded
        $response->assertDontSee('675,000');
        $response->assertDontSee('Grand Total');
        $response->assertDontSee('Advance Payment');
        $response->assertDontSee('Outstanding Balance');
    }

    public function test_kitchen_slip_v2_preserves_exact_reservation_sheet_sort_order()
    {
        $branchId = $this->booking->hall->branch_id;

        // Category 1: Starters / Soup
        $deptSoup = Department::create(['marquee_id' => $this->marquee->id, 'branch_id' => $branchId, 'department_code' => 'DEP-SOUP', 'name' => 'Soup Section', 'department_type' => 'Kitchen Production']);
        $catSoup = MenuCategory::create(['marquee_id' => $this->marquee->id, 'department_id' => $deptSoup->id, 'category_name' => 'Soup', 'category_code' => 'CAT-SOUP']);
        $dishSoup = MenuItem::create([
            'marquee_id' => $this->marquee->id,
            'category_id' => $catSoup->id,
            'item_name' => '1st Hot and Sour Soup',
            'item_code' => 'ITEM-SOUP-01',
            'base_cost' => 150,
            'selling_price' => 250,
            'unit' => 'Bowl',
            'status' => 'active',
        ]);

        // Category 2: BBQ
        $dishBbq = MenuItem::create([
            'marquee_id' => $this->marquee->id,
            'category_id' => $this->chickenTikka->category_id,
            'item_name' => '2nd Mutton Seekh Kabab',
            'item_code' => 'ITEM-BBQ-02',
            'base_cost' => 300,
            'selling_price' => 450,
            'unit' => 'Plate',
            'status' => 'active',
        ]);

        // Category 3: Rice / Pakistani
        $deptMain = Department::create(['marquee_id' => $this->marquee->id, 'branch_id' => $branchId, 'department_code' => 'DEP-MAIN', 'name' => 'Main Kitchen', 'department_type' => 'Kitchen Production']);
        $catRice = MenuCategory::create(['marquee_id' => $this->marquee->id, 'department_id' => $deptMain->id, 'category_name' => 'Rice', 'category_code' => 'CAT-RICE']);
        $dishRice = MenuItem::create([
            'marquee_id' => $this->marquee->id,
            'category_id' => $catRice->id,
            'item_name' => '3rd Sindhi Biryani',
            'item_code' => 'ITEM-RICE-03',
            'base_cost' => 250,
            'selling_price' => 400,
            'unit' => 'Plate',
            'status' => 'active',
        ]);

        // Attach with specific interleaved sort_order matching customer reservation sheet:
        // Soup (sort_order 0) -> BBQ (sort_order 1) -> Rice (sort_order 2)
        $this->booking->menuItems()->sync([
            $dishSoup->id => ['sort_order' => 0, 'custom_note' => 'Spicy'],
            $dishBbq->id => ['sort_order' => 1, 'custom_note' => 'Charcoal grilled'],
            $dishRice->id => ['sort_order' => 2, 'custom_note' => 'Fragrant basmati'],
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('bookings.kitchen-slip-v2', ['booking' => $this->booking->id, 'lang' => 'bilingual']));

        $response->assertStatus(200);
        $content = $response->getContent();

        $soupPos = strpos($content, '1st Hot and Sour Soup');
        $bbqPos = strpos($content, '2nd Mutton Seekh Kabab');
        $ricePos = strpos($content, '3rd Sindhi Biryani');

        $this->assertNotFalse($soupPos);
        $this->assertNotFalse($bbqPos);
        $this->assertNotFalse($ricePos);

        // Verify exact sequential order as saved on the reservation sheet
        $this->assertTrue($soupPos < $bbqPos, 'Soup should precede BBQ in V2 kitchen slip.');
        $this->assertTrue($bbqPos < $ricePos, 'BBQ should precede Rice in V2 kitchen slip.');
    }

    public function test_kitchen_slip_v2_supports_paper_sizes_languages_and_version_audit()
    {
        // 1. Test A4 Paper & Urdu Language
        $responseUrdu = $this->actingAs($this->owner)
            ->get(route('bookings.kitchen-slip-v2', ['booking' => $this->booking->id, 'lang' => 'urdu', 'paper' => 'a4']));

        $responseUrdu->assertStatus(200);
        $responseUrdu->assertSee('paper-a4');
        $responseUrdu->assertSee('size: A4 portrait', false);
        $responseUrdu->assertSee('dir="rtl"', false);
        $responseUrdu->assertSee('کچن مینو آرڈر سلپ');

        // 2. Test A5 Paper & English Language
        $responseEnglish = $this->actingAs($this->owner)
            ->get(route('bookings.kitchen-slip-v2', ['booking' => $this->booking->id, 'lang' => 'english', 'paper' => 'a5']));

        $responseEnglish->assertStatus(200);
        $responseEnglish->assertSee('paper-a5');
        $responseEnglish->assertSee('size: A5 portrait', false);
        $responseEnglish->assertSee('dir="ltr"', false);
        $responseEnglish->assertSee('Dish / Item Name');

        // 3. Test Audit Log was recorded
        $this->assertDatabaseHas('kitchen_print_logs', [
            'booking_id' => $this->booking->id,
            'marquee_id' => $this->marquee->id,
            'printed_by' => $this->owner->id,
            'language' => 'english',
        ]);
    }

    public function test_booking_view_modal_can_select_and_dispatch_kitchen_slip_v2()
    {
        Livewire::actingAs($this->owner)
            ->test(BookingView::class, ['booking' => $this->booking])
            ->set('kitchenSlipVersion', 'v2')
            ->set('kitchenLang', 'bilingual')
            ->call('saveKitchenInstructionsAndPrint')
            ->assertDispatched('open-print-window', function ($eventName, $params) {
                return str_contains($params['url'], 'kitchen-slip-v2');
            });

        Livewire::actingAs($this->owner)
            ->test(BookingView::class, ['booking' => $this->booking])
            ->set('kitchenSlipVersion', 'v1')
            ->set('kitchenLang', 'bilingual')
            ->call('saveKitchenInstructionsAndPrint')
            ->assertDispatched('open-print-window', function ($eventName, $params) {
                return str_contains($params['url'], 'kitchen-slip') && !str_contains($params['url'], 'kitchen-slip-v2');
            });
    }
}
