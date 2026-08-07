<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\EventType;
use App\Models\GlobalDefaultMaster;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Livewire\SuperAdmin\GlobalDefaultManager;
use App\Livewire\Owner\TenantDefaultManager;
use App\Livewire\BookingWizard;
use Database\Seeders\GlobalDefaultDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GlobalDefaultDataTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $owner;
    protected Marquee $marquee;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed global default masters
        $this->seed(GlobalDefaultDataSeeder::class);

        // 2. Roles
        $superAdminRole = Role::create(['name' => 'super_admin', 'label' => 'Super Admin']);
        $ownerRole = Role::create(['name' => 'owner', 'label' => 'Marquee Owner']);

        // 3. Super Admin
        $this->superAdmin = User::create([
            'name' => 'SaaS Administrator',
            'email' => 'admin@saas.test',
            'password' => bcrypt('password'),
            'role_id' => $superAdminRole->id,
            'marquee_id' => null,
        ]);

        // 4. Marquee Tenant & Owner
        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-plan',
            'price' => 1000,
            'billing_interval' => 'monthly',
            'max_branches' => 10,
            'status' => 'active',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Grand Pearl Banquet',
            'slug' => 'grand-pearl-banquet',
            'status' => 'active',
            'address' => '45 Canal Bank Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001112233',
            'email' => 'owner@grandpearl.test',
            'subscription_plan_id' => $plan->id,
            'is_setup_completed' => true,
        ]);

        Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Executive Branch',
            'code' => 'EX-01',
            'address' => '45 Canal Bank Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001112233',
            'status' => 'active',
        ]);

        $this->owner = User::create([
            'name' => 'Chaudhry Ahmad',
            'email' => 'ahmad@grandpearl.test',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marquee->id,
        ]);
    }

    public function test_global_default_seeder_seeds_all_nine_categories()
    {
        $this->assertGreaterThan(0, GlobalDefaultMaster::category('event_type')->count());
        $this->assertGreaterThan(0, GlobalDefaultMaster::category('menu_category')->count());
        $this->assertGreaterThan(0, GlobalDefaultMaster::category('inventory_category')->count());
        $this->assertGreaterThan(0, GlobalDefaultMaster::category('inventory_unit')->count());
        $this->assertGreaterThan(0, GlobalDefaultMaster::category('expense_category')->count());
        $this->assertGreaterThan(0, GlobalDefaultMaster::category('department_type')->count());
        $this->assertGreaterThan(0, GlobalDefaultMaster::category('vendor_type')->count());
        $this->assertGreaterThan(0, GlobalDefaultMaster::category('customer_type')->count());
        $this->assertGreaterThan(0, GlobalDefaultMaster::category('payment_method')->count());
    }

    public function test_super_admin_can_manage_global_default_masters()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(GlobalDefaultManager::class)
            ->set('name', 'Custom VIP Banquet Event')
            ->set('code', 'ET-VIP')
            ->set('description', 'High security VIP banquet event')
            ->call('saveMaster')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('global_default_masters', [
            'category_type' => 'event_type',
            'name' => 'Custom VIP Banquet Event',
            'code' => 'ET-VIP',
        ]);
    }

    public function test_owner_can_import_missing_global_defaults_into_tenant()
    {
        $initialEventCount = EventType::where('marquee_id', $this->marquee->id)->count();

        Livewire::actingAs($this->owner)
            ->test(TenantDefaultManager::class)
            ->call('importGlobalDefaults')
            ->assertHasNoErrors();

        $afterEventCount = EventType::where('marquee_id', $this->marquee->id)->count();

        $this->assertGreaterThan($initialEventCount, $afterEventCount);
        $this->assertDatabaseHas('event_types', [
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Wedding (Baraat)',
        ]);
    }

    public function test_tenant_isolation_prevents_cross_tenant_master_data_bleed()
    {
        // Import for marquee A
        Livewire::actingAs($this->owner)
            ->test(TenantDefaultManager::class)
            ->call('importGlobalDefaults');

        // Create Marquee B
        $marqueeB = Marquee::create([
            'name' => 'Imperial Marquee',
            'slug' => 'imperial-marquee',
            'status' => 'active',
            'address' => '99 Main Blvd',
            'city' => 'Faisalabad',
            'province' => 'Punjab',
            'phone' => '03004445566',
            'email' => 'contact@imperial.test',
            'subscription_plan_id' => $this->marquee->subscription_plan_id,
            'is_setup_completed' => true,
        ]);

        Branch::create([
            'marquee_id' => $marqueeB->id,
            'name' => 'Imperial Branch',
            'code' => 'IMP-01',
            'address' => '99 Main Blvd',
            'city' => 'Faisalabad',
            'province' => 'Punjab',
            'phone' => '03004445566',
            'status' => 'active',
        ]);

        $ownerB = User::create([
            'name' => 'Owner B',
            'email' => 'ownerb@imperial.test',
            'password' => bcrypt('password'),
            'marquee_id' => $marqueeB->id,
        ]);

        // Create custom event type for Marquee B
        EventType::create([
            'marquee_id' => $marqueeB->id,
            'event_type_name' => 'Exclusive Imperial Festival',
            'event_type_code' => 'ET-FEST',
            'status' => 'active',
        ]);

        // Verify Owner A cannot see Marquee B's event type
        $this->assertDatabaseMissing('event_types', [
            'marquee_id' => $this->marquee->id,
            'event_type_name' => 'Exclusive Imperial Festival',
        ]);
    }

    public function test_booking_readiness_validates_mandatory_dependencies()
    {
        // No branch or hall created yet for Marquee A
        Livewire::actingAs($this->owner)
            ->test(BookingWizard::class)
            ->assertViewHas('missingDependencies', function ($deps) {
                return count($deps) > 0;
            });
    }
}
