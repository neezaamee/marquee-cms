<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\BookingStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingStatusAlertTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $marquee;
    protected $branch;
    protected $hall;
    protected $eventType;
    protected $customer;
    protected $booking;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & plans
        $this->artisan('db:seed', ['--class' => 'SubscriptionPlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $plan = SubscriptionPlan::first();

        $this->marquee = Marquee::create([
            'name' => 'Alert Test Marquee',
            'email' => 'alert@marquee.com',
            'phone' => '12345678',
            'address' => 'Test Address',
            'city' => 'Test City',
            'province' => 'Test Province',
            'ntn' => '123456',
            'status' => 'active',
            'subscription_plan_id' => $plan->id,
        ]);

        $ownerRole = Role::where('name', 'owner')->first();

        $this->user = User::create([
            'name' => 'Owner User',
            'email' => 'owner@test.com',
            'username' => 'owner',
            'password' => bcrypt('Password123!'),
            'marquee_id' => $this->marquee->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Lahore Gulberg Branch',
            'address' => 'Gulberg III, Lahore',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '042-35876543',
            'status' => 'active',
        ]);

        $this->hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Royal Ballroom',
            'hall_code' => 'RBR',
            'capacity' => 400,
            'hall_type' => 'Banquet',
            'default_booking_price' => 60000.00,
            'status' => 'active',
        ]);

        $this->eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'event_type_name' => 'Walima',
            'event_type_code' => 'WAL',
            'status' => 'active',
            'is_system_default' => false,
        ]);

        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_code' => 'CUST-00101',
            'customer_type' => 'Individual',
            'first_name' => 'Iftikhar',
            'last_name' => 'Ahmed',
            'phone_number' => '03009876543',
            'email' => 'iftikhar@customer.com',
            'status' => 'Active',
        ]);

        $this->booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'customer_id' => $this->customer->id,
            'hall_id' => $this->hall->id,
            'event_type_id' => $this->eventType->id,
            'booking_date' => '2026-10-15',
            'start_time' => '2026-10-15 18:00:00',
            'end_time' => '2026-10-15 23:00:00',
            'guest_count' => 300,
            'grand_total' => 450000.00,
            'booking_status' => 'Draft',
        ]);
    }

    /** @test */
    public function test_booking_status_change_dispatches_notification_and_logs_sms()
    {
        $this->actingAs($this->user);

        Notification::fake();
        
        // Listen to log writing
        $logSpy = Log::spy();

        // Perform status update
        $this->booking->update([
            'booking_status' => 'Confirmed'
        ]);

        // Assert notification dispatched to the customer email
        Notification::assertSentTo(
            new \Illuminate\Notifications\AnonymousNotifiable,
            BookingStatusNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail'] === 'iftikhar@customer.com';
            }
        );

        // Assert simulated SMS has been printed to system logs
        $logSpy->shouldHaveReceived('info')
            ->withArgs(function ($message) {
                return str_contains($message, 'SMS ALERT Sent to 03009876543') 
                    && str_contains($message, 'Draft') 
                    && str_contains($message, 'Confirmed');
            });
    }
}
