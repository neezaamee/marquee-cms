<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Package;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Livewire\BookingList;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OperationalBookingDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $userOwner;
    protected Marquee $marquee;
    protected Branch $branch;
    protected Customer $customer;
    protected EventType $eventType;
    protected Hall $hall;
    protected Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-plan',
            'price' => 1000,
            'billing_interval' => 'monthly',
            'max_branches' => 10,
            'status' => 'active',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Royal Marriage Complex',
            'slug' => 'royal-marriage-complex',
            'status' => 'active',
            'address' => '123 Boulevard St',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001112233',
            'email' => 'contact@royalcomplex.test',
            'subscription_plan_id' => $plan->id,
            'is_setup_completed' => true,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Executive Branch',
            'code' => 'EX-01',
            'address' => '123 Boulevard St',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001112233',
            'status' => 'active',
        ]);

        $this->userOwner = User::create([
            'name' => 'Executive Manager',
            'email' => 'manager@royalcomplex.test',
            'password' => bcrypt('password'),
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'full_name' => 'Michael Scott',
            'customer_code' => 'CUST-808',
            'phone_number' => '03219998877',
        ]);

        $this->eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Corporate Gala',
            'event_type_code' => 'ET-GALA',
        ]);

        $this->hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Grand Executive Hall',
            'hall_code' => 'HALL-EXEC',
            'hall_type' => 'Banquet',
            'capacity' => 800,
            'default_booking_price' => 75000.00,
            'status' => 'active',
        ]);

        $this->package = Package::create([
            'marquee_id' => $this->marquee->id,
            'package_name' => 'Platinum Menu Package',
            'package_code' => 'PKG-PLAT',
            'per_plate_price' => 2000.00,
            'minimum_guests' => 100,
            'status' => 'active',
        ]);
    }

    public function test_booking_dashboard_renders_metrics_and_list_correctly()
    {
        // 1. Create a today's booking
        $bookingToday = Booking::create([
            'marquee_id' => $this->marquee->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hall->id,
            'package_id' => $this->package->id,
            'booking_date' => Carbon::today()->format('Y-m-d'),
            'start_time' => Carbon::today()->setTime(18, 0),
            'end_time' => Carbon::today()->setTime(22, 0),
            'guest_count' => 300,
            'tentative_guests' => 300,
            'confirmed_guests' => 300,
            'guest_status' => 'Confirmed',
            'per_plate_price' => 2000.00,
            'package_amount' => 600000.00,
            'grand_total' => 600000.00,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Unpaid',
            'created_by' => $this->userOwner->id,
        ]);

        // 2. Create a pending draft booking
        $bookingDraft = Booking::create([
            'marquee_id' => $this->marquee->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hall->id,
            'package_id' => $this->package->id,
            'booking_date' => Carbon::today()->addDays(5)->format('Y-m-d'),
            'start_time' => Carbon::today()->addDays(5)->setTime(18, 0),
            'end_time' => Carbon::today()->addDays(5)->setTime(22, 0),
            'guest_count' => 200,
            'tentative_guests' => 200,
            'confirmed_guests' => null,
            'guest_status' => 'Tentative',
            'per_plate_price' => 2000.00,
            'package_amount' => 400000.00,
            'grand_total' => 400000.00,
            'booking_status' => 'Draft',
            'payment_status' => 'Unpaid',
            'created_by' => $this->userOwner->id,
        ]);

        Livewire::actingAs($this->userOwner)
            ->test(BookingList::class)
            ->assertViewHas('totalBookingsCount', 2)
            ->assertViewHas('confirmedBookingsCount', 1)
            ->assertViewHas('todaysEventsCount', 1)
            ->assertViewHas('pendingApprovalsCount', 1)
            ->assertSee($bookingToday->booking_number)
            ->assertSee($bookingDraft->booking_number);
    }

    public function test_interactive_shortcut_filters_filter_results()
    {
        // Create 1 today booking and 1 future draft
        $todayBooking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hall->id,
            'booking_date' => Carbon::today()->format('Y-m-d'),
            'start_time' => Carbon::today()->setTime(18, 0),
            'end_time' => Carbon::today()->setTime(22, 0),
            'guest_count' => 250,
            'grand_total' => 500000.00,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Paid',
            'created_by' => $this->userOwner->id,
        ]);

        $futureDraft = Booking::create([
            'marquee_id' => $this->marquee->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hall->id,
            'booking_date' => Carbon::today()->addDays(12)->format('Y-m-d'),
            'start_time' => Carbon::today()->addDays(12)->setTime(18, 0),
            'end_time' => Carbon::today()->addDays(12)->setTime(22, 0),
            'guest_count' => 150,
            'grand_total' => 300000.00,
            'booking_status' => 'Draft',
            'payment_status' => 'Unpaid',
            'created_by' => $this->userOwner->id,
        ]);

        // Filter today
        Livewire::actingAs($this->userOwner)
            ->test(BookingList::class)
            ->call('applyShortcutFilter', 'today')
            ->assertSee($todayBooking->booking_number)
            ->assertDontSee($futureDraft->booking_number);

        // Filter pending
        Livewire::actingAs($this->userOwner)
            ->test(BookingList::class)
            ->call('applyShortcutFilter', 'pending')
            ->assertSee($futureDraft->booking_number)
            ->assertDontSee($todayBooking->booking_number);
    }

    public function test_quick_approval_action_updates_booking_status()
    {
        $draftBooking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hall->id,
            'booking_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
            'start_time' => Carbon::today()->addDays(3)->setTime(18, 0),
            'end_time' => Carbon::today()->addDays(3)->setTime(22, 0),
            'guest_count' => 150,
            'grand_total' => 300000.00,
            'booking_status' => 'Draft',
            'payment_status' => 'Unpaid',
            'created_by' => $this->userOwner->id,
        ]);

        Livewire::actingAs($this->userOwner)
            ->test(BookingList::class)
            ->call('approveBooking', $draftBooking->id)
            ->assertHasNoErrors();

        $this->assertEquals('Confirmed', $draftBooking->fresh()->booking_status);

        $this->assertDatabaseHas('booking_histories', [
            'booking_id' => $draftBooking->id,
            'status_from' => 'Draft',
            'status_to' => 'Confirmed',
        ]);
    }
}
