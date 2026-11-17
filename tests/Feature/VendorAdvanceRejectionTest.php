<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Package;
use App\Models\Role;
use App\Models\Slot;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorSale;
use App\Models\VendorService;
use App\Services\BookingFinancialService;
use App\Services\VendorCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorAdvanceRejectionTest extends TestCase
{
    use RefreshDatabase;

    protected Marquee $marquee;
    protected Branch $branch;
    protected User $user;
    protected Customer $customer;
    protected Booking $booking;
    protected Vendor $vendor;
    protected VendorService $vendorService;
    protected Account $cashAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->marquee = Marquee::create([
            'name' => 'Royal Banquet',
            'slug' => 'royal-banquet',
            'is_active' => true,
            'status' => 'active',
            'email' => 'info@royalbanquet.com',
            'phone' => '03001234567',
            'address' => 'Mall Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Branch',
            'code' => 'BR-01',
            'address' => 'Mall Road',
            'phone' => '042-3571234',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'status' => 'active',
            'is_head_office' => true,
        ]);

        $role = Role::firstOrCreate(
            ['name' => 'owner'],
            ['display_name' => 'Owner', 'label' => 'Owner', 'marquee_id' => $this->marquee->id, 'description' => 'Owner']
        );

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@royalbanquet.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'phone_number' => '03211112233',
            'email' => 'ali@example.com',
        ]);

        $eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Wedding',
            'event_type_code' => 'EVT-WED',
            'status' => 'active',
        ]);

        $hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Grand Hall',
            'hall_code' => 'HALL-01',
            'capacity' => 400,
            'hall_type' => 'Banquet Hall',
            'default_booking_price' => 50000,
            'status' => 'active',
        ]);

        $slot = Slot::create([
            'marquee_id' => $this->marquee->id,
            'slot_name' => 'Evening Slot',
            'slot_code' => 'SLOT-EVE',
            'start_time' => '18:00:00',
            'end_time' => '23:00:00',
        ]);

        $package = Package::create([
            'marquee_id' => $this->marquee->id,
            'package_name' => 'Gold Package',
            'package_code' => 'PKG-GOLD',
            'per_plate_price' => 2000,
            'status' => 'active',
        ]);

        $this->booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'event_type_id' => $eventType->id,
            'hall_id' => $hall->id,
            'slot_id' => $slot->id,
            'package_id' => $package->id,
            'booking_date' => '2026-11-20',
            'start_time' => '2026-11-20 18:00:00',
            'end_time' => '2026-11-20 23:00:00',
            'guest_count' => 100,
            'tentative_guests' => 100,
            'per_plate_price' => 2000,
            'package_amount' => 200000,
            'hall_charges' => 50000,
            'extra_charges' => 0,
            'discount_amount' => 0,
            'security_deposit' => 10000,
            'tax_amount' => 0,
            'subtotal' => 250000,
            'grand_total' => 260000,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Unpaid',
            'created_by' => $this->user->id,
        ]);

        $this->vendor = Vendor::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'name' => 'Royal Sound & Lights',
            'vendor_code' => 'VEND-001',
            'vendor_type' => 'DJ / Sound System',
            'contact_person' => 'Hamza',
            'phone' => '03009998877',
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $this->vendorService = VendorService::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'vendor_id' => $this->vendor->id,
            'service_name' => 'Concert Audio Rig',
            'unit' => 'Event',
            'default_sale_price' => 20000,
            'default_cost_price' => 16000,
            'status' => 'active',
        ]);

        $assetType = AccountType::firstOrCreate(
            ['code' => 'CURRENT_ASSETS'],
            ['name' => 'Current Assets', 'nature' => 'Asset']
        );

        $this->cashAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_type_id' => $assetType->id,
            'account_code' => '1001',
            'name' => 'Cash in Hand',
            'nature' => 'Asset',
            'level' => 3,
            'is_active' => true,
        ]);
    }

    public function test_rejecting_vendor_sale_customer_advance_payment_recalculates_paid_and_remaining()
    {
        $vendorService = app(VendorCommissionService::class);
        $financialService = app(BookingFinancialService::class);

        // 1. Create vendor sale: Rs. 20,000 total charge, Rs. 15,000 customer advance
        $sale = $vendorService->createVendorSale([
            'vendor_id' => $this->vendor->id,
            'vendor_service_id' => $this->vendorService->id,
            'booking_id' => $this->booking->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'event_date' => '2026-11-20',
            'sale_date' => '2026-11-17',
            'sale_amount' => 20000,
            'customer_advance_amount' => 15000,
            'customer_payment_method' => 'Cash',
            'include_in_invoice' => true,
        ]);

        $sale->refresh();
        $this->assertEquals(15000.00, (float) $sale->customer_paid_amount);
        $this->assertEquals(15000.00, (float) $sale->customer_paid);
        $this->assertEquals(5000.00, (float) $sale->customer_remaining_amount);
        $this->assertEquals(5000.00, (float) $sale->customer_remaining);

        // 2. Record 1 rupee more as customer advance installment
        $oneRupeePayment = $vendorService->recordCustomerSalePayment($sale, 1.00, [
            'payment_date' => '2026-11-17',
            'payment_method' => 'Cash',
            'notes' => '1 rupee extra advance',
        ]);

        $sale->refresh();
        $this->assertEquals(15001.00, (float) $sale->customer_paid_amount);
        $this->assertEquals(15001.00, (float) $sale->customer_paid);
        $this->assertEquals(4999.00, (float) $sale->customer_remaining_amount);
        $this->assertEquals(4999.00, (float) $sale->customer_remaining);

        // 3. Reject the 1 rupee payment
        $financialService->rejectPayment($oneRupeePayment, 'Mistake - 1 rupee added erroneously', $this->user->id);

        $oneRupeePayment->refresh();
        $this->assertEquals('rejected', $oneRupeePayment->status);

        // 4. Verify VendorSale customer_paid_amount decrements back to 15,000 and customer_remaining reverts to 5,000
        $sale->refresh();
        $this->assertEquals(15000.00, (float) $sale->customer_paid_amount);
        $this->assertEquals(15000.00, (float) $sale->customer_paid);
        $this->assertEquals(5000.00, (float) $sale->customer_remaining_amount);
        $this->assertEquals(5000.00, (float) $sale->customer_remaining);
    }

    public function test_reversing_posted_vendor_sale_payment_recalculates_paid_and_remaining()
    {
        $vendorService = app(VendorCommissionService::class);
        $financialService = app(BookingFinancialService::class);

        // Create vendor sale with 15,000 advance
        $sale = $vendorService->createVendorSale([
            'vendor_id' => $this->vendor->id,
            'vendor_service_id' => $this->vendorService->id,
            'booking_id' => $this->booking->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'event_date' => '2026-11-20',
            'sale_date' => '2026-11-17',
            'sale_amount' => 20000,
            'customer_advance_amount' => 15000,
            'customer_payment_method' => 'Cash',
            'include_in_invoice' => true,
        ]);

        // Post the initial 15,000 payment
        $initialPayment = BookingPayment::where('vendor_sale_id', $sale->id)->firstOrFail();
        $financialService->postPayment($initialPayment, [
            'account_id' => $this->cashAccount->id,
            'posting_date' => '2026-11-17',
            'posted_by' => $this->user->id,
        ]);

        $sale->refresh();
        $this->assertEquals(15000.00, (float) $sale->customer_paid);
        $this->assertEquals(5000.00, (float) $sale->customer_remaining);

        // Reverse the posted payment
        $financialService->reversePayment($initialPayment, 'Customer cancelled sound package', $this->user->id);

        $initialPayment->refresh();
        $this->assertEquals('reversed', $initialPayment->status);

        // Customer paid should now be 0.00, customer remaining should be 20,000.00
        $sale->refresh();
        $this->assertEquals(0.00, (float) $sale->customer_paid_amount);
        $this->assertEquals(0.00, (float) $sale->customer_paid);
        $this->assertEquals(20000.00, (float) $sale->customer_remaining_amount);
        $this->assertEquals(20000.00, (float) $sale->customer_remaining);
    }
}
