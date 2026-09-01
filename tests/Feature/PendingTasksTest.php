<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Marquee;
use App\Models\Branch;
use App\Models\Hall;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\MenuItem;
use App\Models\MenuCategory;
use App\Models\Package;
use App\Models\InventoryItem;
use App\Models\InventoryUnit;
use App\Models\InventoryCategory;
use App\Models\Recipe;
use App\Models\RecipeDetail;
use App\Models\Booking;
use App\Models\EventChecklist;
use App\Models\Vendor;
use App\Models\VendorBooking;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Slot;
use App\Services\RecipeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendingTasksTest extends TestCase
{
    use RefreshDatabase;

    protected $marquee;
    protected $branch;
    protected $hall;
    protected $user;
    protected $employee;
    protected $customer;
    protected $eventType;
    protected $slot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SubscriptionPlanSeeder::class);
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $plan = \App\Models\SubscriptionPlan::where('slug', 'standard')->first() 
             ?? \App\Models\SubscriptionPlan::first();

        $this->marquee = Marquee::create([
            'name' => 'Test Marquee',
            'address' => '123 St',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '+923001234567',
            'email' => 'test@marquee.com',
            'status' => 'active',
            'is_setup_completed' => true,
            'subscription_plan_id' => $plan ? $plan->id : null,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Branch',
            'address' => 'Main Boulevard',
            'phone' => '+92423123456',
            'city' => 'Lahore',
            'province' => 'Punjab',
        ]);

        $this->hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Palace Hall',
            'hall_code' => 'PAL-HL',
            'capacity' => 500,
            'hall_type' => 'Banquet',
            'default_booking_price' => 150000.00,
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'first_name' => 'John',
            'last_name' => 'Customer',
            'phone_number' => '+923001234588',
        ]);

        $this->eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'event_type_name' => 'Wedding',
            'event_type_code' => 'WEDD',
            'default_duration_hours' => 4.00,
            'base_price' => 50000.00,
            'status' => 'Active',
            'sort_order' => 1,
            'is_system_default' => true,
        ]);

        $this->slot = Slot::create([
            'marquee_id' => $this->marquee->id,
            'slot_name' => 'Day Shift',
            'start_time' => '12:00:00',
            'end_time' => '16:00:00',
            'status' => 'Active',
        ]);

        $ownerRole = Role::where('name', 'owner')->first();
        $this->user = User::factory()->create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'role_id' => $ownerRole->id,
        ]);

        $this->employee = Employee::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'name' => 'John Waiter',
            'cnic' => '35202-1111111-1',
            'mobile_number' => '+923001111111',
            'designation' => 'Waiter',
            'joining_date' => '2026-01-01',
            'salary' => 25000,
            'employment_type' => 'Permanent',
            'status' => 'Active',
        ]);
    }

    public function test_staff_attendance_logging_and_scoping()
    {
        $this->actingAs($this->user);

        // 1. Create an attendance record
        $attendance = Attendance::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-08-06',
            'check_in' => '09:00:00',
            'check_out' => '17:00:00',
            'status' => 'Present',
            'notes' => 'Checked in on time',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'Present',
        ]);

        // Verify relationships
        $this->assertEquals($this->employee->id, $attendance->employee->id);
        $this->assertCount(1, $this->employee->attendances);

        // 2. Test Scoping Isolation (branch A cannot see branch B)
        $branchB = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Secondary Branch',
            'address' => 'Mall Road',
            'phone' => '+92423666666',
            'city' => 'Lahore',
            'province' => 'Punjab',
        ]);

        $employeeB = Employee::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $branchB->id,
            'name' => 'Sarah Cashier',
            'cnic' => '35202-2222222-2',
            'mobile_number' => '+923002222222',
            'designation' => 'Cashier',
            'joining_date' => '2026-01-01',
            'salary' => 35000,
            'employment_type' => 'Permanent',
            'status' => 'Active',
        ]);

        $attendanceB = Attendance::create([
            'branch_id' => $branchB->id,
            'employee_id' => $employeeB->id,
            'date' => '2026-08-06',
            'status' => 'Present',
        ]);

        // Login as receptionist/staff restricted to Main Branch (branch A)
        $staffRole = Role::where('name', 'staff')->first();
        $staffUser = User::factory()->create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'role_id' => $staffRole->id,
        ]);

        $this->actingAs($staffUser);

        // Sarah's attendance (branch B) should be isolated/invisible
        $visibleAttendances = Attendance::all();
        $this->assertTrue($visibleAttendances->contains($attendance));
        $this->assertFalse($visibleAttendances->contains($attendanceB));
    }

    public function test_catering_recipe_and_raw_material_calculators()
    {
        $this->actingAs($this->user);

        // Create categories & items
        $category = MenuCategory::create([
            'category_name' => 'Main Course',
            'category_code' => 'MAIN',
            'status' => 'Active',
        ]);

        $menuItem = MenuItem::create([
            'category_id' => $category->id,
            'item_name' => 'Chicken Karahi',
            'item_code' => 'CH-KAR',
            'base_cost' => 250.00,
            'selling_price' => 450.00,
            'status' => 'Active',
        ]);

        $invCat = InventoryCategory::create([
            'name' => 'Food Materials',
            'status' => 'Active',
        ]);

        $unit = InventoryUnit::create([
            'name' => 'Kilogram',
            'short_code' => 'Kg',
            'status' => 'Active',
        ]);

        $chicken = InventoryItem::create([
            'item_code' => 'CHIC-001',
            'category_id' => $invCat->id,
            'unit_id' => $unit->id,
            'name' => 'Raw Chicken',
            'minimum_stock_level' => 50,
            'reorder_level' => 100,
            'default_purchase_rate' => 600.00,
            'status' => 'Active',
        ]);

        // Define Recipe
        $recipe = Recipe::create([
            'menu_item_id' => $menuItem->id,
            'description' => 'Recipe for Chicken Karahi',
        ]);

        $detail = RecipeDetail::create([
            'recipe_id' => $recipe->id,
            'inventory_item_id' => $chicken->id,
            'quantity_per_head' => 0.2500, // 250 grams per plate
        ]);

        $this->assertDatabaseHas('recipes', ['id' => $recipe->id]);
        $this->assertDatabaseHas('recipe_details', ['quantity_per_head' => 0.2500]);
        $this->assertEquals($recipe->id, $menuItem->recipe->id);

        // Define booking with guest count
        $package = Package::create([
            'package_name' => 'Standard Package',
            'package_code' => 'STD-01',
            'minimum_guests' => 100,
            'maximum_guests' => 1000,
            'base_price' => 50000,
            'per_plate_price' => 500,
            'status' => 'Active',
        ]);
        $package->menuItems()->attach($menuItem->id);

        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'booking_date' => '2026-08-10',
            'slot_id' => $this->slot->id,
            'start_time' => $this->slot->start_time,
            'end_time' => $this->slot->end_time,
            'guest_count' => 500, // 500 guests
            'package_id' => $package->id,
            'hall_id' => $this->hall->id,
            'status' => 'Draft',
        ]);

        // Run RecipeService calculator
        $recipeService = new RecipeService();
        $requirements = $recipeService->calculateRequiredIngredients($booking);

        // 500 guests * 0.2500 kg = 125 kg chicken required
        $this->assertCount(1, $requirements);
        $this->assertEquals('Raw Chicken', $requirements[0]['name']);
        $this->assertEquals(125.0000, $requirements[0]['required_qty']);
        $this->assertEquals('Kg', $requirements[0]['unit']);
    }

    public function test_event_day_operations_checklist()
    {
        $this->actingAs($this->user);

        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'booking_date' => '2026-08-10',
            'slot_id' => $this->slot->id,
            'start_time' => $this->slot->start_time,
            'end_time' => $this->slot->end_time,
            'guest_count' => 300,
            'hall_id' => $this->hall->id,
            'status' => 'Draft',
        ]);

        // Create checklist tasks
        $task1 = EventChecklist::create([
            'booking_id' => $booking->id,
            'task_name' => 'Check backup generator fuel',
            'category' => 'Sound System',
            'status' => 'Pending',
            'assigned_to' => $this->employee->id,
        ]);

        $task2 = EventChecklist::create([
            'booking_id' => $booking->id,
            'task_name' => 'Prepare appetizers and welcome drinks',
            'category' => 'Catering',
            'status' => 'In Progress',
        ]);

        $this->assertDatabaseHas('event_checklists', [
            'task_name' => 'Check backup generator fuel',
        ]);

        // Assert relationships
        $this->assertCount(2, $booking->checklists);
        $this->assertEquals($this->employee->id, $task1->assignee->id);

        // Complete a task
        $task1->update([
            'status' => 'Completed',
            'completed_at' => now(),
        ]);

        $this->assertEquals('Completed', $task1->fresh()->status);
        $this->assertNotNull($task1->fresh()->completed_at);
    }

    public function test_event_vendor_management_and_booking_commissions()
    {
        $this->actingAs($this->user);

        // Register Vendor
        $vendor = Vendor::create([
            'name' => 'Shalimar Decorators',
            'vendor_type' => 'Decorator',
            'phone' => '+923005555555',
            'status' => 'Active',
        ]);

        $this->assertDatabaseHas('vendors', [
            'name' => 'Shalimar Decorators',
        ]);

        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'booking_date' => '2026-08-10',
            'slot_id' => $this->slot->id,
            'start_time' => $this->slot->start_time,
            'end_time' => $this->slot->end_time,
            'guest_count' => 300,
            'hall_id' => $this->hall->id,
            'status' => 'Draft',
        ]);

        // Assign vendor to booking with commission rules
        $vendorBooking = VendorBooking::create([
            'vendor_id' => $vendor->id,
            'booking_id' => $booking->id,
            'agreed_price' => 80000.00,
            'commission_rate' => 15.00, // 15% commission
            'payment_status' => 'Unpaid',
        ]);

        // Verify commission amount is auto-calculated on save: 80,000 * 15% = 12,000
        $this->assertDatabaseHas('vendor_bookings', [
            'id' => $vendorBooking->id,
            'commission_amount' => 12000.00,
        ]);

        $this->assertCount(1, $booking->vendorBookings);
        $this->assertCount(1, $vendor->bookings);
        $this->assertEquals($vendor->id, $booking->vendorBookings()->first()->vendor_id);
    }

    public function test_ui_components_load_successfully()
    {
        $this->actingAs($this->user);

        $this->get(route('staff.attendance'))
            ->assertStatus(200);

        $this->get(route('recipes.index'))
            ->assertStatus(200);

        $this->get(route('operations.checklists'))
            ->assertStatus(200);

        $this->get(route('vendors.index'))
            ->assertStatus(200);
    }
}
