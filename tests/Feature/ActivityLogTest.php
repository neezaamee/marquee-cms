<?php

namespace Tests\Feature;

use App\Livewire\ActivityLogManager;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\Slot;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $owner;
    protected User $otherOwner;
    protected User $branchManager;
    protected Marquee $marquee;
    protected Marquee $otherMarquee;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Roles
        $superAdminRole = Role::create(['name' => 'super_admin', 'label' => 'Super Administrator']);
        $ownerRole = Role::create(['name' => 'owner', 'label' => 'Business Owner']);
        $managerRole = Role::create(['name' => 'branch_manager', 'label' => 'Branch Manager']);

        // Subscription Plan
        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise',
            'price' => 1000,
            'billing_interval' => 'monthly',
            'max_branches' => 5,
            'status' => 'active',
        ]);

        // Marquees
        $this->marquee = Marquee::create([
            'name' => 'Royal Palace Marquee',
            'slug' => 'royal-palace',
            'status' => 'active',
            'address' => '123 Palace Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'email' => 'contact@royalpalace.test',
            'subscription_plan_id' => $plan->id,
            'is_setup_completed' => true,
        ]);

        $this->otherMarquee = Marquee::create([
            'name' => 'Grand Arena Marquee',
            'slug' => 'grand-arena',
            'status' => 'active',
            'address' => '456 Arena Boulevard',
            'city' => 'Karachi',
            'province' => 'Sindh',
            'phone' => '03007654321',
            'email' => 'contact@grandarena.test',
            'subscription_plan_id' => $plan->id,
            'is_setup_completed' => true,
        ]);

        // Users
        $this->superAdmin = User::create([
            'name' => 'Super Admin User',
            'email' => 'admin@system.test',
            'password' => bcrypt('password'),
            'role_id' => $superAdminRole->id,
        ]);

        $this->owner = User::create([
            'name' => 'Owner Ahmad',
            'email' => 'ahmad@royalpalace.test',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marquee->id,
        ]);
        $this->owner->ownedMarquees()->attach($this->marquee->id);

        $this->otherOwner = User::create([
            'name' => 'Owner Bilal',
            'email' => 'bilal@grandarena.test',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->otherMarquee->id,
        ]);
        $this->otherOwner->ownedMarquees()->attach($this->otherMarquee->id);

        $this->branchManager = User::create([
            'name' => 'Manager Tariq',
            'email' => 'tariq@royalpalace.test',
            'password' => bcrypt('password'),
            'role_id' => $managerRole->id,
            'marquee_id' => $this->marquee->id,
        ]);
    }

    public function test_guest_is_redirected_from_activity_logs()
    {
        $response = $this->get(route('activity-logs.index'));
        $response->assertRedirect('/login');
    }

    public function test_unauthorized_role_gets_403_forbidden()
    {
        $response = $this->actingAs($this->branchManager)
            ->get(route('activity-logs.index'));

        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_and_see_all_activity_logs()
    {
        // Log for Marquee 1
        ActivityLog::create([
            'marquee_id' => $this->marquee->id,
            'user_id' => $this->owner->id,
            'action' => 'created',
            'description' => 'Created booking #BK-001',
            'model_type' => 'App\Models\Booking',
            'model_id' => 1,
            'ip_address' => '127.0.0.1',
        ]);

        // Log for Marquee 2
        ActivityLog::create([
            'marquee_id' => $this->otherMarquee->id,
            'user_id' => $this->otherOwner->id,
            'action' => 'updated',
            'description' => 'Updated invoice #INV-999',
            'model_type' => 'App\Models\Invoice',
            'model_id' => 999,
            'ip_address' => '192.168.1.1',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('activity-logs.index'));

        $response->assertStatus(200);
        $response->assertSee('Created booking #BK-001');
        $response->assertSee('Updated invoice #INV-999');
        $response->assertSee('Royal Palace Marquee');
        $response->assertSee('Grand Arena Marquee');
    }

    public function test_business_owner_can_access_and_sees_only_own_marquee_logs()
    {
        // Log for Owner's Marquee
        ActivityLog::create([
            'marquee_id' => $this->marquee->id,
            'user_id' => $this->owner->id,
            'action' => 'created',
            'description' => 'Ahmad created custom package #PKG-10',
            'model_type' => 'App\Models\Package',
            'model_id' => 10,
        ]);

        // Log for Other Owner's Marquee
        ActivityLog::create([
            'marquee_id' => $this->otherMarquee->id,
            'user_id' => $this->otherOwner->id,
            'action' => 'created',
            'description' => 'Secret other marquee expense #EXP-888',
            'model_type' => 'App\Models\Expense',
            'model_id' => 888,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('activity-logs.index'));

        $response->assertStatus(200);
        $response->assertSee('Ahmad created custom package #PKG-10');
        $response->assertDontSee('Secret other marquee expense #EXP-888');
    }

    public function test_activity_log_livewire_component_filtering_and_modal()
    {
        $log = ActivityLog::create([
            'marquee_id' => $this->marquee->id,
            'user_id' => $this->owner->id,
            'action' => 'updated',
            'description' => 'Updated payment record #BP-500',
            'model_type' => 'App\Models\BookingPayment',
            'model_id' => 500,
            'old_values' => ['amount' => 50000],
            'new_values' => ['amount' => 60000],
            'ip_address' => '10.0.0.1',
        ]);

        Livewire::actingAs($this->owner)
            ->test(ActivityLogManager::class)
            ->assertSee('Updated payment record #BP-500')
            ->set('search', 'non_existent_search_query_xyz')
            ->assertDontSee('Updated payment record #BP-500')
            ->set('search', 'BP-500')
            ->assertSee('Updated payment record #BP-500')
            ->call('showDetailModal', $log->id)
            ->assertSet('selectedLogId', $log->id)
            ->assertSee('Field Changes (Before vs After)')
            ->assertSee('50000')
            ->assertSee('60000')
            ->call('closeDetailModal')
            ->assertSet('selectedLogId', null);
    }

    public function test_activity_log_csv_export()
    {
        ActivityLog::create([
            'marquee_id' => $this->marquee->id,
            'user_id' => $this->owner->id,
            'action' => 'created',
            'description' => 'Exportable booking created',
            'model_type' => 'App\Models\Booking',
            'model_id' => 123,
            'ip_address' => '127.0.0.1',
        ]);

        $component = Livewire::actingAs($this->owner)
            ->test(ActivityLogManager::class);

        $response = $component->call('exportCsv');
        $this->assertNotNull($response);
    }

    public function test_model_activity_automatically_logged_on_booking_creation()
    {
        $branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Branch',
            'code' => 'BR-01',
            'address' => '12 Main Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'status' => 'active',
        ]);

        $hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $branch->id,
            'hall_name' => 'Main Hall',
            'hall_code' => 'HALL-01',
            'hall_type' => 'Indoor',
            'default_booking_price' => 50000,
            'capacity' => 400,
            'status' => 'active',
        ]);

        $slot = Slot::create([
            'marquee_id' => $this->marquee->id,
            'slot_name' => 'Day Shift',
            'slot_code' => 'SLOT-DAY',
            'start_time' => '12:00:00',
            'end_time' => '16:00:00',
            'status' => 'active',
        ]);

        $eventType = EventType::create([
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Banquet',
            'event_type_code' => 'ET-BANQ',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_code' => 'CUST-001',
            'first_name' => 'Zubair',
            'last_name' => 'Khan',
            'phone_number' => '03001122334',
            'status' => 'active',
        ]);

        $this->actingAs($this->owner);

        $booking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $branch->id,
            'hall_id' => $hall->id,
            'slot_id' => $slot->id,
            'event_type_id' => $eventType->id,
            'customer_id' => $customer->id,
            'booking_number' => 'BK-AUTO-TEST',
            'booking_date' => now()->addDays(3)->format('Y-m-d'),
            'start_time' => '12:00:00',
            'end_time' => '16:00:00',
            'guest_count' => 300,
            'booking_status' => 'Confirmed',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'model_type' => Booking::class,
            'model_id' => $booking->id,
            'action' => 'created',
            'description' => 'Booking #BK-AUTO-TEST was created',
        ]);
    }
}
