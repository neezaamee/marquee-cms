<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingFinalBill;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\CashBankAccount;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\Slot;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalBillInvoiceV2Test extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Marquee $marquee;
    protected Branch $branch;
    protected Hall $hall;
    protected Customer $customer;
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles
        $ownerRole = Role::create(['name' => 'owner', 'label' => 'Marquee Owner']);

        // 2. Subscription Plan & Marquee Tenant
        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-plan',
            'price' => 1000,
            'billing_interval' => 'monthly',
            'max_branches' => 5,
            'status' => 'active',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Royal Pearl Marquee',
            'slug' => 'royal-pearl-marquee',
            'status' => 'active',
            'address' => '12 Main Gulberg',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'email' => 'contact@royalpearl.test',
            'subscription_plan_id' => $plan->id,
            'is_setup_completed' => true,
        ]);

        $this->owner = User::create([
            'name' => 'Owner Ahmad',
            'email' => 'ahmad@royalpearl.test',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marquee->id,
        ]);

        // 3. Branch, Hall, Slot, EventType, Customer
        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Executive Branch',
            'code' => 'EX-01',
            'address' => '12 Main Gulberg',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'status' => 'active',
        ]);

        $this->hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Grand Pearl Hall',
            'hall_code' => 'HALL-01',
            'hall_type' => 'Indoor',
            'default_booking_price' => 50000,
            'capacity' => 500,
            'status' => 'active',
        ]);

        $slot = Slot::create([
            'marquee_id' => $this->marquee->id,
            'slot_name' => 'Night Shift',
            'slot_code' => 'SLOT-NIGHT',
            'start_time' => '19:00:00',
            'end_time' => '22:00:00',
            'status' => 'active',
        ]);

        $eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Wedding (Baraat)',
            'event_type_code' => 'ET-BARAAT',
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_code' => 'CUST-99',
            'first_name' => 'Muhammad',
            'last_name' => 'Tariq',
            'phone_number' => '03009988776',
            'email' => 'tariq@customer.test',
            'address' => 'Model Town Lahore',
            'status' => 'active',
        ]);

        // 4. Booking
        $this->booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_id' => $this->hall->id,
            'slot_id' => $slot->id,
            'event_type_id' => $eventType->id,
            'customer_id' => $this->customer->id,
            'booking_number' => 'BK-2026-9901',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '19:00:00',
            'end_time' => '22:00:00',
            'tentative_guests' => 450,
            'confirmed_guests' => 450,
            'guest_status' => 'Confirmed',
            'guest_count' => 450,
            'per_plate_price' => 1500,
            'subtotal' => 675000,
            'grand_total' => 700000,
            'security_deposit' => 25000,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Pending',
        ]);
    }

    public function test_final_bill_invoice_v2_route_renders_successfully_for_authorized_owner_without_final_bill()
    {
        $response = $this->actingAs($this->owner)
            ->get(route('bookings.final-bill-v2', $this->booking->id));

        $response->assertStatus(200);
        $response->assertSee('Sale Tax Invoice');
        $response->assertSee('BK-2026-9901');
        $response->assertSee('Muhammad Tariq');
        $response->assertSee('Grand Pearl Hall');
        $response->assertSee('printPageSizeModal');
        $response->assertSee('Print Invoice');
    }

    public function test_final_bill_invoice_v2_renders_with_actual_event_final_bill_and_payments()
    {
        // 1. Create Final Bill
        BookingFinalBill::create([
            'booking_id' => $this->booking->id,
            'guest_count' => 500,
            'per_plate_price' => 1600,
            'package_amount' => 800000,
            'hall_charges' => 50000,
            'extra_charges' => 20000,
            'discount_amount' => 10000,
            'tax_amount' => 15000,
            'subtotal' => 870000,
            'grand_total' => 875000,
            'fbr_invoice_number' => 'FBR-882201-PK',
            'fbr_sync_status' => 'synced',
            'usin' => 'USIN-77665544',
            'created_by' => $this->owner->id,
        ]);

        // 2. Add an Advance Payment
        BookingPayment::create([
            'payment_number' => 'BP-2026-001',
            'booking_id' => $this->booking->id,
            'amount' => 300000,
            'status' => 'posted',
            'payment_date' => now()->subDays(2)->format('Y-m-d'),
            'payment_method' => 'Bank Transfer',
            'payment_type' => 'advance',
            'transaction_reference' => 'TXN-MEEZAN-991',
            'recorded_by' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('bookings.final-bill-v2', $this->booking->id));

        $response->assertStatus(200);
        $response->assertSee('FBR Digital Invoicing System');
        $response->assertSee('500 pax', false);
        $response->assertSee('FBR-882201-PK');
        $response->assertSee('875,000.00'); // Grand total
        $response->assertSee('300,000.00'); // Advance paid
        $response->assertSee('575,000.00'); // Net balance due (875,000 - 300,000)
    }

    public function test_final_bill_invoice_v2_displays_configured_bank_accounts()
    {
        $accType = \App\Models\AccountType::create([
            'name' => 'Bank Account',
            'code' => 'BANK',
            'nature' => 'Asset',
        ]);

        $acc = \App\Models\Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => 'ACC-MEEZAN',
            'name' => 'Meezan Bank Operations',
            'account_type_id' => $accType->id,
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        CashBankAccount::create([
            'marquee_id' => $this->marquee->id,
            'account_id' => $acc->id,
            'type' => 'bank',
            'bank_name' => 'Meezan Bank Limited',
            'account_number' => '02010108877665',
            'iban' => 'PK64MEZN0002010108877665',
            'branch_name' => 'Gulberg III Branch Lahore',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('bookings.final-bill-v2', $this->booking->id));

        $response->assertStatus(200);
        $response->assertSee('Meezan Bank Limited');
        $response->assertSee('PK64MEZN0002010108877665');
        $response->assertSee('02010108877665');
    }

    public function test_guest_cannot_access_final_bill_v2()
    {
        $response = $this->get(route('bookings.final-bill-v2', $this->booking->id));
        $response->assertRedirect('/login');
    }

    public function test_other_tenant_cannot_access_booking_final_bill_v2()
    {
        $otherMarquee = Marquee::create([
            'name' => 'Other Marquee',
            'slug' => 'other-marquee',
            'address' => '456 Other Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03009999999',
            'email' => 'other@marquee.test',
            'status' => 'active',
        ]);

        $ownerRole = Role::where('name', 'owner')->first();

        $otherUser = User::create([
            'name' => 'Other Tenant User',
            'email' => 'other@tenant.test',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole->id,
            'marquee_id' => $otherMarquee->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->get(route('bookings.final-bill-v2', $this->booking->id));

        // BelongsToTenant global scope or 403 check
        $this->assertTrue(in_array($response->getStatusCode(), [403, 404]));
    }

    public function test_booking_view_contains_final_bill_v2_links()
    {
        $response = $this->actingAs($this->owner)
            ->get(route('bookings.show', $this->booking->id));

        $response->assertStatus(200);
        $response->assertSee(route('bookings.final-bill-v2', $this->booking->id));
        $response->assertSee('Print Final Bill Invoice (V2)');
    }
}
