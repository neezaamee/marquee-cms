<?php

namespace Tests\Feature;

use App\Livewire\BookingList;
use App\Livewire\Owner\BusinessOwnerDashboard;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClosingReceivablesDueTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Marquee $marquee;
    protected Branch $branch;
    protected Hall $hall;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $ownerRole = Role::where('name', 'owner')->first();

        $this->marquee = Marquee::create([
            'name' => 'Royal Palace Marquee',
            'slug' => 'royal-palace-marquee',
            'status' => 'active',
            'address' => 'Mall Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'email' => 'royal@example.com',
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Executive Wing',
            'code' => 'EW-01',
            'address' => 'Mall Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'email' => 'branch@example.com',
            'status' => 'Active',
            'is_main_branch' => true,
        ]);

        $this->hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Grand Ballroom',
            'hall_code' => 'GB-01',
            'hall_type' => 'Indoor',
            'capacity' => 500,
            'default_booking_price' => 50000,
            'status' => 'Active',
        ]);

        $this->owner = User::create([
            'name' => 'Marquee Owner',
            'email' => 'owner@example.com',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole ? $ownerRole->id : null,
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_code' => 'CUST-00001',
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'phone_number' => '03001112233',
            'created_by' => $this->owner->id,
        ]);
    }

    public function test_booking_list_shows_closing_receivables_due_when_past_date_is_selected(): void
    {
        $this->actingAs($this->owner);

        // Booking on Oct 10, 2026. Grand Total: 100,000.
        // Payment 1: 30,000 paid on Oct 10, 2026.
        // Payment 2: 70,000 paid on Oct 25, 2026.
        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_id' => $this->hall->id,
            'customer_id' => $this->customer->id,
            'booking_number' => 'BKG-HIST-001',
            'booking_date' => '2026-10-10',
            'event_date' => '2026-10-10',
            'shift' => 'Evening',
            'start_time' => '18:00:00',
            'end_time' => '23:00:00',
            'guest_count' => 300,
            'rate_per_person' => 300,
            'grand_total' => 100000,
            'advance_received' => 30000,
            'booking_status' => 'Completed',
            'payment_status' => 'Paid',
            'is_revenue_recognized' => true,
            'receivable_amount' => 0,
            'created_by' => $this->owner->id,
        ]);

        BookingPayment::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'booking_id' => $booking->id,
            'receipt_no' => 'RCP-001',
            'payment_type' => 'advance',
            'payment_method' => 'Cash',
            'amount' => 30000,
            'status' => 'posted',
            'payment_date' => '2026-10-10',
            'created_by' => $this->owner->id,
        ]);

        BookingPayment::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'booking_id' => $booking->id,
            'receipt_no' => 'RCP-002',
            'payment_type' => 'receivable_payment',
            'payment_method' => 'Cash',
            'amount' => 70000,
            'status' => 'posted',
            'payment_date' => '2026-10-25',
            'created_by' => $this->owner->id,
        ]);

        // When filtering with past date cutoff of Oct 15, 2026:
        // Card title should be "Closing Receivables due" and unpaid balance as of that date should be 70,000.
        Livewire::test(BookingList::class)
            ->set('filterDateEnd', '15-10-2026')
            ->assertSee('Closing Receivables due')
            ->assertSee('As of 15 Oct, 2026')
            ->assertSee('70,000')
            ->call('applyShortcutFilter', 'outstanding')
            ->assertSet('filterBalanceStatus', 'outstanding')
            ->assertSet('filterDateEnd', '15-10-2026')
            ->assertSee('BKG-HIST-001');

        // When filtering after full settlement (Oct 30, 2026):
        Livewire::test(BookingList::class)
            ->set('filterDateEnd', '30-10-2026')
            ->assertSee('Closing Receivables due')
            ->assertSee('As of 30 Oct, 2026')
            ->assertSee('Rs. 0');
    }

    public function test_dashboard_shows_closing_receivables_due_when_past_custom_range_is_selected(): void
    {
        $this->actingAs($this->owner);

        // Booking on Oct 10, 2026. Grand Total: 150,000.
        // Payment 1: 50,000 paid on Oct 10, 2026.
        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_id' => $this->hall->id,
            'customer_id' => $this->customer->id,
            'booking_number' => 'BKG-HIST-002',
            'booking_date' => '2026-10-10',
            'event_date' => '2026-10-10',
            'shift' => 'Evening',
            'start_time' => '18:00:00',
            'end_time' => '23:00:00',
            'guest_count' => 300,
            'rate_per_person' => 500,
            'grand_total' => 150000,
            'advance_received' => 50000,
            'booking_status' => 'Completed',
            'payment_status' => 'Partially Paid',
            'is_revenue_recognized' => true,
            'receivable_amount' => 100000,
            'created_by' => $this->owner->id,
        ]);

        BookingPayment::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'booking_id' => $booking->id,
            'receipt_no' => 'RCP-003',
            'payment_type' => 'advance',
            'payment_method' => 'Cash',
            'amount' => 50000,
            'status' => 'posted',
            'payment_date' => '2026-10-10',
            'created_by' => $this->owner->id,
        ]);

        Livewire::test(BusinessOwnerDashboard::class)
            ->set('timeframe', 'custom')
            ->set('customDateFrom', '01-10-2026')
            ->set('customDateTo', '15-10-2026')
            ->assertSee('Closing Receivables due')
            ->assertSee('As of 15 Oct, 2026')
            ->assertSee('Rs. 100.0k');
    }
}

