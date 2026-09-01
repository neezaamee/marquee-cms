<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Booking;
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
use App\Livewire\BookingSlip;
use App\Livewire\BookingSlipV2;
use App\Livewire\BookingSlipV3;
use App\Livewire\BookingView;
use Tests\TestCase;
use Carbon\Carbon;

class BookingBranchDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected $marquee;
    protected $branch;
    protected $subBranch;
    protected $hallMain;
    protected $hallSub;
    protected $user;
    protected $customer;
    protected $eventType;
    protected $package;
    protected $slot;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-30 12:00:00'));

        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-plan',
            'price' => 25000,
            'billing_interval' => 'month',
        ]);

        $this->seed(RolesAndPermissionsSeeder::class);
        $ownerRole = Role::where('name', 'owner')->first();

        $this->marquee = Marquee::create([
            'name' => 'Grand Emerald Marquee Brand',
            'address' => 'Main Head Office Boulevard',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '0300-1111111',
            'email' => 'emerald@marquee.com',
            'status' => 'active',
            'subscription_plan_id' => $plan->id,
        ]);

        // Main Branch (Head Office)
        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Gulberg Branch',
            'address' => '88 Main Boulevard, Gulberg III',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '0300-2222222',
            'branch_manager' => 'Shahid Khan',
            'is_head_office' => true,
            'status' => 'active',
        ]);

        // Sub Branch
        $this->subBranch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'DHA Phase 5 Branch',
            'address' => 'Sector C, Commercial Area, DHA Phase 5',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '0300-3333333',
            'branch_manager' => 'Faisal Mehmood',
            'is_head_office' => false,
            'status' => 'active',
        ]);

        $this->hallMain = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Royal Banquet Hall A',
            'hall_code' => 'RBL-A',
            'capacity' => 500,
            'hall_type' => 'Banquet',
            'default_booking_price' => 80000.00,
            'status' => 'active',
        ]);

        $this->hallSub = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->subBranch->id,
            'hall_name' => 'Emerald Grand Ballroom',
            'hall_code' => 'EGB-DHA',
            'capacity' => 700,
            'hall_type' => 'Banquet',
            'default_booking_price' => 120000.00,
            'status' => 'active',
        ]);

        $this->user = User::create([
            'name' => 'Owner Alim',
            'email' => 'owner.alim@emerald.com',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->subBranch->id,
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_code' => 'CUST-00001',
            'customer_type' => 'Individual',
            'first_name' => 'Tariq',
            'last_name' => 'Jamil',
            'phone_number' => '0321-4444444',
            'email' => 'tariq@test.com',
            'city' => 'Lahore',
        ]);

        $this->eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Barat Reception',
            'event_type_code' => 'EVT-BRT',
            'status' => 'Active',
        ]);

        $this->package = Package::create([
            'marquee_id' => $this->marquee->id,
            'package_name' => 'Royal Gold Package',
            'package_code' => 'PKG-GOLD',
            'per_plate_price' => 1800.00,
            'minimum_guests' => 100,
            'status' => 'Active',
        ]);

        $this->slot = Slot::create([
            'marquee_id' => $this->marquee->id,
            'slot_name' => 'Dinner Shift',
            'slot_code' => 'SLOT-DINNER',
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'status' => 'Active',
        ]);
    }

    /** @test */
    public function test_booking_model_resolves_branch_relationship_and_effective_branch()
    {
        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->subBranch->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hallSub->id,
            'slot_id' => $this->slot->id,
            'package_id' => $this->package->id,
            'booking_date' => '2026-09-15',
            'start_time' => '2026-09-15 19:00:00',
            'end_time' => '2026-09-15 23:00:00',
            'guest_count' => 300,
            'per_plate_price' => 1800.00,
            'package_amount' => 540000.00,
            'hall_charges' => 120000.00,
            'extra_charges' => 0.00,
            'discount_amount' => 0.00,
            'security_deposit' => 25000.00,
            'tax_amount' => 85800.00,
            'subtotal' => 660000.00,
            'grand_total' => 770800.00,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Unpaid',
            'created_by' => $this->user->id,
        ]);

        $this->assertNotNull($booking->branch);
        $this->assertEquals('DHA Phase 5 Branch', $booking->branch->name);
        $this->assertEquals('Sector C, Commercial Area, DHA Phase 5', $booking->branch->address);
        $this->assertEquals('0300-3333333', $booking->branch->phone);

        $this->assertNotNull($booking->effective_branch);
        $this->assertEquals($this->subBranch->id, $booking->effective_branch->id);
    }

    /** @test */
    public function test_booking_slips_display_branch_name_address_and_phone()
    {
        $this->actingAs($this->user);

        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->subBranch->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hallSub->id,
            'slot_id' => $this->slot->id,
            'package_id' => $this->package->id,
            'booking_date' => '2026-09-15',
            'start_time' => '2026-09-15 19:00:00',
            'end_time' => '2026-09-15 23:00:00',
            'guest_count' => 300,
            'per_plate_price' => 1800.00,
            'package_amount' => 540000.00,
            'hall_charges' => 120000.00,
            'extra_charges' => 0.00,
            'discount_amount' => 0.00,
            'security_deposit' => 25000.00,
            'tax_amount' => 85800.00,
            'subtotal' => 660000.00,
            'grand_total' => 770800.00,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Unpaid',
            'created_by' => $this->user->id,
        ]);

        // Test Booking Slip V1
        Livewire::test(BookingSlip::class, ['booking' => $booking])
            ->assertSee('Grand Emerald Marquee Brand')
            ->assertSee('DHA Phase 5 Branch')
            ->assertSee('Sector C, Commercial Area, DHA Phase 5')
            ->assertSee('0300-3333333');

        // Test Booking Slip V2
        Livewire::test(BookingSlipV2::class, ['booking' => $booking])
            ->assertSee('Grand Emerald Marquee Brand')
            ->assertSee('DHA Phase 5 Branch')
            ->assertSee('Sector C, Commercial Area, DHA Phase 5')
            ->assertSee('0300-3333333');

        // Test Booking Slip V3
        Livewire::test(BookingSlipV3::class, ['booking' => $booking])
            ->assertSee('Grand Emerald Marquee Brand')
            ->assertSee('DHA Phase 5 Branch')
            ->assertSee('Sector C, Commercial Area, DHA Phase 5')
            ->assertSee('0300-3333333');
    }

    /** @test */
    public function test_booking_view_shows_branch_details_in_event_details_section()
    {
        $this->actingAs($this->user);

        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->subBranch->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hallSub->id,
            'slot_id' => $this->slot->id,
            'package_id' => $this->package->id,
            'booking_date' => '2026-09-15',
            'start_time' => '2026-09-15 19:00:00',
            'end_time' => '2026-09-15 23:00:00',
            'guest_count' => 300,
            'per_plate_price' => 1800.00,
            'package_amount' => 540000.00,
            'hall_charges' => 120000.00,
            'extra_charges' => 0.00,
            'discount_amount' => 0.00,
            'security_deposit' => 25000.00,
            'tax_amount' => 85800.00,
            'subtotal' => 660000.00,
            'grand_total' => 770800.00,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Unpaid',
            'created_by' => $this->user->id,
        ]);

        Livewire::test(BookingView::class, ['booking' => $booking])
            ->assertSee('DHA Phase 5 Branch')
            ->assertSee('Sector C, Commercial Area, DHA Phase 5')
            ->assertSee('0300-3333333');
    }

    /** @test */
    public function test_kitchen_slip_and_payment_receipt_display_branch_details()
    {
        $this->actingAs($this->user);

        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->subBranch->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hallSub->id,
            'slot_id' => $this->slot->id,
            'package_id' => $this->package->id,
            'booking_date' => '2026-09-15',
            'start_time' => '2026-09-15 19:00:00',
            'end_time' => '2026-09-15 23:00:00',
            'guest_count' => 300,
            'per_plate_price' => 1800.00,
            'package_amount' => 540000.00,
            'hall_charges' => 120000.00,
            'extra_charges' => 0.00,
            'discount_amount' => 0.00,
            'security_deposit' => 25000.00,
            'tax_amount' => 85800.00,
            'subtotal' => 660000.00,
            'grand_total' => 770800.00,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Unpaid',
            'created_by' => $this->user->id,
        ]);

        // Kitchen Slip HTTP route
        $responseKitchen = $this->get(route('bookings.kitchen-slip', $booking->id));
        $responseKitchen->assertStatus(200);
        $responseKitchen->assertSee('DHA Phase 5 Branch');
        $responseKitchen->assertSee('Sector C, Commercial Area, DHA Phase 5');

        // Record a Payment
        $payment = \App\Models\BookingPayment::create([
            'booking_id' => $booking->id,
            'amount' => 200000.00,
            'payment_date' => '2026-09-01',
            'payment_method' => 'Cash',
            'transaction_reference' => 'CASH-001',
            'recorded_by' => $this->user->id,
        ]);

        // Payment Receipt HTTP route
        $responseReceipt = $this->get(route('bookings.payment-receipt', $payment->id));
        $responseReceipt->assertStatus(200);
        $responseReceipt->assertSee('DHA Phase 5 Branch');
        $responseReceipt->assertSee('Sector C, Commercial Area, DHA Phase 5');
        $responseReceipt->assertSee('0300-3333333');
    }
}
