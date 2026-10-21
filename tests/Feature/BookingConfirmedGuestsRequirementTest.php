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
use App\Livewire\BookingWizard;
use App\Livewire\BookingOnePage;
use App\Livewire\BookingEdit;
use App\Livewire\BookingSlip;
use App\Livewire\BookingSlipV2;
use App\Livewire\BookingSlipV3;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingConfirmedGuestsRequirementTest extends TestCase
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

        $role = \App\Models\Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['label' => 'Super Admin']
        );

        $this->user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@marquee.test',
            'password' => bcrypt('password'),
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'role_id' => $role->id,
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

    public function test_wizard_requires_confirmed_guests_and_allows_tentative_guests_optional()
    {
        Livewire::actingAs($this->user);

        // Step 4 validation fails when confirmedGuests is missing
        Livewire::test(BookingWizard::class)
            ->set('currentStep', 4)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('selectedEventTypeId', $this->eventType->id)
            ->set('selectedHallIds', [(string)$this->hall->id])
            ->set('selectedDate', now()->addDays(5)->format('d-m-Y'))
            ->set('startTime', '18:00')
            ->set('endTime', '22:00')
            ->set('selectedPackageId', $this->package->id)
            ->set('confirmedGuests', null)
            ->set('tentativeGuests', 150)
            ->call('nextStep')
            ->assertHasErrors(['confirmedGuests' => 'required']);

        // Step 4 succeeds when confirmedGuests is provided, even if tentativeGuests is null
        Livewire::test(BookingWizard::class)
            ->set('currentStep', 4)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('selectedEventTypeId', $this->eventType->id)
            ->set('selectedHallIds', [(string)$this->hall->id])
            ->set('selectedDate', now()->addDays(5)->format('d-m-Y'))
            ->set('startTime', '18:00')
            ->set('endTime', '22:00')
            ->set('selectedPackageId', $this->package->id)
            ->set('confirmedGuests', 250)
            ->set('tentativeGuests', null)
            ->call('nextStep')
            ->assertHasNoErrors()
            ->assertSet('currentStep', 5);
    }

    public function test_one_page_requires_confirmed_guests_and_allows_tentative_guests_optional()
    {
        Livewire::actingAs($this->user);

        $date = now()->addDays(7)->format('Y-m-d');

        // Validation fails when confirmedGuests is missing
        Livewire::test(BookingOnePage::class)
            ->set('selectedBranchId', (string)$this->branch->id)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('selectedEventTypeId', $this->eventType->id)
            ->set('selectedHallIds', [(string)$this->hall->id])
            ->set('selectedDate', $date)
            ->set('startTime', '18:00')
            ->set('endTime', '22:00')
            ->set('isAvailable', true)
            ->set('selectedPackageId', $this->package->id)
            ->set('confirmedGuests', null)
            ->set('tentativeGuests', null)
            ->call('submitBooking')
            ->assertHasErrors(['confirmedGuests' => 'required']);

        // Succeeds when confirmedGuests is provided and tentativeGuests is omitted
        Livewire::test(BookingOnePage::class)
            ->set('selectedBranchId', (string)$this->branch->id)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('selectedEventTypeId', $this->eventType->id)
            ->set('selectedHallIds', [(string)$this->hall->id])
            ->set('selectedDate', $date)
            ->set('startTime', '18:00')
            ->set('endTime', '22:00')
            ->set('isAvailable', true)
            ->set('selectedPackageId', $this->package->id)
            ->set('confirmedGuests', 300)
            ->set('tentativeGuests', null)
            ->set('bookingStatus', 'Confirmed')
            ->call('submitBooking')
            ->assertHasNoErrors();

        $booking = Booking::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($booking);
        $this->assertEquals(300, $booking->confirmed_guests);
        $this->assertNull($booking->tentative_guests);
        $this->assertEquals(300, $booking->guest_count);
    }

    public function test_booking_edit_requires_confirmed_guests_and_allows_tentative_guests_optional()
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
            'guest_count' => 200,
            'tentative_guests' => null,
            'confirmed_guests' => 200,
            'guest_status' => 'Confirmed',
            'per_plate_price' => 1500.00,
            'package_amount' => 300000.00,
            'subtotal' => 300000.00,
            'grand_total' => 300000.00,
            'booking_status' => 'Reserved',
            'payment_status' => 'Unpaid',
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user);

        // Validation fails when confirmedGuests is cleared
        Livewire::test(BookingEdit::class, ['booking' => $booking])
            ->set('confirmedGuests', null)
            ->call('save')
            ->assertHasErrors(['confirmedGuests' => 'required']);

        // Update succeeds when confirmedGuests is provided and tentativeGuests is optional/empty
        Livewire::test(BookingEdit::class, ['booking' => $booking])
            ->set('confirmedGuests', 350)
            ->set('tentativeGuests', null)
            ->call('save')
            ->assertHasNoErrors();

        $booking->refresh();
        $this->assertEquals(350, $booking->confirmed_guests);
        $this->assertNull($booking->tentative_guests);
    }

    public function test_printable_booking_sheets_render_tentative_and_confirmed_guests_as_separate_entries()
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
            'guest_count' => 400,
            'tentative_guests' => 450,
            'confirmed_guests' => 400,
            'guest_status' => 'Confirmed',
            'per_plate_price' => 1500.00,
            'package_amount' => 600000.00,
            'subtotal' => 600000.00,
            'grand_total' => 600000.00,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Unpaid',
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user);

        // Standard Booking Slip
        Livewire::test(BookingSlip::class, ['booking' => $booking])
            ->assertSee('Tentative Guests:')
            ->assertSee('450')
            ->assertSee('Confirmed Guests:')
            ->assertSee('400');

        // Slip V2
        Livewire::test(BookingSlipV2::class, ['booking' => $booking])
            ->assertSee('Tentative Guests:')
            ->assertSee('450')
            ->assertSee('Confirmed Guests:')
            ->assertSee('400');

        // Slip V3
        Livewire::test(BookingSlipV3::class, ['booking' => $booking])
            ->assertSee('Tentative Guests:')
            ->assertSee('450')
            ->assertSee('Confirmed Guests:')
            ->assertSee('400');
    }
}
