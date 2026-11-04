<?php

namespace Tests\Feature;

use App\Livewire\VendorLedgerView;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Package;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Slot;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorLedgerUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected Marquee $marquee;
    protected Branch $branch;
    protected User $user;
    protected Customer $customer;
    protected Booking $booking;
    protected Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-plan',
            'price' => 25000,
            'billing_interval' => 'month',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Imperial Palace Marquee',
            'slug' => 'imperial-palace',
            'is_active' => true,
            'status' => 'active',
            'email' => 'contact@imperial.pk',
            'phone' => '03001234567',
            'address' => 'Main Boulevard',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'subscription_plan_id' => $plan->id,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Royal Hall',
            'code' => 'BR-01',
            'address' => 'Lahore',
            'phone' => '042-3571234',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'status' => 'active',
        ]);

        $role = Role::create([
            'name' => 'super_admin',
            'label' => 'Super Administrator',
            'marquee_id' => $this->marquee->id,
        ]);

        $this->user = User::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Test Super Admin',
            'email' => 'admin@imperial.pk',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_super_admin' => true,
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'first_name' => 'Malik',
            'last_name' => 'Usman',
            'phone_number' => '03211234567',
            'email' => 'usman@example.com',
            'city' => 'Lahore',
            'province' => 'Punjab',
        ]);

        $eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Wedding Reception',
            'event_type_code' => 'EVT-01',
            'status' => 'active',
        ]);

        $hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Crystal Ballroom',
            'hall_code' => 'HALL-01',
            'hall_type' => 'Banquet Hall',
            'default_booking_price' => 50000,
            'capacity' => 400,
            'status' => 'active',
        ]);

        $slot = Slot::create([
            'marquee_id' => $this->marquee->id,
            'slot_name' => 'Evening Slot',
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
        ]);

        $this->booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $eventType->id,
            'hall_id' => $hall->id,
            'slot_id' => $slot->id,
            'booking_number' => 'BK-2026-9999',
            'booking_date' => date('Y-m-d'),
            'start_time' => date('Y-m-d 19:00:00'),
            'end_time' => date('Y-m-d 23:00:00'),
            'guest_count' => 200,
            'tentative_guests' => 200,
            'per_plate_price' => 2500,
            'package_amount' => 500000,
            'hall_charges' => 50000,
            'subtotal' => 550000,
            'total_amount' => 550000,
            'status' => 'Confirmed',
            'created_by' => $this->user->id,
        ]);

        $this->vendor = Vendor::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Elite Floral & Decor',
            'vendor_code' => 'VEN-0099',
            'vendor_type' => 'Decoration',
            'phone' => '03009998877',
            'status' => 'active',
            'opening_balance' => 0,
        ]);

        // Create 20 ledger records to test pagination
        for ($i = 1; $i <= 20; $i++) {
            VendorLedger::create([
                'marquee_id' => $this->marquee->id,
                'branch_id' => $this->branch->id,
                'vendor_id' => $this->vendor->id,
                'booking_id' => $this->booking->id,
                'transaction_date' => date('Y-m-d', strtotime("-{$i} days")),
                'reference_number' => 'REF-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'transaction_type' => 'sale_credit',
                'description' => "Event stage decor setup #{$i}",
                'sale_amount' => 50000,
                'commission_amount' => 5000,
                'payment_amount' => 0,
                'running_balance' => 45000 * $i,
            ]);
        }
    }

    public function test_vendor_ledger_displays_customer_name_alongside_booking_number(): void
    {
        $this->actingAs($this->user);

        Livewire::test(VendorLedgerView::class, ['vendor' => $this->vendor])
            ->assertStatus(200)
            ->assertSee('BK-2026-9999')
            ->assertSee('Malik Usman') // Customer Name
            ->assertSee('Export CSV')
            ->assertSee('Print Ledger');
    }

    public function test_vendor_ledger_supports_pagination_and_per_page_controls(): void
    {
        $this->actingAs($this->user);

        $test = Livewire::test(VendorLedgerView::class, ['vendor' => $this->vendor]);

        // Default 15 per page -> 20 total records means page 1 has 15 items and pagination exists
        $test->assertStatus(200);
        $test->assertSee('Showing <strong>1</strong> to <strong>15</strong> of <strong>20</strong>', false);
        $test->assertSee('class="pagination"', false);

        // Change perPage to 25 -> all 20 records on page 1
        $test->set('perPage', 25);
        $test->assertSee('Showing <strong>1</strong> to <strong>20</strong> of <strong>20</strong>', false);
    }

    public function test_vendor_ledger_export_csv_streams_correct_data(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(VendorLedgerView::class, ['vendor' => $this->vendor]);

        $response = $component->instance()->exportCsv();
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());

        // Capture streamed content
        ob_start();
        $response->sendContent();
        $csvContent = ob_get_clean();

        $this->assertStringContainsString('SERVICE PROVIDER FINANCIAL LEDGER STATEMENT', $csvContent);
        $this->assertStringContainsString('Elite Floral & Decor', $csvContent);
        $this->assertStringContainsString('Customer Name', $csvContent);
        $this->assertStringContainsString('BK-2026-9999', $csvContent);
        $this->assertStringContainsString('Malik Usman', $csvContent);
    }

    public function test_vendor_ledger_accurate_print_letterhead_and_totals(): void
    {
        $this->actingAs($this->user);

        Livewire::test(VendorLedgerView::class, ['vendor' => $this->vendor])
            ->assertStatus(200)
            ->assertSee('@media print', false)
            ->assertSee('print-letterhead', false)
            ->assertSee($this->marquee->name)
            ->assertSee($this->marquee->address)
            ->assertSee($this->marquee->phone)
            ->assertSee('Service Provider Financial Ledger Statement')
            ->assertSee('Elite Floral & Decor')
            ->assertSee('TOTALS / CLOSING:')
            ->assertSee('Rs. ' . number_format(50000))
            ->assertSee('Rs. ' . number_format(5000))
            ->assertSee('Rs. ' . number_format(45000));
    }
}
