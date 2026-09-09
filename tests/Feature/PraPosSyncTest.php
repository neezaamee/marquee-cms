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

class PraPosSyncTest extends TestCase
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
    protected $posService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->posService = new FbrPosService();

        // Seed roles & plans
        $this->artisan('db:seed', ['--class' => 'SubscriptionPlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $plan = SubscriptionPlan::first();

        $this->marquee = Marquee::create([
            'name' => 'PRA Test Marquee',
            'email' => 'pra@marquee.com',
            'phone' => '03001234567',
            'address' => 'Gulberg, Lahore',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'tax_authority' => 'PRA',
            'ntn' => '1234567-8',
            'status' => 'active',
            'subscription_plan_id' => $plan->id,
        ]);

        $ownerRole = Role::where('name', 'owner')->first();

        $this->user = User::create([
            'name' => 'Marquee Owner',
            'email' => 'owner@pramarquee.com',
            'username' => 'praowner',
            'password' => bcrypt('Password123!'),
            'marquee_id' => $this->marquee->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Lahore Main Branch',
            'address' => 'Main Boulevard, Gulberg III',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '042-35876543',
            'fbr_pos_id' => '822269',
            'fbr_pos_key' => '7408D42B',
            'fbr_sandbox_mode' => true,
            'pos_connection_type' => 'cloud',
            'status' => 'active',
        ]);

        $this->hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Imperial Hall',
            'hall_code' => 'IMP-01',
            'capacity' => 500,
            'hall_type' => 'Banquet',
            'default_booking_price' => 80000.00,
            'status' => 'active',
        ]);

        $this->eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'event_type_name' => 'Barat',
            'event_type_code' => 'BRT',
            'status' => 'active',
            'is_system_default' => false,
        ]);

        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_code' => 'CUST-00201',
            'customer_type' => 'Individual',
            'first_name' => 'Usman',
            'last_name' => 'Ali',
            'phone_number' => '03001234567',
            'email' => 'usman@test.com',
            'status' => 'Active',
        ]);

        $this->booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'customer_id' => $this->customer->id,
            'hall_id' => $this->hall->id,
            'event_type_id' => $this->eventType->id,
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => now()->addDays(5)->format('Y-m-d') . ' 18:00:00',
            'end_time' => now()->addDays(5)->format('Y-m-d') . ' 23:00:00',
            'guest_count' => 300,
            'grand_total' => 678000.00,
            'booking_status' => 'Confirmed',
        ]);

        $this->finalBill = BookingFinalBill::create([
            'booking_id' => $this->booking->id,
            'guest_count' => 300,
            'per_plate_price' => 2000.00,
            'package_amount' => 600000.00,
            'hall_charges' => 0.00,
            'extra_charges' => 0.00,
            'discount_amount' => 0.00,
            'tax_amount' => 78000.00,
            'subtotal' => 600000.00,
            'grand_total' => 678000.00,
            'fbr_sync_status' => 'pending',
            'notes' => 'PRA Test Bill',
        ]);
    }

    /** @test */
    public function test_pra_cloud_web_api_sync_success()
    {
        $this->actingAs($this->user);

        Http::fake([
            'https://ims.pral.com.pk/ims/sandbox/api/Live/PostData' => Http::response([
                'Code' => '100',
                'Response' => 'Data Stored Successfully',
                'InvoiceNumber' => '822269FIAU28346319*test*',
            ], 200),
        ]);

        $result = $this->posService->syncFinalBill($this->finalBill);

        $this->assertTrue($result['success']);
        $this->assertEquals('822269FIAU28346319*test*', $result['fbr_invoice_number']);

        $this->finalBill->refresh();
        $this->assertEquals('synced', $this->finalBill->fbr_sync_status);
        $this->assertEquals('822269FIAU28346319*test*', $this->finalBill->fbr_invoice_number);
        $this->assertNotNull($this->finalBill->fbr_sync_time);
        $this->assertEquals(
            '822269FIAU28346319*test*',
            $this->finalBill->qr_code
        );

        // Verify request payload had correct PCT code and POSID
        Http::assertSent(function ($request) {
            $data = $request->data();
            return $request->url() === 'https://ims.pral.com.pk/ims/sandbox/api/Live/PostData' &&
                   $data['POSID'] === 822269 &&
                   $data['Items'][0]['PCTCode'] === '99010000' &&
                   $request->hasHeader('Authorization', 'Bearer ' . FbrPosService::PRA_SANDBOX_TOKEN);
        });
    }

    /** @test */
    public function test_pra_local_agent_mode()
    {
        $this->actingAs($this->user);

        // Update branch to local fiscal agent mode
        $this->branch->update(['pos_connection_type' => 'local']);

        Http::fake([
            'http://localhost:8524/api/IMSFiscal/GetInvoiceNumberByModel' => Http::response([
                'Code' => '100',
                'Response' => 'Success from local agent',
                'InvoiceNumber' => '822269LOCAL9999',
            ], 200),
        ]);

        $result = $this->posService->syncFinalBill($this->finalBill);

        $this->assertTrue($result['success']);
        $this->assertEquals('822269LOCAL9999', $result['fbr_invoice_number']);

        $this->finalBill->refresh();
        $this->assertEquals('synced', $this->finalBill->fbr_sync_status);
        $this->assertEquals('822269LOCAL9999', $this->finalBill->fbr_invoice_number);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://localhost:8524/api/IMSFiscal/GetInvoiceNumberByModel';
        });
    }

    /** @test */
    public function test_pra_sync_error_response()
    {
        $this->actingAs($this->user);

        Http::fake([
            'https://ims.pral.com.pk/ims/sandbox/api/Live/PostData' => Http::response([
                'Code' => '101',
                'Response' => 'POSID is invalid or not registered in PRA database',
            ], 200),
        ]);

        $result = $this->posService->syncFinalBill($this->finalBill);

        $this->assertFalse($result['success']);
        $this->assertEquals('POSID is invalid or not registered in PRA database', $result['message']);

        $this->finalBill->refresh();
        $this->assertEquals('failed', $this->finalBill->fbr_sync_status);
        $this->assertEquals('POSID is invalid or not registered in PRA database', $this->finalBill->fbr_response_message);
    }

    /** @test */
    public function test_pra_connection_network_failure()
    {
        $this->actingAs($this->user);

        Http::fake([
            'https://ims.pral.com.pk/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out after 10000 milliseconds');
            },
        ]);

        $result = $this->posService->syncFinalBill($this->finalBill);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Connection timed out', $result['message']);

        $this->finalBill->refresh();
        $this->assertEquals('failed', $this->finalBill->fbr_sync_status);
        $this->assertStringContainsString('Connection timed out', $this->finalBill->fbr_response_message);
    }
}
