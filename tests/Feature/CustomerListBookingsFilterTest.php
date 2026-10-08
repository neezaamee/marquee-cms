<?php

namespace Tests\Feature;

use App\Livewire\CustomerList;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Package;
use App\Models\Role;
use App\Models\Slot;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerListBookingsFilterTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $marquee;
    protected $branch;
    protected $hall;
    protected $slot;
    protected $eventType;
    protected $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'SubscriptionPlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $plan = SubscriptionPlan::first();

        $this->marquee = Marquee::create([
            'name' => 'Test Marquee',
            'email' => 'test@marquee.com',
            'phone' => '12345678',
            'address' => 'Test Address',
            'city' => 'Lahore',
            'province' => 'Punjab',
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
            'name' => 'Main Branch',
            'code' => 'BR-01',
            'address' => '123 Main Street',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'status' => 'active',
            'is_main_branch' => true,
        ]);

        $this->hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_code' => 'HALL-01',
            'hall_name' => 'Hall A',
            'hall_type' => 'indoor',
            'capacity' => 500,
            'default_booking_price' => 50000.00,
            'status' => 'active',
        ]);

        $this->slot = Slot::create([
            'marquee_id' => $this->marquee->id,
            'slot_name' => 'Evening Slot',
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
        ]);

        $this->eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_code' => 'EVT-01',
            'event_type_name' => 'Wedding',
            'status' => 'active',
        ]);

        $this->package = Package::create([
            'marquee_id' => $this->marquee->id,
            'package_code' => 'PKG-01',
            'package_name' => 'Gold Package',
            'base_price' => 1500,
            'per_plate_price' => 1500,
            'status' => 'active',
        ]);
    }

    private function createCustomer(string $firstName, string $lastName): Customer
    {
        return Customer::create([
            'marquee_id' => $this->marquee->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone_number' => '03001234567',
            'customer_type' => 'Individual',
            'status' => 'Active',
            'created_by' => $this->user->id,
        ]);
    }

    private function createBooking(Customer $customer, string $status = 'Confirmed'): Booking
    {
        return Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_id' => $this->hall->id,
            'slot_id' => $this->slot->id,
            'event_type_id' => $this->eventType->id,
            'package_id' => $this->package->id,
            'customer_id' => $customer->id,
            'booking_number' => 'BK-' . uniqid(),
            'booking_date' => now()->addDays(5)->toDateString(),
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'guest_count' => 200,
            'per_plate_price' => 1500,
            'grand_total' => 300000,
            'advance_received' => 50000,
            'receivable_amount' => 250000,
            'booking_status' => $status,
            'payment_status' => 'Partially Paid',
            'financial_status' => 'Partially Paid',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_customer_list_filters_by_single_booking(): void
    {
        $this->actingAs($this->user);

        // Customer with 0 bookings
        $cZero = $this->createCustomer('Tariq', 'ZeroClient');

        // Customer with 1 booking
        $cSingle = $this->createCustomer('Bilal', 'SoloClient');
        $this->createBooking($cSingle);

        // Customer with multiple bookings
        $cMulti = $this->createCustomer('Hamza', 'MultiClient');
        $this->createBooking($cMulti);
        $this->createBooking($cMulti);

        Livewire::test(CustomerList::class)
            ->set('filterBookings', 'single')
            ->assertSee($cSingle->full_name)
            ->assertDontSee($cZero->full_name)
            ->assertDontSee($cMulti->full_name);
    }

    public function test_customer_list_filters_by_multiple_bookings(): void
    {
        $this->actingAs($this->user);

        $cZero = $this->createCustomer('Tariq', 'ZeroClient');

        $cSingle = $this->createCustomer('Bilal', 'SoloClient');
        $this->createBooking($cSingle);

        $cMulti = $this->createCustomer('Hamza', 'MultiClient');
        $this->createBooking($cMulti);
        $this->createBooking($cMulti);

        Livewire::test(CustomerList::class)
            ->set('filterBookings', 'multiple')
            ->assertSee($cMulti->full_name)
            ->assertDontSee($cZero->full_name)
            ->assertDontSee($cSingle->full_name);
    }

    public function test_customer_list_filters_by_zero_bookings(): void
    {
        $this->actingAs($this->user);

        $cZero = $this->createCustomer('Tariq', 'ZeroClient');

        $cSingle = $this->createCustomer('Bilal', 'SoloClient');
        $this->createBooking($cSingle);

        Livewire::test(CustomerList::class)
            ->set('filterBookings', 'zero')
            ->assertSee($cZero->full_name)
            ->assertDontSee($cSingle->full_name);
    }

    public function test_cancelled_and_rejected_bookings_are_excluded(): void
    {
        $this->actingAs($this->user);

        // Customer with 1 active booking and 1 cancelled booking -> should count as single
        $cMixed = $this->createCustomer('Zubair', 'MixedClient');
        $this->createBooking($cMixed, 'Confirmed');
        $this->createBooking($cMixed, 'Cancelled');

        Livewire::test(CustomerList::class)
            ->set('filterBookings', 'single')
            ->assertSee($cMixed->full_name);

        Livewire::test(CustomerList::class)
            ->set('filterBookings', 'multiple')
            ->assertDontSee($cMixed->full_name);
    }

    public function test_reset_filters_clears_bookings_filter(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CustomerList::class)
            ->set('filterBookings', 'single')
            ->call('resetFilters')
            ->assertSet('filterBookings', '');
    }
}
