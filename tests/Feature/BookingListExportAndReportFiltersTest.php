<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Package;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingListExportAndReportFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $operator1;
    protected User $operator2;
    protected Marquee $marquee;
    protected Branch $branch1;
    protected Branch $branch2;
    protected Hall $hall1;
    protected Hall $hall2;
    protected EventType $eventType1;
    protected EventType $eventType2;
    protected Customer $customer1;
    protected Customer $customer2;
    protected Booking $booking1;
    protected Booking $booking2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $ownerRole = \App\Models\Role::where('name', 'owner')->first();

        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-plan',
            'price' => 1000,
            'billing_interval' => 'monthly',
            'max_branches' => 10,
            'status' => 'active',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Grand Palace Marquee',
            'slug' => 'grand-palace-marquee',
            'status' => 'active',
            'address' => 'Mall Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001112233',
            'email' => 'contact@grandpalace.test',
            'subscription_plan_id' => $plan->id,
            'is_setup_completed' => true,
        ]);

        $this->branch1 = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Executive Wing',
            'code' => 'EW-01',
            'address' => 'Mall Road Sector A',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001112233',
            'status' => 'active',
        ]);

        $this->branch2 = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Royal Wing',
            'code' => 'RW-02',
            'address' => 'Mall Road Sector B',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03004445566',
            'status' => 'active',
        ]);

        $this->hall1 = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch1->id,
            'hall_name' => 'Emerald Hall',
            'hall_code' => 'HALL-01',
            'hall_type' => 'Banquet',
            'capacity' => 500,
            'default_booking_price' => 50000.00,
            'status' => 'active',
        ]);

        $this->hall2 = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch2->id,
            'hall_name' => 'Diamond Hall',
            'hall_code' => 'HALL-02',
            'hall_type' => 'Banquet',
            'capacity' => 800,
            'default_booking_price' => 75000.00,
            'status' => 'active',
        ]);

        $this->eventType1 = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Wedding Reception',
            'event_type_code' => 'ET-WED',
        ]);

        $this->eventType2 = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Corporate Annual Dinner',
            'event_type_code' => 'ET-CORP',
        ]);

        $this->user = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@grandpalace.test',
            'password' => bcrypt('password'),
            'marquee_id' => $this->marquee->id,
            'role_id' => $ownerRole->id,
            'is_super_admin' => true,
        ]);

        $this->operator1 = User::create([
            'name' => 'Ali Khan',
            'email' => 'ali@grandpalace.test',
            'password' => bcrypt('password'),
            'marquee_id' => $this->marquee->id,
        ]);

        $this->operator2 = User::create([
            'name' => 'Sara Ahmed',
            'email' => 'sara@grandpalace.test',
            'password' => bcrypt('password'),
            'marquee_id' => $this->marquee->id,
        ]);

        $this->customer1 = Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_code' => 'CUST-001',
            'first_name' => 'Zubair',
            'last_name' => 'Ahmad',
            'phone_number' => '03001234567',
            'referred_by_name' => 'Sheikh Usman',
            'referred_by_contact' => '03211112233',
        ]);

        $this->customer2 = Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_code' => 'CUST-002',
            'first_name' => 'Bilal',
            'last_name' => 'Hassan',
            'phone_number' => '03459876543',
            'referred_by_name' => 'Chaudhry Akram',
            'referred_by_contact' => '03334445566',
        ]);

        // Booking 1: Confirmed, Emerald Hall, Branch 1, Wedding, Care Of Sheikh Usman, Per Plate 2000, Confirmed Guests 250
        $this->booking1 = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch1->id,
            'booking_number' => 'BK-FILT-001',
            'customer_id' => $this->customer1->id,
            'event_type_id' => $this->eventType1->id,
            'hall_id' => $this->hall1->id,
            'booking_date' => '2026-07-15',
            'start_time' => '2026-07-15 19:00:00',
            'end_time' => '2026-07-15 23:00:00',
            'tentative_guests' => 300,
            'confirmed_guests' => 250,
            'guest_status' => 'Confirmed',
            'per_plate_price' => 2000.00,
            'grand_total' => 500000.00,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Paid',
            'created_by' => $this->operator1->id,
        ]);

        // Booking 2: Draft, Diamond Hall, Branch 2, Corporate, Care Of Chaudhry Akram, Per Plate 0 (Rent Only), Tentative Guests 150
        $this->booking2 = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch2->id,
            'booking_number' => 'BK-FILT-002',
            'customer_id' => $this->customer2->id,
            'event_type_id' => $this->eventType2->id,
            'hall_id' => $this->hall2->id,
            'booking_date' => '2026-08-25',
            'start_time' => '2026-08-25 10:00:00',
            'end_time' => '2026-08-25 15:00:00',
            'tentative_guests' => 150,
            'confirmed_guests' => null,
            'guest_status' => 'Tentative',
            'no_food' => true,
            'per_plate_price' => 0.00,
            'grand_total' => 150000.00,
            'booking_status' => 'Draft',
            'payment_status' => 'Unpaid',
            'created_by' => $this->operator2->id,
        ]);
    }

    /**
     * Helper to decode CSV content from a Livewire file download.
     */
    protected function getCsvContent($downloadResponse): string
    {
        return base64_decode(data_get($downloadResponse->effects, 'download.content'));
    }

    public function test_export_csv_and_report_reflect_status_filter()
    {
        Livewire::actingAs($this->user);

        // 1. CSV export with filterStatus = Confirmed
        $download = Livewire::test('booking-list')
            ->set('filterStatus', 'Confirmed')
            ->call('exportExcel');
        $csv = $this->getCsvContent($download);
        $this->assertStringContainsString('BK-FILT-001', $csv);
        $this->assertStringNotContainsString('BK-FILT-002', $csv);

        // 2. Print/PDF report with filterStatus = Confirmed
        $this->actingAs($this->user);
        $response = $this->get(route('bookings.report', ['filterStatus' => 'Confirmed']));
        $response->assertStatus(200);
        $response->assertSee('BK-FILT-001');
        $response->assertDontSee('BK-FILT-002');

        // 3. CSV export with filterStatus = Draft
        $downloadDraft = Livewire::test('booking-list')
            ->set('filterStatus', 'Draft')
            ->call('exportExcel');
        $csvDraft = $this->getCsvContent($downloadDraft);
        $this->assertStringContainsString('BK-FILT-002', $csvDraft);
        $this->assertStringNotContainsString('BK-FILT-001', $csvDraft);

        // 4. Print/PDF report with filterStatus = Draft
        $responseDraft = $this->get(route('bookings.report', ['filterStatus' => 'Draft']));
        $responseDraft->assertStatus(200);
        $responseDraft->assertSee('BK-FILT-002');
        $responseDraft->assertDontSee('BK-FILT-001');
    }

    public function test_export_csv_and_report_reflect_payment_status_filter()
    {
        Livewire::actingAs($this->user);

        // Paid filter
        $download = Livewire::test('booking-list')
            ->set('filterPaymentStatus', 'Paid')
            ->call('exportExcel');
        $csv = $this->getCsvContent($download);
        $this->assertStringContainsString('BK-FILT-001', $csv);
        $this->assertStringNotContainsString('BK-FILT-002', $csv);

        $this->actingAs($this->user);
        $response = $this->get(route('bookings.report', ['filterPaymentStatus' => 'Paid']));
        $response->assertStatus(200);
        $response->assertSee('BK-FILT-001');
        $response->assertDontSee('BK-FILT-002');

        // Unpaid filter
        $downloadUnpaid = Livewire::test('booking-list')
            ->set('filterPaymentStatus', 'Unpaid')
            ->call('exportExcel');
        $csvUnpaid = $this->getCsvContent($downloadUnpaid);
        $this->assertStringContainsString('BK-FILT-002', $csvUnpaid);
        $this->assertStringNotContainsString('BK-FILT-001', $csvUnpaid);

        $responseUnpaid = $this->get(route('bookings.report', ['filterPaymentStatus' => 'Unpaid']));
        $responseUnpaid->assertStatus(200);
        $responseUnpaid->assertSee('BK-FILT-002');
        $responseUnpaid->assertDontSee('BK-FILT-001');
    }

    public function test_export_csv_and_report_reflect_branch_and_hall_filters()
    {
        Livewire::actingAs($this->user);

        // Hall 1 filter
        $download = Livewire::test('booking-list')
            ->set('filterHall', $this->hall1->id)
            ->call('exportExcel');
        $csv = $this->getCsvContent($download);
        $this->assertStringContainsString('BK-FILT-001', $csv);
        $this->assertStringNotContainsString('BK-FILT-002', $csv);

        $this->actingAs($this->user);
        $response = $this->get(route('bookings.report', ['filterHall' => $this->hall1->id]));
        $response->assertStatus(200);
        $response->assertSee('BK-FILT-001');
        $response->assertDontSee('BK-FILT-002');

        // Branch 2 filter
        $downloadBranch = Livewire::test('booking-list')
            ->set('filterBranch', $this->branch2->id)
            ->call('exportExcel');
        $csvBranch = $this->getCsvContent($downloadBranch);
        $this->assertStringContainsString('BK-FILT-002', $csvBranch);
        $this->assertStringNotContainsString('BK-FILT-001', $csvBranch);

        $responseBranch = $this->get(route('bookings.report', ['filterBranch' => $this->branch2->id]));
        $responseBranch->assertStatus(200);
        $responseBranch->assertSee('BK-FILT-002');
        $responseBranch->assertDontSee('BK-FILT-001');
    }

    public function test_export_csv_and_report_reflect_care_of_and_created_by_filters()
    {
        Livewire::actingAs($this->user);

        // Care of filter
        $download = Livewire::test('booking-list')
            ->set('filterCareOf', 'Sheikh Usman')
            ->call('exportExcel');
        $csv = $this->getCsvContent($download);
        $this->assertStringContainsString('BK-FILT-001', $csv);
        $this->assertStringContainsString('Sheikh Usman', $csv);
        $this->assertStringNotContainsString('BK-FILT-002', $csv);

        $this->actingAs($this->user);
        $response = $this->get(route('bookings.report', ['filterCareOf' => 'Sheikh Usman']));
        $response->assertStatus(200);
        $response->assertSee('BK-FILT-001');
        $response->assertDontSee('BK-FILT-002');

        // Created by filter
        $downloadOperator = Livewire::test('booking-list')
            ->set('filterCreatedBy', $this->operator2->id)
            ->call('exportExcel');
        $csvOperator = $this->getCsvContent($downloadOperator);
        $this->assertStringContainsString('BK-FILT-002', $csvOperator);
        $this->assertStringNotContainsString('BK-FILT-001', $csvOperator);

        $responseOperator = $this->get(route('bookings.report', ['filterCreatedBy' => $this->operator2->id]));
        $responseOperator->assertStatus(200);
        $responseOperator->assertSee('BK-FILT-002');
        $responseOperator->assertDontSee('BK-FILT-001');
    }

    public function test_export_csv_and_report_reflect_per_head_rate_filter()
    {
        Livewire::actingAs($this->user);

        // Zero rated (Rent Only)
        $downloadZero = Livewire::test('booking-list')
            ->set('filterPerHead', 'zero')
            ->call('exportExcel');
        $csvZero = $this->getCsvContent($downloadZero);
        $this->assertStringContainsString('BK-FILT-002', $csvZero);
        $this->assertStringNotContainsString('BK-FILT-001', $csvZero);

        $this->actingAs($this->user);
        $responseZero = $this->get(route('bookings.report', ['filterPerHead' => 'zero']));
        $responseZero->assertStatus(200);
        $responseZero->assertSee('BK-FILT-002');
        $responseZero->assertDontSee('BK-FILT-001');

        // Specific rate 2000
        $downloadSpecific = Livewire::test('booking-list')
            ->set('filterPerHead', 'specific')
            ->set('filterPerHeadAmount', '2000')
            ->call('exportExcel');
        $csvSpecific = $this->getCsvContent($downloadSpecific);
        $this->assertStringContainsString('BK-FILT-001', $csvSpecific);
        $this->assertStringNotContainsString('BK-FILT-002', $csvSpecific);

        $responseSpecific = $this->get(route('bookings.report', [
            'filterPerHead' => 'specific',
            'filterPerHeadAmount' => '2000'
        ]));
        $responseSpecific->assertStatus(200);
        $responseSpecific->assertSee('BK-FILT-001');
        $responseSpecific->assertDontSee('BK-FILT-002');
    }

    public function test_export_csv_and_report_reflect_date_range_filter()
    {
        Livewire::actingAs($this->user);

        // Date in July 2026 (DD-MM-YYYY format in UI)
        $downloadJuly = Livewire::test('booking-list')
            ->set('filterDateStart', '01-07-2026')
            ->set('filterDateEnd', '31-07-2026')
            ->call('exportExcel');
        $csvJuly = $this->getCsvContent($downloadJuly);
        $this->assertStringContainsString('BK-FILT-001', $csvJuly);
        $this->assertStringNotContainsString('BK-FILT-002', $csvJuly);

        $this->actingAs($this->user);
        $responseJuly = $this->get(route('bookings.report', [
            'filterDateStart' => '01-07-2026',
            'filterDateEnd' => '31-07-2026'
        ]));
        $responseJuly->assertStatus(200);
        $responseJuly->assertSee('BK-FILT-001');
        $responseJuly->assertDontSee('BK-FILT-002');

        // Date in August 2026
        $downloadAugust = Livewire::test('booking-list')
            ->set('filterDateStart', '01-08-2026')
            ->set('filterDateEnd', '31-08-2026')
            ->call('exportExcel');
        $csvAugust = $this->getCsvContent($downloadAugust);
        $this->assertStringContainsString('BK-FILT-002', $csvAugust);
        $this->assertStringNotContainsString('BK-FILT-001', $csvAugust);

        $responseAugust = $this->get(route('bookings.report', [
            'filterDateStart' => '01-08-2026',
            'filterDateEnd' => '31-08-2026'
        ]));
        $responseAugust->assertStatus(200);
        $responseAugust->assertSee('BK-FILT-002');
        $responseAugust->assertDontSee('BK-FILT-001');
    }

    public function test_export_csv_and_report_reflect_multiple_combined_filters_simultaneously()
    {
        Livewire::actingAs($this->user);

        // Apply 12 filters simultaneously:
        $combinedFilters = [
            'filterStatus' => 'Confirmed',
            'filterPaymentStatus' => 'Paid',
            'filterBranch' => (string) $this->branch1->id,
            'filterHall' => (string) $this->hall1->id,
            'filterEventType' => (string) $this->eventType1->id,
            'filterGuestStatus' => 'Confirmed',
            'filterCareOf' => 'Sheikh Usman',
            'filterPerHead' => 'specific',
            'filterPerHeadAmount' => '2000',
            'filterDateStart' => '01-07-2026',
            'filterDateEnd' => '31-07-2026',
            'search' => 'Zubair',
        ];

        // 1. Export CSV with all combined filters
        $component = Livewire::test('booking-list');
        foreach ($combinedFilters as $key => $val) {
            $component->set($key, $val);
        }
        $download = $component->call('exportExcel');
        $csv = $this->getCsvContent($download);

        $this->assertStringContainsString('BK-FILT-001', $csv);
        $this->assertStringContainsString('Zubair', $csv);
        $this->assertStringContainsString('Sheikh Usman', $csv);
        $this->assertStringContainsString('Emerald Hall', $csv);
        $this->assertStringNotContainsString('BK-FILT-002', $csv);

        // 2. Print / PDF report with all combined filters
        $this->actingAs($this->user);
        $response = $this->get(route('bookings.report', $combinedFilters));
        $response->assertStatus(200);
        $response->assertSee('BK-FILT-001');
        $response->assertSee('Zubair');
        $response->assertSee('Emerald Hall');
        $response->assertDontSee('BK-FILT-002');
        $response->assertDontSee('Bilal');
    }

    public function test_export_csv_and_report_reflect_balance_status_filter()
    {
        Livewire::actingAs($this->user);

        // Fully Paid filter
        $downloadPaid = Livewire::test('booking-list')
            ->set('filterBalanceStatus', 'fully_paid')
            ->call('exportExcel');
        $csvPaid = $this->getCsvContent($downloadPaid);
        $this->assertStringContainsString('BK-FILT-001', $csvPaid);
        $this->assertStringNotContainsString('BK-FILT-002', $csvPaid);

        $this->actingAs($this->user);
        $responsePaid = $this->get(route('bookings.report', ['filterBalanceStatus' => 'fully_paid']));
        $responsePaid->assertStatus(200);
        $responsePaid->assertSee('BK-FILT-001');
        $responsePaid->assertDontSee('BK-FILT-002');

        // Outstanding filter (for past or completed events with unpaid balance)
        // Mark booking2 as completed so it qualifies as outstanding balance
        $this->booking2->update([
            'booking_status' => 'Completed',
            'booking_date' => Carbon::yesterday()->format('Y-m-d')
        ]);

        $downloadOutstanding = Livewire::test('booking-list')
            ->set('filterBalanceStatus', 'outstanding')
            ->call('exportExcel');
        $csvOutstanding = $this->getCsvContent($downloadOutstanding);
        $this->assertStringContainsString('BK-FILT-002', $csvOutstanding);
        $this->assertStringNotContainsString('BK-FILT-001', $csvOutstanding);

        $responseOutstanding = $this->get(route('bookings.report', ['filterBalanceStatus' => 'outstanding']));
        $responseOutstanding->assertStatus(200);
        $responseOutstanding->assertSee('BK-FILT-002');
        $responseOutstanding->assertDontSee('BK-FILT-001');
    }

    public function test_export_csv_and_report_reflect_quick_shortcut_filter()
    {
        Livewire::actingAs($this->user);

        // Shortcut: Confirmed
        $downloadConfirmed = Livewire::test('booking-list')
            ->set('filterQuickShortcut', 'confirmed')
            ->call('exportExcel');
        $csvConfirmed = $this->getCsvContent($downloadConfirmed);
        $this->assertStringContainsString('BK-FILT-001', $csvConfirmed);
        $this->assertStringNotContainsString('BK-FILT-002', $csvConfirmed);

        $this->actingAs($this->user);
        $responseConfirmed = $this->get(route('bookings.report', ['filterQuickShortcut' => 'confirmed']));
        $responseConfirmed->assertStatus(200);
        $responseConfirmed->assertSee('BK-FILT-001');
        $responseConfirmed->assertDontSee('BK-FILT-002');

        // Shortcut: Pending
        $downloadPending = Livewire::test('booking-list')
            ->set('filterQuickShortcut', 'pending')
            ->call('exportExcel');
        $csvPending = $this->getCsvContent($downloadPending);
        $this->assertStringContainsString('BK-FILT-002', $csvPending);
        $this->assertStringNotContainsString('BK-FILT-001', $csvPending);

        $responsePending = $this->get(route('bookings.report', ['filterQuickShortcut' => 'pending']));
        $responsePending->assertStatus(200);
        $responsePending->assertSee('BK-FILT-002');
        $responsePending->assertDontSee('BK-FILT-001');
    }

    public function test_page_totals_calculate_paid_and_balance_correctly_and_update_with_filters()
    {
        Livewire::actingAs($this->user);

        // Record a posted payment of Rs. 300,000 for booking 1
        BookingPayment::create([
            'booking_id' => $this->booking1->id,
            'payment_number' => 'PAY-TEST-001',
            'amount' => 300000.00,
            'payment_type' => 'advance',
            'payment_method' => 'Cash',
            'payment_date' => '2026-07-01',
            'status' => 'posted',
            'recorded_by' => $this->user->id,
            'posted_by' => $this->user->id,
            'posted_at' => now(),
        ]);

        // Unfiltered:
        // Booking 1: 500,000, Paid: 300,000, Bal: 200,000
        // Booking 2: 150,000, Paid: 0, Bal: 150,000
        // Aggregate: Total: 650,000, Paid: 300,000, Bal: 350,000 (650,000 - 300,000)
        Livewire::test('booking-list')
            ->assertSee('Rs. 650,000')
            ->assertSee('Paid: Rs. 300,000')
            ->assertSee('Bal: Rs. 350,000')
            // Apply Branch 1 filter: only Booking 1 is active
            ->set('filterBranch', $this->branch1->id)
            ->assertSee('Rs. 500,000')
            ->assertSee('Paid: Rs. 300,000')
            ->assertSee('Bal: Rs. 200,000')
            // Apply Branch 2 filter: only Booking 2 is active
            ->set('filterBranch', $this->branch2->id)
            ->assertSee('Rs. 150,000')
            ->assertSee('Paid: Rs. 0')
            ->assertSee('Bal: Rs. 150,000')
            // Reset branch and apply status filter 'Draft'
            ->set('filterBranch', '')
            ->set('filterStatus', 'Draft')
            ->assertSee('Rs. 150,000')
            ->assertSee('Paid: Rs. 0')
            ->assertSee('Bal: Rs. 150,000');
    }
}

