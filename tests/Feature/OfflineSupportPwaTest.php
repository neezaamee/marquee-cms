<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Marquee;
use App\Models\Branch;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\Slot;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OfflineSupportPwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pwa_manifest_is_accessible_and_valid()
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $json = json_decode(file_get_contents($manifestPath), true);
        $this->assertIsArray($json);
        $this->assertEquals('Marquee CMS - Banquet & Event Management', $json['name']);
        $this->assertEquals('standalone', $json['display']);
        $this->assertEquals('/', $json['start_url']);
        $this->assertNotEmpty($json['icons']);
    }

    public function test_service_worker_file_exists_and_contains_slip_caching_logic()
    {
        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);

        $content = file_get_contents($swPath);
        $this->assertStringContainsString('kitchen-slip', $content);
        $this->assertStringContainsString('slip-v2', $content);
        $this->assertStringContainsString('payment-receipt', $content);
        $this->assertStringContainsString('/offline', $content);
    }

    public function test_offline_fallback_route_renders_successfully()
    {
        $response = $this->get(route('offline'));
        $response->assertStatus(200);
        $response->assertSee('No Internet Connection');
        $response->assertSee('Offline Mode');
        $response->assertSee('آف لائن موڈ');
    }

    public function test_offline_assets_exist()
    {
        $this->assertFileExists(public_path('assets/js/offline-manager.js'));
        $this->assertFileExists(public_path('assets/css/offline-pill.css'));
    }

    public function test_kitchen_slip_includes_pwa_and_offline_vault_integrations()
    {
        $ownerRole = Role::create(['name' => 'owner', 'label' => 'Marquee Owner']);

        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-plan',
            'price' => 1000,
            'billing_interval' => 'monthly',
            'max_branches' => 5,
            'status' => 'active',
        ]);

        $marquee = Marquee::create([
            'name' => 'Royal Pearl Marquee',
            'slug' => 'royal-pearl-offline',
            'status' => 'active',
            'address' => '12 Main Gulberg',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'email' => 'contact@royalpearl.test',
            'subscription_plan_id' => $plan->id,
            'is_setup_completed' => true,
        ]);

        $owner = User::create([
            'name' => 'Owner Ahmad',
            'email' => 'owner.ahmad@royalpearl.test',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole->id,
            'marquee_id' => $marquee->id,
        ]);

        $branch = Branch::create([
            'marquee_id' => $marquee->id,
            'name' => 'Main Executive Branch',
            'code' => 'EX-01',
            'address' => '12 Main Gulberg',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'status' => 'active',
        ]);

        $hall = Hall::create([
            'marquee_id' => $marquee->id,
            'branch_id' => $branch->id,
            'hall_name' => 'Grand Pearl Hall',
            'hall_code' => 'HALL-01',
            'hall_type' => 'Indoor',
            'default_booking_price' => 50000,
            'capacity' => 500,
            'status' => 'active',
        ]);

        $slot = Slot::create([
            'marquee_id' => $marquee->id,
            'slot_name' => 'Dinner Shift',
            'slot_code' => 'SLOT-NIGHT',
            'start_time' => '19:00:00',
            'end_time' => '22:00:00',
            'status' => 'active',
        ]);

        $eventType = EventType::create([
            'marquee_id' => $marquee->id,
            'event_type_name' => 'Wedding (Baraat)',
            'event_type_code' => 'ET-BARAAT',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'marquee_id' => $marquee->id,
            'customer_code' => 'CUST-OFFLINE',
            'first_name' => 'Muhammad',
            'last_name' => 'Tariq',
            'phone_number' => '03219876543',
            'status' => 'active',
        ]);

        $booking = Booking::create([
            'marquee_id' => $marquee->id,
            'booking_number' => 'BK-OFFLINE-001',
            'customer_id' => $customer->id,
            'hall_id' => $hall->id,
            'slot_id' => $slot->id,
            'event_type_id' => $eventType->id,
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'guest_count' => 300,
            'status' => 'confirmed',
            'total_amount' => 500000,
            'advance_amount' => 100000,
            'balance_amount' => 400000,
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->get(route('bookings.kitchen-slip', ['booking' => $booking->id]));

        $response->assertStatus(200);
        $response->assertSee('manifest.json');
        $response->assertSee('offline-manager.js');
        $response->assertSee('offline-pill.css');
        $response->assertSee('offline-vault-badge');
    }
}
