<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingFinalBill;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\FbrPosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FbrPosSyncTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $marquee;
    protected $branch;
    protected $hall;
    protected $eventType;
    protected $customer;
    protected $booking;
    protected $finalBill;
    protected $fbrService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fbrService = new FbrPosService();

        // Seed roles & plans
        $this->artisan('db:seed', ['--class' => 'SubscriptionPlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $plan = SubscriptionPlan::first();

        $this->marquee = Marquee::create([
            'name' => 'FBR Test Marquee',
            'email' => 'fbr@marquee.com',
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
            'fbr_pos_id' => 'PRA-LHR-GUL-01',
            'fbr_pos_key' => 'secret_fbr_key',
            'fbr_sandbox_mode' => true,
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
            'booking_status' => 'Confirmed',
        ]);

        $this->finalBill = BookingFinalBill::create([
            'booking_id' => $this->booking->id,
            'guest_count' => 300,
            'per_plate_price' => 1500.00,
            'package_amount' => 450000.00,
            'hall_charges' => 0.00,
            'extra_charges' => 0.00,
            'discount_amount' => 0.00,
            'tax_amount' => 72000.00, // 16% PRA Tax
            'subtotal' => 450000.00,
            'grand_total' => 522000.00,
            'notes' => 'Test Final Bill',
        ]);
    }

    /** @test */
    public function test_fbr_sync_success_response()
    {
        $this->actingAs($this->user);

        // Fake successful FBR endpoint response (ResponseCode 100)
        Http::fake([
            'https://sandbox.fbr.gov.pk/*' => Http::response([
                'ResponseCode' => 100,
                'FBRInvoiceNumber' => 'FBR-987654321',
                'USIN' => 'USIN-TEST-98765',
                'ResponseMessage' => 'OK',
            ], 200)
        ]);

        $result = $this->fbrService->syncFinalBill($this->finalBill);

        $this->assertTrue($result['success']);
        $this->assertEquals('FBR-987654321', $result['fbr_invoice_number']);

        $this->finalBill->refresh();
        $this->assertEquals('synced', $this->finalBill->fbr_sync_status);
        $this->assertEquals('FBR-987654321', $this->finalBill->fbr_invoice_number);
        $this->assertEquals('USIN-TEST-98765', $this->finalBill->usin);
        $this->assertNotNull($this->finalBill->fbr_sync_time);
        $this->assertStringContainsString('https://verification.fbr.gov.pk', $this->finalBill->qr_code);
    }

    /** @test */
    public function test_fbr_sync_error_response()
    {
        $this->actingAs($this->user);

        // Fake invalid/failure FBR response
        Http::fake([
            'https://sandbox.fbr.gov.pk/*' => Http::response([
                'ResponseCode' => 101,
                'ResponseMessage' => 'Invalid POS Registration Key or ID.',
            ], 200)
        ]);

        $result = $this->fbrService->syncFinalBill($this->finalBill);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid POS Registration Key or ID.', $result['message']);

        $this->finalBill->refresh();
        $this->assertEquals('failed', $this->finalBill->fbr_sync_status);
        $this->assertEquals('Invalid POS Registration Key or ID.', $this->finalBill->fbr_response_message);
    }
}
