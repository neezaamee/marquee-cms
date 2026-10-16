<?php

namespace Tests\Feature;

use App\Livewire\BookingView;
use App\Models\Booking;
use App\Models\BookingFinalBill;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\Slot;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\BookingFinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PrepareFinalBillTaxTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Marquee $marquee;
    protected Branch $branch;
    protected Hall $hall;
    protected Customer $customer;
    protected EventType $eventType;
    protected Slot $slot;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::create(['name' => 'owner', 'label' => 'Marquee Owner']);

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

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Gulberg Branch',
            'code' => 'GB-01',
            'address' => '12 Main Gulberg',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'status' => 'active',
            'tax_rate' => 15.00, // Branch has default 15% tax
        ]);

        $this->hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Grand Crystal Hall',
            'hall_code' => 'HALL-01',
            'hall_type' => 'Indoor',
            'default_booking_price' => 50000.00,
            'capacity' => 500,
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_code' => 'CUST-0001',
            'first_name' => 'Haji Muhammad',
            'last_name' => 'Usman',
            'phone_number' => '03211112233',
            'email' => 'usman@test.com',
            'address' => 'Gulberg Lahore',
            'status' => 'active',
        ]);

        $this->eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Barat',
            'event_type_code' => 'ET-BARAT',
            'status' => 'active',
        ]);

        $this->slot = Slot::create([
            'marquee_id' => $this->marquee->id,
            'slot_name' => 'Evening',
            'slot_code' => 'SL-EVE',
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'status' => 'active',
        ]);

        $this->owner = User::create([
            'name' => 'Sheikh Owner',
            'email' => 'owner@royalpearl.test',
            'password' => bcrypt('password123'),
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
            'is_owner' => true,
        ]);
    }

    public function test_zero_tax_booking_retains_zero_tax_when_commercial_totals_are_synced(): void
    {
        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hall->id,
            'slot_id' => $this->slot->id,
            'booking_number' => 'BK-TEST-001',
            'booking_date' => now()->toDateString(),
            'start_time' => now()->setTime(19, 0),
            'end_time' => now()->setTime(23, 0),
            'guest_count' => 100,
            'per_plate_price' => 1000.00,
            'package_amount' => 100000.00,
            'hall_charges' => 20000.00,
            'extra_charges' => 0.00,
            'discount_amount' => 0.00,
            'security_deposit' => 0.00,
            'tax_rate' => 0.00,
            'tax_amount' => 0.00,
            'subtotal' => 120000.00,
            'grand_total' => 120000.00,
            'booking_status' => 'Confirmed',
            'created_by' => $this->owner->id,
        ]);

        $financialService = app(BookingFinancialService::class);
        $financialService->syncBookingCommercialTotals($booking);

        $booking->refresh();
        $this->assertEquals(0.00, (float) $booking->tax_amount);
        $this->assertEquals(0.00, (float) $booking->tax_rate);
        $this->assertEquals(120000.00, (float) $booking->grand_total);
    }

    public function test_prepare_final_bill_modal_defaults_to_unchecked_tax_for_zero_tax_booking(): void
    {
        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hall->id,
            'slot_id' => $this->slot->id,
            'booking_number' => 'BK-TEST-002',
            'booking_date' => now()->toDateString(),
            'start_time' => now()->setTime(19, 0),
            'end_time' => now()->setTime(23, 0),
            'guest_count' => 100,
            'per_plate_price' => 1000.00,
            'package_amount' => 100000.00,
            'hall_charges' => 20000.00,
            'extra_charges' => 0.00,
            'discount_amount' => 0.00,
            'security_deposit' => 0.00,
            'tax_rate' => 0.00,
            'tax_amount' => 0.00,
            'subtotal' => 120000.00,
            'grand_total' => 120000.00,
            'booking_status' => 'Confirmed',
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner);

        Livewire::test(BookingView::class, ['booking' => $booking])
            ->call('openFinalBillModal')
            ->assertSet('fbApplyTax', false)
            ->assertSet('fbTaxAmount', 0.00)
            ->set('newAddonName', 'Extra Projector')
            ->set('newAddonPrice', 5000.00)
            ->set('newAddonQty', 1)
            ->call('addFbAddon')
            ->assertSet('fbApplyTax', false)
            ->assertSet('fbTaxAmount', 0.00) // Tax stays 0.00 even after adding addon
            ->assertSet('fbExtraCharges', 5000.00);
    }

    public function test_prepare_final_bill_allows_toggling_tax_checkbox_and_saving(): void
    {
        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $this->eventType->id,
            'hall_id' => $this->hall->id,
            'slot_id' => $this->slot->id,
            'booking_number' => 'BK-TEST-003',
            'booking_date' => now()->toDateString(),
            'start_time' => now()->setTime(19, 0),
            'end_time' => now()->setTime(23, 0),
            'guest_count' => 100,
            'per_plate_price' => 1000.00,
            'package_amount' => 100000.00,
            'hall_charges' => 20000.00,
            'extra_charges' => 0.00,
            'discount_amount' => 0.00,
            'security_deposit' => 0.00,
            'tax_rate' => 0.00,
            'tax_amount' => 0.00,
            'subtotal' => 120000.00,
            'grand_total' => 120000.00,
            'booking_status' => 'Confirmed',
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner);

        // 1. Save final bill without tax (checkbox unchecked)
        Livewire::test(BookingView::class, ['booking' => $booking])
            ->call('openFinalBillModal')
            ->assertSet('fbApplyTax', false)
            ->assertSet('fbTaxAmount', 0.00)
            ->call('saveFinalBill');

        $booking->refresh();
        $finalBill = $booking->finalBill;
        $this->assertNotNull($finalBill);
        $this->assertEquals(0.00, (float) $finalBill->tax_amount);
        $this->assertEquals(0.00, (float) $finalBill->tax_rate);
        $this->assertEquals(0.00, (float) $booking->tax_amount);
        $this->assertEquals(0.00, (float) $booking->tax_rate);

        // 2. Open modal again, toggle tax checkbox to ON, and save
        Livewire::test(BookingView::class, ['booking' => $booking])
            ->call('openFinalBillModal')
            ->assertSet('fbApplyTax', false)
            ->set('fbApplyTax', true)
            ->assertSet('fbTaxRate', 15.00)
            ->assertSet('fbTaxAmount', 18000.00) // 15% of 120,000 = 18,000
            ->call('saveFinalBill');

        $booking->refresh();
        $finalBill = $booking->fresh()->finalBill;
        $this->assertEquals(18000.00, (float) $finalBill->tax_amount);
        $this->assertEquals(15.00, (float) $finalBill->tax_rate);
        $this->assertEquals(18000.00, (float) $booking->tax_amount);
        $this->assertEquals(15.00, (float) $booking->tax_rate);
        $this->assertEquals(138000.00, (float) $booking->grand_total);
    }
}
