<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Package;
use App\Models\User;
use App\Livewire\BookingView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuestConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Marquee $marquee;
    protected Branch $branch;
    protected Customer $customer;
    protected EventType $eventType;
    protected Hall $hall;
    protected Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = \App\Models\SubscriptionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-plan',
            'price' => 1000,
            'billing_interval' => 'monthly',
            'max_branches' => 10,
            'status' => 'active',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Grand Palace Marquee',
            'slug' => 'grand-palace',
            'status' => 'active',
            'address' => '123 Main St',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'zip_code' => '54000',
            'country' => 'Pakistan',
            'phone' => '03001234567',
            'email' => 'info@grandpalace.test',
            'subscription_plan_id' => $plan->id,
            'is_setup_completed' => true,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Branch',
            'address' => '123 Main St',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'code' => 'MB-01',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@marquee.test',
            'password' => bcrypt('password'),
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'full_name' => 'John Doe',
            'customer_code' => 'CUST-001',
            'phone_number' => '03001234567',
        ]);

        $this->eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Wedding Reception',
            'event_type_code' => 'ET-001',
        ]);

        $this->hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Royal Hall',
            'hall_code' => 'HALL-001',
            'hall_type' => 'Marquee',
            'capacity' => 1000,
            'default_booking_price' => 50000.00,
            'status' => 'active',
        ]);

        $this->package = Package::create([
            'marquee_id' => $this->marquee->id,
            'package_name' => 'Gold Package',
            'package_code' => 'PKG-001',
            'per_plate_price' => 1500.00,
            'minimum_guests' => 100,
            'status' => 'active',
        ]);
    }

    public function test_booking_saves_tentative_and_confirmed_guests()
    {
        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hall->id,
            'package_id' => $this->package->id,
            'booking_date' => now()->addDays(10)->format('Y-m-d'),
            'start_time' => now()->addDays(10)->setTime(18, 0),
            'end_time' => now()->addDays(10)->setTime(22, 0),
            'guest_count' => 500,
            'tentative_guests' => 500,
            'confirmed_guests' => null,
            'guest_status' => 'Tentative',
            'per_plate_price' => 1500.00,
            'package_amount' => 750000.00,
            'subtotal' => 750000.00,
            'grand_total' => 750000.00,
            'booking_status' => 'Reserved',
            'payment_status' => 'Unpaid',
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'tentative_guests' => 500,
            'confirmed_guests' => null,
            'guest_status' => 'Tentative',
            'guest_count' => 500,
        ]);

        $this->assertEquals(500, $booking->effective_guest_count);
        $this->assertFalse($booking->is_guest_confirmed);
    }

    public function test_booking_guest_count_can_be_confirmed_via_livewire()
    {
        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hall->id,
            'package_id' => $this->package->id,
            'booking_date' => now()->addDays(10)->format('Y-m-d'),
            'start_time' => now()->addDays(10)->setTime(18, 0),
            'end_time' => now()->addDays(10)->setTime(22, 0),
            'guest_count' => 500,
            'tentative_guests' => 500,
            'confirmed_guests' => null,
            'guest_status' => 'Tentative',
            'per_plate_price' => 1500.00,
            'package_amount' => 750000.00,
            'subtotal' => 750000.00,
            'grand_total' => 750000.00,
            'booking_status' => 'Reserved',
            'payment_status' => 'Unpaid',
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(BookingView::class, ['booking' => $booking])
            ->call('openGuestModal')
            ->set('modalTentativeGuests', 500)
            ->set('modalConfirmedGuests', 450)
            ->call('confirmGuestCount')
            ->assertHasNoErrors();

        $booking->refresh();

        $this->assertEquals(500, $booking->tentative_guests);
        $this->assertEquals(450, $booking->confirmed_guests);
        $this->assertEquals('Confirmed', $booking->guest_status);
        $this->assertEquals(450, $booking->effective_guest_count);
        $this->assertTrue($booking->is_guest_confirmed);
    }
}
